<?php

/* For licensing terms, see /license.txt */

$plugin = ExerciseMonitoringPlugin::create();
$em = Database::getManager();

$showOverviewRegion = $plugin->isEnabled(true)
    && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/overview.php') !== false;
$showSubmitRegion = $plugin->isEnabled(true)
    && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/exercise_submit.php') !== false;

$_template['enabled'] = false;
$_template['show_overview_region'] = $showOverviewRegion;
$_template['show_submit_region'] = $showSubmitRegion;

if ($showOverviewRegion || $showSubmitRegion) {
    $exerciseId = (int) $_GET['exerciseId'];

    $objFieldValue = new ExtraFieldValue('exercise');
    $values = $objFieldValue->get_values_by_handler_and_field_variable(
        $exerciseId,
        ExerciseMonitoringPlugin::FIELD_SELECTED
    );

    $_template['enabled'] = $values && (bool) $values['value'];
    $_template['exercise_id'] = $exerciseId;
}

if ($showOverviewRegion && $_template['enabled']) {
}

if ($showSubmitRegion && $_template['enabled']) {
    $exercise = new Exercise(api_get_course_int_id());

    if ($exercise->read($_template['exercise_id'])) {
        $_template['exercise_type'] = (int) $exercise->selectType();
    }
}
