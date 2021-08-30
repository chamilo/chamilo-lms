<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig;

use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Model\SettingsInterface;
use Sylius\Bundle\SettingsBundle\Templating\Helper\SettingsHelperInterface;
use Symfony\Component\Templating\Helper\Helper;

class SettingsHelper extends Helper implements SettingsHelperInterface
{
    private SettingsManagerInterface $settingsManager;

    public function __construct(SettingsManagerInterface $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function getName()
    {
        return 'chamilo_settings';
    }

    /**
     * @param string $schemaAlias Example: admin, agenda, etc
     *
     * @return Settings
     */
    public function getSettings($schemaAlias): SettingsInterface
    {
        return $this->settingsManager->load($schemaAlias);
    }

    /**
     * @param string $parameter Example: platform.theme
     */
    public function getSettingsParameter(string $parameter)
    {
        return $this->settingsManager->getSetting($parameter);
    }
}
