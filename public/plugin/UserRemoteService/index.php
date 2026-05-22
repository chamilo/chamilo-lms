<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin = UserRemoteServicePlugin::create();

if (!api_user_is_login() || !$plugin->isEnabled() || $plugin->get_hide_link_from_navigation_menu()) {
    return;
}

foreach ($plugin->getNavigationMenu() as $key => $menu) {
    $template->params['menu'][$key] = $menu;
}
