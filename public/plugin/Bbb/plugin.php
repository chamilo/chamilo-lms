<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

$plugin_info = BbbPlugin::create()->get_info();

$plugin_info['commercial_model'] = 'freemium';
$plugin_info['commercial_model_reason'] = 'BigBlueButton requires an external BBB server/service; deployments may depend on free, freemium or paid hosting plans.';
