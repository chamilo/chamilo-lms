<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;

$plugin = ExerciseFocusedPlugin::create();

$_template['show_region'] = 'true' === $plugin->get('tool_enable')
    && strpos($_SERVER['SCRIPT_NAME'], '/main/exercise/exercise_submit.php') !== false;

if ($_template['show_region']) {
    $exerciseId = (int) $_GET['exerciseId'];

    $objFieldValue = new ExtraFieldValue('exercise');
    $values = $objFieldValue->get_values_by_handler_and_field_variable($exerciseId, ExerciseFocusedPlugin::FIELD_SELECTED);

    if ($values && (bool) $values['value']) {
        $em = Database::getManager();
        $logRepository = $em->getRepository(Log::class);

        $_template['sec_token'] = Security::get_token('exercisefocused');

        if ('true' === $plugin->get('enable_abandonment_limit')) {
            $existingExeId = (int) ChamiloSession::read('exe_id');

            if ($existingExeId) {
                $trackingExercise = $em->find(TrackEExercises::class, $existingExeId);

                $countAbandonments = $logRepository->countByActionInExe($trackingExercise, Log::TYPE_OUTFOCUSED);
            } else {
                $countAbandonments = 0;
            }

            $_template['count_abandonments'] = $countAbandonments;
            $_template['remaining_abandonments'] = (int) $plugin->get('abandonment_limit') - $countAbandonments;
        }
    }
}
