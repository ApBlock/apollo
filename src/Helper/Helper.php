<?php
namespace ApBlock\Apollo\Helper;


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
     * @param LoggerInterface|null $logger
     */
    public function __construct(EntityManagerInterface $entityManager, Config $config, LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;
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
        //TODO módosítsd
        if (!empty($_SESSION[$this->session_key])) {
            /** @var SessionRepository $sessionRepository */
            $sessionRepository = $this->entityManager->getRepository($this->config->get(array('session', 'entity', 'session'), 'Session:Session'));
            /** @var Session $session */
            $session = $sessionRepository->findOneBy(array('user' => $_SESSION[$this->session_key], 'sessionId' => session_id()));
            if ($session) {
                return $session->getUser();
            }
        }else{
            $headerToken = $_SERVER["HTTP_THUNDERTOKEN"];
            $token = (isset($_GET["token"]) ? $_GET["token"] : (isset($_POST["token"]) ? $_POST["token"] : (!empty($headerToken) ? $headerToken : "")));
            if(!empty($token)){
                try{
                    $decodedData = JWT::decode($token, $this->jwt["key"], array('HS256'));
                    if(is_object($decodedData)){
                        $fetchData = $decodedData->data;
                        return $this->entityManager->getRepository("Session:Users")->findOneBy(array('email' => $fetchData->email));
                    }
                }catch (\Exception $e){}
            }
        }
        return false;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    public function parseLang(ServerRequestInterface $request, Config $config)
    {
        $params = $request->getQueryParams();
        if (array_key_exists('request', $params)) {
            $tmp = explode('/', $params['request']);
            $lng = array_shift($tmp);
            $headerLang = (isset($_SERVER["HTTP_THUNDERLANG"]) ? $_SERVER["HTTP_THUNDERLANG"] : null);
            return in_array($lng, $config->get('languages', array('en'))) ? $lng : (!empty($headerLang) ? (in_array($headerLang,$config->get('languages', array('en'))) ? $headerLang : $config->get('default_language')) : $config->get('default_language'));
        } else {
            return $config->get('default_language', 'en');
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
