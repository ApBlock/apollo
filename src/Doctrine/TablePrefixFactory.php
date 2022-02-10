<?php
namespace ApBlock\Apollo\Doctrine;

use ApBlock\Apollo\Config\Config;
use ApBlock\Apollo\Config\ConfigurableFactoryInterface;
use ApBlock\Apollo\Config\ConfigurableFactoryTrait;
use ApBlock\Apollo\Utils\InvokableFactoryInterface;
use Exception;


class TablePrefixFactory implements InvokableFactoryInterface, ConfigurableFactoryInterface
{
    use ConfigurableFactoryTrait;

    /**
     * @return TablePrefix
     * @throws Exception
     */
    public function __invoke()
    {
        if (!$this->config instanceof Config) {
            throw new Exception(__CLASS__ . " can't work without configuration");
        }

        return new TablePrefix(
            $this->config->get('prefix', ''),
            $this->config->get('prefix_namespaces', array())
        );
    }
}
