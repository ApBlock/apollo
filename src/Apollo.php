<?php

declare(strict_types=1);

namespace ApBlock\Apollo;

/*
 * require 'vendor/autoload.php';
 * session_start();
 *
 * setlocale(LC_TIME, 'hu_HU.UTF8', 'Hungarian');
 *
 * $apollo = new Apollo();
 * $apollo->errorReporting("on"); //not required
 * $apollo->setBaseDir(__DIR__); //not required
 * $apollo->setHomeDir(""); //not required
 * $apollo->setMaxLoggerFiles(7); //not required
 * $apollo->setConfigModules(array('db', 'routing', 'twig', 'route', 'jwt', 'doctrine', 'services'));
 * $response = $apollo->run();
 * session_write_close();
 * echo Html::response($response);
 *
 * */


use GuzzleHttp\Psr7\ServerRequest;
use ApBlock\Apollo\Factory\Factory;
use ApBlock\Apollo\Logger\ErrorLogger;
use ApBlock\Apollo\Logger\Logger;
use ApBlock\Apollo\Utils\ServiceProvider;

class Apollo
{
    /** @var array $configModules */
    private $configModules = array();

    /** @var $config */
    private $config;

    /** @var $container */
    private $container;

    /** @var string $baseDir */
    private $baseDir = __DIR__;

    /** @var string $homeDir */
    private $homeDir = "";

    /** @var int $maxLoggerFiles */
    private $maxLoggerFiles = 7;

    public function __construct()
    {
        $this->initErrorHandler();
        $this->initConfig();
        $this->initContainers();
    }

    private function initErrorHandler()
    {
        $error_logger = new ErrorLogger(new Logger('PHP', $this->getBaseDir(),$this->maxLoggerFiles));
        set_error_handler(array($error_logger, 'customErrorHandler'));
    }

    private function initConfig()
    {
        Factory::setConfigPath($this->getBaseDir()."/config/");
        $this->config = Factory::fromNames($this->configModules, true);
        $modules_config = $this->config->get(array('route', 'modules'));
        foreach ($modules_config as $module) {
            if (is_array($module) && !empty($module['paths'])) {
                if (count($module['paths']) == 1 && array_key_exists('/', $module['paths'])) {
                    $cfg = $module['paths'];
                } else {
                    $cfg = array('/' => array('paths' => $module['paths']));
                }
                $this->config->merge(array('routing' => array('paths' => $cfg)));
            }
        }
    }

    private function initContainers()
    {
        $this->container = new \League\Container\Container();
        $request = ServerRequest::fromGlobals();
        $serviceProvider = new ServiceProvider($this->config, $request);
        $this->container->addServiceProvider($serviceProvider);
    }

    /**
     * @param array $configModules
     */
    public function setConfigModules($configModules = array())
    {
        $this->configModules = $configModules;
    }

    /**
     * @param string $state
     */
    public function errorReporting($state = "on")
    {
        switch ($state) {
            case "on":
                ini_set("display_errors", 1);
                error_reporting(E_ALL);
                break;
            case "off":
                ini_set("display_errors", 0);
                error_reporting(0);
                break;
        }
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * @param string $baseDir
     * @return Apollo
     */
    public function setBaseDir($baseDir)
    {
        $this->baseDir = $baseDir;
        return $this;
    }

    /**
     * @return string
     */
    public function getHomeDir()
    {
        return $this->homeDir;
    }

    /**
     * @param string $homeDir
     * @return Apollo
     */
    public function setHomeDir($homeDir)
    {
        $this->homeDir = $homeDir;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLoggerFiles()
    {
        return $this->maxLoggerFiles;
    }

    /**
     * @param int $maxLoggerFiles
     * @return Apollo
     */
    public function setMaxLoggerFiles($maxLoggerFiles)
    {
        $this->maxLoggerFiles = $maxLoggerFiles;
        return $this;
    }

    public function run()
    {
        $core = new ApolloKernel($this->container);
        return $core->go();
    }
}