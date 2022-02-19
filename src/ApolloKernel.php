<?php


namespace ApBlock\Apollo;

use ApBlock\Apollo\Html\Html;
use Psr\Log\LoggerInterface;
use ApBlock\Apollo\Config\Config;
use ApBlock\Apollo\Form\ConfigProvider;
use ApBlock\Apollo\Logger\Interfaces\LoggerHelperInterface;
use ApBlock\Apollo\Logger\Traits\LoggerHelperTrait;
use ApBlock\Apollo\Route\Router;
use ApBlock\Apollo\Twig\Interfaces\TwigAwareInterface;
use ApBlock\Apollo\Twig\Twig;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\Route\Http\Exception as HttpException;
use League\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Twig\Environment;
use Twig\TwigFunction;
use Laminas\View\Renderer\PhpRenderer;

class ApolloKernel implements LoggerHelperInterface
{
    use LoggerHelperTrait;

    /**
     * @var Container
     */
    private $container;
    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        if (!ob_get_level()) {
            ob_start();
        }
        $this->container = $container;
        $config = $this->container->get(Config::class);
        $logger = $this->container->get(LoggerInterface::class);
        if ($logger) {
            $this->setLogger($logger);
        }

        $this->setLogDebug($config->get(array('route','debug'), false));
        $twig = $this->container->get(Environment::class);
        $this->twig = $twig;

        if ($this->twig instanceof Environment) {
            $plugin_config = (new ConfigProvider())->getViewHelperConfig();
            if ($config->has('form')) {
                $plugin_config['aliases'] = array_merge(
                    $plugin_config['aliases'],
                    $config->get(array('form', 'aliases'), array())
                );
                $plugin_config['factories'] = array_merge(
                    $plugin_config['factories'],
                    $config->get(array('form', 'factories'), array())
                );
                $plugin_config['initializers'] = array_merge(
                    $plugin_config['initializers'],
                    $config->get(array('form', 'initializers'), array())
                );
            }
            $plugin_config['initializers'][] = function /** @noinspection PhpUnusedParameterInspection */
            (
                $context,
                $object
            ) use ($twig) {
                if ($object instanceof TwigAwareInterface) {
                    $object->setTwig($twig);
                }
            };

            $renderer = new PhpRenderer();
            $plugins = $renderer->getHelperPluginManager();
            $plugins->configure($plugin_config);

            $this->twig->registerUndefinedFunctionCallback(
                function ($name) use ($renderer, $plugins) {
                    if (!$plugins->has($name)) {
                        return false;
                    }

                    $callable = array($renderer->plugin($name), '__invoke');
                    $options = array('is_safe' => array('html'));
                    return new TwigFunction($name, $callable, $options);
                }
            );
        }

        //register_shutdown_function(array($this,'_fatal_handler'));
    }

    /**
     * @return ResponseInterface
     */
    public function go()
    {
        $router = $this->container->get(Router::class);
        /** @var Router $router */
        $router->buildRoutes();

        return $router->go();
    }

    /**
     *
     */
    public function _fatal_handler()
    {
        $error = error_get_last();
        if(isset($error['type'])){
            switch ($error['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_RECOVERABLE_ERROR:
                    $this->error(ServerRequest::fromGlobals()->getUri()->getPath(), $error);
                    $exception = new HttpException(500);
                    $response = new Response($exception->getStatusCode(), array(), strtok($exception->getMessage(), "\n"));
                    if ($this->twig instanceof Environment) {
                        $params = array(
                            'title' => $response->getStatusCode(),
                            'block' => array(
                                'title' => $response->getReasonPhrase(),
                                'content' => $response->getBody()
                            ),
                        );
                        /** @var Twig $twig */
                        $twig = $this->twig;
                        $response->getBody()->write($twig->render('errors.html.twig', $params));
                    }
                    ob_end_clean();
                    echo Html::response($response);
                    break;
            }
        }
    }
}
