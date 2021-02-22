<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Twig;

use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
use Sylius\Bundle\SettingsBundle\Model\Settings;
use Sylius\Bundle\SettingsBundle\Templating\Helper\SettingsHelperInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class SettingsHelper.
 */
class SettingsHelper extends Helper implements SettingsHelperInterface
{
    /**
     * @var SettingsManagerInterface
     */
    private $settingsManager;

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
    public function getSettings($schemaAlias)
    {
        return $this->settingsManager->load($schemaAlias);
    }

    /**
     * @param string $parameter Example: admin.administrator_name
     */
    public function getSettingsParameter($parameter)
    {
        return $this->settingsManager->getSetting($parameter);
    }
}
