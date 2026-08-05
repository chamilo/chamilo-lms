<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$plugin = UserRemoteServicePlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

Display::display_header($plugin->get_title());

echo $plugin->getIFrame();

Display::display_footer();
