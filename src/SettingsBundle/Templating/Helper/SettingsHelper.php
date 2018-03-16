<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\SettingsBundle\Templating\Helper;

use Chamilo\SettingsBundle\Manager\SettingsManager;
use Sylius\Bundle\SettingsBundle\Manager\SettingsManagerInterface;
use Sylius\Bundle\SettingsBundle\Templating\Helper\SettingsHelperInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class SettingsHelper.
 *
 * @package Chamilo\SettingsBundle\Templating\Helper
 */
class SettingsHelper extends Helper implements SettingsHelperInterface
{
    /**
     * @var SettingsManager
     */
    private $settingsManager;

    /**
     * @param SettingsManagerInterface $settingsManager
     */
    public function __construct(SettingsManagerInterface $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_settings';
    }

    /**
     * @param string $schemaAlias Example: admin, agenda, etc
     *
     * @return \Sylius\Bundle\SettingsBundle\Model\Settings
     */
    public function getSettings($schemaAlias)
    {
        return $this->settingsManager->load($schemaAlias);
    }

    /**
     * @param string $parameter Example: admin.administrator_name
     *
     * @return mixed
     */
    public function getSettingsParameter($parameter)
    {
        return $this->settingsManager->getSetting($parameter);
    }
}
