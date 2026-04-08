<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$plugin = XApiPlugin::create();
$plugin->ensureCurrentUrlConfigurationDefaults();
$plugin_info = $plugin->get_info();
