<?php
namespace ApBlock\Apollo\Form\View\Helper;


use ApBlock\Apollo\Twig\Interfaces\TwigAwareInterface;
use ApBlock\Apollo\Twig\Traits\TwigAwareTrait;
use ApBlock\Apollo\Twig\Twig;
use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormCollection as BaseFormCollection;

class FormCollection extends BaseFormCollection implements TwigAwareInterface
{
    use TwigAwareTrait;

    protected $onlyRenderTemplateWrapper =  '<span data-%s-template="%s"></span>';

    public function render(ElementInterface $element)
    {
        $this->setWrapper('<div%4$s>%2$s%1$s%3$s</div>');
        return parent::render($element);
    }

    public function renderOnlyTemplate($twigFile, $id, $collection)
    {
        $this->setTemplateWrapper('%s');
        $this->setShouldWrap(false);

        $content = html_entity_decode(trim(parent::renderTemplate($collection)));

        /** @var Twig $twig */
        $twig = $this->twig;
        $template = $twig->render($twigFile, array('template'=>$content));

        return sprintf(
            $this->onlyRenderTemplateWrapper,
            $id,
            htmlentities($template)
        );
    }
}
