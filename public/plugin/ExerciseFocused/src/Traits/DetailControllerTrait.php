<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\ExerciseFocused\Traits;

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Display;
use Exercise;

trait DetailControllerTrait
{
    private function generateHeader(Exercise $objExercise, User $student, TrackEExercise $trackExe): string
    {
        $startDate = api_get_local_time($trackExe->getStartDate(), null, null, true, true, true);
        $endDate = api_get_local_time($trackExe->getExeDate(), null, null, true, true, true);

        return Display::page_subheader2($objExercise->selectTitle())
            .Display::tag('p', $student->getFullNameWithUsername(), ['class' => 'lead'])
            .Display::tag('p', sprintf(get_lang('Test start date: %s'), $startDate))
            .Display::tag('p', sprintf(get_lang('Test end date: %s'), $endDate))
            .Display::tag('p', sprintf(get_lang('Test duration: %s'), api_format_time($trackExe->getExeDuration())))
        ;
    }
}
