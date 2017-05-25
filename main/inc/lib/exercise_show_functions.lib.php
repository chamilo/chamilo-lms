<?php
/* See license terms in /license.txt */
/**
* EVENTS LIBRARY
*
* This is the events library for Chamilo.
* Functions of this library are used to record informations when some kind
* of event occur. Each event has his own types of informations then each event
* use its own function.
*
* @package chamilo.library
* @todo convert queries to use Database API
*/
/**
 * Class
 * @package chamilo.library
 */
class ExerciseShowFunctions
{
    /**
     * Shows the answer to a fill-in-the-blanks question, as HTML
     * @param int $feedbackType
     * @param string    $answer
     * @param int $id       Exercise ID
     * @param int $questionId      Question ID
     * @param int $resultsDisabled
     * @param string $originalStudentAnswer
     * @param bool $showTotalScoreAndUserChoices
     *
     * @return void
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
        $answerHTML = FillBlanks::getHtmlDisplayForAnswer($answer, $feedbackType, $resultsDisabled, $showTotalScoreAndUserChoices);
        if (strpos($originalStudentAnswer, 'font color') !== false) {
            $answerHTML = $originalStudentAnswer;
        }

        if (empty($id)) {
            echo '<tr><td>';
            echo Security::remove_XSS($answerHTML, COURSEMANAGERLOWSECURITY);
            echo '</td></tr>';
        } else {
            ?>
            <tr>
                <td>
                    <?php echo nl2br(Security::remove_XSS($answerHTML, COURSEMANAGERLOWSECURITY)); ?>
                </td>

                <?php
                if (!api_is_allowed_to_edit(null, true) && $feedbackType != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
                    <td>
                        <?php
                        $comm = Event::get_comments($id, $questionId);
                        ?>
                    </td>
                <?php } ?>
            </tr>
        <?php
        }
    }

    /**
     * Shows the answer to a calculated question, as HTML
     * @param string    Answer text
     * @param int       Exercise ID
     * @param int       Question ID
     * @return void
     */
    public static function display_calculated_answer(
        $feedback_type,
        $answer,
        $id,
        $questionId,
        $results_disabled,
        $showTotalScoreAndUserChoices
    ) {
        if (empty($id)) {
            echo '<tr><td>'.Security::remove_XSS($answer).'</td></tr>';
        } else {
        ?>
            <tr>
                <td>
                    <?php
                    echo Security::remove_XSS($answer);
                    ?>
                </td>

            <?php
            if (!api_is_allowed_to_edit(null, true) && $feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
                <td>
                    <?php
                    $comm = Event::get_comments($id, $questionId);
                    ?>
                </td>
            <?php } ?>
            </tr>
        <?php
        }
    }

    /**
     * Shows the answer to a free-answer question, as HTML
     * @param string    Answer text
     * @param int       Exercise ID
     * @param int       Question ID
     * @return void
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
            echo nl2br(Security::remove_XSS($answer));
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
    * @param null $nano
    * @param int $results_disabled
     */
    public static function display_oral_expression_answer($feedback_type, $answer, $id, $questionId, $fileUrl = null, $results_disabled = 0)
    {
        if (isset($fileUrl)) {
            echo '
                <tr>
                    <td><audio src="' . $fileUrl.'" controls></audio></td>
                </tr>
            ';
        }

        if (empty($id)) {
            echo '<tr>';
            echo Display::tag('td', nl2br(Security::remove_XSS($answer)), array('width'=>'55%'));
            echo '</tr>';
            if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) {
                echo '<tr>';
                echo Display::tag('td', ExerciseLib::getNotCorrectedYetText(), array('width'=>'45%'));
                echo '</tr>';
            } else {
                echo '<tr><td>&nbsp;</td></tr>';
            }
        } else {
            echo '<tr>';
            echo '<td>';
            if (!empty($answer)) {
                echo nl2br(Security::remove_XSS($answer));
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
     * Displays the answer to a hotspot question
     * @param int $feedback_type
     * @param int $answerId
     * @param string $answer
     * @param string $studentChoice
     * @param string $answerComment
     * @param int $resultsDisabled
     * @param int $orderColor
     * @param bool $showTotalScoreAndUserChoices
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
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            } else {
                $hide_expected_answer = true;
            }
        }

        $hotspot_colors = array(
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
            "#F7BDE2"
        );

        ?>
        <table class="data_table">
        <tr>
            <td class="text-center" width="5%">
                <span class="fa fa-square fa-fw fa-2x" aria-hidden="true" style="color: <?php echo $hotspot_colors[$orderColor]; ?>"></span>
            </td>
            <td class="text-left" width="25%">
                <?php echo "$answerId - $answer"; ?>
            </td>
            <td class="text-left" width="10%">
                <?php
                if (!$hide_expected_answer) {
                    $my_choice = $studentChoice ? get_lang('Correct') : get_lang('Fault');
                    echo $my_choice;
                }
                ?>
            </td>
            <?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
            <td class="text-left" width="60%">
                <?php
                if ($studentChoice) {
                    echo '<span style="font-weight: bold; color: #008000;">'.nl2br($answerComment).'</span>';
                }
                ?>
            </td>
            <?php } else { ?>
                <td class="text-left" width="60%">&nbsp;</td>
            <?php } ?>
        </tr>
        <?php
    }

    /**
     * Display the answers to a multiple choice question
     * @param int $feedback_type Feedback type
     * @param int $answerType Answer type
     * @param int $studentChoice Student choice
     * @param string $answer Textual answer
     * @param string $answerComment Comment on answer
     * @param string $answerCorrect Correct answer comment
     * @param int $id Exercise ID
     * @param int $questionId Question ID
     * @param boolean $ans Whether to show the answer comment or not
     * @param bool $resultsDisabled
     * @param bool $showTotalScoreAndUserChoices
     *
     * @return void
     */
    public static function display_unique_or_multiple_answer(
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
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            } else {
                $hide_expected_answer = true;
            }
        }

        $icon = in_array($answerType, array(UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION)) ? 'radio' : 'checkbox';
        $icon .= $studentChoice ? '_on' : '_off';
        $icon .= '.gif';
        $iconAnswer = in_array($answerType, array(UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION)) ? 'radio' : 'checkbox';
        $iconAnswer .= $answerCorrect ? '_on' : '_off';
        $iconAnswer .= '.gif';

        ?>
        <tr>
        <td width="5%">
            <?php echo Display::return_icon($icon); ?>
        </td>
        <td width="5%">
            <?php if (!$hide_expected_answer) {
                echo Display::return_icon($iconAnswer);
            } else {
                echo "-";
            } ?>
        </td>
        <td width="40%">
            <?php
            echo $answer;
            ?>
        </td>

        <?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
        <td width="20%">
            <?php
            if ($studentChoice) {
                if ($answerCorrect) {
                    $color = 'green';
                    //echo '<span style="font-weight: bold; color: #008000;">'.nl2br($answerComment).'</span>';
                } else {
                    $color = 'black';
                    //echo '<span style="font-weight: bold; color: #FF0000;">'.nl2br($answerComment).'</span>';
                }
                if ($hide_expected_answer) {
                    $color = '';
                }
                echo '<span style="font-weight: bold; color: '.$color.';">'.nl2br($answerComment).'</span>';
            }
            ?>
        </td>
            <?php
            if ($ans == 1) {
                $comm = Event::get_comments($id, $questionId);
            }
            ?>
         <?php } else { ?>
            <td>&nbsp;</td>
        <?php } ?>
        </tr>
        <?php
    }

    /**
     * Display the answers to a multiple choice question
     *
     * @param integer Answer type
     * @param integer Student choice
     * @param string  Textual answer
     * @param string  Comment on answer
     * @param string  Correct answer comment
     * @param integer Exercise ID
     * @param integer Question ID
     * @param boolean Whether to show the answer comment or not
     * @return void
     */
    public static function display_multiple_answer_true_false(
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
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            } else {
                $hide_expected_answer = true;
            }
        }

        ?>
        <tr>
        <td width="5%">
        <?php
        $course_id   = api_get_course_int_id();
        $new_options = Question::readQuestionOption($questionId, $course_id);

        //Your choice
        if (isset($new_options[$studentChoice])) {
            echo get_lang($new_options[$studentChoice]['name']);
        } else {
            echo '-';
        }

        ?>
        </td>
        <td width="5%">
        <?php

        //Expected choice
        if (!$hide_expected_answer) {
            if (isset($new_options[$answerCorrect])) {
                echo get_lang($new_options[$answerCorrect]['name']);
            } else {
                echo '-';
            }
        } else {
            echo '-';
        }
        ?>
        </td>
        <td width="40%">
            <?php echo $answer; ?>
        </td>

        <?php if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
        <td width="20%">
            <?php
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
            ?>
        </td>
            <?php
            if ($ans == 1) {
                $comm = Event::get_comments($id, $questionId);
            }
            ?>
         <?php } else { ?>
            <td>&nbsp;</td>
        <?php } ?>
        </tr>
        <?php
    }

     /**
     * Display the answers to a multiple choice question
     *
     * @param integer Answer type
     * @param integer Student choice
     * @param string  Textual answer
     * @param string  Comment on answer
     * @param string  Correct answer comment
     * @param integer Exercise ID
     * @param integer Question ID
     * @param boolean Whether to show the answer comment or not
     * @return void
     */
    public static function display_multiple_answer_combination_true_false(
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
            if ($showTotalScoreAndUserChoices) {
                $hide_expected_answer = false;
            } else {
                $hide_expected_answer = true;
            }
        }

        ?>
        <tr>
        <td width="5%">
        <?php
        //Your choice
        $question = new MultipleAnswerCombinationTrueFalse();
        if (isset($question->options[$studentChoice])) {
            echo $question->options[$studentChoice];
        } else {
            echo $question->options[2];
        }
        ?>
        </td>
        <td width="5%">
        <?php
        //Expected choice
        if (!$hide_expected_answer) {
            if (isset($question->options[$answerCorrect])) {
                echo $question->options[$answerCorrect];
            } else {
                echo $question->options[2];
            }
        } else {
            echo '-';
        }
        ?>
        </td>
        <td width="40%">
            <?php
            //my answer
            echo $answer;
            ?>
        </td>
        <?php
        if ($feedback_type != EXERCISE_FEEDBACK_TYPE_EXAM) { ?>
        <td width="20%">
            <?php
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
            ?>
        </td>
            <?php
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
    * @param int $results_disabled
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
