<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin_info = GoogleMapsPlugin::create()->get_info();

$plugin_info['commercial_model'] = 'freemium';
$plugin_info['commercial_model_reason'] = 'Google Maps API usage normally depends on a provider account and billing/free-tier model.';
