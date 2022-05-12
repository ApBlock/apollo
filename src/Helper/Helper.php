<?php
namespace ApBlock\Apollo\Helper;


use ApBlock\Apollo\Auth\Auth;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ApBlock\Apollo\Config\Config;
use Generator;
use ApBlock\Apollo\Logger\Interfaces\LoggerHelperInterface;
use ApBlock\Apollo\Logger\Traits\LoggerHelperTrait;
use ApBlock\Apollo\modules\Session\Entity\Session;
use ApBlock\Apollo\modules\Session\Entity\SessionRepository;

class Helper implements LoggerHelperInterface
{
    use LoggerHelperTrait;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var string
     */
    protected $basepath;
    /**
     * @var string
     */
    protected $session_key = 'user';

    /**
     * @var bool
     */
    protected $session_destroy = true;

    /**
     * ApolloContainer constructor.
     * @param EntityManagerInterface $entityManager
     * @param Config $config
     * @param Auth $auth
     * @param LoggerInterface|null $logger
     */
    public function __construct(EntityManagerInterface $entityManager, Config $config, Auth $auth, LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;
        $this->auth = $auth;
        $this->basepath = $config->get(array('routing','basepath'), '/');
        $this->config = $config->fromDimension(array('route','modules'));
        $this->setLogDebug($this->config->get('debug', false));
        if ($logger) {
            $this->setLogger($logger);
        }
        $this->session_key = $this->config->get(array('Session', 'session_key'), 'user');
        $this->session_destroy = $this->config->get(array('Session', 'session_destroy'), true);
    }

    /**
     * @return bool|object
     */
    public function getSessionUser()
    {
        if (!empty($_SESSION[$this->session_key])) {
            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $this->entityManager->getRepository($this->config->get(array('Session', 'entity', 'session'), 'Session:Session'));
            /** @var Session $session */
            $session = $sessionRepository->findOneBy(array('userid' => $_SESSION[$this->session_key], 'sessionid' => session_id()));
            if ($session) {
                return $session->getUserid();
            }
        }else{
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    $jwt = $matches[1];
                    if ($jwt) {
                        $user = $this->auth->getUserByJWT($jwt);
                        if (is_object($user)) {
                            return $user;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    public function parseLang(ServerRequestInterface $request, Config $config, array $languages)
    {
        $params = $request->getQueryParams();
        if(isset($params["language"])){
            if(in_array($params["request"],$languages)){
                return $params["request"];
            }
            if(in_array($params["language"],$languages)){
                return $params["language"];
            }
        }

        if(isset($_SERVER["HTTP_CONTENT_LANGUAGE"])){
            if(!empty($_SERVER["HTTP_CONTENT_LANGUAGE"])){
                if(in_array($_SERVER["HTTP_CONTENT_LANGUAGE"], $languages)) {
                    return $_SERVER["HTTP_CONTENT_LANGUAGE"];
                }
            }
        }

        if (array_key_exists('request', $params)) {
            $tmp = explode('/', $params['request']);
            $lng = array_shift($tmp);
            if (strpos($params["request"], 'api/') === false) {
                if(isset($_COOKIE["default_language"])){
                    return $_COOKIE["default_language"];
                }
            }
            $headerLang = (isset($_SERVER["HTTP_CONTENT_LANGUAGE"]) ? $_SERVER["HTTP_CONTENT_LANGUAGE"] : $config->get(array('translator','default'), 'en'));
            return in_array($lng, $languages) ? $lng : (!empty($headerLang) ? (in_array($headerLang,$languages) ? $headerLang : $config->get(array('translator','default'), 'en')) : $config->get(array('translator','default'), 'en'));
        } else {
            return $config->get(array('translator','default'), 'en');
        }
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntitymanager()
    {
       return $this->entityManager;
    }

    /**
     * @return string
     */
    public function getDefaultUrl()
    {
        $url = '';
        return $url;
    }

    /**
     * @return string
     */
    public function getSessionKey()
    {
        return $this->session_key;
    }

    /**
     * @return bool
     */
    public function isSessionDestroy()
    {
        return $this->session_destroy;
    }

    /**
     * @return string
     */
    public function getBasepath()
    {
        return $this->basepath;
    }

    /**
     * @param $url
     * @return string
     */
    public function getRealUrl($url)
    {
        $basepath = rtrim($this->basepath, '/');
        return implode('/', array($basepath, ltrim($url, '/')));
    }
}
