<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Repository\IllustrationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class ChamiloExtension.
 */
class ChamiloExtension extends AbstractExtension
{
    private $illustrationRepository;

    public function __construct(IllustrationRepository $illustrationRepository)
    {
        $this->illustrationRepository = $illustrationRepository;
    }

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
            new TwigFilter('api_convert_and_format_date', 'api_convert_and_format_date'),
            new TwigFilter('format_date', 'api_format_date'),
            new TwigFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate'),
            new TwigFilter('api_get_configuration_value', 'api_get_configuration_value'),
            new TwigFilter('format_user_full_name', 'UserManager::formatUserFullName'),
            new TwigFilter('illustration', [$this, 'getIllustration']),
            new TwigFilter('user_illustration', [$this, 'getUserIllustration']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [];
    }

    public function getIllustration($node): string
    {
        $url = $this->illustrationRepository->getIllustrationUrlFromNode($node);

        return $url;
    }

    public function getUserIllustration($node): string
    {
        $url = $this->getIllustration($node);

        if (empty($url)) {
            return 'img/icons/32/unknown.png';
        }

        return $url;
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
