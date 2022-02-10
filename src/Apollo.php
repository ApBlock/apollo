<?php

declare(strict_types=1);

namespace ApBlock\Apollo;

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
    private $baseDir;

    /** @var string $homeDir */
    private $homeDir = "";

    /** @var int $maxLoggerFiles */
    private $maxLoggerFiles = 7;

    /** @var bool $allowErrorReporting */
    private $allowErrorReporting = false;

    private function initErrorHandler()
    {
        $error_logger = new ErrorLogger(new Logger('PHP', $this->maxLoggerFiles));
        set_error_handler(array($error_logger, 'customErrorHandler'));
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

    public function allowErrorReporting()
    {
        $this->allowErrorReporting = true;
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
        define("BASE_DIR",$this->baseDir);
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
        define("HOME_DIR",$this->homeDir);
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
        if($this->allowErrorReporting){
            ini_set('display_errors','true');
            error_reporting(E_ALL);
        }else{
            ini_set("display_errors", 'false');
            error_reporting(0);
        }
        Factory::setConfigPath($this->baseDir."/config/");
        $this->config = Factory::fromNames($this->configModules, true);
        $this->initErrorHandler();
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
        $this->initContainers();
        $core = new ApolloKernel($this->container);
        return $core->go();
    }
}