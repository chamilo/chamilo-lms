<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use Twig\TwigFunction;

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
            new \Twig_SimpleFilter('api_get_local_time', 'api_get_local_time'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('chamilo_page_title_get', [$this, 'getChamiloPageTitle']),
        ];
    }

    public function getChamiloPageTitle(): string
    {
        $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $urlPath = rtrim($urlPath, '/');
        $parts = explode('/', $urlPath);
        $title = end($parts);

        $titleList = [];
        $titleList[] = 'OFAJ';     // api_get_setting('Institution');
        $titleList[] = 'PARKUR';     // api_get_setting('siteName');
        $titleList[] = ucfirst(str_replace('-', ' ', $title));    // Page title

        $titleString = '';
        for ($i = 0; $i < count($titleList); $i++) {
            $titleString .= $titleList[$i];
            if (isset($titleList[$i + 1])) {
                $item = trim($titleList[$i + 1]);
                if (!empty($item)) {
                    $titleString .= ' - ';
                }
            }
        }

        return $titleString;
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
