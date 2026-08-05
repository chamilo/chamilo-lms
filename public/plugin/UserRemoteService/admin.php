<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_admin_script(true);

$plugin = UserRemoteServicePlugin::create();
$plugin->handleAdminPost();

Display::display_header($plugin->get_title());

echo $plugin->getAdminPageHtml();

Display::display_footer();
