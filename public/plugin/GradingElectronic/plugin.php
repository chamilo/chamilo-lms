<?php

/* For licensing terms, see /license.txt */

$plugin_info = GradingElectronicPlugin::create()->get_info();
$plugin_info['supports_regions'] = true;
$plugin_info['templates'] = ['view/grading.html.twig'];
