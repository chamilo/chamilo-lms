<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin_info = TeamsPlugin::create()->get_info();
$plugin_info['source'] = 'official';
$plugin_info['commercial_model'] = 'commercial_service';
$plugin_info['commercial_model_reason'] = 'Microsoft 365 / Microsoft Graph tenant configuration is required.';
