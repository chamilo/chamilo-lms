<?php

/* See license terms in /license.txt */

/**
 * EVENTS LIBRARY.
 *
 * This is the events library for Chamilo.
 * Functions of this library are used to record informations when some kind
 * of event occur. Each event has his own types of informations then each event
 * use its own function.
 *
 * @todo convert queries to use Database API
 */
class ExerciseShowFunctions
{
    /**
     * Shows the answer to a fill-in-the-blanks question, as HTML.
     *
     * @param Exercise $exercise
     * @param int      $feedbackType
     * @param string   $answer
     * @param int      $id                           Exercise ID
     * @param int      $questionId                   Question ID
     * @param int      $resultsDisabled
     * @param string   $originalStudentAnswer
     * @param bool     $showTotalScoreAndUserChoices
     */
    public static function display_fill_in_blanks_answer(
        $exercise,
        $feedbackType,
        $answer,
        $id,
        $questionId,
        $resultsDisabled,
        $originalStudentAnswer = '',
        $showTotalScoreAndUserChoices
    ) {
        $answerHTML = FillBlanks::getHtmlDisplayForAnswer(
            $answer,
            $feedbackType,
            $resultsDisabled,
            $showTotalScoreAndUserChoices
        );

        if (empty($id)) {
            echo '<tr><td>';
            echo Security::remove_XSS($answerHTML, COURSEMANAGERLOWSECURITY);
            echo '</td></tr>';
        } else {
            echo '<tr><td>';
            echo Security::remove_XSS($answerHTML, COURSEMANAGERLOWSECURITY);
            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Shows the answer to a calculated question, as HTML.
     *
     *  @param Exercise $exercise
     * @param string    Answer text
     * @param int       Exercise ID
     * @param int       Question ID
     */
    public static function display_calculated_answer(
        $exercise,
        $feedback_type,
        $answer,
        $id,
        $questionId,
        $resultsDisabled,
        $showTotalScoreAndUserChoices,
        $expectedChoice = '',
        $choice = '',
        $status = ''
    ) {
        if ($exercise->showExpectedChoice()) {
            if (empty($id)) {
                echo '<tr><td>'.Security::remove_XSS($answer).'</td>';
                echo '<td>'.Security::remove_XSS($choice).'</td>';
                if ($exercise->showExpectedChoiceColumn()) {
                    echo '<td>'.Security::remove_XSS($expectedChoice).'</td>';
                }

                echo '<td>'.Security::remove_XSS($status).'</td>';
                echo '</tr>';
            } else {
                echo '<tr><td>';
                echo Security::remove_XSS($answer);
                echo '</td><td>';
                echo Security::remove_XSS($choice);
                echo '</td>';
                if ($exercise->showExpectedChoiceColumn()) {
                    echo '<td>';
                    echo Security::remove_XSS($expectedChoice);
                    echo '</td>';
                }
                echo '<td>';
                echo Security::remove_XSS($status);
                echo '</td>';
                echo '</tr>';
            }
        } else {
            if (empty($id)) {
                echo '<tr><td>'.Security::remove_XSS($answer).'</td></tr>';
            } else {
                echo '<tr><td>';
                echo Security::remove_XSS($answer);
                echo '</tr>';
            }
        }
    }

    /**
     * Shows the answer to a free-answer question, as HTML.
     *
     * @param string    Answer text
     * @param int       Exercise ID
     * @param int       Question ID
     */
    public static function display_free_answer(
        $feedback_type,
        $answer,
        $exe_id,
        $questionId,
        $questionScore = null,
        $resultsDisabled = 0
    ) {
        $comments = Event::get_comments($exe_id, $questionId);

        if (!empty($answer)) {
            echo '<tr><td>';
            echo Security::remove_XSS($answer);
            echo '</td></tr>';
        }

        if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedback_type) {
            if ($questionScore > 0 || !empty($comments)) {
            } else {
                echo '<tr>';
                echo Display::tag('td', ExerciseLib::getNotCorrectedYetText());
                echo '</tr>';
            }
        }
    }

    /**
     * @param $feedback_type
     * @param $answer
     * @param $id
     * @param $questionId
     * @param null $fileUrl
     * @param int  $resultsDisabled
     * @param int  $questionScore
     */
    public static function display_oral_expression_answer(
        $feedback_type,
        $answer,
        $id,
        $questionId,
        $fileUrl = null,
        $resultsDisabled = 0,
        $questionScore = 0
    ) {
        if (isset($fileUrl)) {
            echo '
                <tr>
                    <td><audio src="'.$fileUrl.'" controls></audio></td>
                </tr>
            ';
        }

        if (empty($id)) {
            echo '<tr>';
            if (!empty($answer)) {
                echo Display::tag('td', Security::remove_XSS($answer), ['width' => '55%']);
            }
            echo '</tr>';
            if (!$questionScore && EXERCISE_FEEDBACK_TYPE_EXAM != $feedback_type) {
                echo '<tr>';
                echo Display::tag('td', ExerciseLib::getNotCorrectedYetText(), ['width' => '45%']);
                echo '</tr>';
            } else {
                echo '<tr><td>&nbsp;</td></tr>';
            }
        } else {
            echo '<tr>';
            echo '<td>';
            if (!empty($answer)) {
                echo Security::remove_XSS($answer);
            }
            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Displays the answer to a hotspot question.
     *
     * @param int    $feedback_type
     * @param int    $answerId
     * @param string $answer
     * @param string $studentChoice
     * @param string $answerComment
     * @param int    $resultsDisabled
     * @param int    $orderColor
     * @param bool   $showTotalScoreAndUserChoices
     */
    public static function display_hotspot_answer(
        $exercise,
        $feedback_type,
        $answerId,
        $answer,
        $studentChoice,
        $answerComment,
        $resultsDisabled,
        $orderColor,
        $showTotalScoreAndUserChoices
    ) {
        $hide_expected_answer = false;
        switch ($resultsDisabled) {
            case RESULT_DISABLE_SHOW_SCORE_ONLY:
                if (0 == $feedback_type) {
                    $hide_expected_answer = true;
                }
                break;
            case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                $hide_expected_answer = true;
                if ($showTotalScoreAndUserChoices) {
                    $hide_expected_answer = false;
                }
                break;
        }

        if (!$hide_expected_answer
            && !$studentChoice
            && in_array($resultsDisabled, [RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER])
        ) {
            return;
        }

        $hotspotColors = [
            '', // $i starts from 1 on next loop (ugly fix)
            '#4271B5',
            '#FE8E16',
            '#45C7F0',
            '#BCD631',
            '#D63173',
            '#D7D7D7',
            '#90AFDD',
            '#AF8640',
            '#4F9242',
            '#F4EB24',
            '#ED2024',
            '#3B3B3B',
            '#F7BDE2',
        ];

        $content = '<tr>';
        $content .= '<td class="text-center" width="5%">';
        $content .= '<span class="fa fa-square fa-fw fa-2x" aria-hidden="true" style="color:'.
            $hotspotColors[$orderColor].'"></span>';
        $content .= '</td>';
        $content .= '<td class="text-left" width="25%">';
        $content .= "$answerId - $answer";
        $content .= '</td>';

        if (false === $exercise->hideComment) {
            $content .= '<td class="text-left" width="10%">';
            if (!$hide_expected_answer) {
                $status = Display::label(get_lang('Incorrect'), 'danger');
                if ($studentChoice) {
                    $status = Display::label(get_lang('Correct'), 'success');
                }
                $content .= $status;
            } else {
                $content .= '&nbsp;';
            }
            $content .= '</td>';

            if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedback_type) {
                $content .= '<td class="text-left" width="60%">';
                if ($studentChoice) {
                    $content .= '<span style="font-weight: bold; color: #008000;">'.nl2br($answerComment).'</span>';
                } else {
                    $content .= '&nbsp;';
                }
                $content .= '</td>';
            } else {
                $content .= '<td class="text-left" width="60%">&nbsp;</td>';
            }
        }

        $content .= '</tr>';

        echo $content;
    }

    /**
     * Display the answers to a multiple choice question.
     *
     * @param Exercise $exercise
     * @param int      $feedbackType                 Feedback type
     * @param int      $answerType                   Answer type
     * @param int      $studentChoice                Student choice
     * @param string   $answer                       Textual answer
     * @param string   $answerComment                Comment on answer
     * @param string   $answerCorrect                Correct answer comment
     * @param int      $id                           Exercise ID
     * @param int      $questionId                   Question ID
     * @param bool     $ans                          Whether to show the answer comment or not
     * @param bool     $resultsDisabled
     * @param bool     $showTotalScoreAndUserChoices
     * @param bool     $export
     */
    public static function display_unique_or_multiple_answer(
        $exercise,
        $feedbackType,
        $answerType,
        $studentChoice,
        $answer,
        $answerComment,
        $answerCorrect,
        $id,
        $questionId,
        $ans,
        $resultsDisabled,
        $showTotalScoreAndUserChoices,
        $export = false
    ) {
        if (true === $exercise->hideNoAnswer && empty($studentChoice)) {
            return '';
        }
        if ($export) {
            $answer = strip_tags_blacklist($answer, ['title', 'head']);
            // Fix answers that contains this tags
            $tags = [
                '<html>',
                '</html>',
                '<body>',
                '</body>',
            ];
            $answer = str_replace($tags, '', $answer);
        }

        $studentChoiceInt = (int) $studentChoice;
        $answerCorrectChoice = (int) $answerCorrect;

        $hide_expected_answer = false;
        $showComment = false;
        switch ($resultsDisabled) {
            case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
                $hide_expected_answer = true;
                $showComment = true;
                if (!$answerCorrect && empty($studentChoice)) {
                    return '';
                }
                break;
            case RESULT_DISABLE_SHOW_SCORE_ONLY:
                if (0 == $feedbackType) {
                    $hide_expected_answer = true;
                }
                break;
            case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                $hide_expected_answer = true;
                if ($showTotalScoreAndUserChoices) {
                    $hide_expected_answer = false;
                }
                break;
        }

        $icon = in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION]) ? 'radio' : 'checkbox';
        $icon .= $studentChoice ? '_on' : '_off';
        $icon .= '.png';
        $iconAnswer = in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION]) ? 'radio' : 'checkbox';
        $iconAnswer .= $answerCorrect ? '_on' : '_off';
        $iconAnswer .= '.png';

        $studentChoiceClass = '';
        if (in_array(
            $resultsDisabled,
            [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ]
        )
        ) {
            if ($answerCorrect) {
                $studentChoiceClass = 'success';
            }
        }

        echo '<tr class="'.$studentChoiceClass.'">';

        echo '<td width="5%">';
        echo Display::return_icon($icon, null, null, ICON_SIZE_TINY);
        echo '</td>';
        if ($exercise->showExpectedChoiceColumn()) {
            if (false === $hide_expected_answer) {
                echo '<td width="5%">';
                echo Display::return_icon($iconAnswer, null, null, ICON_SIZE_TINY);
                echo '</td>';
            } else {
                echo '<td width="5%">';
                echo '-';
                echo '</td>';
            }
        }

        echo '<td width="40%">';
        echo $answer;
        echo '</td>';

        if ($exercise->showExpectedChoice()) {
            $status = Display::label(get_lang('Incorrect'), 'danger');
            if ($answerCorrect || ($answerCorrect && $studentChoiceInt === $answerCorrectChoice)) {
                $status = Display::label(get_lang('Correct'), 'success');
            }
            echo '<td width="20%">';
            // Show only status for the selected student answer BT#16256
            if ($studentChoice) {
                echo $status;
            }

            echo '</td>';
        }

        if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedbackType) {
            $showComment = true;
        }

        if (false === $exercise->hideComment) {
            if ($showComment) {
                echo '<td width="20%">';
                $color = 'black';
                if ($answerCorrect) {
                    $color = 'green';
                }
                if ($hide_expected_answer) {
                    $color = '';
                }
                $comment = '<span style="font-weight: bold; color: '.$color.';">'.
                    Security::remove_XSS($answerComment).
                    '</span>';
                echo $comment;
                echo '</td>';
            } else {
                echo '<td>&nbsp;</td>';
            }
        }

        echo '</tr>';
    }

    /**
     * Display the answers to a multiple choice question.
     *
     * @param Exercise $exercise
     * @param int Answer type
     * @param int Student choice
     * @param string  Textual answer
     * @param string  Comment on answer
     * @param string  Correct answer comment
     * @param int Exercise ID
     * @param int Question ID
     * @param bool Whether to show the answer comment or not
     */
    public static function display_multiple_answer_true_false(
        $exercise,
        $feedbackType,
        $answerType,
        $studentChoice,
        $answer,
        $answerComment,
        $answerCorrect,
        $id,
        $questionId,
        $ans,
        $resultsDisabled,
        $showTotalScoreAndUserChoices
    ) {
        $hide_expected_answer = false;
        $hideStudentChoice = false;
        switch ($resultsDisabled) {
            //case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING:
            case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
                $hideStudentChoice = false;
                $hide_expected_answer = true;
                break;
            case RESULT_DISABLE_SHOW_SCORE_ONLY:
                if (0 == $feedbackType) {
                    $hide_expected_answer = true;
                }
                break;
            case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                $hide_expected_answer = true;
                if ($showTotalScoreAndUserChoices) {
                    $hide_expected_answer = false;
                }
                break;
        }

        $content = '<tr>';
        if (false === $hideStudentChoice) {
            $content .= '<td width="5%">';
            $course_id = api_get_course_int_id();
            $new_options = Question::readQuestionOption($questionId, $course_id);
            // Your choice
            if (isset($new_options[$studentChoice])) {
                $content .= get_lang($new_options[$studentChoice]['name']);
            } else {
                $content .= '-';
            }
            $content .= '</td>';
        }

        // Expected choice
        if ($exercise->showExpectedChoiceColumn()) {
            if (!$hide_expected_answer) {
                $content .= '<td width="5%">';
                if (isset($new_options[$answerCorrect])) {
                    $content .= get_lang($new_options[$answerCorrect]['name']);
                } else {
                    $content .= '-';
                }
                $content .= '</td>';
            }
        }

        $content .= '<td width="40%">';
        $content .= $answer;
        $content .= '</td>';

        if ($exercise->showExpectedChoice()) {
            $status = Display::label(get_lang('Incorrect'), 'danger');
            if (isset($new_options[$studentChoice])) {
                if ($studentChoice == $answerCorrect) {
                    $status = Display::label(get_lang('Correct'), 'success');
                }
            }
            $content .= '<td width="20%">';
            $content .= $status;
            $content .= '</td>';
        }

        if (false === $exercise->hideComment) {
            if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedbackType) {
                $content .= '<td width="20%">';
                $color = 'black';
                if (isset($new_options[$studentChoice]) || in_array(
                        $exercise->results_disabled,
                        [
                            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                            RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                        ]
                    )
                ) {
                    if ($studentChoice == $answerCorrect) {
                        $color = 'green';
                    }

                    if ($hide_expected_answer) {
                        $color = '';
                    }
                    $content .= '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
                }
                $content .= '</td>';
            }
        }
        $content .= '</tr>';

        echo $content;
    }

    /**
     * Display the answers to a multiple choice question.
     *
     * @param Exercise $exercise
     * @param int      $feedbackType
     * @param int      $studentChoice
     * @param int      $studentChoiceDegree
     * @param string   $answer
     * @param string   $answerComment
     * @param int      $answerCorrect
     * @param int      $questionId
     * @param bool     $inResultsDisabled
     */
    public static function displayMultipleAnswerTrueFalseDegreeCertainty(
        $exercise,
        $feedbackType,
        $studentChoice,
        $studentChoiceDegree,
        $answer,
        $answerComment,
        $answerCorrect,
        $questionId,
        $inResultsDisabled
    ) {
        $hideExpectedAnswer = false;
        if (0 == $feedbackType && 2 == $inResultsDisabled) {
            $hideExpectedAnswer = true;
        }

        echo '<tr><td width="5%">';
        $question = new MultipleAnswerTrueFalseDegreeCertainty();
        $courseId = api_get_course_int_id();
        $newOptions = Question::readQuestionOption($questionId, $courseId);

        // Your choice
        if (isset($newOptions[$studentChoice])) {
            echo get_lang($newOptions[$studentChoice]['name']);
        } else {
            echo '-';
        }
        echo '</td>';

        // Expected choice
        if ($exercise->showExpectedChoiceColumn()) {
            echo '<td width="5%">';
            if (!$hideExpectedAnswer) {
                if (isset($newOptions[$answerCorrect])) {
                    echo get_lang($newOptions[$answerCorrect]['name']);
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            echo '</td>';
        }

        echo '<td width="20%">';
        echo $answer;
        echo '</td><td width="5%" style="text-align:center;">';
        if (isset($newOptions[$studentChoiceDegree])) {
            echo $newOptions[$studentChoiceDegree]['name'];
        }
        echo '</td>';

        $position = isset($newOptions[$studentChoiceDegree]) ? $newOptions[$studentChoiceDegree]['position'] : '';
        $degreeInfo = $question->getResponseDegreeInfo(
            $studentChoice,
            $answerCorrect,
            $position
        );

        $degreeInfo['color'] = isset($degreeInfo['color']) ? $degreeInfo['color'] : '';
        $degreeInfo['background-color'] = isset($degreeInfo['background-color']) ? $degreeInfo['background-color'] : '';
        $degreeInfo['description'] = isset($degreeInfo['description']) ? $degreeInfo['description'] : '';
        $degreeInfo['label'] = isset($degreeInfo['label']) ? $degreeInfo['label'] : '';

        echo '
            <td width="15%">
                <div style="text-align:center;color: '.$degreeInfo['color'].';
                    background-color: '.$degreeInfo['background-color'].';
                    line-height:30px;height:30px;width: 100%;margin:auto;"
                    title="'.$degreeInfo['description'].'">'.
                    nl2br($degreeInfo['label']).
                '</div>
            </td>';

        if (false === $exercise->hideComment) {
            if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedbackType) {
                echo '<td width="20%">';
                if (isset($newOptions[$studentChoice])) {
                    echo '<span style="font-weight: bold; color: black;">'.nl2br($answerComment).'</span>';
                }
                echo '</td>';
            } else {
                echo '<td>&nbsp;</td>';
            }
        }

        echo '</tr>';
    }

    /**
     * Display the answers to a multiple choice question.
     *
     * @param Exercise $exercise
     * @param int Answer type
     * @param int Student choice
     * @param string  Textual answer
     * @param string  Comment on answer
     * @param string  Correct answer comment
     * @param int Exercise ID
     * @param int Question ID
     * @param bool Whether to show the answer comment or not
     */
    public static function display_multiple_answer_combination_true_false(
        $exercise,
        $feedbackType,
        $answerType,
        $studentChoice,
        $answer,
        $answerComment,
        $answerCorrect,
        $id,
        $questionId,
        $ans,
        $resultsDisabled,
        $showTotalScoreAndUserChoices
    ) {
        $hide_expected_answer = false;
        $hideStudentChoice = false;
        switch ($resultsDisabled) {
            case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING:
            case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
                $hideStudentChoice = true;
                $hide_expected_answer = true;
                break;
            case RESULT_DISABLE_SHOW_SCORE_ONLY:
                if (0 == $feedbackType) {
                    $hide_expected_answer = true;
                }
                break;
            case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                $hide_expected_answer = true;
                if ($showTotalScoreAndUserChoices) {
                    $hide_expected_answer = false;
                }
                break;
        }

        echo '<tr>';

        if (false === $hideStudentChoice) {
            echo '<td width="5%">';
            // Your choice
            $question = new MultipleAnswerCombinationTrueFalse();
            if (isset($question->options[$studentChoice])) {
                echo $question->options[$studentChoice];
            } else {
                echo $question->options[2];
            }
            echo '</td>';
        }

        // Expected choice
        if ($exercise->showExpectedChoiceColumn()) {
            if (!$hide_expected_answer) {
                echo '<td width="5%">';
                if (isset($question->options[$answerCorrect])) {
                    echo $question->options[$answerCorrect];
                } else {
                    echo $question->options[2];
                }
                echo '</td>';
            }
        }

        echo '<td width="40%">';
        echo $answer;
        echo '</td>';

        if ($exercise->showExpectedChoice()) {
            $status = '';
            if (isset($studentChoice)) {
                $status = Display::label(get_lang('Incorrect'), 'danger');
                if ($studentChoice == $answerCorrect) {
                    $status = Display::label(get_lang('Correct'), 'success');
                }
            }
            echo '<td width="20%">';
            echo $status;
            echo '</td>';
        }

        if (false === $exercise->hideComment) {
            if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedbackType) {
                echo '<td width="20%">';
                //@todo replace this harcoded value
                if ($studentChoice || in_array(
                        $resultsDisabled,
                        [
                            RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                            RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                        ]
                    )
                ) {
                    $color = 'black';
                    if ($studentChoice == $answerCorrect) {
                        $color = 'green';
                    }
                    if ($hide_expected_answer) {
                        $color = '';
                    }
                    echo '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
                }
                echo '</td>';
            } else {
                echo '<td>&nbsp;</td>';
            }
        }
        echo '</tr>';
    }

    /**
     * @param int  $feedbackType
     * @param int  $exeId
     * @param int  $questionId
     * @param null $questionScore
     * @param int  $resultsDisabled
     */
    public static function displayAnnotationAnswer(
        $feedbackType,
        $exeId,
        $questionId,
        $questionScore = null,
        $resultsDisabled = 0
    ) {
        $comments = Event::get_comments($exeId, $questionId);
        if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedbackType) {
            if ($questionScore <= 0 && empty($comments)) {
                echo '<br />'.ExerciseLib::getNotCorrectedYetText();
            }
        }
    }
}
