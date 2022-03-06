<?php

namespace ApBlock\Apollo\Strategies;

use \Exception;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception as HttpException;
use League\Route\Http\Exception\UnauthorizedException;
use League\Route\Route;
use League\Route\Strategy\StrategyInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ApBlock\Apollo\Factory\Factory;
use ApBlock\Apollo\Logger\Interfaces\LoggerHelperInterface;
use ApBlock\Apollo\Logger\Traits\LoggerHelperTrait;
use ApBlock\Apollo\Route\Router;
use Twig\Environment;

class HtmlStrategy implements StrategyInterface, LoggerHelperInterface
{
    use LoggerHelperTrait;

    /**
     * @var string
     */
    private $content_type = 'text/html';

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var Router
     */
    protected $router;

    /**
     * HtmlStrategy constructor.
     * @param \Twig\Environment $twig
     * @param Router $router
     * @param LoggerInterface|null $logger
     */
    public function __construct(Environment $twig, Router $router, LoggerInterface $logger = null)
    {
        $this->twig = $twig;
        $this->router = $router;
        if ($logger) {
            $this->setLogger($logger);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCallable(Route $route, array $vars)
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, callable $next) use ($route, $vars) {
            $response = call_user_func_array($route->getCallable(), array($request, $response, $vars));

            if (!$response instanceof ResponseInterface) {
                throw new RuntimeException(
                    'Route callables must return an instance of (Psr\Http\Message\ResponseInterface)'
                );
            }

            $response = $this->setHeader($response);

            return $next($request, $response);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getNotFoundDecorator(NotFoundException $exception)
    {
        return function /** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            $response = $response->withStatus(404);
            $params = array(
                'title' => $response->getStatusCode(),
                'block' => array(
                    'title' => $response->getReasonPhrase(),
                ),
            );
            $response->getBody()->write($this->twig->render('errors.html.twig', $params));
            return $this->setHeader($response);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception)
    {
        return function /** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            $response = $response->withStatus(405);
            $params = array(
                'title' => $response->getStatusCode(),
                'block' => array(
                    'title' => $response->getReasonPhrase(),
                ),
            );
            $response->getBody()->write($this->twig->render('errors.html.twig', $params));
            return $this->setHeader($response);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionDecorator(Exception $exception)
    {
        return function /** @noinspection PhpUnusedParameterInspection */ (ServerRequestInterface $request, ResponseInterface $response) use ($exception) {
            $response = $this->setHeader($response);
            if ($exception instanceof UnauthorizedException) {
                $response = $response->withHeader('Location', $this->router->getRealUrl($this->router->getNamedRoute('login')->getPath()));
                return $response;
            }

            if ($exception instanceof HttpException) {
                $response = $response->withStatus($exception->getStatusCode());
                $params = array(
                    'title' => $response->getStatusCode(),
                    'block' => array(
                        'title' => $response->getReasonPhrase(),
                        'content' => json_decode(strtok("\n"), true),
                    ),
                );
                $response->getBody()->write($this->twig->render('errors.html.twig', $params));
                return $response;
            }

            $response = $response->withStatus(500);
            $params = array(
                'title' => $response->getStatusCode(),
                'block' => array(
                    'title' => $response->getReasonPhrase(),
                ),
            );
            $response->getBody()->write($this->twig->render('errors.html.twig', $params));
            return $response;
        };
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function setHeader(ResponseInterface $response)
    {
        if (!$response->hasHeader('content-type')) {
            $response = $response->withHeader('content-type', $this->content_type);
        }
        return $response;
    }
}
