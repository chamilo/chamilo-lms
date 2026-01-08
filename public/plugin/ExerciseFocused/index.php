<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Chamilo\PluginBundle\ExerciseFocused\Repository\LogRepository;

$plugin = ExerciseFocusedPlugin::create();

$request = Container::getRequest();

$exerciseId = $request->query->getInt('exerciseId');

$renderRegion = $plugin->isEnableForExercise($exerciseId);

if ($renderRegion) {
    $_template['show_region'] = true;

    $em = Database::getManager();

    $existingExeId = (int) ChamiloSession::read('exe_id');
    $trackingExercise = null;

    if ($existingExeId) {
        $trackingExercise = $em->find(TrackEExercise::class, $existingExeId);
    }

    $_template['sec_token'] = Security::get_token('exercisefocused');

    if ('true' === $plugin->get(ExerciseFocusedPlugin::SETTING_ENABLE_OUTFOCUSED_LIMIT)) {
        $countOutfocused = 0;
        /** @var LogRepository $logRepository */
        $logRepository = $em->getRepository(Log::class);

        if ($trackingExercise) {
            $countOutfocused = $logRepository->countByActionInExe($trackingExercise, Log::TYPE_OUTFOCUSED);
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
