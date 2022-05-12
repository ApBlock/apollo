<?php

namespace ApBlock\Apollo\Form;


use ApBlock\Apollo\Form\Translator\TranslatorAwareInterface;
use ApBlock\Apollo\Form\Translator\TranslatorAwareTrait;
use ApBlock\Apollo\Form\Translator\TranslatorHelperInterface;
use ApBlock\Apollo\Form\Translator\TranslatorHelperTrait;
use ApBlock\Apollo\Form\Translator\TranslatorLoaderInterface;
use Laminas\I18n\Translator\Translator;
use Laminas\Validator\AbstractValidator;
use Laminas\Mvc\I18n\Translator as MvcTranslator;


class Form extends \Laminas\Form\Form implements TranslatorAwareInterface, TranslatorHelperInterface
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

    public function lang(){
        return $_COOKIE["default_language"] ?? 'en';
    }

    public static function generateInputNameErrors($array)
    {
        $result = self::generateInputNameRec($array);
        $retArray = array();
        foreach ($result as $arrKey => $arrVal) {
            $exp = explode("]", $arrKey);
            $newKey = array_shift($exp);
            array_pop($exp);
            $newKey = $newKey.implode("]", $exp).(count($exp) > 0 ? "]" : "");
            $retArray[$newKey][] = $arrVal;
        }
        return $retArray;
    }

    /**
     * @param $array
     * @param string $prefix
     * @return array
     */
    public static function generateInputNameRec($array, $prefix = '')
    {
        $result = array();
        foreach ($array as $key=>$value) {
            if (is_array($value)) {
                $result = $result + self::generateInputNameRec($value, $prefix . $key . '][');
            } else {
                $result[$prefix.$key] = $value;
            }
        }
        return $result;
    }
}
