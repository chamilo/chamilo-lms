<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Sylius settings extension for Twig.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class SettingsExtension extends AbstractExtension
{
    /**
     * @var SettingsHelper
     */
    private $helper;

    public function __construct(SettingsHelper $helper)
    {
        $this->helper = $helper;
    }

    public function getFunctions()
    {
        return [
             new TwigFunction('chamilo_settings_all', [$this, 'getSettings']),
             new TwigFunction('chamilo_settings_get', [$this, 'getSettingsParameter']),
             new TwigFunction('chamilo_settings_has', [$this, 'hasSettingsParameter']),
        ];
    }

    public function getFilters()
    {
        return [
             //new \Twig_SimpleFunction('chamilo_settings_all', array($this, 'getSettings')),
             new TwigFilter('get_setting', [$this, 'getSettingsParameter']),
             new TwigFilter('api_get_setting', [$this, 'getSettingsParameter']),
             //new \Twig_SimpleFunction('chamilo_settings_has', [$this, 'hasSettingsParameter']),
        ];
    }

    /**
     * Load settings from given namespace.
     *
     * @param string $namespace
     *
     * @return array
     */
    public function getSettings($namespace)
    {
        return $this->helper->getSettings($namespace);
    }

    /**
     * @param $name
     */
    public function getSettingsParameter($name)
    {
        return $this->helper->getSettingsParameter($name);
    }

    public function getName()
    {
        return 'chamilo_settings';
    }
}
