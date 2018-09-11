<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

/**
 * Class ChamiloExtension.
 *
 * @package Chamilo\CoreBundle\Twig\Extension
 */
class ChamiloExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('var_dump', 'var_dump'),
            new \Twig_SimpleFilter('icon', 'Template::get_icon_path'),
            new \Twig_SimpleFilter('get_lang', 'get_lang'),
            new \Twig_SimpleFilter('get_plugin_lang', 'get_plugin_lang'),
            new \Twig_SimpleFilter('icon', 'Template::get_icon_path'),
            new \Twig_SimpleFilter('img', 'Template::get_image'),
            new \Twig_SimpleFilter('api_get_local_time', 'api_get_local_time'),
            new \Twig_SimpleFilter('format_date', 'Template::format_date'),
            new \Twig_SimpleFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate'),
            new \Twig_SimpleFilter('api_get_configuration_value', 'api_get_configuration_value'),
            new \Twig_SimpleFilter('format_user_full_name', 'UserManager::formatUserFullName'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [];
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
