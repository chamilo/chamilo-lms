<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


/**
 * Class ChamiloExtension.
 *
 * @package Chamilo\CoreBundle\Twig\Extension
 */
class ChamiloExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('var_dump', 'var_dump'),
            new TwigFilter('icon', 'Display::get_icon_path'),
            new TwigFilter('get_lang', 'get_lang'),
            new TwigFilter('get_plugin_lang', 'get_plugin_lang'),
            new TwigFilter('icon', 'Display::get_icon_path'),
            new TwigFilter('img', 'Display::get_image'),
            new TwigFilter('api_get_local_time', 'api_get_local_time'),
            new TwigFilter('format_date', 'api_format_date'),
            new TwigFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate'),
            new TwigFilter('api_get_configuration_value', 'api_get_configuration_value'),
            new TwigFilter('format_user_full_name', 'UserManager::formatUserFullName'),
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
