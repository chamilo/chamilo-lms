<?php

namespace Chamilo\CoreBundle\Twig\Extension;

class ChamiloExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            //new \Twig_SimpleFunction('code', array($this, 'getCode'), array('is_safe' => array('html'))),
            new \Twig_SimpleFilter('get_setting', array($this, 'getSetting')),
            new \Twig_SimpleFilter('icon', 'Template::get_icon_path'),
            new \Twig_SimpleFilter('display_page_subheader', 'Display::page_subheader_and_translate'),
            new \Twig_SimpleFilter('var_dump', 'var_dump'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            //new \Twig_SimpleFunction('code', array($this, 'getCode'), array('is_safe' => array('html'))),
            //new \Twig_SimpleFunction('get_setting', array($this, 'getSetting')),
            new \Twig_SimpleFunction('get_path', 'api_get_path'),

            new \Twig_SimpleFunction('return_message', 'Display::return_message_and_translate'),
            new \Twig_SimpleFunction('display_page_header', 'Display::page_header_and_translate'),

            new \Twig_SimpleFunction('icon', 'Template::get_icon_path'),
            new \Twig_SimpleFunction('format_date', 'Template::format_date')
        );
    }

    public function getSetting($value)
    {
        //return 'true';
        return api_get_setting($value);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'chamilo_extension';
    }
}
