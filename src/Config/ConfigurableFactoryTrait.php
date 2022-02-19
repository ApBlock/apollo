<?php
namespace ApBlock\Apollo\Config;

trait ConfigurableFactoryTrait
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function configure(Config $config)
    {
        $this->config = $config;
    }
}
