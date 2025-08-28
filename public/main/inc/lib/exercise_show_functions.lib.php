<?php
/* See license terms in /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;

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
     * @param bool     $showTotalScoreAndUserChoices
     * @param string   $originalStudentAnswer
     */
    public static function display_fill_in_blanks_answer(
        $exercise,
        $feedbackType,
        $answer,
        $id,
        $questionId,
        $resultsDisabled,
        $showTotalScoreAndUserChoices,
        $originalStudentAnswer = ''
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
     * @param $trackExerciseId
     * @param $questionId
     * @param int $resultsDisabled
     * @param int $questionScore
     */
    public static function display_oral_expression_answer(
        $feedback_type,
        $answer,
        $trackExerciseId,
        $questionId,
        $resultsDisabled = 0,
        $questionScore = 0,
        $showAlertIfNotCorrected = false
    ) {
        /** @var TrackEExercise $trackExercise */
        $trackExercise = Container::getTrackEExerciseRepository()->find($trackExerciseId);

        if (null === $trackExerciseId) {
            return;
        }

        $questionAttempt = $trackExercise->getAttemptByQuestionId($questionId);

        if (null === $questionAttempt) {
            return;
        }

        $assetRepo = Container::getAssetRepository();

        foreach ($questionAttempt->getAttemptFiles() as $attemptFile) {
            echo Display::tag(
                'audio',
                '',
                [
                    'src' => $assetRepo->getAssetUrl($attemptFile->getAsset()),
                    'controls' => '',
                ]
            );
        }

        if (!empty($answer)) {
            echo Display::tag('p', Security::remove_XSS($answer));
        }

        $comment = Event::get_comments($trackExerciseId, $questionId);
        $teacherAudio = ExerciseLib::getOralFeedbackAudio(
                        $trackExerciseId,
                        $questionId
                    );

        if ($showAlertIfNotCorrected && !$questionScore && EXERCISE_FEEDBACK_TYPE_EXAM != $feedback_type && empty($comment) && empty($teacherAudio)) {
            echo Display::tag('p', ExerciseLib::getNotCorrectedYetText());
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
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
                $hide_expected_answer = true;
                if ($showTotalScoreAndUserChoices) {
                    $hide_expected_answer = false;
                }
                if (false === $showTotalScoreAndUserChoices && empty($studentChoice)) {
                    return '';
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
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
                if (false === $showTotalScoreAndUserChoices && empty($studentChoiceInt)) {
                    return '';
                }
                break;
        }

        if (in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION])) {
            if ($studentChoice) {
                $icon = StateIcon::RADIOBOX_MARKED;
            } else {
                $icon = StateIcon::RADIOBOX_BLANK;
            }
        } else {
            if ($studentChoice) {
                $icon = StateIcon::CHECKBOX_MARKED;
            } else {
                $icon = StateIcon::CHECKBOX_BLANK;
            }
        }
        if (in_array($answerType, [UNIQUE_ANSWER, UNIQUE_ANSWER_NO_OPTION])) {
            if ($answerCorrect) {
                $iconAnswer = StateIcon::RADIOBOX_MARKED;
            } else {
                $iconAnswer = StateIcon::RADIOBOX_BLANK;
            }
        } else {
            if ($answerCorrect) {
                $iconAnswer = StateIcon::CHECKBOX_MARKED;
            } else {
                $iconAnswer = StateIcon::CHECKBOX_BLANK;
            }

        }

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

        echo '<td style="width:5%">';
        echo Display::getMdiIcon($icon, 'ch-tool-icon', null, ICON_SIZE_TINY);
        echo '</td>';
        if ($exercise->showExpectedChoiceColumn()) {
            if (false === $hide_expected_answer) {
                echo '<td style="width:5%">';
                echo Display::getMdiIcon($iconAnswer, 'ch-tool-icon', null, ICON_SIZE_TINY);
                echo '</td>';
            } else {
                echo '<td style="width:5%">';
                echo '-';
                echo '</td>';
            }
        }

        echo '<td style="width:40%">';
        echo $answer;
        echo '</td>';

        if ($exercise->showExpectedChoice()) {
            $status = Display::label(get_lang('Incorrect'), 'danger');
            if ($answerCorrect || ($answerCorrect && $studentChoiceInt === $answerCorrectChoice)) {
                $status = Display::label(get_lang('Correct'), 'success');
            }
            echo '<td class="text-center">';
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
                echo '<td style="width:20%">';
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
     * @param int $feedbackType Feedback type
     * @param int $answerType Answer type
     * @param int $studentChoice Student choice
     * @param string  $answer Textual answer
     * @param string  $answerComment Comment on answer
     * @param string  $answerCorrect Correct answer comment
     * @param int $id Exercise ID
     * @param int $questionId Question ID
     * @param bool $ans Whether to show the answer comment or not
     * @param int $resultsDisabled
     * @param bool $showTotalScoreAndUserChoices
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
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
                if (false === $showTotalScoreAndUserChoices && empty($studentChoice)) {
                    return '';
                }
                break;
        }

        $content = '<tr>';
        if (false === $hideStudentChoice) {
            $content .= '<td width="5%">';
            $course_id = api_get_course_int_id();
            $new_options = [];
            $originOptions = Question::readQuestionOption($questionId);

            if (!empty($originOptions)) {
                foreach ($originOptions as $item) {
                    $new_options[$item['iid']] = $item;
                }
            }

            // Your choice
            if (isset($new_options[$studentChoice])) {
                $content .= get_lang($new_options[$studentChoice]['title']);
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
                    $content .= get_lang($new_options[$answerCorrect]['title']);
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
            $content .= '<td class="text-center">';
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
            case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
                if (false === $showTotalScoreAndUserChoices && empty($studentChoice)) {
                    return '';
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
            echo '<td class="text-center">';
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

    /**
     * Displays the answers for a Multiple Answer Dropdown question (result view).
     * Renders a row per choice, showing: student choice, expected choice (if allowed),
     * the textual answer, and status (Correct/Incorrect).
     *
     * Returned string contains <tr>...</tr> rows to be echoed in the answer table.
     */
    public static function displayMultipleAnswerDropdown(
        Exercise $exercise,
        Answer $answer,
        array $correctAnswers,
        array $studentChoices,
        bool $showTotalScoreAndUserChoices = true
    ): string {
        // Hide if teacher wants to hide empty answers and user gave no answer
        if (true === $exercise->hideNoAnswer && empty($studentChoices)) {
            return '';
        }

        // Normalize inputs
        $correctAnswers = array_map('intval', (array) $correctAnswers);
        $studentChoices = array_map(
            'intval',
            array_filter((array) $studentChoices, static fn ($v) => $v !== '' && $v !== null && (int)$v !== -1)
        );

        // Build id => text map from Answer::getAnswers()
        // getAnswers() typically returns rows with keys: iid, answer, correct, comment, weighting, position
        $idToText = [];
        if (method_exists($answer, 'getAnswers')) {
            $rows = $answer->getAnswers();
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row['iid'])) {
                        $id = (int) $row['iid'];
                        $idToText[$id] = $row['answer'] ?? '';
                    }
                }
            }
        }

        // Union of expected + student choices to render a single row per unique option
        $allChoices = array_values(array_unique(array_merge($correctAnswers, $studentChoices)));
        sort($allChoices);

        // Icons/labels
        $checkboxOn  = Display::getMdiIcon(StateIcon::CHECKBOX_MARKED, 'ch-tool-icon', null, ICON_SIZE_TINY);
        $checkboxOff = Display::getMdiIcon(StateIcon::CHECKBOX_BLANK,  'ch-tool-icon', null, ICON_SIZE_TINY);
        $labelOk     = Display::label(get_lang('Correct'), 'success');
        $labelKo     = Display::label(get_lang('Incorrect'), 'danger');

        $html = '';

        foreach ($allChoices as $choiceId) {
            $isStudentAnswer  = in_array($choiceId, $studentChoices, true);
            $isExpectedAnswer = in_array($choiceId, $correctAnswers, true);
            $isCorrectAnswer  = $isStudentAnswer && $isExpectedAnswer;

            // Resolve displayed text safely; fall back to "None" if not found
            $answerText = $idToText[$choiceId] ?? get_lang('None');

            if ($exercise->export) {
                // Strip potentially problematic wrappers on export
                $answerText = strip_tags_blacklist($answerText, ['title', 'head']);
                $answerText = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $answerText);
            }

            // Respect result-visibility policy
            $hideExpected = false;
            switch ($exercise->selectResultsDisabled()) {
                case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
                    $hideExpected = true;
                    if (!$isCorrectAnswer && empty($studentChoices)) {
                        continue 2;
                    }
                    break;
                case RESULT_DISABLE_SHOW_SCORE_ONLY:
                    if (0 == $exercise->getFeedbackType()) {
                        $hideExpected = true;
                    }
                    break;
                case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
                case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                    $hideExpected = true;
                    if ($showTotalScoreAndUserChoices) {
                        $hideExpected = false;
                    }
                    break;
                case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
                    if (false === $showTotalScoreAndUserChoices && empty($studentChoices)) {
                        continue 2;
                    }
                    break;
            }

            // Highlight only when policy requires and the student/expected match
            $rowClass = '';
            if ($isCorrectAnswer
                && in_array(
                    $exercise->selectResultsDisabled(),
                    [RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER, RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING],
                    true
                )
            ) {
                $rowClass = 'success';
            }

            $html .= '<tr class="'.$rowClass.'">';

            // Student choice icon
            $html .= '<td class="text-center">'.($isStudentAnswer ? $checkboxOn : $checkboxOff).'</td>';

            // Expected choice icon (optional)
            if ($exercise->showExpectedChoiceColumn()) {
                $html .= '<td class="text-center">';
                $html .= $hideExpected ? '<span class="text-muted">&mdash;</span>' : ($isExpectedAnswer ? $checkboxOn : $checkboxOff);
                $html .= '</td>';
            }

            // Answer text
            $html .= '<td>'.Security::remove_XSS($answerText).'</td>';

            // Status (optional)
            if ($exercise->showExpectedChoice()) {
                $html .= '<td class="text-center">'.($isCorrectAnswer ? $labelOk : $labelKo).'</td>';
            }

            $html .= '</tr>';
        }

        return $html;
    }
}
