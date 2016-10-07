<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class ExerciseLib
 * shows a question and its answers
 * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
 * @author Hubert Borderiou 2011-10-21
 * @author ivantcholakov2009-07-20
 *
 */
class ExerciseLib
{
    /**
     * Shows a question
     *
     * @param int $questionId $questionId question id
     * @param bool $only_questions if true only show the questions, no exercise title
     * @param bool $origin i.e = learnpath
     * @param string $current_item current item from the list of questions
     * @param bool $show_title
     * @param bool $freeze
     * @param array $user_choice
     * @param bool $show_comment
     * @param null $exercise_feedback
     * @param bool $show_answers
     * @return bool|int
     */
    public static function showQuestion(
        $questionId,
        $only_questions = false,
        $origin = false,
        $current_item = '',
        $show_title = true,
        $freeze = false,
        $user_choice = array(),
        $show_comment = false,
        $exercise_feedback = null,
        $show_answers = false
    ) {
        $course_id = api_get_course_int_id();
        // Change false to true in the following line to enable answer hinting
        $debug_mark_answer = $show_answers;

        // Reads question information
        if (!$objQuestionTmp = Question::read($questionId)) {
            // Question not found
            return false;
        }

        if ($exercise_feedback != EXERCISE_FEEDBACK_TYPE_END) {
            $show_comment = false;
        }

        $answerType = $objQuestionTmp->selectType();
        $pictureName = $objQuestionTmp->selectPicture();
        $s = '';

        if ($answerType != HOT_SPOT && $answerType != HOT_SPOT_DELINEATION) {
            // Question is not a hotspot

            if (!$only_questions) {
                $questionDescription = $objQuestionTmp->selectDescription();
                if ($show_title) {
                    TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    echo Display::div(
                        $current_item . '. ' . $objQuestionTmp->selectTitle(),
                        array('class' => 'question_title')
                    );
                }
                if (!empty($questionDescription)) {
                    echo Display::div(
                        $questionDescription,
                        array('class' => 'question_description')
                    );
                }
            }

            if (in_array($answerType, array(FREE_ANSWER, ORAL_EXPRESSION)) &&
                $freeze
            ) {
                return '';
            }

            echo '<div class="question_options">';

            // construction of the Answer object (also gets all answers details)
            $objAnswerTmp = new Answer($questionId);
            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

            $quiz_question_options = Question::readQuestionOption(
                $questionId,
                $course_id
            );

            // For "matching" type here, we need something a little bit special
            // because the match between the suggestions and the answers cannot be
            // done easily (suggestions and answers are in the same table), so we
            // have to go through answers first (elems with "correct" value to 0).
            $select_items = array();
            //This will contain the number of answers on the left side. We call them
            // suggestions here, for the sake of comprehensions, while the ones
            // on the right side are called answers
            $num_suggestions = 0;
            if (in_array($answerType, [MATCHING, DRAGGABLE, MATCHING_DRAGGABLE])) {
                if ($answerType == DRAGGABLE) {
                    $s .= '<div class="col-md-12 ui-widget ui-helper-clearfix">
                        <div class="clearfix">
                        <ul class="exercise-draggable-answer ui-helper-reset ui-helper-clearfix list-inline">';
                } else {
                    $s .= '<div id="drag' . $questionId . '_question" class="drag_question">
                           <table class="data_table">';
                }

                // Iterate through answers
                $x = 1;
                //mark letters for each answer
                $letter = 'A';
                $answer_matching = array();
                $cpt1 = array();
                for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                    $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                    $numAnswer = $objAnswerTmp->selectAutoId($answerId);
                    if ($answerCorrect == 0) {
                        // options (A, B, C, ...) that will be put into the list-box
                        // have the "correct" field set to 0 because they are answer
                        $cpt1[$x] = $letter;
                        $answer_matching[$x] = $objAnswerTmp->selectAnswerByAutoId(
                            $numAnswer
                        );
                        $x++;
                        $letter++;
                    }
                }

                $i = 1;

                $select_items[0]['id'] = 0;
                $select_items[0]['letter'] = '--';
                $select_items[0]['answer'] = '';
                foreach ($answer_matching as $id => $value) {
                    $select_items[$i]['id'] = $value['id_auto'];
                    $select_items[$i]['letter'] = $cpt1[$id];
                    $select_items[$i]['answer'] = $value['answer'];
                    $i++;
                }

                $user_choice_array_position = array();
                if (!empty($user_choice)) {
                    foreach ($user_choice as $item) {
                        $user_choice_array_position[$item['position']] = $item['answer'];
                    }
                }
                $num_suggestions = ($nbrAnswers - $x) + 1;
            } elseif ($answerType == FREE_ANSWER) {
                $fck_content = isset($user_choice[0]) && !empty($user_choice[0]['answer']) ? $user_choice[0]['answer'] : null;

                $form = new FormValidator('free_choice_' . $questionId);
                $config = array(
                    'ToolbarSet' => 'TestFreeAnswer'
                );
                $form->addHtmlEditor("choice[" . $questionId . "]", null, false, false, $config);
                $form->setDefaults(array("choice[" . $questionId . "]" => $fck_content));
                $s .= $form->returnForm();
            } elseif ($answerType == ORAL_EXPRESSION) {
                // Add nanog
                if (api_get_setting('enable_record_audio') == 'true') {
                    //@todo pass this as a parameter
                    global $exercise_stat_info, $exerciseId, $exe_id;

                    if (!empty($exercise_stat_info)) {
                        $objQuestionTmp->initFile(
                            api_get_session_id(),
                            api_get_user_id(),
                            $exercise_stat_info['exe_exo_id'],
                            $exercise_stat_info['exe_id']
                        );
                    } else {
                        $objQuestionTmp->initFile(
                            api_get_session_id(),
                            api_get_user_id(),
                            $exerciseId,
                            'temp_exe'
                        );
                    }

                    echo $objQuestionTmp->returnRecorder();
                }

                $form = new FormValidator('free_choice_' . $questionId);
                $config = array(
                    'ToolbarSet' => 'TestFreeAnswer'
                );
                $form->addHtmlEditor("choice[" . $questionId . "]", null, false, false, $config);
                //$form->setDefaults(array("choice[" . $questionId . "]" => $fck_content));
                $s .= $form->returnForm();
            }

            // Now navigate through the possible answers, using the max number of
            // answers for the question as a limiter
            $lines_count = 1; // a counter for matching-type answers

            if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE ||
                $answerType == MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE
            ) {
                $header = Display::tag('th', get_lang('Options'));
                foreach ($objQuestionTmp->options as $item) {
                    if ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {
                        if (in_array($item, $objQuestionTmp->options)) {
                            $header .= Display::tag('th', get_lang($item));
                        } else {
                            $header .= Display::tag('th', $item);
                        }
                    } else {
                        $header .= Display::tag('th', $item);
                    }

                }
                if ($show_comment) {
                    $header .= Display::tag('th', get_lang('Feedback'));
                }
                $s .= '<table class="table table-hover table-striped">';
                $s .= Display::tag(
                    'tr',
                    $header,
                    array('style' => 'text-align:left;')
                );
            }

            if ($show_comment) {
                if (
                in_array(
                    $answerType,
                    array(
                        MULTIPLE_ANSWER,
                        MULTIPLE_ANSWER_COMBINATION,
                        UNIQUE_ANSWER,
                        UNIQUE_ANSWER_IMAGE,
                        UNIQUE_ANSWER_NO_OPTION,
                        GLOBAL_MULTIPLE_ANSWER
                    )
                )
                ) {
                    $header = Display::tag('th', get_lang('Options'));
                    if ($exercise_feedback == EXERCISE_FEEDBACK_TYPE_END) {
                        $header .= Display::tag('th', get_lang('Feedback'));
                    }
                    $s .= '<table class="table table-hover table-striped">';
                    $s .= Display::tag(
                        'tr',
                        $header,
                        array('style' => 'text-align:left;')
                    );
                }
            }

            $matching_correct_answer = 0;
            $user_choice_array = array();
            if (!empty($user_choice)) {
                foreach ($user_choice as $item) {
                    $user_choice_array[] = $item['answer'];
                }
            }

            for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                $answer = $objAnswerTmp->selectAnswer($answerId);
                $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                $numAnswer = $objAnswerTmp->selectAutoId($answerId);
                $comment = $objAnswerTmp->selectComment($answerId);

                $attributes = array();

                switch ($answerType) {
                    case UNIQUE_ANSWER:
                        //no break
                    case UNIQUE_ANSWER_NO_OPTION:
                        //no break
                    case UNIQUE_ANSWER_IMAGE:
                        $input_id = 'choice-' . $questionId . '-' . $answerId;

                        if (isset($user_choice[0]['answer']) && $user_choice[0]['answer'] == $numAnswer) {
                            $attributes = array(
                                'id' => $input_id,
                                'checked' => 1,
                                'selected' => 1
                            );
                        } else {
                            $attributes = array('id' => $input_id);
                        }

                        if ($debug_mark_answer) {
                            if ($answerCorrect) {
                                $attributes['checked'] = 1;
                                $attributes['selected'] = 1;
                            }
                        }

                        if ($show_comment) {
                            $s .= '<tr><td>';
                        }

                        if ($answerType == UNIQUE_ANSWER_IMAGE) {
                            if ($show_comment) {
                                if (empty($comment)) {
                                    $s .= '<div id="answer' . $questionId . $numAnswer . '" '
                                        . 'class="exercise-unique-answer-image" style="text-align: center">';
                                } else {
                                    $s .= '<div id="answer' . $questionId . $numAnswer . '" '
                                        . 'class="exercise-unique-answer-image col-xs-6 col-sm-12" style="text-align: center">';
                                }
                            } else {
                                $s .= '<div id="answer' . $questionId . $numAnswer . '" '
                                    . 'class="exercise-unique-answer-image col-xs-6 col-md-3" style="text-align: center">';
                            }
                        }

                        $answer = Security::remove_XSS($answer, STUDENT);

                        $s .= Display::input(
                            'hidden',
                            'choice2[' . $questionId . ']',
                            '0'
                        );

                        $answer_input = null;

                        if ($answerType == UNIQUE_ANSWER_IMAGE) {
                            $attributes['style'] = 'display: none;';
                            $answer = '<div class="thumbnail">' . $answer . '</div>';
                        }

                        $answer_input .= '<label class="radio">';
                        $answer_input .= Display::input(
                            'radio',
                            'choice[' . $questionId . ']',
                            $numAnswer,
                            $attributes
                        );
                        $answer_input .= $answer;
                        $answer_input .= '</label>';

                        if ($answerType == UNIQUE_ANSWER_IMAGE) {
                            $answer_input .= "</div>";
                        }

                        if ($show_comment) {
                            $s .= $answer_input;
                            $s .= '</td>';
                            $s .= '<td>';
                            $s .= $comment;
                            $s .= '</td>';
                            $s .= '</tr>';
                        } else {
                            $s .= $answer_input;
                        }
                        break;
                    case MULTIPLE_ANSWER:
                        //no break
                    case MULTIPLE_ANSWER_TRUE_FALSE:
                        //no break
                    case GLOBAL_MULTIPLE_ANSWER:
                        $input_id = 'choice-' . $questionId . '-' . $answerId;
                        $answer = Security::remove_XSS($answer, STUDENT);

                        if (in_array($numAnswer, $user_choice_array)) {
                            $attributes = array(
                                'id' => $input_id,
                                'checked' => 1,
                                'selected' => 1
                            );
                        } else {
                            $attributes = array('id' => $input_id);
                        }

                        if ($debug_mark_answer) {
                            if ($answerCorrect) {
                                $attributes['checked'] = 1;
                                $attributes['selected'] = 1;
                            }
                        }

                        if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                            $s .= '<input type="hidden" name="choice2[' . $questionId . ']" value="0" />';

                            $answer_input = '<label class="checkbox">';
                            $answer_input .= Display::input(
                                'checkbox',
                                'choice[' . $questionId . '][' . $numAnswer . ']',
                                $numAnswer,
                                $attributes
                            );
                            $answer_input .= $answer;
                            $answer_input .= '</label>';

                            if ($show_comment) {
                                $s .= '<tr><td>';
                                $s .= $answer_input;
                                $s .= '</td>';
                                $s .= '<td>';
                                $s .= $comment;
                                $s .= '</td>';
                                $s .= '</tr>';
                            } else {
                                $s .= $answer_input;
                            }
                        } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE) {

                            $my_choice = array();
                            if (!empty($user_choice_array)) {
                                foreach ($user_choice_array as $item) {
                                    $item = explode(':', $item);
                                    $my_choice[$item[0]] = $item[1];
                                }
                            }

                            $s .= '<tr>';
                            $s .= Display::tag('td', $answer);

                            if (!empty($quiz_question_options)) {
                                foreach ($quiz_question_options as $id => $item) {

                                    if (isset($my_choice[$numAnswer]) && $id == $my_choice[$numAnswer]) {
                                        $attributes = array(
                                            'checked' => 1,
                                            'selected' => 1
                                        );
                                    } else {
                                        $attributes = array();
                                    }

                                    if ($debug_mark_answer) {
                                        if ($id == $answerCorrect) {
                                            $attributes['checked'] = 1;
                                            $attributes['selected'] = 1;
                                        }
                                    }
                                    $s .= Display::tag(
                                        'td',
                                        Display::input(
                                            'radio',
                                            'choice[' . $questionId . '][' . $numAnswer . ']',
                                            $id,
                                            $attributes
                                        ),
                                        array('style' => '')
                                    );
                                }
                            }

                            if ($show_comment) {
                                $s .= '<td>';
                                $s .= $comment;
                                $s .= '</td>';
                            }
                            $s .= '</tr>';
                        }
                        break;
                    case MULTIPLE_ANSWER_COMBINATION:
                        // multiple answers
                        $input_id = 'choice-' . $questionId . '-' . $answerId;

                        if (in_array($numAnswer, $user_choice_array)) {
                            $attributes = array(
                                'id' => $input_id,
                                'checked' => 1,
                                'selected' => 1
                            );
                        } else {
                            $attributes = array('id' => $input_id);
                        }

                        if ($debug_mark_answer) {
                            if ($answerCorrect) {
                                $attributes['checked'] = 1;
                                $attributes['selected'] = 1;
                            }
                        }

                        $answer = Security::remove_XSS($answer, STUDENT);
                        $answer_input = '<input type="hidden" name="choice2[' . $questionId . ']" value="0" />';
                        $answer_input .= '<label class="checkbox">';
                        $answer_input .= Display::input(
                            'checkbox',
                            'choice[' . $questionId . '][' . $numAnswer . ']',
                            1,
                            $attributes
                        );
                        $answer_input .= $answer;
                        $answer_input .= '</label>';

                        if ($show_comment) {
                            $s .= '<tr>';
                            $s .= '<td>';
                            $s .= $answer_input;
                            $s .= '</td>';
                            $s .= '<td>';
                            $s .= $comment;
                            $s .= '</td>';
                            $s .= '</tr>';
                        } else {
                            $s .= $answer_input;
                        }
                        break;
                    case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
                        $s .= '<input type="hidden" name="choice2[' . $questionId . ']" value="0" />';

                        $my_choice = array();
                        if (!empty($user_choice_array)) {
                            foreach ($user_choice_array as $item) {
                                $item = explode(':', $item);
                                if (isset($item[1]) && isset($item[0])) {
                                    $my_choice[$item[0]] = $item[1];
                                }
                            }
                        }
                        $answer = Security::remove_XSS($answer, STUDENT);
                        $s .= '<tr>';
                        $s .= Display::tag('td', $answer);

                        foreach ($objQuestionTmp->options as $key => $item) {
                            if (isset($my_choice[$numAnswer]) && $key == $my_choice[$numAnswer]) {
                                $attributes = array(
                                    'checked' => 1,
                                    'selected' => 1
                                );
                            } else {
                                $attributes = array();
                            }

                            if ($debug_mark_answer) {
                                if ($key == $answerCorrect) {
                                    $attributes['checked'] = 1;
                                    $attributes['selected'] = 1;
                                }
                            }
                            $s .= Display::tag(
                                'td',
                                Display::input(
                                    'radio',
                                    'choice[' . $questionId . '][' . $numAnswer . ']',
                                    $key,
                                    $attributes
                                )
                            );
                        }

                        if ($show_comment) {
                            $s .= '<td>';
                            $s .= $comment;
                            $s .= '</td>';
                        }
                        $s .= '</tr>';
                        break;
                    case FILL_IN_BLANKS:
                        // display the question, with field empty, for student to fill it,
                        // or filled to display the answer in the Question preview of the exercise/admin.php page
                        $displayForStudent = true;
                        $listAnswerInformations = FillBlanks::getAnswerInfo($answer);
                        $separatorStartRegexp = FillBlanks::escapeForRegexp($listAnswerInformations['blankseparatorstart']);
                        $separatorEndRegexp = FillBlanks::escapeForRegexp($listAnswerInformations['blankseparatorend']);

                        list($answer) = explode('::', $answer);

                        //Correct answers
                        $correctAnswerList = $listAnswerInformations['tabwords'];

                        //Student's answer
                        $studentAnswerList = array();
                        if (isset($user_choice[0]['answer'])) {
                            $arrayStudentAnswer = FillBlanks::getAnswerInfo($user_choice[0]['answer'], true);
                            $studentAnswerList = $arrayStudentAnswer['studentanswer'];
                        }

                        // If the question must be shown with the answer (in page exercise/admin.php) for teacher preview
                        // set the student-answer to the correct answer
                        if ($debug_mark_answer) {
                            $studentAnswerList = $correctAnswerList;
                            $displayForStudent = false;
                        }

                        if (!empty($correctAnswerList) && !empty($studentAnswerList)) {
                            $answer = "";
                            for ($i = 0; $i < count($listAnswerInformations["commonwords"]) - 1; $i++) {
                                // display the common word
                                $answer .= $listAnswerInformations["commonwords"][$i];
                                // display the blank word
                                $correctItem = $listAnswerInformations["tabwords"][$i];
                                $correctItemRegexp = $correctItem;
                                // replace / with \/ to allow the preg_replace bellow and all the regexp char
                                $correctItemRegexp = FillBlanks::getRegexpProtected($correctItemRegexp);
                                if (isset($studentAnswerList[$i])) {
                                    // If student already started this test and answered this question,
                                    // fill the blank with his previous answers
                                    // may be "" if student viewed the question, but did not fill the blanks
                                    $correctItem = $studentAnswerList[$i];
                                }
                                $attributes["style"] = "width:" . $listAnswerInformations["tabinputsize"][$i] . "px";
                                $answer .= FillBlanks::getFillTheBlankHtml($separatorStartRegexp, $separatorEndRegexp, $correctItemRegexp, $questionId, $correctItem, $attributes, $answer, $listAnswerInformations, $displayForStudent, $i);
                            }
                            // display the last common word
                            $answer .= $listAnswerInformations["commonwords"][$i];
                        } else {
                            // display empty [input] with the right width for student to fill it
                            $separatorStartRegexp = FillBlanks::escapeForRegexp($listAnswerInformations['blankseparatorstart']);
                            $separatorEndRegexp = FillBlanks::escapeForRegexp($listAnswerInformations['blankseparatorend']);
                            $answer = "";
                            for ($i = 0; $i < count($listAnswerInformations["commonwords"]) - 1; $i++) {
                                // display the common words
                                $answer .= $listAnswerInformations["commonwords"][$i];
                                // display the blank word
                                $attributes["style"] = "width:" . $listAnswerInformations["tabinputsize"][$i] . "px";
                                $correctItem = $listAnswerInformations["tabwords"][$i];
                                $correctItemRegexp = $correctItem;
                                // replace / with \/ to allow the preg_replace bellow and all the regexp char
                                $correctItemRegexp = FillBlanks::getRegexpProtected($correctItemRegexp);
                                $answer .= FillBlanks::getFillTheBlankHtml($separatorStartRegexp, $separatorEndRegexp, $correctItemRegexp, $questionId, '', $attributes, $answer, $listAnswerInformations, $displayForStudent, $i);
                            }
                            // display the last common word
                            $answer .= $listAnswerInformations["commonwords"][$i];
                        }
                        $s .= $answer;
                        break;
                    case CALCULATED_ANSWER:
                        /*
                         * In the CALCULATED_ANSWER test
                         * you mustn't have [ and ] in the textarea
                         * you mustn't have @@ in the textarea
                         * the text to find mustn't be empty or contains only spaces
                         * the text to find mustn't contains HTML tags
                         * the text to find mustn't contains char "
                         */
                        if ($origin !== null) {
                            global $exe_id;
                            $trackAttempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
                            $sql = 'SELECT answer FROM ' . $trackAttempts . '
                                    WHERE exe_id=' . $exe_id . ' AND question_id=' . $questionId;
                            $rsLastAttempt = Database::query($sql);
                            $rowLastAttempt = Database::fetch_array($rsLastAttempt);
                            $answer = $rowLastAttempt['answer'];
                            if (empty($answer)) {
                                $_SESSION['calculatedAnswerId'][$questionId] = mt_rand(
                                    1,
                                    $nbrAnswers
                                );
                                $answer = $objAnswerTmp->selectAnswer(
                                    $_SESSION['calculatedAnswerId'][$questionId]
                                );
                            }
                        }

                        list($answer) = explode('@@', $answer);
                        // $correctAnswerList array of array with correct anwsers 0=> [0=>[\p] 1=>[plop]]
                        api_preg_match_all(
                            '/\[[^]]+\]/',
                            $answer,
                            $correctAnswerList
                        );

                        // get student answer to display it if student go back to previous calculated answer question in a test
                        if (isset($user_choice[0]['answer'])) {
                            api_preg_match_all(
                                '/\[[^]]+\]/',
                                $answer,
                                $studentAnswerList
                            );
                            $studentAnswerListTobecleaned = $studentAnswerList[0];
                            $studentAnswerList = array();

                            for ($i = 0; $i < count(
                                $studentAnswerListTobecleaned
                            ); $i++) {
                                $answerCorrected = $studentAnswerListTobecleaned[$i];
                                $answerCorrected = api_preg_replace(
                                    '| / <font color="green"><b>.*$|',
                                    '',
                                    $answerCorrected
                                );
                                $answerCorrected = api_preg_replace(
                                    '/^\[/',
                                    '',
                                    $answerCorrected
                                );
                                $answerCorrected = api_preg_replace(
                                    '|^<font color="red"><s>|',
                                    '',
                                    $answerCorrected
                                );
                                $answerCorrected = api_preg_replace(
                                    '|</s></font>$|',
                                    '',
                                    $answerCorrected
                                );
                                $answerCorrected = '[' . $answerCorrected . ']';
                                $studentAnswerList[] = $answerCorrected;
                            }
                        }

                        // If display preview of answer in test view for exemple, set the student answer to the correct answers
                        if ($debug_mark_answer) {
                            // contain the rights answers surronded with brackets
                            $studentAnswerList = $correctAnswerList[0];
                        }

                        /*
                        Split the response by bracket
                        tabComments is an array with text surrounding the text to find
                        we add a space before and after the answerQuestion to be sure to
                        have a block of text before and after [xxx] patterns
                        so we have n text to find ([xxx]) and n+1 block of texts before,
                        between and after the text to find
                        */
                        $tabComments = api_preg_split(
                            '/\[[^]]+\]/',
                            ' ' . $answer . ' '
                        );
                        if (!empty($correctAnswerList) && !empty($studentAnswerList)) {
                            $answer = "";
                            $i = 0;
                            foreach ($studentAnswerList as $studentItem) {
                                // remove surronding brackets
                                $studentResponse = api_substr(
                                    $studentItem,
                                    1,
                                    api_strlen($studentItem) - 2
                                );
                                $size = strlen($studentItem);
                                $attributes['class'] = self::detectInputAppropriateClass(
                                    $size
                                );

                                $answer .= $tabComments[$i] .
                                    Display::input(
                                        'text',
                                        "choice[$questionId][]",
                                        $studentResponse,
                                        $attributes
                                    );
                                $i++;
                            }
                            $answer .= $tabComments[$i];
                        } else {
                            // display exercise with empty input fields
                            // every [xxx] are replaced with an empty input field
                            foreach ($correctAnswerList[0] as $item) {
                                $size = strlen($item);
                                $attributes['class'] = self::detectInputAppropriateClass(
                                    $size
                                );
                                $answer = str_replace(
                                    $item,
                                    Display::input(
                                        'text',
                                        "choice[$questionId][]",
                                        '',
                                        $attributes
                                    ),
                                    $answer
                                );
                            }
                        }
                        if ($origin !== null) {
                            $s = $answer;
                            break;
                        } else {
                            $s .= $answer;
                        }
                        break;
                    case MATCHING:
                        // matching type, showing suggestions and answers
                        // TODO: replace $answerId by $numAnswer

                        if ($answerCorrect != 0) {
                            // only show elements to be answered (not the contents of
                            // the select boxes, who are corrrect = 0)
                            $s .= '<tr><td width="45%" valign="top">';
                            $parsed_answer = $answer;
                            //left part questions
                            $s .= '<p class="indent">' . $lines_count . '.&nbsp;' . $parsed_answer . '</p></td>';
                            //middle part (matches selects)

                            $s .= '<td width="10%" valign="top" align="center" >
                                <div class="select-matching">
                                <select name="choice[' . $questionId . '][' . $numAnswer . ']">';

                            // fills the list-box
                            foreach ($select_items as $key => $val) {
                                // set $debug_mark_answer to true at function start to
                                // show the correct answer with a suffix '-x'
                                $selected = '';
                                if ($debug_mark_answer) {
                                    if ($val['id'] == $answerCorrect) {
                                        $selected = 'selected="selected"';
                                    }
                                }
                                //$user_choice_array_position
                                if (isset($user_choice_array_position[$numAnswer]) && $val['id'] == $user_choice_array_position[$numAnswer]) {
                                    $selected = 'selected="selected"';
                                }
                                $s .= '<option value="' . $val['id'] . '" ' . $selected . '>' . $val['letter'] . '</option>';

                            }  // end foreach()

                            $s .= '</select></div></td><td width="5%" class="separate">&nbsp;</td>';
                            $s .= '<td width="40%" valign="top" >';
                            if (isset($select_items[$lines_count])) {
                                $s .= '<div class="text-right"><p class="indent">' . $select_items[$lines_count]['letter'] . '.&nbsp; ' . $select_items[$lines_count]['answer'] . '</p></div>';
                            } else {
                                $s .= '&nbsp;';
                            }
                            $s .= '</td>';
                            $s .= '</tr>';
                            $lines_count++;
                            //if the left side of the "matching" has been completely
                            // shown but the right side still has values to show...
                            if (($lines_count - 1) == $num_suggestions) {
                                // if it remains answers to shown at the right side
                                while (isset($select_items[$lines_count])) {
                                    $s .= '<tr>
                                      <td colspan="2"></td>
                                      <td valign="top">';
                                    $s .= '<b>' . $select_items[$lines_count]['letter'] . '.</b> ' . $select_items[$lines_count]['answer'];
                                    $s .= "</td>
                                </tr>";
                                    $lines_count++;
                                }    // end while()
                            }  // end if()
                            $matching_correct_answer++;
                        }
                        break;
                    case DRAGGABLE:
                        if ($answerCorrect != 0) {
                            $parsed_answer = $answer;
                            /*$lines_count = '';
                            $data = $objAnswerTmp->getAnswerByAutoId($numAnswer);
                            $data = $objAnswerTmp->getAnswerByAutoId($data['correct']);
                            $lines_count = $data['answer'];*/

                            $windowId = $questionId . '_' . $lines_count;

                            $s .= '<li class="touch-items" id="' . $windowId . '">';
                            $s .= Display::div(
                                $parsed_answer,
                                [
                                    'id' => "window_$windowId",
                                    'class' => "window{$questionId}_question_draggable exercise-draggable-answer-option"
                                ]
                            );
                            $selectedValue = 0;
                            $draggableSelectOptions = [];

                            foreach ($select_items as $key => $val) {
                                if ($debug_mark_answer) {
                                    if ($val['id'] == $answerCorrect) {
                                        $selectedValue = $val['id'];
                                    }
                                }

                                if (
                                    isset($user_choice[$matching_correct_answer]) &&
                                    $val['id'] == $user_choice[$matching_correct_answer]['answer']
                                ) {
                                    $selectedValue = $val['id'];
                                }

                                $draggableSelectOptions[$val['id']] = $val['letter'];
                            }

                            $s .= Display::select(
                                "choice[$questionId][$numAnswer]",
                                $draggableSelectOptions,
                                $selectedValue,
                                [
                                    'id' => "window_{$windowId}_select",
                                    'class' => 'select_option',
                                    'style' => 'display: none;'
                                ],
                                false
                            );

                            if (!empty($answerCorrect) && !empty($selectedValue)) {
                                $s .= <<<JAVASCRIPT
                                <script>
                                    $(function() {
                                        DraggableAnswer.deleteItem(
                                            $('#{$questionId}_{$selectedValue}'),
                                            $('#drop_$windowId')
                                        );
                                    });
                                </script>
JAVASCRIPT;
                            }

                            if (isset($select_items[$lines_count])) {
                                $s .= Display::div(
                                    Display::tag(
                                        'b',
                                        $select_items[$lines_count]['letter']
                                    ) . $select_items[$lines_count]['answer'],
                                    [
                                        'id' => "window_{$windowId}_answer",
                                        'style' => 'display: none;'
                                    ]
                                );
                            } else {
                                $s .= '&nbsp;';
                            }

                            $lines_count++;

                            if (($lines_count - 1) == $num_suggestions) {
                                while (isset($select_items[$lines_count])) {
                                    $s .= Display::tag('b', $select_items[$lines_count]['letter']);
                                    $s .= $select_items[$lines_count]['answer'];
                                    $lines_count++;
                                }
                            }

                            $matching_correct_answer++;

                            $s .= '</li>';
                        }
                        break;
                    case MATCHING_DRAGGABLE:
                        if ($answerId == 1) {
                            echo $objAnswerTmp->getJs();
                        }

                        if ($answerCorrect != 0) {
                            $parsed_answer = $answer;
                            $windowId = "{$questionId}_{$lines_count}";

                            $s .= <<<HTML
                            <tr>
                                <td widht="45%">
                                    <div id="window_{$windowId}" class="window window_left_question window{$questionId}_question">
                                        <strong>$lines_count.</strong> $parsed_answer
                                    </div>
                                </td>
                                <td width="10%">
HTML;
                            $selectedValue = 0;
                            $selectedPosition = 0;
                            $questionOptions = [];

                            $iTempt = 0;

                            foreach ($select_items as $key => $val) {
                                if ($debug_mark_answer) {
                                    if ($val['id'] == $answerCorrect) {
                                        $selectedValue = $val['id'];
                                        $selectedPosition = $iTempt;
                                    }
                                }

                                if (
                                    isset($user_choice[$matching_correct_answer]) &&
                                    $val['id'] == $user_choice[$matching_correct_answer]['answer']
                                ) {
                                    $selectedValue = $val['id'];
                                    $selectedPosition = $iTempt;
                                }

                                $questionOptions[$val['id']] = $val['letter'];
                                $iTempt++;
                            }

                            $s .= Display::select(
                                "choice[$questionId][$numAnswer]",
                                $questionOptions,
                                $selectedValue,
                                [
                                    'id' => "window_{$windowId}_select",
                                    'class' => 'hidden'
                                ],
                                false
                            );

                            if (!empty($answerCorrect) && !empty($selectedValue)) {
                                // Show connect if is not freeze (question preview)
                                if (!$freeze) {
                                    $s .= <<<JAVASCRIPT
                                <script>
                                    $(document).on('ready', function () {
                                        jsPlumb.ready(function() {
                                            jsPlumb.connect({
                                                source: 'window_$windowId',
                                                target: 'window_{$questionId}_{$selectedPosition}_answer',
                                                endpoint: ['Blank', {radius: 15}],
                                                anchors: ['RightMiddle', 'LeftMiddle'],
                                                paintStyle: {strokeStyle: '#8A8888', lineWidth: 8},
                                                connector: [
                                                    MatchingDraggable.connectorType,
                                                    {curvines: MatchingDraggable.curviness}
                                                ]
                                            });
                                        });
                                    });
                                </script>
JAVASCRIPT;
                                }
                            }

                            $s .= <<<HTML
                            </td>
                            <td width="45%">
HTML;

                            if (isset($select_items[$lines_count])) {
                                $s .= <<<HTML
                                <div id="window_{$windowId}_answer" class="window window_right_question">
                                    <strong>{$select_items[$lines_count]['letter']}.</strong> {$select_items[$lines_count]['answer']}
                                </div>
HTML;
                            } else {
                                $s .= '&nbsp;';
                            }

                            $s .= '</td></tr>';

                            $lines_count++;

                            if (($lines_count - 1) == $num_suggestions) {
                                while (isset($select_items[$lines_count])) {
                                    $s .= <<<HTML
                                    <tr>
                                        <td colspan="2"></td>
                                        <td>
                                            <strong>{$select_items[$lines_count]['letter']}</strong>
                                            {$select_items[$lines_count]['answer']}
                                        </td>
                                    </tr>
HTML;
                                    $lines_count++;
                                }
                            }
                            $matching_correct_answer++;
                        }
                        break;
                }
            }    // end for()

            if ($show_comment) {
                $s .= '</table>';
            } elseif (
            in_array(
                $answerType,
                [
                    MATCHING,
                    MATCHING_DRAGGABLE,
                    UNIQUE_ANSWER_NO_OPTION,
                    MULTIPLE_ANSWER_TRUE_FALSE,
                    MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE
                ]
            )
            ) {
                $s .= '</table>';
            }

            if ($answerType == DRAGGABLE) {
                $s .= "</ul>";
                $s .= "</div>"; //clearfix

                $counterAnswer = 1;

                $s .= '<div class="col-md-12"><div class="row">';

                for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                    $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                    $windowId = $questionId . '_' . $counterAnswer;

                    if ($answerCorrect) {
                        $s .= Display::div(
                                Display::div('&nbsp;',
                                        [
                                            'id' => "drop_$windowId",
                                            'class' => 'droppable'
                                        ])
                                ,
                                [
                                    'class' => 'col-md-3'
                                ]
                                );

                        $counterAnswer++;
                    }
                }

                $s .= '</div>'; // row
                $s .= '</div>'; // col-md-12
                $s .= '</div>'; // col-md-12 ui-widget ui-helper-clearfix
            }

            if (in_array($answerType, [MATCHING, MATCHING_DRAGGABLE])) {
                $s .= '</div>'; //drag_question
            }

            $s .= '</div>'; //question_options row

            // destruction of the Answer object
            unset($objAnswerTmp);

            // destruction of the Question object
            unset($objQuestionTmp);

            if ($origin == 'export') {
                return $s;
            }

            echo $s;
        } elseif ($answerType == HOT_SPOT || $answerType == HOT_SPOT_DELINEATION) {
            global $exerciseId, $exe_id;
            // Question is a HOT_SPOT
            //checking document/images visibility
            if (api_is_platform_admin() || api_is_course_admin()) {
                $course = api_get_course_info();
                $doc_id = DocumentManager::get_document_id(
                    $course,
                    '/images/' . $pictureName
                );
                if (is_numeric($doc_id)) {
                    $images_folder_visibility = api_get_item_visibility(
                        $course,
                        'document',
                        $doc_id,
                        api_get_session_id()
                    );
                    if (!$images_folder_visibility) {
                        //This message is shown only to the course/platform admin if the image is set to visibility = false
                        Display::display_warning_message(
                            get_lang('ChangeTheVisibilityOfTheCurrentImage')
                        );
                    }
                }
            }
            $questionName = $objQuestionTmp->selectTitle();
            $questionDescription = $objQuestionTmp->selectDescription();
            if ($freeze) {
                $relPath = api_get_path(WEB_CODE_PATH);
                echo "
                    <script>
//                        $(document).on('ready', function () {
                            new " . ($answerType == HOT_SPOT ? "HotspotQuestion" : "DelineationQuestion") . "({
                                questionId: $questionId,
                                exerciseId: $exerciseId,
                                selector: '#hotspot-preview-$questionId',
                                for: 'preview',
                                relPath: '$relPath'
                            });
//                        });
                    </script>
                    <div id=\"hotspot-preview-$questionId\"></div>
                ";
                return;
            }

            // Get the answers, make a list
            $objAnswerTmp = new Answer($questionId);
            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

            // get answers of hotpost
            $answers_hotspot = array();
            for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                $answers = $objAnswerTmp->selectAnswerByAutoId(
                    $objAnswerTmp->selectAutoId($answerId)
                );
                $answers_hotspot[$answers['id']] = $objAnswerTmp->selectAnswer(
                    $answerId
                );
            }

            $answerList = '';
            if ($answerType != HOT_SPOT_DELINEATION) {
                $answerList = '
                    <div class="well well-sm">
                        <h5 class="page-header">' . get_lang('HotspotZones') . '</h5>
                        <ul>
                ';

                if (!empty($answers_hotspot)) {
                    Session::write('hotspot_ordered', array_keys($answers_hotspot));
                    $countAnswers = 1;
                    foreach ($answers_hotspot as $value) {
                        $answerList .= "<li><p>{$countAnswers} - {$value}</p></li>";
                        $countAnswers++;
                    }
                }

                $answerList .= '
                        </ul>
                    </div>
                ';
            }

            if (!$only_questions) {
                if ($show_title) {
                    TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    echo '<div class="question_title">' . $current_item . '. ' . $questionName . '</div>';
                }
                //@todo I need to the get the feedback type
                echo <<<HOTSPOT
                    <input type="hidden" name="hidden_hotspot_id" value="$questionId" />
                    <div class="exercise_questions">
                        $questionDescription
                        <div class="row">
HOTSPOT;
            }

            $relPath = api_get_path(WEB_CODE_PATH);
            $s .= "
                            <div class=\"col-sm-8 col-md-9\">
                                <div class=\"hotspot-image\"></div>
                                <script>
                                    $(document).on('ready', function () {
                                        new " . ($answerType == HOT_SPOT_DELINEATION ? 'DelineationQuestion' : 'HotspotQuestion') . "({
                                            questionId: $questionId,
                                            exerciseId: $exe_id,
                                            selector: '#question_div_' + $questionId + ' .hotspot-image',
                                            for: 'user',
                                            relPath: '$relPath'
                                        });
                                    });
                                </script>
                            </div>
                            <div class=\"col-sm-4 col-md-3\">
                                $answerList
                            </div>
            ";

            echo <<<HOTSPOT
                            $s
                        </div>
                    </div>
HOTSPOT;
        }
        return $nbrAnswers;
    }

    /**
     * @param int $exe_id
     * @return array
     */
    public static function get_exercise_track_exercise_info($exe_id)
    {
        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
        $TBL_TRACK_EXERCICES = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_EXERCISES
        );
        $TBL_COURSE = Database::get_main_table(TABLE_MAIN_COURSE);
        $exe_id = intval($exe_id);
        $result = array();
        if (!empty($exe_id)) {
            $sql = " SELECT q.*, tee.*
                FROM $TBL_EXERCICES as q
                INNER JOIN $TBL_TRACK_EXERCICES as tee
                ON q.id = tee.exe_exo_id
                INNER JOIN $TBL_COURSE c
                ON c.id = tee.c_id
                WHERE tee.exe_id = $exe_id
                AND q.c_id = c.id";

            $res_fb_type = Database::query($sql);
            $result = Database::fetch_array($res_fb_type, 'ASSOC');
        }

        return $result;
    }

    /**
     * Validates the time control key
     */
    public static function exercise_time_control_is_valid(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    )
    {
        $course_id = api_get_course_int_id();
        $exercise_id = intval($exercise_id);
        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT expired_time FROM $TBL_EXERCICES
            WHERE c_id = $course_id AND id = $exercise_id";
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        if (!empty($row['expired_time'])) {
            $current_expired_time_key = ExerciseLib::get_time_control_key(
                $exercise_id,
                $lp_id,
                $lp_item_id
            );
            if (isset($_SESSION['expired_time'][$current_expired_time_key])) {
                $current_time = time();
                $expired_time = api_strtotime(
                    $_SESSION['expired_time'][$current_expired_time_key],
                    'UTC'
                );
                $total_time_allowed = $expired_time + 30;
                if ($total_time_allowed < $current_time) {
                    return false;
                }
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Deletes the time control token
     */
    public static function exercise_time_control_delete(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    )
    {
        $current_expired_time_key = self::get_time_control_key(
            $exercise_id,
            $lp_id,
            $lp_item_id
        );
        unset($_SESSION['expired_time'][$current_expired_time_key]);
    }

    /**
     * Generates the time control key
     */
    public static function get_time_control_key($exercise_id, $lp_id = 0, $lp_item_id = 0)
    {
        $exercise_id = intval($exercise_id);
        $lp_id = intval($lp_id);
        $lp_item_id = intval($lp_item_id);
        return
            api_get_course_int_id() . '_' .
            api_get_session_id() . '_' .
            $exercise_id . '_' .
            api_get_user_id() . '_' .
            $lp_id . '_' .
            $lp_item_id;
    }

    /**
     * Get session time control
     */
    public static function get_session_time_control_key(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    )
    {
        $return_value = 0;
        $time_control_key = self::get_time_control_key(
            $exercise_id,
            $lp_id,
            $lp_item_id
        );
        if (isset($_SESSION['expired_time']) && isset($_SESSION['expired_time'][$time_control_key])) {
            $return_value = $_SESSION['expired_time'][$time_control_key];
        }
        return $return_value;
    }

    /**
     * Gets count of exam results
     * @todo this function should be moved in a library  + no global calls
     */
    public static function get_count_exam_results($exercise_id, $extra_where_conditions)
    {
        $count = self::get_exam_results_data(
            null,
            null,
            null,
            null,
            $exercise_id,
            $extra_where_conditions,
            true
        );
        return $count;
    }

    /**
     * @param string $in_hotpot_path
     * @return int
     */
    public static function get_count_exam_hotpotatoes_results($in_hotpot_path)
    {
        return self::get_exam_results_hotpotatoes_data(
            0,
            0,
            '',
            '',
            $in_hotpot_path,
            true,
            ''
        );
    }

    /**
     * @param int $in_from
     * @param int $in_number_of_items
     * @param int $in_column
     * @param int $in_direction
     * @param string $in_hotpot_path
     * @param bool $in_get_count
     * @param null $where_condition
     * @return array|int
     */
    public static function get_exam_results_hotpotatoes_data(
        $in_from,
        $in_number_of_items,
        $in_column,
        $in_direction,
        $in_hotpot_path,
        $in_get_count = false,
        $where_condition = null
    )
    {
        $courseId = api_get_course_int_id();
        /* by default in_column = 1 If parameters given,
    it is the name of the column witch is the bdd field name*/
        if ($in_column == 1) {
            $in_column = 'firstname';
        }
        $in_hotpot_path = Database::escape_string($in_hotpot_path);
        $in_direction = Database::escape_string($in_direction);
        $in_column = Database::escape_string($in_column);
        $in_number_of_items = intval($in_number_of_items);
        $in_from = intval($in_from);

        $TBL_TRACK_HOTPOTATOES = Database:: get_main_table(
            TABLE_STATISTIC_TRACK_E_HOTPOTATOES
        );
        $TBL_USER = Database:: get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT * FROM $TBL_TRACK_HOTPOTATOES thp
            JOIN $TBL_USER u ON thp.exe_user_id = u.user_id
            WHERE thp.c_id = $courseId AND exe_name LIKE '$in_hotpot_path%'";

        // just count how many answers
        if ($in_get_count) {
            $res = Database::query($sql);
            return Database::num_rows($res);
        }
        // get a number of sorted results
        $sql .= " $where_condition
            ORDER BY $in_column $in_direction
            LIMIT $in_from, $in_number_of_items";

        $res = Database::query($sql);
        $result = array();
        $apiIsAllowedToEdit = api_is_allowed_to_edit();
        $urlBase = api_get_path(WEB_CODE_PATH) .
            'exercise/hotpotatoes_exercise_report.php?action=delete&' .
            api_get_cidreq() . '&id=';
        while ($data = Database::fetch_array($res)) {
            $actions = null;

            if ($apiIsAllowedToEdit) {
                $url = $urlBase . $data['id'] . '&path=' . $data['exe_name'];
                $actions = Display::url(
                    Display::return_icon('delete.png', get_lang('Delete')),
                    $url
                );
            }

            $result[] = array(
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'username' => $data['username'],
                'group_name' => implode(
                    "<br/>",
                    GroupManager::get_user_group_name($data['user_id'])
                ),
                'exe_date' => $data['exe_date'],
                'score' => $data['exe_result'] . ' / ' . $data['exe_weighting'],
                'actions' => $actions,
            );
        }

        return $result;
    }

    /**
     * @param string $exercisePath
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public static function getLatestHotPotatoResult(
        $exercisePath,
        $userId,
        $courseId,
        $sessionId
    )
    {
        $table = Database:: get_main_table(
            TABLE_STATISTIC_TRACK_E_HOTPOTATOES
        );

        $courseInfo = api_get_course_info_by_id($courseId);
        $exercisePath = Database::escape_string($exercisePath);
        $userId = intval($userId);

        $sql = "SELECT * FROM $table
            WHERE
                c_id = $courseId AND
                exe_name LIKE '$exercisePath%' AND
                exe_user_id = $userId
            ORDER BY id
            LIMIT 1";
        $result = Database::query($sql);
        $attempt = array();
        if (Database::num_rows($result)) {
            $attempt = Database::fetch_array($result, 'ASSOC');
        }
        return $attempt;
    }

    /**
     * Gets the exam'data results
     * @todo this function should be moved in a library  + no global calls
     * @param int $from
     * @param int $number_of_items
     * @param int $column
     * @param string $direction
     * @param int $exercise_id
     * @param null $extra_where_conditions
     * @param bool $get_count
     * @return array
     */
    public static function get_exam_results_data(
        $from,
        $number_of_items,
        $column,
        $direction,
        $exercise_id,
        $extra_where_conditions = null,
        $get_count = false
    ) {
        //@todo replace all this globals
        global $documentPath, $filter;

        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $is_allowedToEdit = api_is_allowed_to_edit(null, true) ||
            api_is_allowed_to_edit(true) || api_is_drh() || api_is_student_boss();

        $TBL_USER = Database:: get_main_table(TABLE_MAIN_USER);
        $TBL_EXERCICES = Database:: get_course_table(TABLE_QUIZ_TEST);
        $TBL_GROUP_REL_USER = Database:: get_course_table(TABLE_GROUP_USER);
        $TBL_GROUP = Database:: get_course_table(TABLE_GROUP);
        $TBL_TRACK_EXERCICES = Database:: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_TRACK_HOTPOTATOES = Database:: get_main_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $TBL_TRACK_ATTEMPT_RECORDING = Database:: get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);

        $session_id_and = ' AND te.session_id = ' . $sessionId . ' ';
        $exercise_id = intval($exercise_id);

        $exercise_where = '';
        if (!empty($exercise_id)) {
            $exercise_where .= ' AND te.exe_exo_id = ' . $exercise_id . '  ';
        }

        $hotpotatoe_where = '';
        if (!empty($_GET['path'])) {
            $hotpotatoe_path = Database::escape_string($_GET['path']);
            $hotpotatoe_where .= ' AND exe_name = "' . $hotpotatoe_path . '"  ';
        }

        // sql for chamilo-type tests for teacher / tutor view
        $sql_inner_join_tbl_track_exercices = "
        (
            SELECT DISTINCT ttte.*, if(tr.exe_id,1, 0) as revised
            FROM $TBL_TRACK_EXERCICES ttte LEFT JOIN $TBL_TRACK_ATTEMPT_RECORDING tr
            ON (ttte.exe_id = tr.exe_id)
            WHERE
                c_id = $course_id AND
                exe_exo_id = $exercise_id AND
                ttte.session_id = " . $sessionId . "
        )";

        if ($is_allowedToEdit) {
            //@todo fix to work with COURSE_RELATION_TYPE_RRHH in both queries

            // Hack in order to filter groups
            $sql_inner_join_tbl_user = '';

            if (strpos($extra_where_conditions, 'group_id')) {
                $sql_inner_join_tbl_user = "
                (
                    SELECT
                        u.user_id,
                        firstname,
                        lastname,
                        official_code,
                        email,
                        username,
                        g.name as group_name,
                        g.id as group_id
                    FROM $TBL_USER u
                    INNER JOIN $TBL_GROUP_REL_USER gru
                    ON (gru.user_id = u.user_id AND gru.c_id=" . $course_id . ")
                    INNER JOIN $TBL_GROUP g
                    ON (gru.group_id = g.id AND g.c_id=" . $course_id . ")
                )";
            }

            if (strpos($extra_where_conditions, 'group_all')) {
                $extra_where_conditions = str_replace(
                    "AND (  group_id = 'group_all'  )",
                    '',
                    $extra_where_conditions
                );
                $extra_where_conditions = str_replace(
                    "AND group_id = 'group_all'",
                    '',
                    $extra_where_conditions
                );
                $extra_where_conditions = str_replace(
                    "group_id = 'group_all' AND",
                    '',
                    $extra_where_conditions
                );

                $sql_inner_join_tbl_user = "
                (
                    SELECT
                        u.user_id,
                        firstname,
                        lastname,
                        official_code,
                        email,
                        username,
                        '' as group_name,
                        '' as group_id
                    FROM $TBL_USER u
                )";
                $sql_inner_join_tbl_user = null;
            }

            if (strpos($extra_where_conditions, 'group_none')) {
                $extra_where_conditions = str_replace(
                    "AND (  group_id = 'group_none'  )",
                    "AND (  group_id is null  )",
                    $extra_where_conditions
                );
                $extra_where_conditions = str_replace(
                    "AND group_id = 'group_none'",
                    "AND (  group_id is null  )",
                    $extra_where_conditions
                );
                $sql_inner_join_tbl_user = "
            (
                SELECT
                    u.user_id,
                    firstname,
                    lastname,
                    official_code,
                    email,
                    username,
                    g.name as group_name,
                    g.id as group_id
                FROM $TBL_USER u
                LEFT OUTER JOIN $TBL_GROUP_REL_USER gru
                ON ( gru.user_id = u.user_id AND gru.c_id=" . $course_id . " )
                LEFT OUTER JOIN $TBL_GROUP g
                ON (gru.group_id = g.id AND g.c_id = " . $course_id . ")
            )";
            }

            // All
            $is_empty_sql_inner_join_tbl_user = false;

            if (empty($sql_inner_join_tbl_user)) {
                $is_empty_sql_inner_join_tbl_user = true;
                $sql_inner_join_tbl_user = "
            (
                SELECT u.user_id, firstname, lastname, email, username, ' ' as group_name, '' as group_id, official_code
                FROM $TBL_USER u
                WHERE u.status NOT IN(" . api_get_users_status_ignored_in_reports('string') . ")
            )";
            }

            $sqlFromOption = " , $TBL_GROUP_REL_USER AS gru ";
            $sqlWhereOption = "  AND gru.c_id = " . $course_id . " AND gru.user_id = user.user_id ";
            $first_and_last_name = api_is_western_name_order() ? "firstname, lastname" : "lastname, firstname";

            if ($get_count) {
                $sql_select = "SELECT count(te.exe_id) ";
            } else {
                $sql_select = "SELECT DISTINCT
                    user_id,
                    $first_and_last_name,
                    official_code,
                    ce.title,
                    username,
                    te.exe_result,
                    te.exe_weighting,
                    te.exe_date,
                    te.exe_id,
                    email as exemail,
                    te.start_date,
                    steps_counter,
                    exe_user_id,
                    te.exe_duration,
                    propagate_neg,
                    revised,
                    group_name,
                    group_id,
                    orig_lp_id,
                    te.user_ip";
            }

            $sql = " $sql_select
                FROM $TBL_EXERCICES AS ce
                INNER JOIN $sql_inner_join_tbl_track_exercices AS te
                ON (te.exe_exo_id = ce.id)
                INNER JOIN $sql_inner_join_tbl_user AS user
                ON (user.user_id = exe_user_id)
                WHERE
                    te.status != 'incomplete' AND
                    te.c_id = " . $course_id . " $session_id_and AND
                    ce.active <>-1 AND 
                    ce.c_id = " . $course_id . "
                    $exercise_where
                    $extra_where_conditions
                ";

            // sql for hotpotatoes tests for teacher / tutor view

            if ($get_count) {
                $hpsql_select = "SELECT count(username)";
            } else {
                $hpsql_select = "SELECT
                    $first_and_last_name ,
                    username,
                    official_code,
                    tth.exe_name,
                    tth.exe_result ,
                    tth.exe_weighting,
                    tth.exe_date";
            }

            $hpsql = " $hpsql_select
                FROM
                    $TBL_TRACK_HOTPOTATOES tth,
                    $TBL_USER user
                    $sqlFromOption
                WHERE
                    user.user_id=tth.exe_user_id
                    AND tth.c_id = " . $course_id . "
                    $hotpotatoe_where
                    $sqlWhereOption
                    AND user.status NOT IN(" . api_get_users_status_ignored_in_reports('string') . ")
                ORDER BY
                    tth.c_id ASC,
                    tth.exe_date DESC";
        }

        if ($get_count) {
            $resx = Database::query($sql);
            $rowx = Database::fetch_row($resx, 'ASSOC');

            return $rowx[0];
        }

        $teacher_list = CourseManager::get_teacher_list_from_course_code(
            api_get_course_id()
        );
        $teacher_id_list = array();
        foreach ($teacher_list as $teacher) {
            $teacher_id_list[] = $teacher['user_id'];
        }

        $list_info = array();

        // Simple exercises
        if (empty($hotpotatoe_where)) {
            $column = !empty($column) ? Database::escape_string($column) : null;
            $from = intval($from);
            $number_of_items = intval($number_of_items);

            if (!empty($column)) {
                $sql .= " ORDER BY $column $direction ";
            }
            $sql .= " LIMIT $from, $number_of_items";

            $results = array();
            $resx = Database::query($sql);
            while ($rowx = Database::fetch_array($resx, 'ASSOC')) {
                $results[] = $rowx;
            }

            $group_list = GroupManager::get_group_list();
            $clean_group_list = array();

            if (!empty($group_list)) {
                foreach ($group_list as $group) {
                    $clean_group_list[$group['id']] = $group['name'];
                }
            }

            $lp_list_obj = new LearnpathList(api_get_user_id());
            $lp_list = $lp_list_obj->get_flat_list();

            if (is_array($results)) {

                $users_array_id = array();
                $from_gradebook = false;
                if (isset($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
                    $from_gradebook = true;
                }
                $sizeof = count($results);

                $user_list_id = array();
                $locked = api_resource_is_locked_by_gradebook(
                    $exercise_id,
                    LINK_EXERCISE
                );

                // Looping results
                for ($i = 0; $i < $sizeof; $i++) {
                    $revised = $results[$i]['revised'];

                    if ($from_gradebook && ($is_allowedToEdit)) {
                        if (in_array(
                            $results[$i]['username'] . $results[$i]['firstname'] . $results[$i]['lastname'],
                            $users_array_id
                        )) {
                            continue;
                        }
                        $users_array_id[] = $results[$i]['username'] . $results[$i]['firstname'] . $results[$i]['lastname'];
                    }

                    $lp_obj = isset($results[$i]['orig_lp_id']) && isset($lp_list[$results[$i]['orig_lp_id']]) ? $lp_list[$results[$i]['orig_lp_id']] : null;
                    $lp_name = null;

                    if ($lp_obj) {
                        $url = api_get_path(
                                WEB_CODE_PATH
                            ) . 'lp/lp_controller.php?' . api_get_cidreq() . '&action=view&lp_id=' . $results[$i]['orig_lp_id'];
                        $lp_name = Display::url(
                            $lp_obj['lp_name'],
                            $url,
                            array('target' => '_blank')
                        );
                    }

                    //Add all groups by user
                    $group_name_list = null;

                    if ($is_empty_sql_inner_join_tbl_user) {
                        $group_list = GroupManager::get_group_ids(
                            api_get_course_int_id(),
                            $results[$i]['user_id']
                        );

                        foreach ($group_list as $id) {
                            $group_name_list .= $clean_group_list[$id] . '<br/>';
                        }
                        $results[$i]['group_name'] = $group_name_list;
                    }

                    $results[$i]['exe_duration'] = !empty($results[$i]['exe_duration']) ? round(
                        $results[$i]['exe_duration'] / 60
                    ) : 0;

                    $user_list_id[] = $results[$i]['exe_user_id'];
                    $id = $results[$i]['exe_id'];

                    $dt = api_convert_and_format_date(
                        $results[$i]['exe_weighting']
                    );

                    // we filter the results if we have the permission to
                    if (isset($results[$i]['results_disabled'])) {
                        $result_disabled = intval(
                            $results[$i]['results_disabled']
                        );
                    } else {
                        $result_disabled = 0;
                    }

                    if ($result_disabled == 0) {
                        $my_res = $results[$i]['exe_result'];
                        $my_total = $results[$i]['exe_weighting'];

                        $results[$i]['start_date'] = api_get_local_time(
                            $results[$i]['start_date']
                        );
                        $results[$i]['exe_date'] = api_get_local_time(
                            $results[$i]['exe_date']
                        );

                        if (!$results[$i]['propagate_neg'] && $my_res < 0) {
                            $my_res = 0;
                        }

                        $score = self::show_score($my_res, $my_total);

                        $actions = '';
                        if ($is_allowedToEdit) {
                            if (isset($teacher_id_list)) {
                                if (in_array(
                                    $results[$i]['exe_user_id'],
                                    $teacher_id_list
                                )) {
                                    $actions .= Display::return_icon(
                                        'teacher.png',
                                        get_lang('Teacher')
                                    );
                                }
                            }
                            if ($revised) {
                                $actions .= "<a href='exercise_show.php?" . api_get_cidreq() . "&action=edit&id=$id'>" .
                                    Display:: return_icon(
                                        'edit.png',
                                        get_lang('Edit'),
                                        array(),
                                        ICON_SIZE_SMALL
                                    );
                                $actions .= '&nbsp;';
                            } else {
                                $actions .= "<a href='exercise_show.php?" . api_get_cidreq() . "&action=qualify&id=$id'>" .
                                    Display:: return_icon(
                                        'quiz.png',
                                        get_lang('Qualify')
                                    );
                                $actions .= '&nbsp;';
                            }
                            $actions .= "</a>";

                            if ($filter == 2) {
                                $actions .= ' <a href="exercise_history.php?' . api_get_cidreq() . '&exe_id=' . $id . '">' .
                                    Display:: return_icon(
                                        'history.png',
                                        get_lang('ViewHistoryChange')
                                    ) . '</a>';
                            }

                            //Admin can always delete the attempt
                            if (($locked == false || api_is_platform_admin()) && !api_is_student_boss()) {
                                $ip = TrackingUserLog::get_ip_from_user_event(
                                    $results[$i]['exe_user_id'],
                                    api_get_utc_datetime(),
                                    false
                                );
                                $actions .= '<a href="http://www.whatsmyip.org/ip-geo-location/?ip=' . $ip . '" target="_blank">
                                ' . Display::return_icon('info.png', $ip) . '
                                </a>';


                                $recalculateUrl = api_get_path(WEB_CODE_PATH) . 'exercise/recalculate.php?' .
                                    api_get_cidreq() . '&' .
                                    http_build_query([
                                        'id' => $id,
                                        'exercise' => $exercise_id,
                                        'user' => $results[$i]['exe_user_id']
                                    ]);
                                $actions .= Display::url(
                                    Display::return_icon('reload.png', get_lang('RecalculateResults')),
                                    $recalculateUrl,
                                    [
                                        'data-exercise' => $exercise_id,
                                        'data-user' => $results[$i]['exe_user_id'],
                                        'data-id' => $id,
                                        'class' => 'exercise-recalculate'
                                    ]
                                );

                                $delete_link = '<a href="exercise_report.php?' . api_get_cidreq() . '&filter_by_user=' . intval($_GET['filter_by_user']) . '&filter=' . $filter . '&exerciseId=' . $exercise_id . '&delete=delete&did=' . $id . '"
                                onclick="javascript:if(!confirm(\'' . sprintf(
                                        get_lang('DeleteAttempt'),
                                        $results[$i]['username'],
                                        $dt
                                    ) . '\')) return false;">' . Display:: return_icon(
                                        'delete.png',
                                        get_lang('Delete')
                                    ) . '</a>';
                                $delete_link = utf8_encode($delete_link);

                                if (api_is_drh() && !api_is_platform_admin()) {
                                    $delete_link = null;
                                }
                                $actions .= $delete_link . '&nbsp;';
                            }

                        } else {
                            $attempt_url = api_get_path(
                                    WEB_CODE_PATH
                                ) . 'exercise/result.php?' . api_get_cidreq() . '&id=' . $results[$i]['exe_id'] . '&id_session=' . $sessionId;
                            $attempt_link = Display::url(
                                get_lang('Show'),
                                $attempt_url,
                                [
                                    'class' => 'ajax btn btn-default',
                                    'data-title' => get_lang('Show')
                                ]
                            );
                            $actions .= $attempt_link;
                        }

                        if ($revised) {
                            $revised = Display::label(
                                get_lang('Validated'),
                                'success'
                            );
                        } else {
                            $revised = Display::label(
                                get_lang('NotValidated'),
                                'info'
                            );
                        }

                        $results[$i]['id'] = $results[$i]['exe_id'];

                        if ($is_allowedToEdit) {
                            $results[$i]['status'] = $revised;
                            $results[$i]['score'] = $score;
                            $results[$i]['lp'] = $lp_name;
                            $results[$i]['actions'] = $actions;
                            $list_info[] = $results[$i];
                        } else {
                            $results[$i]['status'] = $revised;
                            $results[$i]['score'] = $score;
                            $results[$i]['actions'] = $actions;
                            $list_info[] = $results[$i];
                        }
                    }
                }
            }
        } else {
            $hpresults = StatsUtils::getManyResultsXCol($hpsql, 6);
            // Print HotPotatoes test results.
            if (is_array($hpresults)) {
                for ($i = 0; $i < sizeof($hpresults); $i++) {
                    $hp_title = GetQuizName($hpresults[$i][3], $documentPath);
                    if ($hp_title == '') {
                        $hp_title = basename($hpresults[$i][3]);
                    }

                    $hp_date = api_get_local_time(
                        $hpresults[$i][6],
                        null,
                        date_default_timezone_get()
                    );
                    $hp_result = round(
                            ($hpresults[$i][4] / ($hpresults[$i][5] != 0 ? $hpresults[$i][5] : 1)) * 100,
                            2
                        ) . '% (' . $hpresults[$i][4] . ' / ' . $hpresults[$i][5] . ')';
                    if ($is_allowedToEdit) {
                        $list_info[] = array(
                            $hpresults[$i][0],
                            $hpresults[$i][1],
                            $hpresults[$i][2],
                            '',
                            $hp_title,
                            '-',
                            $hp_date,
                            $hp_result,
                            '-'
                        );
                    } else {
                        $list_info[] = array(
                            $hp_title,
                            '-',
                            $hp_date,
                            $hp_result,
                            '-'
                        );
                    }
                }
            }
        }

        return $list_info;
    }

    /**
     * Converts the score with the exercise_max_note and exercise_min_score
     * the platform settings + formats the results using the float_format function
     *
     * @param float $score
     * @param float $weight
     * @param bool $show_percentage show percentage or not
     * @param bool $use_platform_settings use or not the platform settings
     * @param bool $show_only_percentage
     * @return  string  an html with the score modified
     */
    public static function show_score(
        $score,
        $weight,
        $show_percentage = true,
        $use_platform_settings = true,
        $show_only_percentage = false
    )
    {
        if (is_null($score) && is_null($weight)) {
            return '-';
        }

        $max_note = api_get_setting('exercise_max_score');
        $min_note = api_get_setting('exercise_min_score');

        if ($use_platform_settings) {
            if ($max_note != '' && $min_note != '') {
                if (!empty($weight) && intval($weight) != 0) {
                    $score = $min_note + ($max_note - $min_note) * $score / $weight;
                } else {
                    $score = $min_note;
                }
                $weight = $max_note;
            }
        }
        $percentage = (100 * $score) / ($weight != 0 ? $weight : 1);

        // Formats values
        $percentage = float_format($percentage, 1);
        $score = float_format($score, 1);
        $weight = float_format($weight, 1);

        $html = null;
        if ($show_percentage) {
            $parent = '(' . $score . ' / ' . $weight . ')';
            $html = $percentage . "%  $parent";
            if ($show_only_percentage) {
                $html = $percentage . "% ";
            }
        } else {
            $html = $score . ' / ' . $weight;
        }
        $html = Display::span($html, array('class' => 'score_exercise'));

        return $html;
    }

    /**
     * @param float $score
     * @param float $weight
     * @param string $pass_percentage
     * @return bool
     */
    public static function is_success_exercise_result($score, $weight, $pass_percentage)
    {
        $percentage = float_format(
            ($score / ($weight != 0 ? $weight : 1)) * 100,
            1
        );
        if (isset($pass_percentage) && !empty($pass_percentage)) {
            if ($percentage >= $pass_percentage) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param float $score
     * @param float $weight
     * @param string $pass_percentage
     * @return string
     */
    public static function show_success_message($score, $weight, $pass_percentage)
    {
        $res = "";
        if (self::is_pass_pourcentage_enabled($pass_percentage)) {
            $is_success = self::is_success_exercise_result(
                $score,
                $weight,
                $pass_percentage
            );

            if ($is_success) {
                $html = get_lang('CongratulationsYouPassedTheTest');
                $icon = Display::return_icon(
                    'completed.png',
                    get_lang('Correct'),
                    array(),
                    ICON_SIZE_MEDIUM
                );
            } else {
                //$html .= Display::return_message(get_lang('YouDidNotReachTheMinimumScore'), 'warning');
                $html = get_lang('YouDidNotReachTheMinimumScore');
                $icon = Display::return_icon(
                    'warning.png',
                    get_lang('Wrong'),
                    array(),
                    ICON_SIZE_MEDIUM
                );
            }
            $html = Display::tag('h4', $html);
            $html .= Display::tag(
                'h5',
                $icon,
                array('style' => 'width:40px; padding:2px 10px 0px 0px')
            );
            $res = $html;
        }
        return $res;
    }

    /**
     * Return true if pass_pourcentage activated (we use the pass pourcentage feature
     * return false if pass_percentage = 0 (we don't use the pass pourcentage feature
     * @param $in_pass_pourcentage
     * @return boolean
     * In this version, pass_percentage and show_success_message are disabled if
     * pass_percentage is set to 0
     */
    public static function is_pass_pourcentage_enabled($in_pass_pourcentage)
    {
        return $in_pass_pourcentage > 0;
    }

    /**
     * Converts a numeric value in a percentage example 0.66666 to 66.67 %
     * @param $value
     * @return float Converted number
     */
    public static function convert_to_percentage($value)
    {
        $return = '-';
        if ($value != '') {
            $return = float_format($value * 100, 1) . ' %';
        }
        return $return;
    }

    /**
     * Converts a score/weight values to the platform scale
     * @param   float $score
     * @param   float $weight
     * @deprecated seem not to be used
     * @return  float   the score rounded converted to the new range
     */
    public static function convert_score($score, $weight)
    {
        $max_note = api_get_setting('exercise_max_score');
        $min_note = api_get_setting('exercise_min_score');

        if ($score != '' && $weight != '') {
            if ($max_note != '' && $min_note != '') {
                if (!empty($weight)) {
                    $score = $min_note + ($max_note - $min_note) * $score / $weight;
                } else {
                    $score = $min_note;
                }
            }
        }
        $score_rounded = float_format($score, 1);
        return $score_rounded;
    }

    /**
     * Getting all active exercises from a course from a session
     * (if a session_id is provided we will show all the exercises in the course +
     * all exercises in the session)
     * @param   array $course_info
     * @param   int $session_id
     * @param   boolean $check_publication_dates
     * @param   string $search Search exercise name
     * @param   boolean $search_all_sessions Search exercises in all sessions
     * @param   int 0 = only inactive exercises
     *                  1 = only active exercises,
     *                  2 = all exercises
     *                  3 = active <> -1
     * @return  array   array with exercise data
     */
    public static function get_all_exercises(
        $course_info = null,
        $session_id = 0,
        $check_publication_dates = false,
        $search = '',
        $search_all_sessions = false,
        $active = 2
    )
    {
        $course_id = api_get_course_int_id();

        if (!empty($course_info) && !empty($course_info['real_id'])) {
            $course_id = $course_info['real_id'];
        }

        if ($session_id == -1) {
            $session_id = 0;
        }

        $now = api_get_utc_datetime();
        $time_conditions = '';

        if ($check_publication_dates) {
            //start and end are set
            $time_conditions = " AND ((start_time <> '' AND start_time < '$now' AND end_time <> '' AND end_time > '$now' )  OR ";
            // only start is set
            $time_conditions .= " (start_time <> '' AND start_time < '$now' AND end_time is NULL) OR ";
            // only end is set
            $time_conditions .= " (start_time IS NULL AND end_time <> '' AND end_time > '$now') OR ";
            // nothing is set
            $time_conditions .= " (start_time IS NULL AND end_time IS NULL))  ";
        }

        $needle_where = !empty($search) ? " AND title LIKE '?' " : '';
        $needle = !empty($search) ? "%" . $search . "%" : '';

        // Show courses by active status
        $active_sql = '';
        if ($active == 3) {
            $active_sql = ' active <> -1 AND';
        } else {
            if ($active != 2) {
                $active_sql = sprintf(' active = %d AND', $active);
            }
        }

        if ($search_all_sessions == true) {
            $conditions = array(
                'where' => array(
                    $active_sql . ' c_id = ? ' . $needle_where . $time_conditions => array(
                        $course_id,
                        $needle
                    )
                ),
                'order' => 'title'
            );
        } else {
            if ($session_id == 0) {
                $conditions = array(
                    'where' => array(
                        $active_sql . ' session_id = ? AND c_id = ? ' . $needle_where . $time_conditions => array(
                            $session_id,
                            $course_id,
                            $needle
                        )
                    ),
                    'order' => 'title'
                );
            } else {
                $conditions = array(
                    'where' => array(
                        $active_sql . ' (session_id = 0 OR session_id = ? ) AND c_id = ? ' . $needle_where . $time_conditions => array(
                            $session_id,
                            $course_id,
                            $needle
                        )
                    ),
                    'order' => 'title'
                );
            }
        }

        $table = Database:: get_course_table(TABLE_QUIZ_TEST);

        return Database::select('*', $table, $conditions);
    }

    /**
     * Get exercise information by id
     * @param int $exerciseId Exercise Id
     * @param int $courseId The course ID (necessary as c_quiz.id is not unique)
     * @return array Exercise info
     */
    public static function get_exercise_by_id($exerciseId = 0, $courseId = null)
    {
        $TBL_EXERCICES = Database:: get_course_table(TABLE_QUIZ_TEST);
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        } else {
            $courseId = intval($courseId);
        }
        $conditions = array(
            'where' => array(
                'id = ?' => array($exerciseId),
                ' AND c_id = ? ' => $courseId
            )
        );

        return Database::select('*', $TBL_EXERCICES, $conditions);
    }

    /**
     * Getting all exercises (active only or all)
     * from a course from a session
     * (if a session_id is provided we will show all the exercises in the
     * course + all exercises in the session)
     * @param   array   course data
     * @param   int     session id
     * @param    int        course c_id
     * @param   boolean $only_active_exercises
     * @return  array   array with exercise data
     * modified by Hubert Borderiou
     */
    public static function get_all_exercises_for_course_id(
        $course_info = null,
        $session_id = 0,
        $course_id = 0,
        $only_active_exercises = true
    )
    {
        $TBL_EXERCISES = Database:: get_course_table(TABLE_QUIZ_TEST);

        if ($only_active_exercises) {
            // Only active exercises.
            $sql_active_exercises = "active = 1 AND ";
        } else {
            // Not only active means visible and invisible NOT deleted (-2)
            $sql_active_exercises = "active IN (1, 0) AND ";
        }

        if ($session_id == -1) {
            $session_id = 0;
        }

        $params = array(
            $session_id,
            $course_id
        );

        if ($session_id == 0) {
            $conditions = array(
                'where' => array("$sql_active_exercises session_id = ? AND c_id = ?" => $params),
                'order' => 'title'
            );
        } else {
            // All exercises
            $conditions = array(
                'where' => array("$sql_active_exercises (session_id = 0 OR session_id = ? ) AND c_id=?" => $params),
                'order' => 'title'
            );
        }

        return Database::select('*', $TBL_EXERCISES, $conditions);
    }

    /**
     * Gets the position of the score based in a given score (result/weight)
     * and the exe_id based in the user list
     * (NO Exercises in LPs )
     * @param   float $my_score user score to be compared *attention*
     * $my_score = score/weight and not just the score
     * @param   int $my_exe_id exe id of the exercise
     * (this is necessary because if 2 students have the same score the one
     * with the minor exe_id will have a best position, just to be fair and FIFO)
     * @param   int $exercise_id
     * @param   string $course_code
     * @param   int $session_id
     * @param   array $user_list
     * @param   bool $return_string
     *
     * @return  int     the position of the user between his friends in a course
     * (or course within a session)
     */
    public static function get_exercise_result_ranking(
        $my_score,
        $my_exe_id,
        $exercise_id,
        $course_code,
        $session_id = 0,
        $user_list = array(),
        $return_string = true
    )
    {
        //No score given we return
        if (is_null($my_score)) {
            return '-';
        }
        if (empty($user_list)) {
            return '-';
        }

        $best_attempts = array();
        foreach ($user_list as $user_data) {
            $user_id = $user_data['user_id'];
            $best_attempts[$user_id] = self::get_best_attempt_by_user(
                $user_id,
                $exercise_id,
                $course_code,
                $session_id
            );
        }

        if (empty($best_attempts)) {
            return 1;
        } else {
            $position = 1;
            $my_ranking = array();
            foreach ($best_attempts as $user_id => $result) {
                if (!empty($result['exe_weighting']) && intval(
                        $result['exe_weighting']
                    ) != 0
                ) {
                    $my_ranking[$user_id] = $result['exe_result'] / $result['exe_weighting'];
                } else {
                    $my_ranking[$user_id] = 0;
                }
            }
            //if (!empty($my_ranking)) {
            asort($my_ranking);
            $position = count($my_ranking);
            if (!empty($my_ranking)) {
                foreach ($my_ranking as $user_id => $ranking) {
                    if ($my_score >= $ranking) {
                        if ($my_score == $ranking) {
                            $exe_id = $best_attempts[$user_id]['exe_id'];
                            if ($my_exe_id < $exe_id) {
                                $position--;
                            }
                        } else {
                            $position--;
                        }
                    }
                }
            }
            //}
            $return_value = array(
                'position' => $position,
                'count' => count($my_ranking)
            );

            if ($return_string) {
                if (!empty($position) && !empty($my_ranking)) {
                    $return_value = $position . '/' . count($my_ranking);
                } else {
                    $return_value = '-';
                }
            }
            return $return_value;
        }
    }

    /**
     * Gets the position of the score based in a given score (result/weight) and the exe_id based in all attempts
     * (NO Exercises in LPs ) old functionality by attempt
     * @param   float   user score to be compared attention => score/weight
     * @param   int     exe id of the exercise
     * (this is necessary because if 2 students have the same score the one
     * with the minor exe_id will have a best position, just to be fair and FIFO)
     * @param   int     exercise id
     * @param   string  course code
     * @param   int     session id
     * @return  int     the position of the user between his friends in a course (or course within a session)
     */
    public static function get_exercise_result_ranking_by_attempt(
        $my_score,
        $my_exe_id,
        $exercise_id,
        $courseId,
        $session_id = 0,
        $return_string = true
    )
    {
        if (empty($session_id)) {
            $session_id = 0;
        }
        if (is_null($my_score)) {
            return '-';
        }
        $user_results = Event::get_all_exercise_results(
            $exercise_id,
            $courseId,
            $session_id,
            false
        );
        $position_data = array();
        if (empty($user_results)) {
            return 1;
        } else {
            $position = 1;
            $my_ranking = array();
            foreach ($user_results as $result) {
                //print_r($result);
                if (!empty($result['exe_weighting']) && intval(
                        $result['exe_weighting']
                    ) != 0
                ) {
                    $my_ranking[$result['exe_id']] = $result['exe_result'] / $result['exe_weighting'];
                } else {
                    $my_ranking[$result['exe_id']] = 0;
                }
            }
            asort($my_ranking);
            $position = count($my_ranking);
            if (!empty($my_ranking)) {
                foreach ($my_ranking as $exe_id => $ranking) {
                    if ($my_score >= $ranking) {
                        if ($my_score == $ranking) {
                            if ($my_exe_id < $exe_id) {
                                $position--;
                            }
                        } else {
                            $position--;
                        }
                    }
                }
            }
            $return_value = array(
                'position' => $position,
                'count' => count($my_ranking)
            );

            if ($return_string) {
                if (!empty($position) && !empty($my_ranking)) {
                    return $position . '/' . count($my_ranking);
                }
            }
            return $return_value;
        }
    }

    /**
     * Get the best attempt in a exercise (NO Exercises in LPs )
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array
     */
    public static function get_best_attempt_in_course($exercise_id, $courseId, $session_id)
    {
        $user_results = Event::get_all_exercise_results(
            $exercise_id,
            $courseId,
            $session_id,
            false
        );

        $best_score_data = array();
        $best_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['exe_weighting']) &&
                    intval($result['exe_weighting']) != 0
                ) {
                    $score = $result['exe_result'] / $result['exe_weighting'];
                    if ($score >= $best_score) {
                        $best_score = $score;
                        $best_score_data = $result;
                    }
                }
            }
        }

        return $best_score_data;
    }

    /**
     * Get the best score in a exercise (NO Exercises in LPs )
     * @param int $user_id
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array
     */
    public static function get_best_attempt_by_user(
        $user_id,
        $exercise_id,
        $courseId,
        $session_id
    )
    {
        $user_results = Event::get_all_exercise_results(
            $exercise_id,
            $courseId,
            $session_id,
            false,
            $user_id
        );
        $best_score_data = array();
        $best_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                    $score = $result['exe_result'] / $result['exe_weighting'];
                    if ($score >= $best_score) {
                        $best_score = $score;
                        $best_score_data = $result;
                    }
                }
            }
        }

        return $best_score_data;
    }

    /**
     * Get average score (NO Exercises in LPs )
     * @param    int    exercise id
     * @param    int $courseId
     * @param    int    session id
     * @return    float    Average score
     */
    public static function get_average_score($exercise_id, $courseId, $session_id)
    {
        $user_results = Event::get_all_exercise_results(
            $exercise_id,
            $courseId,
            $session_id
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['exe_weighting']) && intval(
                        $result['exe_weighting']
                    ) != 0
                ) {
                    $score = $result['exe_result'] / $result['exe_weighting'];
                    $avg_score += $score;
                }
            }
            $avg_score = float_format($avg_score / count($user_results), 1);
        }

        return $avg_score;
    }

    /**
     * Get average score by score (NO Exercises in LPs )
     * @param    int    exercise id
     * @param    int $courseId
     * @param    int    session id
     * @return    float    Average score
     */
    public static function get_average_score_by_course($courseId, $session_id)
    {
        $user_results = Event::get_all_exercise_results_by_course(
            $courseId,
            $session_id,
            false
        );
        //echo $course_code.' - '.$session_id.'<br />';
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['exe_weighting']) && intval(
                        $result['exe_weighting']
                    ) != 0
                ) {
                    $score = $result['exe_result'] / $result['exe_weighting'];
                    $avg_score += $score;
                }
            }
            //We asume that all exe_weighting
            $avg_score = ($avg_score / count($user_results));
        }

        return $avg_score;
    }

    /**
     * @param int $user_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return float|int
     */
    public static function get_average_score_by_course_by_user(
        $user_id,
        $courseId,
        $session_id
    )
    {
        $user_results = Event::get_all_exercise_results_by_user(
            $user_id,
            $courseId,
            $session_id
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['exe_weighting']) && intval(
                        $result['exe_weighting']
                    ) != 0
                ) {
                    $score = $result['exe_result'] / $result['exe_weighting'];
                    $avg_score += $score;
                }
            }
            //We asume that all exe_weighting
            //$avg_score = show_score( $avg_score / count($user_results) , $result['exe_weighting']);
            $avg_score = ($avg_score / count($user_results));
        }

        return $avg_score;
    }

    /**
     * Get average score by score (NO Exercises in LPs )
     * @param    int        exercise id
     * @param    int $courseId
     * @param    int        session id
     * @return    float    Best average score
     */
    public static function get_best_average_score_by_exercise(
        $exercise_id,
        $courseId,
        $session_id,
        $user_count
    )
    {
        $user_results = Event::get_best_exercise_results_by_user(
            $exercise_id,
            $courseId,
            $session_id
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['exe_weighting']) && intval($result['exe_weighting']) != 0) {
                    $score = $result['exe_result'] / $result['exe_weighting'];
                    $avg_score += $score;
                }
            }
            //We asume that all exe_weighting
            //$avg_score = show_score( $avg_score / count($user_results) , $result['exe_weighting']);
            //$avg_score = ($avg_score / count($user_results));
            if (!empty($user_count)) {
                $avg_score = float_format($avg_score / $user_count, 1) * 100;
            } else {
                $avg_score = 0;
            }
        }

        return $avg_score;
    }

    /**
     * @param string $course_code
     * @param int $session_id
     *
     * @return array
     */
    public static function get_exercises_to_be_taken($course_code, $session_id)
    {
        $course_info = api_get_course_info($course_code);
        $exercises = self::get_all_exercises($course_info, $session_id);
        $result = array();
        $now = time() + 15 * 24 * 60 * 60;
        foreach ($exercises as $exercise_item) {
            if (isset($exercise_item['end_time']) &&
                !empty($exercise_item['end_time']) &&
                api_strtotime($exercise_item['end_time'], 'UTC') < $now
            ) {
                $result[] = $exercise_item;
            }
        }
        return $result;
    }

    /**
     * Get student results (only in completed exercises) stats by question
     * @param    int $question_id
     * @param    int $exercise_id
     * @param    string $course_code
     * @param    int $session_id
     *
     **/
    public static function get_student_stats_by_question(
        $question_id,
        $exercise_id,
        $course_code,
        $session_id
    )
    {
        $track_exercises = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_EXERCISES
        );
        $track_attempt = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_ATTEMPT
        );

        $question_id = intval($question_id);
        $exercise_id = intval($exercise_id);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);
        $courseId = api_get_course_int_id($course_code);

        $sql = "SELECT MAX(marks) as max, MIN(marks) as min, AVG(marks) as average
    		FROM $track_exercises e
    		INNER JOIN $track_attempt a
    		ON (
    		    a.exe_id = e.exe_id AND
    		    e.c_id = a.c_id AND
    		    e.session_id  = a.session_id
            )
    		WHERE
    		    exe_exo_id 	= $exercise_id AND
                a.c_id = $courseId AND
                e.session_id = $session_id AND
                question_id = $question_id AND
                status = ''
            LIMIT 1";
        $result = Database::query($sql);
        $return = array();
        if ($result) {
            $return = Database::fetch_array($result, 'ASSOC');
        }

        return $return;
    }

    /**
     * Get the correct answer count for a fill blanks question
     *
     * @param int $question_id
     * @param int $exercise_id
     * @return int
     */
    public static function getNumberStudentsFillBlanksAnwserCount(
        $question_id,
        $exercise_id
    )
    {
        $listStudentsId = [];
        $listAllStudentInfo = CourseManager::get_student_list_from_course_code(
            api_get_course_id(),
            true
        );
        foreach ($listAllStudentInfo as $i => $listStudentInfo) {
            $listStudentsId[] = $listStudentInfo['user_id'];
        }

        $listFillTheBlankResult = FillBlanks::getFillTheBlankTabResult(
            $exercise_id,
            $question_id,
            $listStudentsId,
            '1970-01-01',
            '3000-01-01'
        );

        $arrayCount = [];

        foreach ($listFillTheBlankResult as $resultCount) {
            foreach ($resultCount as $index => $count) {
                //this is only for declare the array index per answer
                $arrayCount[$index] = 0;
            }
        }

        foreach ($listFillTheBlankResult as $resultCount) {
            foreach ($resultCount as $index => $count) {
                $count = ($count === 0) ? 1 : 0;
                $arrayCount[$index] += $count;
            }
        }

        return $arrayCount;
    }

    /**
     * @param int $question_id
     * @param int $exercise_id
     * @param string $course_code
     * @param int $session_id
     * @param string $questionType
     * @return int
     */
    public static function get_number_students_question_with_answer_count(
        $question_id,
        $exercise_id,
        $course_code,
        $session_id,
        $questionType = ''
    )
    {
        $track_exercises = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_EXERCISES
        );
        $track_attempt = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_ATTEMPT
        );
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserSession = Database::get_main_table(
            TABLE_MAIN_SESSION_COURSE_USER
        );

        $question_id = intval($question_id);
        $exercise_id = intval($exercise_id);
        $courseId = api_get_course_int_id($course_code);
        $session_id = intval($session_id);

        if ($questionType == FILL_IN_BLANKS) {
            $listStudentsId = array();
            $listAllStudentInfo = CourseManager::get_student_list_from_course_code(
                api_get_course_id(),
                true
            );
            foreach ($listAllStudentInfo as $i => $listStudentInfo) {
                $listStudentsId[] = $listStudentInfo['user_id'];
            }

            $listFillTheBlankResult = FillBlanks::getFillTheBlankTabResult(
                $exercise_id,
                $question_id,
                $listStudentsId,
                '1970-01-01',
                '3000-01-01'
            );

            return FillBlanks::getNbResultFillBlankAll($listFillTheBlankResult);
        }

        if (empty($session_id)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id  = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = " . STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON cu.c_id = c.id AND cu.user_id = exe_user_id";
            $courseConditionWhere = " AND cu.status = 0 ";
        }

        $sql = "SELECT DISTINCT exe_user_id
    		FROM $track_exercises e
    		INNER JOIN $track_attempt a
    		ON (
    		    a.exe_id = e.exe_id AND
    		    e.c_id = a.c_id AND
    		    e.session_id  = a.session_id
            )
            INNER JOIN $courseTable c
            ON (c.id = a.c_id)
    		$courseCondition
    		WHERE
    		    exe_exo_id = $exercise_id AND
                a.c_id = $courseId AND
                e.session_id = $session_id AND
                question_id = $question_id AND
                answer <> '0' AND
                e.status = ''
                $courseConditionWhere
            ";
        $result = Database::query($sql);
        $return = 0;
        if ($result) {
            $return = Database::num_rows($result);
        }
        return $return;
    }

    /**
     * @param int $answer_id
     * @param int $question_id
     * @param int $exercise_id
     * @param string $course_code
     * @param int $session_id
     *
     * @return int
     */
    public static function get_number_students_answer_hotspot_count(
        $answer_id,
        $question_id,
        $exercise_id,
        $course_code,
        $session_id
    )
    {
        $track_exercises = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_EXERCISES
        );
        $track_hotspot = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_HOTSPOT
        );
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $courseUserSession = Database::get_main_table(
            TABLE_MAIN_SESSION_COURSE_USER
        );

        $question_id = intval($question_id);
        $answer_id = intval($answer_id);
        $exercise_id = intval($exercise_id);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);

        if (empty($session_id)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id  = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = " . STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON cu.c_id = c.id AND cu.user_id = exe_user_id";
            $courseConditionWhere = " AND cu.status = 0 ";
        }

        $sql = "SELECT DISTINCT exe_user_id
    		FROM $track_exercises e
    		INNER JOIN $track_hotspot a
    		ON (a.hotspot_exe_id = e.exe_id)
    		INNER JOIN $courseTable c
    		ON (hotspot_course_code = c.code)
    		$courseCondition
    		WHERE
    		    exe_exo_id              = $exercise_id AND
                a.hotspot_course_code 	= '$course_code' AND
                e.session_id            = $session_id AND
                hotspot_answer_id       = $answer_id AND
                hotspot_question_id     = $question_id AND
                hotspot_correct         =  1 AND
                e.status                = ''
                $courseConditionWhere
            ";

        $result = Database::query($sql);
        $return = 0;
        if ($result) {
            $return = Database::num_rows($result);
        }
        return $return;
    }

    /**
     * @param int $answer_id
     * @param int $question_id
     * @param int $exercise_id
     * @param string $course_code
     * @param int $session_id
     * @param string $question_type
     * @param string $correct_answer
     * @param string $current_answer
     * @return int
     */
    public static function get_number_students_answer_count(
        $answer_id,
        $question_id,
        $exercise_id,
        $course_code,
        $session_id,
        $question_type = null,
        $correct_answer = null,
        $current_answer = null
    )
    {
        $track_exercises = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_EXERCISES
        );
        $track_attempt = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_ATTEMPT
        );
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseUserSession = Database::get_main_table(
            TABLE_MAIN_SESSION_COURSE_USER
        );

        $question_id = intval($question_id);
        $answer_id = intval($answer_id);
        $exercise_id = intval($exercise_id);
        $courseId = api_get_course_int_id($course_code);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);

        switch ($question_type) {
            case FILL_IN_BLANKS:
                $answer_condition = "";
                $select_condition = " e.exe_id, answer ";
                break;
            case MATCHING:
                //no break
            case MATCHING_DRAGGABLE:
                //no break
            default:
                $answer_condition = " answer = $answer_id AND ";
                $select_condition = " DISTINCT exe_user_id ";
        }

        if (empty($session_id)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id  = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = " . STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON cu.c_id = a.c_id AND cu.user_id = exe_user_id";
            $courseConditionWhere = " AND cu.status = 0 ";
        }

        $sql = "SELECT $select_condition
    		FROM $track_exercises e
    		INNER JOIN $track_attempt a
    		ON (
    		    a.exe_id = e.exe_id AND
    		    e.c_id = a.c_id AND
    		    e.session_id  = a.session_id
            )
            INNER JOIN $courseTable c
            ON c.id = a.c_id
    		$courseCondition
    		WHERE
    		    exe_exo_id = $exercise_id AND
                a.c_id = $courseId AND
                e.session_id = $session_id AND
                $answer_condition
                question_id = $question_id AND
                e.status = ''
                $courseConditionWhere
            ";
        $result = Database::query($sql);
        $return = 0;
        if ($result) {
            $good_answers = 0;
            switch ($question_type) {
                case FILL_IN_BLANKS:
                    while ($row = Database::fetch_array($result, 'ASSOC')) {
                        $fill_blank = self::check_fill_in_blanks(
                            $correct_answer,
                            $row['answer'],
                            $current_answer
                        );
                        if (isset($fill_blank[$current_answer]) && $fill_blank[$current_answer] == 1) {
                            $good_answers++;
                        }
                    }
                    return $good_answers;
                    break;
                case MATCHING:
                    //no break
                case MATCHING_DRAGGABLE:
                    //no break
                default:
                    $return = Database::num_rows($result);
            }
        }

        return $return;
    }

    /**
     * @param array $answer
     * @param string $user_answer
     * @return array
     */
    public static function check_fill_in_blanks($answer, $user_answer, $current_answer)
    {
        // the question is encoded like this
        // [A] B [C] D [E] F::10,10,10@1
        // number 1 before the "@" means that is a switchable fill in blank question
        // [A] B [C] D [E] F::10,10,10@ or  [A] B [C] D [E] F::10,10,10
        // means that is a normal fill blank question
        // first we explode the "::"
        $pre_array = explode('::', $answer);
        // is switchable fill blank or not
        $last = count($pre_array) - 1;
        $is_set_switchable = explode('@', $pre_array[$last]);
        $switchable_answer_set = false;
        if (isset ($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
            $switchable_answer_set = true;
        }
        $answer = '';
        for ($k = 0; $k < $last; $k++) {
            $answer .= $pre_array[$k];
        }
        // splits weightings that are joined with a comma
        $answerWeighting = explode(',', $is_set_switchable[0]);

        // we save the answer because it will be modified
        //$temp = $answer;
        $temp = $answer;

        $answer = '';
        $j = 0;
        //initialise answer tags
        $user_tags = $correct_tags = $real_text = array();
        // the loop will stop at the end of the text
        while (1) {
            // quits the loop if there are no more blanks (detect '[')
            if (($pos = api_strpos($temp, '[')) === false) {
                // adds the end of the text
                $answer = $temp;
                $real_text[] = $answer;
                break; //no more "blanks", quit the loop
            }
            // adds the piece of text that is before the blank
            //and ends with '[' into a general storage array
            $real_text[] = api_substr($temp, 0, $pos + 1);
            $answer .= api_substr($temp, 0, $pos + 1);
            //take the string remaining (after the last "[" we found)
            $temp = api_substr($temp, $pos + 1);
            // quit the loop if there are no more blanks, and update $pos to the position of next ']'
            if (($pos = api_strpos($temp, ']')) === false) {
                // adds the end of the text
                $answer .= $temp;
                break;
            }

            $str = $user_answer;

            preg_match_all('#\[([^[]*)\]#', $str, $arr);
            $str = str_replace('\r\n', '', $str);
            $choices = $arr[1];
            $choice = [];
            $check = false;
            $i = 0;
            foreach ($choices as $item) {
                if ($current_answer === $item) {
                    $check = true;
                }
                if ($check) {
                    $choice[] = $item;
                    $i++;
                }
                if ($i == 3) {
                    break;
                }
            }
            $tmp = api_strrpos($choice[$j], ' / ');

            if ($tmp !== false) {
                $choice[$j] = api_substr($choice[$j], 0, $tmp);
            }

            $choice[$j] = trim($choice[$j]);

            //Needed to let characters ' and " to work as part of an answer
            $choice[$j] = stripslashes($choice[$j]);

            $user_tags[] = api_strtolower($choice[$j]);
            //put the contents of the [] answer tag into correct_tags[]
            $correct_tags[] = api_strtolower(api_substr($temp, 0, $pos));
            $j++;
            $temp = api_substr($temp, $pos + 1);
        }

        $answer = '';
        $real_correct_tags = $correct_tags;
        $chosen_list = array();
        $good_answer = array();

        for ($i = 0; $i < count($real_correct_tags); $i++) {
            if (!$switchable_answer_set) {
                //needed to parse ' and " characters
                $user_tags[$i] = stripslashes($user_tags[$i]);
                if ($correct_tags[$i] == $user_tags[$i]) {
                    $good_answer[$correct_tags[$i]] = 1;
                } elseif (!empty ($user_tags[$i])) {
                    $good_answer[$correct_tags[$i]] = 0;
                } else {
                    $good_answer[$correct_tags[$i]] = 0;
                }
            } else {
                // switchable fill in the blanks
                if (in_array($user_tags[$i], $correct_tags)) {
                    $correct_tags = array_diff($correct_tags, $chosen_list);
                    $good_answer[$correct_tags[$i]] = 1;
                } elseif (!empty ($user_tags[$i])) {
                    $good_answer[$correct_tags[$i]] = 0;
                } else {
                    $good_answer[$correct_tags[$i]] = 0;
                }
            }
            // adds the correct word, followed by ] to close the blank
            $answer .= ' / <font color="green"><b>' . $real_correct_tags[$i] . '</b></font>]';
            if (isset ($real_text[$i + 1])) {
                $answer .= $real_text[$i + 1];
            }
        }

        return $good_answer;
    }

    /**
     * @param int $exercise_id
     * @param string $course_code
     * @param int $session_id
     * @return int
     */
    public static function get_number_students_finish_exercise(
        $exercise_id,
        $course_code,
        $session_id
    )
    {
        $track_exercises = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_EXERCISES
        );
        $track_attempt = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_ATTEMPT
        );

        $exercise_id = intval($exercise_id);
        $course_code = Database::escape_string($course_code);
        $session_id = intval($session_id);

        $sql = "SELECT DISTINCT exe_user_id
    		FROM $track_exercises e
    		INNER JOIN $track_attempt a ON (a.exe_id = e.exe_id)
    		WHERE
    		    exe_exo_id 	 = $exercise_id AND
    			course_code  = '$course_code' AND
    			e.session_id = $session_id AND
    			status = ''";
        $result = Database::query($sql);
        $return = 0;
        if ($result) {
            $return = Database::num_rows($result);

        }
        return $return;
    }

    /**
     * @param string $in_name is the name and the id of the <select>
     * @param string $in_default default value for option
     * @param string $in_onchange
     * @return string the html code of the <select>
     */
    public static function displayGroupMenu($in_name, $in_default, $in_onchange = "")
    {
        // check the default value of option
        $tabSelected = array($in_default => " selected='selected' ");
        $res = "";
        $res .= "<select name='$in_name' id='$in_name' onchange='" . $in_onchange . "' >";
        $res .= "<option value='-1'" . $tabSelected["-1"] . ">-- " . get_lang(
                'AllGroups'
            ) . " --</option>";
        $res .= "<option value='0'" . $tabSelected["0"] . ">- " . get_lang(
                'NotInAGroup'
            ) . " -</option>";
        $tabGroups = GroupManager::get_group_list();
        $currentCatId = 0;
        for ($i = 0; $i < count($tabGroups); $i++) {
            $tabCategory = GroupManager::get_category_from_group(
                $tabGroups[$i]['iid']
            );
            if ($tabCategory["id"] != $currentCatId) {
                $res .= "<option value='-1' disabled='disabled'>" . $tabCategory["title"] . "</option>";
                $currentCatId = $tabCategory["id"];
            }
            $res .= "<option " . $tabSelected[$tabGroups[$i]["id"]] . "style='margin-left:40px' value='" . $tabGroups[$i]["id"] . "'>" . $tabGroups[$i]["name"] . "</option>";
        }
        $res .= "</select>";
        return $res;
    }

    /**
     * @param int $exe_id
     */
    public static function create_chat_exercise_session($exe_id)
    {
        if (!isset($_SESSION['current_exercises'])) {
            $_SESSION['current_exercises'] = array();
        }
        $_SESSION['current_exercises'][$exe_id] = true;
    }

    /**
     * @param int $exe_id
     */
    public static function delete_chat_exercise_session($exe_id)
    {
        if (isset($_SESSION['current_exercises'])) {
            $_SESSION['current_exercises'][$exe_id] = false;
        }
    }

    /**
     * Display the exercise results
     * @param Exercise $objExercise
     * @param int $exe_id
     * @param bool $save_user_result save users results (true) or just show the results (false)
     */
    public static function display_question_list_by_attempt(
        $objExercise,
        $exe_id,
        $save_user_result = false
    ) {
        global $origin;

        // Getting attempt info
        $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id(
            $exe_id
        );

        // Getting question list
        $question_list = array();
        if (!empty($exercise_stat_info['data_tracking'])) {
            $question_list = explode(',', $exercise_stat_info['data_tracking']);
        } else {
            // Try getting the question list only if save result is off
            if ($save_user_result == false) {
                $question_list = $objExercise->get_validated_question_list();
            }
        }

        $counter = 1;
        $total_score = $total_weight = 0;
        $exercise_content = null;

        // Hide results
        $show_results = false;
        $show_only_score = false;

        if ($objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS) {
            $show_results = true;
        }

        if (in_array(
            $objExercise->results_disabled,
            array(
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES
            )
        )
        ) {
            $show_only_score = true;
        }

        // Not display expected answer, but score, and feedback
        $show_all_but_expected_answer = false;
        if ($objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_ONLY &&
            $objExercise->feedback_type == EXERCISE_FEEDBACK_TYPE_END
        ) {
            $show_all_but_expected_answer = true;
            $show_results = true;
            $show_only_score = false;
        }

        $showTotalScoreAndUserChoicesInLastAttempt = true;

        if ($objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
            $show_only_score = true;
            $show_results = true;
            if ($objExercise->attempts > 0) {
                $attempts = Event::getExerciseResultsByUser(
                    api_get_user_id(),
                    $objExercise->id,
                    api_get_course_int_id(),
                    api_get_session_id(),
                    $exercise_stat_info['orig_lp_id'],
                    $exercise_stat_info['orig_lp_item_id'],
                    'desc'
                );

                if ($attempts) {
                    $numberAttempts = count($attempts);
                } else {
                    $numberAttempts = 0;
                }

                if ($save_user_result) {
                    $numberAttempts++;
                }
                if ($numberAttempts >= $objExercise->attempts) {
                    $show_results = true;
                    $show_only_score = false;
                    $showTotalScoreAndUserChoicesInLastAttempt = true;
                } else {
                    $showTotalScoreAndUserChoicesInLastAttempt = false;
                }
            }
        }

        if ($show_results || $show_only_score) {
            $user_info = api_get_user_info($exercise_stat_info['exe_user_id']);
            //Shows exercise header
            echo $objExercise->show_exercise_result_header(
                $user_info,
                api_convert_and_format_date(
                    $exercise_stat_info['start_date'],
                    DATE_TIME_FORMAT_LONG
                ),
                $exercise_stat_info['duration'],
                $exercise_stat_info['user_ip']
            );
        }

        // Display text when test is finished #4074 and for LP #4227
        $end_of_message = $objExercise->selectTextWhenFinished();
        if (!empty($end_of_message)) {
            Display::display_normal_message($end_of_message, false);
            echo "<div class='clear'>&nbsp;</div>";
        }

        $question_list_answers = array();
        $media_list = array();
        $category_list = array();

        // Loop over all question to show results for each of them, one by one
        if (!empty($question_list)) {
            foreach ($question_list as $questionId) {

                // creates a temporary Question object
                $objQuestionTmp = Question::read($questionId);

                // This variable came from exercise_submit_modal.php
                ob_start();

                // We're inside *one* question. Go through each possible answer for this question
                $result = $objExercise->manage_answer(
                    $exercise_stat_info['exe_id'],
                    $questionId,
                    null,
                    'exercise_result',
                    [],
                    $save_user_result,
                    true,
                    $show_results,
                    $objExercise->selectPropagateNeg(),
                    [],
                    $showTotalScoreAndUserChoicesInLastAttempt
                );

                if (empty($result)) {
                    continue;
                }

                $total_score += $result['score'];
                $total_weight += $result['weight'];

                $question_list_answers[] = array(
                    'question' => $result['open_question'],
                    'answer' => $result['open_answer'],
                    'answer_type' => $result['answer_type']
                );

                $my_total_score = $result['score'];
                $my_total_weight = $result['weight'];

                // Category report
                $category_was_added_for_this_test = false;

                if (isset($objQuestionTmp->category) && !empty($objQuestionTmp->category)) {
                    if (!isset($category_list[$objQuestionTmp->category]['score'])) {
                        $category_list[$objQuestionTmp->category]['score'] = 0;
                    }
                    if (!isset($category_list[$objQuestionTmp->category]['total'])) {
                        $category_list[$objQuestionTmp->category]['total'] = 0;
                    }
                    $category_list[$objQuestionTmp->category]['score'] += $my_total_score;
                    $category_list[$objQuestionTmp->category]['total'] += $my_total_weight;
                    $category_was_added_for_this_test = true;
                }

                if (isset($objQuestionTmp->category_list) && !empty($objQuestionTmp->category_list)) {
                    foreach ($objQuestionTmp->category_list as $category_id) {
                        $category_list[$category_id]['score'] += $my_total_score;
                        $category_list[$category_id]['total'] += $my_total_weight;
                        $category_was_added_for_this_test = true;
                    }
                }

                // No category for this question!
                if ($category_was_added_for_this_test == false) {
                    if (!isset($category_list['none']['score'])) {
                        $category_list['none']['score'] = 0;
                    }
                    if (!isset($category_list['none']['total'])) {
                        $category_list['none']['total'] = 0;
                    }

                    $category_list['none']['score'] += $my_total_score;
                    $category_list['none']['total'] += $my_total_weight;
                }

                if ($objExercise->selectPropagateNeg() == 0 && $my_total_score < 0
                ) {
                    $my_total_score = 0;
                }

                $comnt = null;
                if ($show_results) {
                    $comnt = Event::get_comments($exe_id, $questionId);
                    if (!empty($comnt)) {
                        echo '<b>' . get_lang('Feedback') . '</b>';
                        echo '<div id="question_feedback">' . $comnt . '</div>';
                    }
                }

                if ($show_results) {
                    $score = array(
                        'result' => get_lang('Score') . " : " . self::show_score(
                                $my_total_score,
                                $my_total_weight,
                                false,
                                true
                            ),
                        'pass' => $my_total_score >= $my_total_weight ? true : false,
                        'score' => $my_total_score,
                        'weight' => $my_total_weight,
                        'comments' => $comnt,
                    );
                } else {
                    $score = array();
                }

                $contents = ob_get_clean();

                $question_content = '';
                if ($show_results) {
                    $question_content = '<div class="question_row_answer">';
                    // Shows question title an description
                    $question_content .= $objQuestionTmp->return_header(
                        null,
                        $counter,
                        $score
                    );
                }
                $counter++;

                $question_content .= $contents;

                if ($show_results) {
                    $question_content .= '</div>';
                }

                $exercise_content .= $question_content;

            } // end foreach() block that loops over all questions
        }

        $total_score_text = null;

        if ($origin != 'learnpath') {
            if ($show_results || $show_only_score) {
                $total_score_text .= '<div class="question_row_score">';
                $total_score_text .= self::get_question_ribbon(
                    $objExercise,
                    $total_score,
                    $total_weight,
                    true
                );
                $total_score_text .= '</div>';
            }
        }

        if (!empty($category_list) && ($show_results || $show_only_score)) {
            //Adding total
            $category_list['total'] = array(
                'score' => $total_score,
                'total' => $total_weight
            );
            echo TestCategory::get_stats_table_by_attempt(
                $objExercise->id,
                $category_list
            );
        }

        if ($show_all_but_expected_answer) {
            $exercise_content .= "<div class='normal-message'>" . get_lang(
                    "ExerciseWithFeedbackWithoutCorrectionComment"
                ) . "</div>";
        }
        // Remove audio auto play from questions on results page - refs BT#7939
        $exercise_content = preg_replace(
            ['/autoplay[\=\".+\"]+/', '/autostart[\=\".+\"]+/'],
            '',
            $exercise_content
        );

        echo $total_score_text;
        echo $exercise_content;

        if (!$show_only_score) {
            echo $total_score_text;
        }

        if ($save_user_result) {

            // Tracking of results
            $learnpath_id = $exercise_stat_info['orig_lp_id'];
            $learnpath_item_id = $exercise_stat_info['orig_lp_item_id'];
            $learnpath_item_view_id = $exercise_stat_info['orig_lp_item_view_id'];

            if (api_is_allowed_to_session_edit()) {
                Event::update_event_exercice(
                    $exercise_stat_info['exe_id'],
                    $objExercise->selectId(),
                    $total_score,
                    $total_weight,
                    api_get_session_id(),
                    $learnpath_id,
                    $learnpath_item_id,
                    $learnpath_item_view_id,
                    $exercise_stat_info['exe_duration'],
                    $question_list,
                    '',
                    array()
                );
            }

            // Send notification ..
            if (!api_is_allowed_to_edit(null, true) && !api_is_excluded_user_type()
            ) {
                if (api_get_course_setting(
                        'email_alert_manager_on_new_quiz'
                    ) == 1
                ) {
                    $objExercise->send_mail_notification_for_exam(
                        $question_list_answers,
                        $origin,
                        $exe_id
                    );
                }
                $objExercise->send_notification_for_open_questions(
                    $question_list_answers,
                    $origin,
                    $exe_id
                );
                $objExercise->send_notification_for_oral_questions(
                    $question_list_answers,
                    $origin,
                    $exe_id
                );
            }
        }
    }

    /**
     * @param Exercise $objExercise
     * @param float $score
     * @param float $weight
     * @param bool $check_pass_percentage
     * @return string
     */
    public static function get_question_ribbon(
        $objExercise,
        $score,
        $weight,
        $check_pass_percentage = false
    )
    {
        $ribbon = '<div class="title-score">';
        if ($check_pass_percentage) {
            $is_success = self::is_success_exercise_result(
                $score,
                $weight,
                $objExercise->selectPassPercentage()
            );
            // Color the final test score if pass_percentage activated
            $ribbon_total_success_or_error = "";
            if (self::is_pass_pourcentage_enabled(
                $objExercise->selectPassPercentage()
            )
            ) {
                if ($is_success) {
                    $ribbon_total_success_or_error = ' ribbon-total-success';
                } else {
                    $ribbon_total_success_or_error = ' ribbon-total-error';
                }
            }
            $ribbon .= '<div class="total ' . $ribbon_total_success_or_error . '">';
        } else {
            $ribbon .= '<div class="total">';
        }
        $ribbon .= '<h3>' . get_lang('YourTotalScore') . ":&nbsp;";
        $ribbon .= self::show_score($score, $weight, false, true);
        $ribbon .= '</h3>';
        $ribbon .= '</div>';
        if ($check_pass_percentage) {
            $ribbon .= self::show_success_message(
                $score,
                $weight,
                $objExercise->selectPassPercentage()
            );
        }
        $ribbon .= '</div>';

        return $ribbon;
    }

    /**
     * @param int $countLetter
     * @return mixed
     */
    public static function detectInputAppropriateClass($countLetter)
    {
        $limits = array(
            0 => 'input-mini',
            10 => 'input-mini',
            15 => 'input-medium',
            20 => 'input-xlarge',
            40 => 'input-xlarge',
            60 => 'input-xxlarge',
            100 => 'input-xxlarge',
            200 => 'input-xxlarge',
        );

        foreach ($limits as $size => $item) {
            if ($countLetter <= $size) {
                return $item;
            }
        }
        return $limits[0];
    }
}
