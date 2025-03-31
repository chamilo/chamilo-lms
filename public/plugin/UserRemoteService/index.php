<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

if ('true' !== UserRemoteServicePlugin::create()->get_hide_link_from_navigation_menu()) {
    foreach (UserRemoteServicePlugin::create()->getNavigationMenu() as $key => $menu) {
        $template->params['menu'][$key] = $menu;
    }
}
