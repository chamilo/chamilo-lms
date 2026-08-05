<?php

declare(strict_types=1);

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/src/ExtAuthChamiloLogoutButtonBehaviourPlugin.php';

api_protect_admin_script();

$plugin = ExtAuthChamiloLogoutButtonBehaviourPlugin::create();

Display::display_header($plugin->get_title());

echo Display::return_message($plugin->get_lang('admin_message'), 'normal', false);

Display::display_footer();
