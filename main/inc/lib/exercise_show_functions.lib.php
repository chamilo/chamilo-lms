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
 * @package chamilo.library
 *
 * @todo convert queries to use Database API
 */
/**
 * Class.
 *
 * @package chamilo.library
 */
class ExerciseShowFunctions
{
    /**
     * Shows the answer to a fill-in-the-blanks question, as HTML.
     *
     * @param int    $feedbackType
     * @param string $answer
     * @param int    $id                           Exercise ID
     * @param int    $questionId                   Question ID
     * @param int    $resultsDisabled
     * @param string $originalStudentAnswer
     * @param bool   $showTotalScoreAndUserChoices
     */
    public static function display_fill_in_blanks_answer(
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
        // ofaj
        /*if (strpos($originalStudentAnswer, 'font color') !== false) {
            $answerHTML = $originalStudentAnswer;
        }*/
        if (empty($id)) {
            echo '<tr><td>';
            echo Security::remove_XSS($answerHTML, COURSEMANAGERLOWSECURITY);
            echo '</td></tr>';
        } else {
            echo '<tr><td>';
            echo Security::remove_XSS($answerHTML, COURSEMANAGERLOWSECURITY);
            echo '</td>';
            if (!api_is_allowed_to_edit(null, true) && $feedbackType != EXERCISE_FEEDBACK_TYPE_EXAM) {
                echo '<td>';
                $comm = Event::get_comments($id, $questionId);
                echo '</td>';
            }
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
        $results_disabled,
        $showTotalScoreAndUserChoices,
        $expectedChoice = '',
        $choice = '',
        $status = ''
    ) {
        if ($exercise->showExpectedChoice()) {
            if (empty($id)) {
                echo '<tr><td>'.Security::remove_XSS($answer).'</td>';
                echo '<td>'.Security::remove_XSS($choice).'</td>';
                echo '<td>'.Security::remove_XSS($expectedChoice).'</td>';
                echo '<td>'.Security::remove_XSS($status).'</td>';
                echo '</tr>';
            } else {
                echo '<tr><td>';
                echo Security::remove_XSS($answer);
                echo '</td><td>';
                echo Security::remove_XSS($choice);
                echo '</td><td>';
                echo Security::remove_XSS($expectedChoice);
                echo '</td><td>';
                echo Security::remove_XSS($status);
                echo '</td>';
                if (!api_is_allowed_to_edit(null, true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                    echo '<td>';
                    $comm = Event::get_comments($id, $questionId);
                    echo '</td>';
                }
                echo '</tr>';
            }
        } else {
            if (empty($id)) {
                echo '<tr><td>'.Security::remove_XSS($answer).'</td></tr>';
            } else {
                echo '<tr><td>';
                echo Security::remove_XSS($answer);
                if (!api_is_allowed_to_edit(null, true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                    echo '<td>';
                    $comm = Event::get_comments($id, $questionId);
                    echo '</td>';
                }
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
        $results_disabled = 0
    ) {
        $comments = Event::get_comments($exe_id, $questionId);

        if (!empty($answer)) {
            echo '<tr><td>';
            echo Security::remove_XSS($answer);
            echo '</td></tr>';
        }

        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            if ($questionScore > 0 || !empty($comments)) {
            } else {
                echo '<tr>';
                echo Display::tag('td', ExerciseLib::getNotCorrectedYetText(), []);
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
     * @param int  $results_disabled
     * @param int  $questionScore
     */
    public static function display_oral_expression_answer(
        $feedback_type,
        $answer,
        $id,
        $questionId,
        $fileUrl = null,
        $results_disabled = 0,
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
            echo Display::tag('td', Security::remove_XSS($answer), ['width' => '55%']);
            echo '</tr>';
            if (!$questionScore && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
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

            if (!api_is_allowed_to_edit(null, true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                echo '<td>';
                $comm = Event::get_comments($id, $questionId);
                echo '</td>';
            }
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
        if ($feedback_type == 0 && $resultsDisabled == 2) {
            $hide_expected_answer = true;
        }

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            $hide_expected_answer = true;
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            }
        }

        $hotspot_colors = [
            "", // $i starts from 1 on next loop (ugly fix)
            "#4271B5",
            "#FE8E16",
            "#45C7F0",
            "#BCD631",
            "#D63173",
            "#D7D7D7",
            "#90AFDD",
            "#AF8640",
            "#4F9242",
            "#F4EB24",
            "#ED2024",
            "#3B3B3B",
            "#F7BDE2",
        ];
        echo '<table class="data_table"><tr>';
        echo '<td class="text-center" width="5%">';
        echo '<span class="fa fa-square fa-fw fa-2x" aria-hidden="true" style="color:'.
            $hotspot_colors[$orderColor].'"></span>';
        echo '</td>';
        echo '<td class="text-left" width="25%">';
        echo "$answerId - $answer";
        echo '</td>';
        echo '<td class="text-left" width="10%">';
        if (!$hide_expected_answer) {
            $status = Display::label(get_lang('Incorrect'), 'danger');
            if ($studentChoice) {
                $status = Display::label(get_lang('Correct'), 'success');
            }
            echo $status;
        }
        echo '</td>';
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            echo '<td class="text-left" width="60%">';
            if ($studentChoice) {
                echo '<span style="font-weight: bold; color: #008000;">'.nl2br($answerComment).'</span>';
            }
            echo '</td>';
        } else {
            echo '<td class="text-left" width="60%">&nbsp;</td>';
        }
        echo '</tr>';
    }

    /**
     * Display the answers to a multiple choice question.
     *
     * @param Exercise $exercise
     * @param int      $feedback_type                Feedback type
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
        $feedback_type,
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

        $hide_expected_answer = false;
        if ($feedback_type == 0 && ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ONLY)) {
            $hide_expected_answer = true;
        }

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            $hide_expected_answer = true;
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            }
        }

        $icon = in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION]) ? 'radio' : 'checkbox';
        $icon .= $studentChoice ? '_on' : '_off';
        $icon .= '.png';
        $iconAnswer = in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION]) ? 'radio' : 'checkbox';
        $iconAnswer .= $answerCorrect ? '_on' : '_off';
        $iconAnswer .= '.png';

        echo '<tr>';
        echo '<td width="5%">';
        echo Display::return_icon($icon, null, null, ICON_SIZE_TINY);
        echo '</td><td width="5%">';
        if (!$hide_expected_answer) {
            echo Display::return_icon($iconAnswer, null, null, ICON_SIZE_TINY);
        } else {
            echo "-";
        }
        echo '</td><td width="40%">';
        echo $answer;
        echo '</td>';

        if ($exercise->showExpectedChoice()) {
            $status = Display::label(get_lang('Incorrect'), 'danger');
            if ($studentChoice) {
                if ($answerCorrect) {
                    $status = Display::label(get_lang('Correct'), 'success');
                }
            }
            echo '<td width="20%">';
            echo $status;
            echo '</td>';
        }

        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            echo '<td width="20%">';
            if ($studentChoice) {
                $color = 'black';
                if ($answerCorrect) {
                    $color = 'green';
                }
                if ($hide_expected_answer) {
                    $color = '';
                }
                echo '<span style="font-weight: bold; color: '.$color.';">'.
                    Security::remove_XSS($answerComment).'</span>';
            }
            echo '</td>';
            if ($ans == 1) {
                $comm = Event::get_comments($id, $questionId);
            }
        } else {
            echo '<td>&nbsp;</td>';
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
        $feedback_type,
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
        if ($feedback_type == 0 && ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ONLY)) {
            $hide_expected_answer = true;
        }

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            $hide_expected_answer = true;
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            }
        }
        echo '<tr><td width="5%">';
        $course_id = api_get_course_int_id();
        $new_options = Question::readQuestionOption($questionId, $course_id);
        // Your choice
        if (isset($new_options[$studentChoice])) {
            echo get_lang($new_options[$studentChoice]['name']);
        } else {
            echo '-';
        }
        echo '</td><td width="5%">';
        // Expected choice
        if (!$hide_expected_answer) {
            if (isset($new_options[$answerCorrect])) {
                echo get_lang($new_options[$answerCorrect]['name']);
            } else {
                echo '-';
            }
        } else {
            echo '-';
        }
        echo '</td><td width="40%">';
        echo $answer;
        echo '</td>';
        if ($exercise->showExpectedChoice()) {
            $status = Display::label(get_lang('Incorrect'), 'danger');
            if (isset($new_options[$studentChoice])) {
                if ($studentChoice == $answerCorrect) {
                    $status = Display::label(get_lang('Correct'), 'success');
                }
            }
            echo '<td width="20%">';
            echo $status;
            echo '</td>';
        }
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            echo '<td width="20%">';
            $color = "black";
            if (isset($new_options[$studentChoice])) {
                if ($studentChoice == $answerCorrect) {
                    $color = "green";
                }

                if ($hide_expected_answer) {
                    $color = '';
                }
                echo '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
            }
            echo '</td>';
            if ($ans == 1) {
                $comm = Event::get_comments($id, $questionId);
            }
        } else {
            echo '<td>&nbsp;</td>';
        }
        echo '</tr>';
    }

    /**
     * Display the answers to a multiple choice question.
     *
     * @param int    $feedbackType
     * @param int    $studentChoice
     * @param int    $studentChoiceDegree
     * @param string $answer
     * @param string $answerComment
     * @param int    $answerCorrect
     * @param int    $questionId
     * @param bool   $inResultsDisabled
     */
    public static function displayMultipleAnswerTrueFalseDegreeCertainty(
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
        if ($feedbackType == 0 && $inResultsDisabled == 2) {
            $hideExpectedAnswer = true;
        }

        echo '<tr><td width="5%">';
        $question = new MultipleAnswerTrueFalseDegreeCertainty();
        $courseId = api_get_course_int_id();
        $newOptions = Question::readQuestionOption($questionId, $courseId);

        //Your choice
        if (isset($newOptions[$studentChoice])) {
            echo get_lang($newOptions[$studentChoice]['name']);
        } else {
            echo '-';
        }
        echo '</td><td width="5%">';

        // Expected choice
        if (!$hideExpectedAnswer) {
            if (isset($newOptions[$answerCorrect])) {
                echo get_lang($newOptions[$answerCorrect]['name']);
            } else {
                echo '-';
            }
        } else {
            echo '-';
        }

        echo '</td><td width="20%">';
        echo $answer;
        echo '</td><td width="5%" style="text-align:center;">';
        echo $newOptions[$studentChoiceDegree]['name'];
        echo '</td>';

        $degreeInfo = $question->getResponseDegreeInfo(
            $studentChoice,
            $answerCorrect,
            $newOptions[$studentChoiceDegree]['position']
        );

        echo '
            <td width="15%">
                <div style="text-align:center;color: '.$degreeInfo['color'].';
                    background-color: '.$degreeInfo['background-color'].';
                    line-height:30px;height:30px;width: 100%;margin:auto;"
                    title="'.$degreeInfo['description'].'">'.
                    nl2br($degreeInfo['label']).
                '</div>
            </td>';

        if ($feedbackType != EXERCISE_FEEDBACK_TYPE_EXAM) {
            echo '<td width="20%">';
            if (isset($newOptions[$studentChoice])) {
                echo '<span style="font-weight: bold; color: black;">'.nl2br($answerComment).'</span>';
            }
            echo '</td>';
        } else {
            echo '<td>&nbsp;</td>';
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
        $feedback_type,
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
        if ($feedback_type == 0 && ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ONLY)) {
            $hide_expected_answer = true;
        }

        if ($resultsDisabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            $hide_expected_answer = true;
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            }
        }

        echo '<tr><td width="5%">';
        // Your choice
        $question = new MultipleAnswerCombinationTrueFalse();
        if (isset($question->options[$studentChoice])) {
            echo $question->options[$studentChoice];
        } else {
            echo $question->options[2];
        }
        echo '</td><td width="5%">';
        // Expected choice
        if (!$hide_expected_answer) {
            if (isset($question->options[$answerCorrect])) {
                echo $question->options[$answerCorrect];
            } else {
                echo $question->options[2];
            }
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td width="40%">';
        // my answer
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

        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            echo '<td width="20%">';
            //@todo replace this harcoded value
            if ($studentChoice) {
                $color = "black";
                if ($studentChoice == $answerCorrect) {
                    $color = "green";
                }
                if ($hide_expected_answer) {
                    $color = '';
                }
                echo '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
            }
            echo '</td>';
            if ($ans == 1) {
                $comm = Event::get_comments($id, $questionId);
            }
        } else {
            echo '<td>&nbsp;</td>';
        }
        echo '</tr>';
    }

    /**
     * @param $feedback_type
     * @param $exe_id
     * @param $questionId
     * @param null $questionScore
     * @param int  $results_disabled
     */
    public static function displayAnnotationAnswer(
        $feedback_type,
        $exe_id,
        $questionId,
        $questionScore = null,
        $results_disabled = 0
    ) {
        $comments = Event::get_comments($exe_id, $questionId);
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
            if ($questionScore <= 0 && empty($comments)) {
                echo '<br />'.ExerciseLib::getNotCorrectedYetText();
            }
        }
    }
}
