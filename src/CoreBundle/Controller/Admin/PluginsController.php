<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\SettingsBundle\Manager\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sylius\Bundle\SettingsBundle\Controller\SettingsController as SyliusSettingsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SettingsController.
 *
 * @package Chamilo\SettingsBundle\Controller
 */
class PluginsController extends SyliusSettingsController
{
    /**
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @Route("/plugins")
     *
     * @return array
     */
    public function pluginsAction()
    {
        $appPlugin = new \AppPlugin();
        $installedPlugins = $appPlugin->get_installed_plugins();

        return $this->render(
            '@ChamiloTheme/Admin/Settings/plugins.html.twig',
            [
                'plugins' => $installedPlugins,
            ]
        );
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @Route("/plugins/add")
     *
     * @return array
     */
    public function pluginsAddAction()
    {
        $appPlugin = new \AppPlugin();
        $allPlugins = $appPlugin->read_plugins_from_path();
        $allPluginsList = [];
        foreach ($allPlugins as $pluginName) {
            $file = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';

            if (is_file($file)) {
                $pluginInfo = require $file;
                var_dump($pluginInfo);
                exit;
                $allPluginsList[] = $pluginInfo;
            }
        }

        $installedPlugins = $appPlugin->get_installed_plugins();

        $manager = $this->getSettingsManager();
        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloTheme/Admin/Settings/pluginsAdd.html.twig',
            [
                'plugins' => $allPluginsList,
                'installed_plugins' => $installedPlugins,
            ]
        );
    }

    /**
     * @return SettingsManager
     */
    protected function getSettingsManager()
    {
        return $this->get('chamilo.settings.manager');
    }
}
