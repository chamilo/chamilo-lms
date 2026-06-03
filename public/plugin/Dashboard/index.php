<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/src/DashboardPlugin.php';

api_protect_admin_script(true);

$plugin = DashboardPlugin::create();

if (!$plugin->isEnabled()) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('PluginIsNotEnabled'), 'warning')
    );
    api_not_allowed(true);
}

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'Dashboard/admin.php');
exit;
