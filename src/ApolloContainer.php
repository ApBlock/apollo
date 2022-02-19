<?php


namespace ApBlock\Apollo;

use ApBlock\Apollo\Helper\Helper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ApBlock\Apollo\Config\Config;
use ApBlock\Apollo\Logger\Interfaces\LoggerHelperInterface;
use ApBlock\Apollo\Logger\Traits\LoggerHelperTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ApBlock\Apollo\modules\Session\Entity\Users;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ApolloContainer implements LoggerHelperInterface
{
    use LoggerHelperTrait;

    /**
     * @var
     */
    protected static $NAME;
    /**
     * @var
     */
    protected static $URL;

    /**
     * @var array
     */
    protected static $permissions = array();
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var \Twig\Environment
     */
    protected $twig;
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var Helper
     */
    protected $helper;
    /**
     * @var array
     */
    private $hooks = array();

    /**
     * ApolloContainer constructor.
     * @param Config $config
     * @param \Twig\Environment $twig
     * @param EntityManagerInterface $entityManager
     * @param Helper $helper
     * @param LoggerInterface|null $logger
     */
    public function __construct(Config $config, Environment $twig, EntityManagerInterface $entityManager, Helper $helper, LoggerInterface $logger = null)
    {
        $this->config = $config->fromDimension(array('route','modules'));
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->helper = $helper;
        $this->setLogDebug($this->config->get('debug', false));
        if ($logger) {
            $this->setLogger($logger);
        }
        try {
            $cn = (new \ReflectionClass($this))->getShortName();
            $this->config->setBase($cn);
        } catch (\ReflectionException $e) {
            $this->error('ReflectionClass', array($e->getMessage()));
        }
    }

    /**
     * @return array
     */
    public static function getPermissionList()
    {
        return static::$permissions;
    }

    /**
     * @return string
     */
    public static function getNAME()
    {
        return static::$NAME;
    }

    /**
     * @return string
     */
    public static function getURL()
    {
        return static::$URL;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function notImplemented(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $params = array(
            'block' => array(
                'contents' => array(
                    $this->dump($args, 'args', 0, true),
                    $this->dump($request->getQueryParams(), 'params', 0, true),
                ),
            ),
        );

        return $this->twigErrorResponse($response, 501, $params);
    }

    /**
     * @param $name
     * @return bool
     */
    protected function hasHook($name)
    {
        return isset($this->hooks[$name]);
    }

    /**
     * @return array
     */
    protected function getHooks()
    {
        return $this->hooks;
    }

    /**
     * @param $name
     * @param $callable
     * @return ApolloContainer
     */
    protected function addHook($name, $callable)
    {
        if (!isset($this->hooks[$name])) {
            if (is_callable($callable)) {
                $this->hooks[$name] = $callable;
            }
        }
        return $this;
    }

    /**
     * @param $name
     * @param $callable
     * @return ApolloContainer
     */
    protected function setHook($name, $callable)
    {
        if (isset($this->hooks[$name])) {
            if (is_callable($callable)) {
                $this->hooks[$name] = $callable;
            }
        }
        return $this;
    }

    /**
     * @param $name
     * @return ApolloContainer
     */
    protected function delHook($name)
    {
        if (isset($this->hooks[$name])) {
            unset($this->hooks[$name]);
        }
        return $this;
    }

    /**
     * @param array $hooks
     * @return ApolloContainer
     */
    protected function setHooks(array $hooks)
    {
        $this->hooks = array();
        if (!empty($hooks)) {
            foreach ($hooks as $name => $callable) {
                $this->addHook($name, $callable);
            }
        }
        return $this;
    }

    /**
     * @return mixed
     */
    protected function callHook()
    {
        if (!empty($this->hooks)) {
            $args = func_get_args();
            $name = array_shift($args);
            if ($name && isset($this->hooks[$name])) {
                if (is_callable($this->hooks[$name])) {
                    return call_user_func_array($this->hooks[$name], $args);
                }
            }
        }
        return null;
    }


    /**
     * @param ResponseInterface $response
     * @param $code
     * @param array $params
     * @return ResponseInterface|static
     */
    protected function twigErrorResponse(ResponseInterface $response, $code, $params = array())
    {
        $response = $response->withStatus($code);

        if (!isset($params['title'])) {
            $params['title'] = $response->getStatusCode();
        }
        if (!isset($params['block']['title'])) {
            $params['block']['title'] = $response->getReasonPhrase();
        }

        /** @var Environment $twig */
        $twig = $this->twig;
        try {
            $response->getBody()->write($twig->render('errors.html.twig', $params));
        } catch (LoaderError $e) {
        } catch (RuntimeError $e) {
        } catch (SyntaxError $e) {
        }
        return $response;
    }

    /**
     * @param $var
     * @param string $name
     * @param int $mode
     * @param bool $return
     * @return string | null
     */
    function dump($var, $name = '', $mode = 0, $return = false)
    {
        switch ($mode) {
            case 1: case 'r': $dump = print_r($var, true); break;
            case 2: case 'o':
            ob_start();
            var_dump($var);
            $dump = ob_get_clean();
            break;
            case 3: $dump = var_export(json_encode($var, JSON_PRETTY_PRINT), true); break;
            case 0: default: $dump = var_export($var, true); break;
        }
        $ret = '<pre>'.highlight_string("<?php\n".($name ? "\${$name} =\n" : '')."{$dump}\n?>", true).'</pre>';
        if ($return) {
            return $ret;
        }
        echo $ret;
        return null;
    }
}
