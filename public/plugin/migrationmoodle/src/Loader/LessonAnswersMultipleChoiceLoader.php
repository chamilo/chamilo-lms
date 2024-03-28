<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

/**
 * Class LessonAnswersMultipleChoiceLoader.
 *
 * Loader to create Unique Answer question comming from Multiple Choice lesson page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonAnswersMultipleChoiceLoader extends LessonAnswersTrueFalseLoader
{
    /**
     * @return int
     */
    public function load(array $incomingData)
    {
        return parent::load($incomingData);
    }
}
