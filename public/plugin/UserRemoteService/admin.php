<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_admin_script(true);

$plugin = UserRemoteServicePlugin::create();

Display::display_header($plugin->get_title());

echo $plugin->getCreationForm()->returnForm();

echo $plugin->getDeletionForm()->returnForm();

echo $plugin->getServiceHTMLTable();

Display::display_footer();
