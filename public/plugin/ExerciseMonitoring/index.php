<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$plugin = ExerciseMonitoringPlugin::create();
$em = Container::getEntityManager();

$request = Container::getRequest();
$requestRoute = $request->query->get('_route');

$isEnabled = $plugin->isEnabled(true);
$showOverviewRegion = $isEnabled && '/main/exercise/overview.php' === $requestRoute;
$showSubmitRegion = $isEnabled && '/main/exercise/exercise_submit.php' === $requestRoute;

$_template['enabled'] = false;
$_template['show_overview_region'] = $showOverviewRegion;
$_template['show_submit_region'] = $showSubmitRegion;

if ($showOverviewRegion || $showSubmitRegion) {
    $exerciseId = $request->query->getInt('exerciseId');

    $objFieldValue = new ExtraFieldValue('exercise');
    $values = $objFieldValue->get_values_by_handler_and_field_variable(
        $exerciseId,
        ExerciseMonitoringPlugin::FIELD_SELECTED
    );

    $_template['enabled'] = $values && (bool) $values['value'];
    $_template['exercise_id'] = $exerciseId;
}

$_template['enable_snapshots'] = true;

$isAdult = $plugin->isAdult();

if ($showOverviewRegion && $_template['enabled']) {
    $_template['instructions'] = $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTIONS);

    if ('true' === $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE)) {
        $_template['instructions'] = $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTIONS_MINORS);

        if ($isAdult) {
            $_template['instructions'] = $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTIONS_ADULTS);
        } else {
            $_template['enable_snapshots'] = false;
        }
    }

    $_template['instructions'] = Security::remove_XSS($_template['instructions']);
}

if ($showSubmitRegion && $_template['enabled']) {
    $exercise = new Exercise(api_get_course_int_id());

    if ($exercise->read($_template['exercise_id'])) {
        $_template['exercise_type'] = $exercise->selectType();

        if ('true' === $plugin->get(ExerciseMonitoringPlugin::SETTING_INSTRUCTION_AGE_DISTINCTION_ENABLE)
            && !$isAdult
        ) {
            $_template['enable_snapshots'] = false;
        }
    }
}
