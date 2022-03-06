<?php
namespace ApBlock\Apollo\Form\Translator;

use Zend\Validator\Translator\TranslatorInterface;

interface TranslatorAwareInterface
{
    /**
     * @return TranslatorInterface|null
     */
    public function getTranslator();

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator);
}