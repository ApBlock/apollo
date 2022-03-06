<?php

namespace ApBlock\Apollo\Form;


use ApBlock\Apollo\Form\Translator\TranslatorAwareInterface;
use ApBlock\Apollo\Form\Translator\TranslatorAwareTrait;
use ApBlock\Apollo\Form\Translator\TranslatorHelperInterface;
use ApBlock\Apollo\Form\Translator\TranslatorHelperTrait;
use ApBlock\Apollo\Form\Translator\TranslatorLoaderInterface;
use Zend\Mvc\I18n\Translator as MvcTranslator;
use Zend\I18n\Translator\Translator;
use Zend\Validator\AbstractValidator;

class Form extends \Zend\Form\Form implements TranslatorAwareInterface, TranslatorHelperInterface
{
    use TranslatorAwareTrait;
    use TranslatorHelperTrait;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);
        if ($this instanceof TranslatorLoaderInterface) {
            $this->autoLoadTranslator();
        }
        if (!$this->translator instanceof MvcTranslator) {
            $this->setTranslator(new MvcTranslator(Translator::factory(array(
                    'locale' => $_COOKIE["default_language"],
                    'translation_file_patterns' => array(
                        array(
                            'type' => 'phparray',
                            'base_dir' => BASE_DIR. "/config/translations",
                            'pattern' => '%s.php',
                        ),
                    ),
            ))));
        }
        AbstractValidator::setDefaultTranslator($this->translator);
        AbstractValidator::setDefaultTranslatorTextDomain(static::class);
    }
}
