<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SettingsController.
 */
class PluginsController extends BaseController
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
        $installedPlugins = $appPlugin->getInstalledPlugins();

        return $this->render(
            '@ChamiloCore/Admin/Settings/plugins.html.twig',
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

        $installedPlugins = $appPlugin->getInstalledPlugins();

        return $this->render(
            '@ChamiloCore/Admin/Settings/pluginsAdd.html.twig',
            [
                'plugins' => $allPluginsList,
                'installed_plugins' => $installedPlugins,
            ]
        );
    }
}
