<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;

$plugin = ExerciseFocusedPlugin::create();

$exerciseId = (int) ($_GET['exerciseId'] ?? 0);

$renderRegion = $plugin->isEnableForExercise($exerciseId);

if ($renderRegion) {
    $_template['show_region'] = true;

    $em = Database::getManager();

    $existingExeId = (int) ChamiloSession::read('exe_id');
    $trackingExercise = null;

    if ($existingExeId) {
        $trackingExercise = $em->find(TrackEExercises::class, $existingExeId);
    }

    $_template['sec_token'] = Security::get_token('exercisefocused');

    if ('true' === $plugin->get(ExerciseFocusedPlugin::SETTING_ENABLE_OUTFOCUSED_LIMIT)) {
        $logRepository = $em->getRepository(Log::class);

        if ($trackingExercise) {
            $countOutfocused = $logRepository->countByActionInExe($trackingExercise, Log::TYPE_OUTFOCUSED);
        } else {
            $countOutfocused = 0;
        }

        $_template['count_outfocused'] = $countOutfocused;
        $_template['remaining_outfocused'] = (int) $plugin->get(ExerciseFocusedPlugin::SETTING_OUTFOCUSED_LIMIT) - $countOutfocused;
    }

    if ($trackingExercise) {
        $exercise = new Exercise($trackingExercise->getCId());

        if ($exercise->read($trackingExercise->getExeExoId())) {
            $_template['exercise_type'] = (int) $exercise->selectType();
        }
    }
}
