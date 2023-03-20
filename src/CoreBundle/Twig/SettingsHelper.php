<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Twig;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
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

    public function getName(): string
    {
        return 'chamilo_settings';
    }

    /**
     * @param string $schemaAlias example: admin, agenda, etc
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
        return $this->settingsManager instanceof SettingsManager ? $this->settingsManager->getSetting($parameter) : '';
    }
}
