<?php
namespace ApBlock\Apollo\Form\View\Helper;

class Form extends \Laminas\Form\View\Helper\Form
{
    protected $translations;

    public function setTranslations($translations){
        $this->translations = $translations;
    }

    public function trans($key){
        return $this->translations[$key];
    }
}
