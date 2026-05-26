<?php

/* For licensing terms, see /license.txt */

if (!class_exists('Plugin', false)) {
    require_once __DIR__.'/../../main/inc/global.inc.php';
}

require_once __DIR__.'/lib/customfooter_plugin.class.php';

// No custom database structure is required. Settings are stored in the
// plugin configuration for the current access URL by Chamilo.
