<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

foreach (UserRemoteServicePlugin::create()->getNavigationMenu() as $key => $menu) {
    $template->params['menu'][$key] = $menu;
}
