<?php

/* For licensing terms, see /license.txt */

/**
 * Region entry point.
 *
 * AppPlugin::getAllPluginContentsByRegion() executes this file for the configured region.
 * The visible output must be printed directly from index.php, following the Static plugin pattern.
 */

require_once __DIR__.'/lib/show_user_info_plugin.class.php';

echo ShowUserInfoPlugin::create()->renderUserBlock();
