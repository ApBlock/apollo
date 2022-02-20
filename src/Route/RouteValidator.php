<?php

namespace ApBlock\Apollo\Route;

use ApBlock\Apollo\Auth\Auth;
use ApBlock\Apollo\Helper\Helper;
use Doctrine\ORM\EntityManagerInterface;
use League\Route\Http\Exception\ForbiddenException;
use ApBlock\Apollo\Config\Config;
use ApBlock\Apollo\Html;
use ApBlock\Apollo\modules\Session;
use Twig\Environment;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\UnauthorizedException;
use League\Route\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteValidator implements RouteValidatorInterface
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * ApolloContainer constructor.
     * @param Config $config
     * @param \Twig\Environment $twig
     * @param Helper $helper
     */
    public function __construct(Config $config, Environment $twig, EntityManagerInterface $entityManager, Helper $helper, Auth $auth)
    {
        $this->config = $config;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->helper = $helper;
        $this->auth = $auth;
    }

    /**
     * @param Route $map
     * @param array $requires
     * @param array $options
     * @return Route
     */
    public function validate(Route $map, array $requires, array $options)
    {
        if (!empty($requires['require_permissions'])) {
            if (!is_array($requires['require_permissions'][0])) {
                $requires['require_permissions'] = array($requires['require_permissions']);
            }
            $options['require_permissions'] = $requires['require_permissions'];
            $map->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($options) {
                if ($this->checkPermission($request, $response, $options)) {
                    return $next($request, $response);
                }
                return $response;
            });
        }
        if ($requires['require_auth']) {
            $options['require_auth'] = $requires['require_auth'];
            $options['auth_method'] = $requires['auth_method'];
            $map->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($options) {
                if ($this->checkAuth($request, $response, $options)) {
                    return $next($request, $response);
                }
                return $response;
            });
        }
        if (!empty($requires['required_fields'])) {
            $options['required_fields'] = (array)$requires['required_fields'];
            $map->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($options) {
                if ($this->checkFields($request, $response, $options)) {
                    return $next($request, $response);
                }
                return $response;
            });
        }
        if (!empty($requires['required_headers'])) {
            $options['required_headers'] = (array)$requires['required_headers'];
            $map->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($options) {
                if ($this->checkHeaders($request, $response, $options)) {
                    return $next($request, $response);
                }
                return $response;
            });
        }
        if ($requires['required_ContentType'] && in_array($requires['required_ContentType'], $options['valid_ContentTypes']) && in_array($options['method'], array('POST', 'PUT', 'PATCH'))) {
            $options['required_ContentType'] = $requires['required_ContentType'];
            $map->middleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($options) {
                if ($this->checkContentType($request, $response, $options)) {
                    return $next($request, $response);
                }
                return $response;
            });
        }
        return $map;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $options
     * @return bool
     * @throws BadRequestException
     */
    public function checkHeaders(ServerRequestInterface $request, ResponseInterface &$response, array $options)
    {
        $required_headers = array_unique($options['required_headers']);
        $errors = array();
        foreach ($required_headers as $required_header) {
            $header = $request->getHeaderLine($required_header);
            if (!$header) {
                $errors[] = $required_header;
            }
        }
        if (!empty($errors)) {
            throw new BadRequestException(implode("\n", array('Bad Request', json_encode($errors))));
        }
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $options
     * @return bool
     * @throws BadRequestException
     */
    public function checkFields(ServerRequestInterface $request, ResponseInterface &$response, array $options)
    {
        $required_fields = array_unique($options['required_fields']);
        $params = $request->getQueryParams();
        $errors = array();
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || (is_array($params[$field]) && empty($params[$field])) || (!is_array($params[$field]) && $params[$field] == '')) {
                $errors[] = $field;
            }
        }
        if (!empty($errors)) {
            throw new BadRequestException(implode("\n", array('Bad Request', json_encode($errors))));
        }
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $options
     * @return bool
     * @throws BadRequestException
     */
    public function checkContentType(ServerRequestInterface $request, ResponseInterface &$response, array $options)
    {
        $required_ContentType = $options['required_ContentType'];
        $contentType = Html::getContentType($request);
        if ($required_ContentType && $contentType != $required_ContentType) {
            throw new BadRequestException(implode("\n", array('Bad Request', json_encode(array('required' => $required_ContentType, 'got' => $contentType)))));
        }
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $options
     * @return bool
     * @throws UnauthorizedException
     */
    public function checkAuth(ServerRequestInterface $request, ResponseInterface &$response, array $options)
    {
        $valid = false;
        if ($options["auth_method"] == Auth::JWT) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    $jwt = $matches[1];
                    if ($jwt) {
                        if($this->auth->validateJWT($jwt)){
                            $valid = true;
                        }
                    }
                }
            }
        }

        if ($options["auth_method"] == Auth::Session) {
            $sessionRep = $this->config->get(array('route', 'modules', 'Session', 'entity', 'session'), 'Session:Session');
            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $this->entityManager->getRepository($sessionRep);

            $sessionRepository->removeExpired();
            if (!empty($_SESSION['user'])) {
                /** @var SessionEntity $session */
                $session = $sessionRepository->findOneBy(array('user' => $_SESSION['user'], 'sessionId' => session_id()));
                if ($session) {
                    /** @var UsersEntity $sessionUser */
                    $sessionUser = $session->getUser();
                    if ($sessionUser) {
                        if ($this->password_match($sessionUser, $session)) {
                            $valid = true;
                        } else {
                            $this->entityManager->remove($session);
                            $this->entityManager->flush();
                        }
                    }
                }
            }
            if (!$valid) {
                unset($_SESSION['user']);
                session_destroy();
            }
        }

        if (!$valid) {
            throw new UnauthorizedException();
        }
        return true;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $options
     * @return bool
     * @throws ForbiddenException
     */
    public function checkPermission/** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface &$response, array $options)
    {
        $sessionUser = $this->helper->getSessionUser();

        foreach ($options['require_permissions'] as $require_permission) {
            list($module, $right) = $require_permission;
            if (!$sessionUser || !$sessionUser->hasPermission($module, $right)) {
                throw new ForbiddenException();
            }
        }
        return true;
    }
}
