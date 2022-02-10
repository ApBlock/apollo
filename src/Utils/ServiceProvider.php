<?php

namespace ApBlock\Apollo\Utils;

use ApBlock\Apollo\Config\Config;
use GuzzleHttp\Psr7\ServerRequest;
use ApBlock\Apollo\Html\Html;
use ApBlock\Apollo\Logger\Logger;
use League\Container\Container;
use League\Container\ContainerAwareInterface;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var array
     */
    protected $provides = array(
        Config::class,
        LoggerInterface::class,
        ServerRequestInterface::class,
    );

    public function __construct(Config $config, ServerRequestInterface $request = null)
    {
        $this->config = $config;
        if (!$request) {
            $request = ServerRequest::fromGlobals();
        }
        if ($this->config->has(array('routing','basepath'))) {
            $request = Html::removePathPrefix($request, $this->config->get(array('routing', 'basepath')));
        }
        $this->request = $request;
    }

    public function boot() :void
    {
        /** @var Container $container */
        $container = $this->getContainer();
        $container
            ->inflector(ContainerAwareInterface::class)
            ->invokeMethod('setContainer', array('container'=>$container));
        $serviceManager = new ServiceManager($this->getContainer());
        $serviceManager->configure($this->config->fromDimension('services'));
        $container->delegate($serviceManager);
        $container->delegate(new ReflectionContainer());
    }

    public function register() :void
    {
        $this->getContainer()->addShared(LoggerInterface::class, ($this->logger instanceof LoggerInterface ? $this->logger : new Logger('Apollo')));
        $this->getContainer()->addShared(ServerRequestInterface::class, $this->request);
        $this->getContainer()->addShared(Config::class, $this->config);
    }

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides);
    }
}