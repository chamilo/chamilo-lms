<?php

/* For licensing terms, see /license.txt. */

require_once __DIR__.'/lib/PENSPlugin.php';

$plugin_info = [];
$plugin_info['plugin_class'] = 'PENSPlugin';

$plugin_info = array_merge($plugin_info, PENSPlugin::create()->get_info());
