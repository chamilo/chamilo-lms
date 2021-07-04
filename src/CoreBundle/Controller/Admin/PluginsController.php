<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use AppPlugin;
use Chamilo\CoreBundle\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PluginsController extends BaseController
{
    /**
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @Route("/plugins")
     */
    public function pluginsAction(): Response
    {
        $appPlugin = new AppPlugin();
        $installedPlugins = $appPlugin->getInstalledPlugins();

        return $this->render(
            '@ChamiloCore/Admin/Settings/plugins.html.twig',
            [
                'plugins' => $installedPlugins,
            ]
        );
    }

    /**
     * @Security("is_granted('ROLE_ADMIN')")
     *
     * @Route("/plugins/add")
     */
    public function pluginsAddAction(): Response
    {
        $appPlugin = new AppPlugin();
        $allPlugins = $appPlugin->read_plugins_from_path();
        $allPluginsList = [];
        foreach ($allPlugins as $pluginName) {
            /*$file = api_get_path(SYS_PLUGIN_PATH).$pluginName.'/plugin.php';

            if (is_file($file)) {
                $pluginInfo = require $file;
                var_dump($pluginInfo);exit;
                $allPluginsList[] = $pluginInfo;
            }*/
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
