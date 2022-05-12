<?php
namespace ApBlock\Apollo\Language;

use ApBlock\Apollo\Factory\Factory;

class TranslatableListener extends \Gedmo\Translatable\TranslatableListener
{
    public function __construct()
    {
        parent::__construct();
        $config = Factory::fromNames(array('route'), true);
        $lang = $config->get(array('route','translator','default'), null);
        if($lang == null){
            $config = Factory::fromNames(array('api_route'), true);
            $lang = $config->get(array('route','translator','default'), 'en');
        }
        $this->setDefaultLocale($lang);
    }
}
