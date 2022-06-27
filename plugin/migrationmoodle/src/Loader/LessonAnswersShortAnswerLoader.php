<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonAnswersShortAnswerLoader.
 *
 * Loader to create Fill Blanks question answers comming from Short Answer and Numerical lesson page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonAnswersShortAnswerLoader implements LoaderInterface
{
    public const INPUT_WIDTH = 300;

    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $courseInfo = api_get_course_info_by_id($incomingData['c_id']);

        $exercise = new \Exercise($incomingData['c_id']);
        $exercise->read($incomingData['quiz_id']);

        $question = \Question::read($incomingData['question_id'], $courseInfo);
        $question->weighting = 0;

        $answer = new \Answer($incomingData['question_id'], $incomingData['c_id'], $exercise);

        $incomingData['answers'] = '['.$incomingData['answers'].']';
        // Remove the "::" eventually written by the user
        $incomingData['answers'] = str_replace('::', '', $incomingData['answers']);
        // Remove starting and ending space and &nbsp;
        $incomingData['answers'] = api_preg_replace("/\xc2\xa0/", " ", $incomingData['answers']);

        $blankStartSeparator = '[';
        $blankEndSeparator = ']';
        $blankStartSeparatorRegexp = \FillBlanks::escapeForRegexp($blankStartSeparator);
        $blankEndSeparatorRegexp = \FillBlanks::escapeForRegexp($blankEndSeparator);

        // Remove spaces at the beginning and the end of text in square brackets
        $return = preg_replace_callback(
            "/".$blankStartSeparatorRegexp."[^]]+".$blankEndSeparatorRegexp."/",
            function ($matches) use ($blankStartSeparator, $blankEndSeparator) {
                $matchingResult = $matches[0];
                $matchingResult = trim($matchingResult, $blankStartSeparator);
                $matchingResult = trim($matchingResult, $blankEndSeparator);
                $matchingResult = trim($matchingResult);
                // Remove forbidden chars
                $matchingResult = str_replace("/\\/", "", $matchingResult);
                $matchingResult = str_replace('/"/', "", $matchingResult);

                return $blankStartSeparator.$matchingResult.$blankEndSeparator;
            },
            $incomingData['answers']
        );

        $question->weighting += $incomingData['scores'];

        $return .= '::'.$incomingData['scores'].':';
        $return .= self::INPUT_WIDTH;
        $return .= ':0@';

        $answer->createAnswer($return, 0, $incomingData['comment'], 0, 1);
        $answer->save();

        $question->save($exercise);

        return $question->id;
    }
}
