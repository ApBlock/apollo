<?php

namespace ApBlock\Apollo\Config;

interface ConfigurableFactoryInterface
{
    /**
     * @param Config $config
     * @return mixed
     */
    public function configure(Config $config);
}
