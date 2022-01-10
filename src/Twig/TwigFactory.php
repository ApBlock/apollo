<?php

namespace ApBlock\Apollo\Twig;

use Exception;
use ApBlock\Apollo\Config\ConfigurableFactoryInterface;
use ApBlock\Apollo\Config\ConfigurableFactoryTrait;
use ApBlock\Apollo\Logger\Logger;
use ApBlock\Apollo\Utils\InvokableFactoryInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Twig\Extension\AbstractExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\EscaperExtension;
use Twig\Loader\FilesystemLoader;

class TwigFactory implements InvokableFactoryInterface, ConfigurableFactoryInterface, ContainerAwareInterface
{
    use ConfigurableFactoryTrait;
    use ContainerAwareTrait;

    /**
     * @return Twig
     * @throws Exception
     */
    public function __invoke()
    {
        $logger = new Logger('TWIG');

        if (null==$this->config) {
            $logger->error('Factory', (array)" can't work without configuration");
            throw new Exception(__CLASS__ . " can't work without configuration");
        }

        $loader = new FilesystemLoader($this->config->get('templates_path', '/src/templates'));
        $paths = $this->config->get('paths', array());
        if (!empty($paths)) {
            foreach ($paths as $module => $module_paths) {
                foreach ($module_paths as $path) {
                    try {
                        $loader->addPath($path, $module);
                    } catch (Exception $e) {
                        $logger->error('Path not found', array($path));
                    }
                }
            }
        }
        $options = array(
            'debug' => $this->config->get('debug', false),
            'cache' => $this->config->get('cache', false),
        );

        $twig = new Twig($loader, $options);
        $twig->setLogDebug($this->config->get('debug', false));
        if ($logger) {
            $twig->setLogger($logger);
        }


        $globals = $this->config->get('globals', array());
        if (!empty($globals)) {
            foreach ($globals as $name => $value) {
                $twig->addGlobal($name, $value);
            }
        }
        if ($twig->isDebug()) {
            $twig->addExtension(new DebugExtension());
        }

        $extensions = $this->config->get('extensions', array());
        if (!empty($extensions)) {
            foreach ($extensions as $extension) {
                try {
                    $twig_extension = $this->container->get($extension);
                    if ($twig_extension instanceof AbstractExtension) {
                        $twig->addExtension($twig_extension);
                    } else {
                        $twig->error('Twig::addExtension', (array)(get_class($twig_extension) . " MUST implement Twig_Extension"));
                    }
                } catch (Exception $e) {
                    $twig->error('Twig::addExtension', (array)$e->getMessage());
                }
            }
        }
        return $twig;
    }
}
