<?php

use ApBlock\Apollo\Factory\Factory;

class TranslatableListener extends \Gedmo\Translatable\TranslatableListener
{
    public function __construct()
    {
        parent::__construct();
        $config = Factory::fromNames(array('route'), true);
        $lang = $config->get('default_language', 'en');
        $this->setDefaultLocale($lang);
    }
}
