<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin = UserRemoteServicePlugin::create();

Display::display_header();

echo $plugin->getIFrame();

Display::display_footer();
