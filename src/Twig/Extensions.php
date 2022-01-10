<?php

namespace ApBlock\Apollo\Twig;

use ApBlock\Apollo\Helper\Helper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extensions extends AbstractExtension
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Base constructor.
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('getBasepath', array($this, 'basepath')),
            new TwigFunction('get_filemtime', array($this, 'getFilemtime')),
        );
    }

    /**
     * @param $path
     * @return string
     */
    public function basepath($path)
    {
        return $this->helper->getRealUrl($path);
    }

    /**
     * file modify date
     *
     * @param string $path
     * @return int|false
     */
    public function getFilemtime($path)
    {
        return filemtime(implode(DIRECTORY_SEPARATOR, array($_SERVER["DOCUMENT_ROOT"], ltrim($path, '/\\'))));
    }
}
