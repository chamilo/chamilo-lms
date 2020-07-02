<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

if (!api_user_is_login()) {
    api_not_allowed(true);
}

$plugin = UserRemoteServicePlugin::create();

Display::display_header();

echo $plugin->getIFrame();

Display::display_footer();
