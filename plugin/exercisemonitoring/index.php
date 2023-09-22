<?php

/* For licensing terms, see /license.txt */

$plugin = ExerciseMonitoringPlugin::create();

$_template['show_overview_region'] = $plugin->isEnabled(true)
    && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/overview.php') !== false;

$_template['enabled'] = false;

if ($_template['show_overview_region']) {
    $exerciseId = (int) $_GET['exerciseId'];

    $objFieldValue = new ExtraFieldValue('exercise');
    $values = $objFieldValue->get_values_by_handler_and_field_variable($exerciseId, ExerciseMonitoringPlugin::FIELD_SELECTED);

    if ($values && (bool) $values['value']) {
        $_template['enabled'] = true;

        $existingExeId = (int) ChamiloSession::read('exe_id');
    }

    $_template['exercise_id'] = $exerciseId;
}

$_template['show_submit_region'] = $plugin->isEnabled(true)
    && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/exercise_submit.php') !== false;

if ($_template['show_submit_region']) {
    $exerciseId = (int) $_GET['exerciseId'];

    $objFieldValue = new ExtraFieldValue('exercise');
    $values = $objFieldValue->get_values_by_handler_and_field_variable($exerciseId, ExerciseMonitoringPlugin::FIELD_SELECTED);

    if ($values && (bool) $values['value']) {
        $existingExeId = (int) ChamiloSession::read('exe_id');

        $_template['enabled'] = true;
        $_template['exercise_id'] = $exerciseId;
        $_template['exe_id'] = $existingExeId;
    }
}
