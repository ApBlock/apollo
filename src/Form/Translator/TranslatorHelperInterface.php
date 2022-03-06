<?php
namespace ApBlock\Apollo\Form\Translator;

interface TranslatorHelperInterface
{
    /**
     * @param $key
     * @return string
     */
    public function trans($key);
}