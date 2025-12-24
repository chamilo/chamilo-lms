<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Traits;

use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\UserBundle\Entity\User;
use Display;
use Exercise;

trait DetailControllerTrait
{
    private function generateHeader(Exercise $objExercise, User $student, TrackEExercises $trackExe): string
    {
        $startDate = api_get_local_time($trackExe->getStartDate(), null, null, true, true, true);
        $endDate = api_get_local_time($trackExe->getExeDate(), null, null, true, true, true);

        return Display::page_subheader2($objExercise->selectTitle())
            .Display::tag('p', $student->getCompleteNameWithUsername(), ['class' => 'lead'])
            .Display::tag(
                'p',
                sprintf(get_lang('QuizRemindStartDate'), $startDate)
                .sprintf(get_lang('QuizRemindEndDate'), $endDate)
                .sprintf(get_lang('QuizRemindDuration'), api_format_time($trackExe->getExeDuration()))
            );
    }
}
