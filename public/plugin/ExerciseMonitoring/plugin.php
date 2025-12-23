<?php

/* For licensing terms, see /license.txt */

$plugin_info = ExerciseMonitoringPlugin::create()->get_info();

$plugin_info['templates'] = [
    'templates/modal.html.twig',
    'templates/exercise_submit.html.twig',
];
