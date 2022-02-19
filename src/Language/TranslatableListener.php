<?php

namespace ApBlock\Apollo\Language;

class TranslatableListener extends \Gedmo\Translatable\TranslatableListener
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultLocale('en');
    }
}
