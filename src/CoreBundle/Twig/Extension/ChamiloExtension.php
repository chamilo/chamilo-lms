<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Twig\SettingsHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class ChamiloExtension.
 */
class ChamiloExtension extends AbstractExtension
{
    private IllustrationRepository $illustrationRepository;
    private SettingsHelper $helper;

    public function __construct(IllustrationRepository $illustrationRepository, SettingsHelper $helper)
    {
        $this->illustrationRepository = $illustrationRepository;
        $this->helper = $helper;
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
            new TwigFilter('format_file_size', 'format_file_size'),
            new TwigFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate'),
            new TwigFilter('api_get_configuration_value', 'api_get_configuration_value'),
            new TwigFilter('remove_xss', 'Security::remove_XSS'),
            new TwigFilter('format_user_full_name', 'UserManager::formatUserFullName'),
            new TwigFilter('illustration', [$this, 'getIllustration']),

            //new \Twig_SimpleFunction('chamilo_settings_all', array($this, 'getSettings')),
            new TwigFilter('get_setting', [$this, 'getSettingsParameter']),
            new TwigFilter('api_get_setting', [$this, 'getSettingsParameter']),
            //new \Twig_SimpleFunction('chamilo_settings_has', [$this, 'hasSettingsParameter']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('chamilo_settings_all', [$this, 'getSettings']),
            new TwigFunction('chamilo_settings_get', [$this, 'getSettingsParameter']),
            new TwigFunction('chamilo_settings_has', [$this, 'hasSettingsParameter']),
        ];
    }

    public function getIllustration(ResourceIllustrationInterface $resource): string
    {
        return $this->illustrationRepository->getIllustrationUrl($resource);
    }

    public function getSettings($namespace)
    {
        return $this->helper->getSettings($namespace);
    }

    public function getSettingsParameter($name)
    {
        return $this->helper->getSettingsParameter($name);
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
