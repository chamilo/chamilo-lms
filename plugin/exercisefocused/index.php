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
    $logRepository = $em->getRepository(Log::class);

    $_template['sec_token'] = Security::get_token('exercisefocused');

    if ('true' === $plugin->get(ExerciseFocusedPlugin::SETTING_ENABLE_OUTFOCUSED_LIMIT)) {
        $existingExeId = (int) ChamiloSession::read('exe_id');

        if ($existingExeId) {
            $trackingExercise = $em->find(TrackEExercises::class, $existingExeId);

            $countOutfocused = $logRepository->countByActionInExe($trackingExercise, Log::TYPE_OUTFOCUSED);
        } else {
            $countOutfocused = 0;
        }

        $_template['count_outfocused'] = $countOutfocused;
        $_template['remaining_outfocused'] = (int) $plugin->get(ExerciseFocusedPlugin::SETTING_OUTFOCUSED_LIMIT) - $countOutfocused;
    }
}
