<?php

namespace ApBlock\Apollo\Form\View\Helper;

class FormMultiCheckbox extends \Laminas\Form\View\Helper\FormMultiCheckbox
{
    /**
     * {@inheritdoc}
     */
    public function getInlineClosingBracket()
    {
        return '><span class="checkmark"></span>';
    }
}
