<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig\Extension;

use Chamilo\CoreBundle\Component\Utils\NameConvention;
use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Twig\SettingsHelper;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ChamiloExtension extends AbstractExtension
{
    private IllustrationRepository $illustrationRepository;
    private SettingsHelper $helper;
    private RouterInterface $router;
    private NameConvention $nameConvention;

    public function __construct(IllustrationRepository $illustrationRepository, SettingsHelper $helper, RouterInterface $router, NameConvention $nameConvention)
    {
        $this->illustrationRepository = $illustrationRepository;
        $this->helper = $helper;
        $this->router = $router;
        $this->nameConvention = $nameConvention;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('var_dump', 'var_dump'),
            new TwigFilter('icon', 'Display::get_icon_path'),
            new TwigFilter('mdi_icon', 'Display::getMdiIcon'),
            new TwigFilter('get_lang', 'get_lang'),
            new TwigFilter('get_plugin_lang', 'get_plugin_lang'),
            new TwigFilter('img', 'Display::get_image'),
            new TwigFilter('api_get_local_time', 'api_get_local_time'),
            new TwigFilter('api_convert_and_format_date', 'api_convert_and_format_date'),
            new TwigFilter('format_date', 'api_format_date'),
            new TwigFilter('format_file_size', 'format_file_size'),
            new TwigFilter('date_to_time_ago', 'Display::dateToStringAgoAndLongDate'),
            new TwigFilter('api_get_configuration_value', 'api_get_configuration_value'),
            new TwigFilter('remove_xss', 'Security::remove_XSS'),
            new TwigFilter('user_complete_name', 'UserManager::formatUserFullName'),
            new TwigFilter('user_complete_name_with_link', [$this, 'completeUserNameWithLink']),
            new TwigFilter('illustration', [$this, 'getIllustration']),
            new TwigFilter('api_get_setting', [$this, 'getSettingsParameter']),
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

    public function completeUserNameWithLink(User $user): string
    {
        $url = $this->router->generate(
            'legacy_main',
            [
                'name' => '/inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id='.$user->getId(),
            ]
        );

        $name = $this->nameConvention->getPersonName($user);

        return "<a href=\"$url\" class=\"ajax\">$name</a>";
    }

    public function getIllustration(ResourceIllustrationInterface $resource): string
    {
        return $this->illustrationRepository->getIllustrationUrl($resource);
    }

    public function getSettings($namespace): SettingsInterface
    {
        return $this->helper->getSettings($namespace);
    }

    public function getSettingsParameter($name)
    {
        return $this->helper->getSettingsParameter($name);
    }

    /**
     * Returns the name of the extension.
     */
    public function getName(): string
    {
        return 'chamilo_extension';
    }
}
