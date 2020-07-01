<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin = UserRemoteServicePlugin::create();

Display::display_header($plugin->get_title());

echo $plugin->getIFrame($_REQUEST['serviceId']);

Display::display_footer();
