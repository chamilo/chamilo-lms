<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/src/HelloWorldPlugin.php';

$plugin_info = HelloWorldPlugin::create()->get_info();
$plugin_info['source'] = 'official';
$plugin_info['commercial_model'] = 'free';
$plugin_info['supports_regions'] = true;
