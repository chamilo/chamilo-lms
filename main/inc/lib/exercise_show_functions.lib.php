<?php

/* See license terms in /license.txt */

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
        $originalStudentAnswer,
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
        $answer = explode(':::', $answer);
        $answer = $answer[0];
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
     * Shows the answer to an upload question.
     *
     * @param float|null $questionScore   Only used to check if > 0
     * @param int        $resultsDisabled Unused
     */
    public static function displayUploadAnswer(
        string $feedbackType,
        string $answer,
        int $exeId,
        int $questionId,
        $questionScore = null,
        $resultsDisabled = 0
    ) {
        if (!empty($answer)) {
            $exeInfo = Event::get_exercise_results_by_attempt($exeId);
            if (empty($exeInfo)) {
                global $exercise_stat_info;
                $userId = $exercise_stat_info['exe_user_id'];
            } else {
                $userId = $exeInfo[$exeId]['exe_user_id'];
            }
            $userWebpath = UserManager::getUserPathById($userId, 'web').'my_files'.'/upload_answer/'.$exeId.'/'.$questionId.'/';
            $filesNames = explode('|', $answer);
            echo '<tr><td>';
            foreach ($filesNames as $filename) {
                $filename = Security::remove_XSS($filename);
                echo '<p><a href="'.$userWebpath.$filename.'" target="_blank">'.$filename.'</a></p>';
            }
            echo '</td></tr>';
        }

        if (EXERCISE_FEEDBACK_TYPE_EXAM != $feedbackType) {
            $comments = Event::get_comments($exeId, $questionId);
            if ($questionScore > 0 || !empty($comments)) {
            } else {
                echo '<tr>';
                echo Display::tag('td', ExerciseLib::getNotCorrectedYetText());
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
            if (!empty($answer) && ($answer != basename($fileUrl))) {
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
                    $content .= '<span style="font-weight: bold; color: #008000;">'.Security::remove_XSS(nl2br($answerComment)).'</span>';
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

    public static function displayMultipleAnswerDropdown(
        Exercise $exercise,
        Answer $answer,
        array $correctAnswers,
        array $studentChoices,
        bool $showTotalScoreAndUserChoices = true
    ): string {
        if (true === $exercise->hideNoAnswer && empty($studentChoices)) {
            return '';
        }

        $studentChoices = array_filter(
            $studentChoices,
            function ($studentAnswerId) {
                return -1 !== (int) $studentAnswerId;
            }
        );

        $allChoices = array_unique(
            array_merge($correctAnswers, $studentChoices)
        );
        sort($allChoices);

        $checkboxOn = Display::return_icon('checkbox_on.png', null, null, ICON_SIZE_TINY);
        $checkboxOff = Display::return_icon('checkbox_off.png', null, null, ICON_SIZE_TINY);

        $labelSuccess = Display::label(get_lang('Correct'), 'success');
        $labelIncorrect = Display::label(get_lang('Incorrect'), 'danger');

        $html = '';

        foreach ($allChoices as $choice) {
            $isStudentAnswer = in_array($choice, $studentChoices);
            $isExpectedAnswer = in_array($choice, $correctAnswers);
            $isCorrectAnswer = $isStudentAnswer && $isExpectedAnswer;
            $answerPosition = array_search($choice, $answer->iid);

            $hideExpectedAnswer = false;

            switch ($exercise->selectResultsDisabled()) {
                case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
                    $hideExpectedAnswer = true;

                    if (!$isCorrectAnswer && empty($studentChoices)) {
                        continue 2;
                    }
                    break;
                case RESULT_DISABLE_SHOW_SCORE_ONLY:
                    if (0 == $exercise->getFeedbackType()) {
                        $hideExpectedAnswer = true;
                    }
                    break;
                case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
                case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
                    $hideExpectedAnswer = true;
                    if ($showTotalScoreAndUserChoices) {
                        $hideExpectedAnswer = false;
                    }
                    break;
                case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
                    if (false === $showTotalScoreAndUserChoices && empty($studentChoices)) {
                        continue 2;
                    }
                    break;
            }

            $studentAnswerClass = '';

            if ($isCorrectAnswer
                && in_array(
                    $exercise->selectResultsDisabled(),
                    [
                        RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                        RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                    ]
                )
            ) {
                $studentAnswerClass = 'success';
            }

            $html .= '<tr class="'.$studentAnswerClass.'">';
            $html .= '<td class="text-center">'.($isStudentAnswer ? $checkboxOn : $checkboxOff).'</td>';

            if ($exercise->showExpectedChoiceColumn()) {
                $html .= '<td class="text-center">';

                if ($hideExpectedAnswer) {
                    $html .= '<span class="text-muted">&mdash;</span>';
                } else {
                    $html .= $isExpectedAnswer ? $checkboxOn : $checkboxOff;
                }

                $html .= '</td>';
            }

            $answerText = $answer->answer[$answerPosition] ?? get_lang('None');

            if ($exercise->export) {
                $answerText = strip_tags_blacklist($answerText, ['title', 'head']);
                // Fix answers that contains this tags
                $tags = ['<html>', '</html>', '<body>', '</body>'];
                $answerText = str_replace($tags, '', $answerText);
            }

            $html .= '<td>'.Security::remove_XSS($answerText).'</td>';

            if ($exercise->showExpectedChoice()) {
                $html .= '<td class="text-center">'.($isCorrectAnswer ? $labelSuccess : $labelIncorrect).'</td>';
            }

            $html .= '</tr>';
        }

        return $html;
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
        echo Security::remove_XSS($answer);
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
        $content .= Security::remove_XSS($answer);
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
                    $content .= '<span style="font-weight: bold; color: '.$color.';">'.Security::remove_XSS(nl2br($answerComment)).'</span>';
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
        echo Security::remove_XSS($answer);
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
        echo Security::remove_XSS($answer);
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
