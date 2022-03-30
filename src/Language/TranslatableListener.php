<?php

use ApBlock\Apollo\Factory\Factory;

class TranslatableListener extends \Gedmo\Translatable\TranslatableListener
{
    public function __construct()
    {
        parent::__construct();
        $config = Factory::fromNames(array('route','translator'), true);
        $lang = $config->get('default', 'en');
        $this->setDefaultLocale($lang);
    }
}
