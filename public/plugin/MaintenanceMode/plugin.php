<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/** @var MaintenanceModePlugin $plugin */
$plugin = MaintenanceModePlugin::create();

$plugin_info = $plugin->get_info();
$plugin_info['source'] = 'official';
$plugin_info['commercial_model'] = 'free';
