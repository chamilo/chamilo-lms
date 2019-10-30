<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

/**
 * Class ExerciseLib
 * shows a question and its answers.
 *
 * @author Olivier Brouckaert <oli.brouckaert@skynet.be>
 * @author Hubert Borderiou 2011-10-21
 * @author ivantcholakov2009-07-20
 * @author Julio Montoya
 */
class ExerciseLib
{
    /**
     * Shows a question.
     *
     * @param Exercise $exercise
     * @param int      $questionId     $questionId question id
     * @param bool     $only_questions if true only show the questions, no exercise title
     * @param bool     $origin         i.e = learnpath
     * @param string   $current_item   current item from the list of questions
     * @param bool     $show_title
     * @param bool     $freeze
     * @param array    $user_choice
     * @param bool     $show_comment
     * @param bool     $show_answers
     *
     * @throws \Exception
     *
     * @return bool|int
     */
    public static function showQuestion(
        $exercise,
        $questionId,
        $only_questions = false,
        $origin = false,
        $current_item = '',
        $show_title = true,
        $freeze = false,
        $user_choice = [],
        $show_comment = false,
        $show_answers = false,
        $show_icon = false
    ) {
        $course_id = $exercise->course_id;
        $exerciseId = $exercise->iId;

        if (empty($course_id)) {
            return '';
        }
        $course = $exercise->course;

        // Change false to true in the following line to enable answer hinting
        $debug_mark_answer = $show_answers;
        // Reads question information
        if (!$objQuestionTmp = Question::read($questionId, $course)) {
            // Question not found
            return false;
        }

        if ($exercise->getFeedbackType() != EXERCISE_FEEDBACK_TYPE_END) {
            $show_comment = false;
        }

        $answerType = $objQuestionTmp->selectType();
        $pictureName = $objQuestionTmp->getPictureFilename();
        $s = '';
        if ($answerType != HOT_SPOT &&
            $answerType != HOT_SPOT_DELINEATION &&
            $answerType != ANNOTATION
        ) {
            // Question is not a hotspot
            if (!$only_questions) {
                $questionDescription = $objQuestionTmp->selectDescription();
                if ($show_title) {
                    if ($exercise->display_category_name) {
                        TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    }
                    $titleToDisplay = $objQuestionTmp->getTitleToDisplay($current_item);
                    if ($answerType == READING_COMPREHENSION) {
                        // In READING_COMPREHENSION, the title of the question
                        // contains the question itself, which can only be
                        // shown at the end of the given time, so hide for now
                        $titleToDisplay = Display::div(
                            $current_item.'. '.get_lang('Reading comprehension'),
                            ['class' => 'question_title']
                        );
                    }
                    echo $titleToDisplay;
                }
                if (!empty($questionDescription) && $answerType != READING_COMPREHENSION) {
                    echo Display::div(
                        $questionDescription,
                        ['class' => 'question_description']
                    );
                }
            }

            if (in_array($answerType, [FREE_ANSWER, ORAL_EXPRESSION]) && $freeze) {
                return '';
            }

            echo '<div class="question_options">';
            // construction of the Answer object (also gets all answers details)
            $objAnswerTmp = new Answer($questionId, $course_id, $exercise);
            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
            $quizQuestionOptions = Question::readQuestionOption($questionId, $course_id);

            // For "matching" type here, we need something a little bit special
            // because the match between the suggestions and the answers cannot be
            // done easily (suggestions and answers are in the same table), so we
            // have to go through answers first (elems with "correct" value to 0).
            $select_items = [];
            //This will contain the number of answers on the left side. We call them
            // suggestions here, for the sake of comprehensions, while the ones
            // on the right side are called answers
            $num_suggestions = 0;

            switch ($answerType) {
                case MATCHING:
                case DRAGGABLE:
                case MATCHING_DRAGGABLE:
                    if ($answerType == DRAGGABLE) {
                        $isVertical = $objQuestionTmp->extra == 'v';
                        $s .= '
                            <div class="col-md-12 ui-widget ui-helper-clearfix">
                                <div class="clearfix">
                                <ul class="exercise-draggable-answer '.($isVertical ? '' : 'list-inline').'"
                                    id="question-'.$questionId.'" data-question="'.$questionId.'">
                        ';
                    } else {
                        $s .= '<div id="drag'.$questionId.'_question" class="drag_question">
                               <table class="data_table">';
                    }

                    // Iterate through answers
                    $x = 1;
                    //mark letters for each answer
                    $letter = 'A';
                    $answer_matching = [];
                    $cpt1 = [];
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

                    $user_choice_array_position = [];
                    if (!empty($user_choice)) {
                        foreach ($user_choice as $item) {
                            $user_choice_array_position[$item['position']] = $item['answer'];
                        }
                    }

                    $num_suggestions = ($nbrAnswers - $x) + 1;
                    break;
                case FREE_ANSWER:
                    $fck_content = isset($user_choice[0]) && !empty($user_choice[0]['answer']) ? $user_choice[0]['answer'] : null;
                    $form = new FormValidator('free_choice_'.$questionId);
                    $config = [
                        'ToolbarSet' => 'TestFreeAnswer',
                        'id' => 'choice['.$questionId.']',
                    ];
                    $form->addHtmlEditor(
                        'choice['.$questionId.']',
                        null,
                        false,
                        false,
                        $config
                    );
                    $form->setDefaults(["choice[".$questionId."]" => $fck_content]);
                    $s .= $form->returnForm();
                    break;
                case ORAL_EXPRESSION:
                    // Add nanog
                    if (api_get_setting('enable_record_audio') === 'true') {
                        //@todo pass this as a parameter
                        global $exercise_stat_info;
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

                    $form = new FormValidator('free_choice_'.$questionId);
                    $config = ['ToolbarSet' => 'TestFreeAnswer'];

                    $form->addHtml('<div id="'.'hide_description_'.$questionId.'_options" style="display: none;">');
                    $form->addHtmlEditor(
                        "choice[$questionId]",
                        null,
                        false,
                        false,
                        $config
                    );
                    $form->addHtml('</div>');
                    $s .= $form->returnForm();
                    break;
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
                    ['style' => 'text-align:left;']
                );
            } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                $header = Display::tag('th', get_lang('Options'), ['width' => '50%']);
                echo "
                <script>
                    function RadioValidator(question_id, answer_id) 
                    {
                        var ShowAlert = '';
                        var typeRadioB = '';
                        var AllFormElements = window.document.getElementById('exercise_form').elements;
                    
                        for (i = 0; i < AllFormElements.length; i++) {
                            if (AllFormElements[i].type == 'radio') {
                                var ThisRadio = AllFormElements[i].name;
                                var ThisChecked = 'No';
                                var AllRadioOptions = document.getElementsByName(ThisRadio);
                              
                                for (x = 0; x < AllRadioOptions.length; x++) {
                                     if (AllRadioOptions[x].checked && ThisChecked == 'No') {
                                         ThisChecked = 'Yes';
                                         break;
                                     } 
                                }  
                              
                                var AlreadySearched = ShowAlert.indexOf(ThisRadio);                                
                                if (ThisChecked == 'No' && AlreadySearched == -1) { 
                                    ShowAlert = ShowAlert + ThisRadio;
                                }     
                            }
                        }
                        if (ShowAlert != '') {
                    
                        } else {
                            $('.question-validate-btn').removeAttr('disabled');
                        }
                    }
                    
                    function handleRadioRow(event, question_id, answer_id) {
                        var t = event.target;
                        if (t && t.tagName == 'INPUT')
                            return;
                        while (t && t.tagName != 'TD') {
                            t = t.parentElement;
                        }
                        var r = t.getElementsByTagName('INPUT')[0];
                        r.click();
                        RadioValidator(question_id, answer_id);
                    }
                    
                    $(function() {
                        var ShowAlert = '';
                        var typeRadioB = '';
                        var question_id = $('input[name=question_id]').val();
                        var AllFormElements = window.document.getElementById('exercise_form').elements;
                    
                        for (i = 0; i < AllFormElements.length; i++) {
                            if (AllFormElements[i].type == 'radio') {
                                var ThisRadio = AllFormElements[i].name;
                                var ThisChecked = 'No';
                                var AllRadioOptions = document.getElementsByName(ThisRadio);
                                
                                for (x = 0; x < AllRadioOptions.length; x++) {
                                    if (AllRadioOptions[x].checked && ThisChecked == 'No') {
                                        ThisChecked = \"Yes\";
                                        break;
                                    }
                                }
                                
                                var AlreadySearched = ShowAlert.indexOf(ThisRadio);                                
                                if (ThisChecked == 'No' && AlreadySearched == -1) { 
                                    ShowAlert = ShowAlert + ThisRadio;
                                }
                            }
                        }
                        
                        if (ShowAlert != '') {
                             $('.question-validate-btn').attr('disabled', 'disabled');
                        } else {
                            $('.question-validate-btn').removeAttr('disabled');
                        }                    
                    });
                </script>";

                foreach ($objQuestionTmp->optionsTitle as $item) {
                    if (in_array($item, $objQuestionTmp->optionsTitle)) {
                        $properties = [];
                        if ($item === 'Answers') {
                            $properties['colspan'] = 2;
                            $properties['style'] = 'background-color: #F56B2A; color: #ffffff;';
                        } elseif ($item == 'DegreeOfCertaintyThatMyAnswerIsCorrect') {
                            $properties['colspan'] = 6;
                            $properties['style'] = 'background-color: #330066; color: #ffffff;';
                        }
                        $header .= Display::tag('th', get_lang($item), $properties);
                    } else {
                        $header .= Display::tag('th', $item);
                    }
                }

                if ($show_comment) {
                    $header .= Display::tag('th', get_lang('Feedback'));
                }

                $s .= '<table class="data_table">';
                $s .= Display::tag('tr', $header, ['style' => 'text-align:left;']);

                // ajout de la 2eme ligne d'entête pour true/falss et les pourcentages de certitude
                $header1 = Display::tag('th', '&nbsp;');
                $cpt1 = 0;
                foreach ($objQuestionTmp->options as $item) {
                    $colorBorder1 = ($cpt1 == (count($objQuestionTmp->options) - 1))
                        ? '' : 'border-right: solid #FFFFFF 1px;';
                    if ($item === 'True' || $item === 'False') {
                        $header1 .= Display::tag(
                            'th',
                            get_lang($item),
                            ['style' => 'background-color: #F7C9B4; color: black;'.$colorBorder1]
                        );
                    } else {
                        $header1 .= Display::tag(
                            'th',
                            $item,
                            ['style' => 'background-color: #e6e6ff; color: black;padding:5px; '.$colorBorder1]
                        );
                    }
                    $cpt1++;
                }
                if ($show_comment) {
                    $header1 .= Display::tag('th', '&nbsp;');
                }

                $s .= Display::tag('tr', $header1);

                // add explanation
                $header2 = Display::tag('th', '&nbsp;');
                $descriptionList = [
                    get_lang('I don\'t know the answer and I\'ve picked at random'),
                    get_lang('I am very unsure'),
                    get_lang('I am unsure'),
                    get_lang('I am pretty sure'),
                    get_lang('I am almost 100% sure'),
                    get_lang('I am totally sure'),
                ];
                $counter2 = 0;
                foreach ($objQuestionTmp->options as $item) {
                    if ($item == 'True' || $item == 'False') {
                        $header2 .= Display::tag('td',
                            '&nbsp;',
                            ['style' => 'background-color: #F7E1D7; color: black;border-right: solid #FFFFFF 1px;']);
                    } else {
                        $color_border2 = ($counter2 == (count($objQuestionTmp->options) - 1)) ?
                            '' : 'border-right: solid #FFFFFF 1px;font-size:11px;';
                        $header2 .= Display::tag(
                            'td',
                            nl2br($descriptionList[$counter2]),
                            ['style' => 'background-color: #EFEFFC; color: black; width: 110px; text-align:center; 
                                vertical-align: top; padding:5px; '.$color_border2]);
                        $counter2++;
                    }
                }
                if ($show_comment) {
                    $header2 .= Display::tag('th', '&nbsp;');
                }
                $s .= Display::tag('tr', $header2);
            }

            if ($show_comment) {
                if (in_array(
                    $answerType,
                    [
                        MULTIPLE_ANSWER,
                        MULTIPLE_ANSWER_COMBINATION,
                        UNIQUE_ANSWER,
                        UNIQUE_ANSWER_IMAGE,
                        UNIQUE_ANSWER_NO_OPTION,
                        GLOBAL_MULTIPLE_ANSWER,
                    ]
                )) {
                    $header = Display::tag('th', get_lang('Options'));
                    if ($exercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_END) {
                        $header .= Display::tag('th', get_lang('Feedback'));
                    }
                    $s .= '<table class="table table-hover table-striped">';
                    $s .= Display::tag(
                        'tr',
                        $header,
                        ['style' => 'text-align:left;']
                    );
                }
            }

            $matching_correct_answer = 0;
            $userChoiceList = [];
            if (!empty($user_choice)) {
                foreach ($user_choice as $item) {
                    $userChoiceList[] = $item['answer'];
                }
            }

            $hidingClass = '';
            if ($answerType == READING_COMPREHENSION) {
                $objQuestionTmp->setExerciseType($exercise->selectType());
                $objQuestionTmp->processText($objQuestionTmp->selectDescription());
                $hidingClass = 'hide-reading-answers';
                $s .= Display::div(
                    $objQuestionTmp->selectTitle(),
                    ['class' => 'question_title '.$hidingClass]
                );
            }

            for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                $answer = $objAnswerTmp->selectAnswer($answerId);
                $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                $numAnswer = $objAnswerTmp->selectAutoId($answerId);
                $comment = $objAnswerTmp->selectComment($answerId);
                $attributes = [];

                switch ($answerType) {
                    case UNIQUE_ANSWER:
                    case UNIQUE_ANSWER_NO_OPTION:
                    case UNIQUE_ANSWER_IMAGE:
                    case READING_COMPREHENSION:
                        $input_id = 'choice-'.$questionId.'-'.$answerId;
                        if (isset($user_choice[0]['answer']) && $user_choice[0]['answer'] == $numAnswer) {
                            $attributes = [
                                'id' => $input_id,
                                'checked' => 1,
                                'selected' => 1,
                            ];
                        } else {
                            $attributes = ['id' => $input_id];
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
                                    $s .= '<div id="answer'.$questionId.$numAnswer.'" 
                                            class="exercise-unique-answer-image" style="text-align: center">';
                                } else {
                                    $s .= '<div id="answer'.$questionId.$numAnswer.'" 
                                            class="exercise-unique-answer-image col-xs-6 col-sm-12" 
                                            style="text-align: center">';
                                }
                            } else {
                                $s .= '<div id="answer'.$questionId.$numAnswer.'" 
                                        class="exercise-unique-answer-image col-xs-6 col-md-3" 
                                        style="text-align: center">';
                            }
                        }

                        $answer = Security::remove_XSS($answer, STUDENT);
                        $s .= Display::input(
                            'hidden',
                            'choice2['.$questionId.']',
                            '0'
                        );

                        $answer_input = null;
                        $attributes['class'] = 'checkradios';
                        if ($answerType == UNIQUE_ANSWER_IMAGE) {
                            $attributes['class'] = '';
                            $attributes['style'] = 'display: none;';
                            $answer = '<div class="thumbnail">'.$answer.'</div>';
                        }

                        $answer_input .= '<label class="radio '.$hidingClass.'">';
                        $answer_input .= Display::input(
                            'radio',
                            'choice['.$questionId.']',
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
                    case MULTIPLE_ANSWER_TRUE_FALSE:
                    case GLOBAL_MULTIPLE_ANSWER:
                    case MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY:
                        $input_id = 'choice-'.$questionId.'-'.$answerId;
                        $answer = Security::remove_XSS($answer, STUDENT);

                        if (in_array($numAnswer, $userChoiceList)) {
                            $attributes = [
                                'id' => $input_id,
                                'checked' => 1,
                                'selected' => 1,
                            ];
                        } else {
                            $attributes = ['id' => $input_id];
                        }

                        if ($debug_mark_answer) {
                            if ($answerCorrect) {
                                $attributes['checked'] = 1;
                                $attributes['selected'] = 1;
                            }
                        }

                        if ($answerType == MULTIPLE_ANSWER || $answerType == GLOBAL_MULTIPLE_ANSWER) {
                            $s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
                            $attributes['class'] = 'checkradios';
                            $answer_input = '<label class="checkbox">';
                            $answer_input .= Display::input(
                                'checkbox',
                                'choice['.$questionId.']['.$numAnswer.']',
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
                            $myChoice = [];
                            if (!empty($userChoiceList)) {
                                foreach ($userChoiceList as $item) {
                                    $item = explode(':', $item);
                                    if (!empty($item)) {
                                        $myChoice[$item[0]] = isset($item[1]) ? $item[1] : '';
                                    }
                                }
                            }

                            $s .= '<tr>';
                            $s .= Display::tag('td', $answer);

                            if (!empty($quizQuestionOptions)) {
                                foreach ($quizQuestionOptions as $id => $item) {
                                    if (isset($myChoice[$numAnswer]) && $id == $myChoice[$numAnswer]) {
                                        $attributes = [
                                            'checked' => 1,
                                            'selected' => 1,
                                        ];
                                    } else {
                                        $attributes = [];
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
                                            'choice['.$questionId.']['.$numAnswer.']',
                                            $id,
                                            $attributes
                                        ),
                                        ['style' => '']
                                    );
                                }
                            }

                            if ($show_comment) {
                                $s .= '<td>';
                                $s .= $comment;
                                $s .= '</td>';
                            }
                            $s .= '</tr>';
                        } elseif ($answerType == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                            $myChoice = [];
                            if (!empty($userChoiceList)) {
                                foreach ($userChoiceList as $item) {
                                    $item = explode(':', $item);
                                    $myChoice[$item[0]] = $item[1];
                                }
                            }
                            $myChoiceDegreeCertainty = [];
                            if (!empty($userChoiceList)) {
                                foreach ($userChoiceList as $item) {
                                    $item = explode(':', $item);
                                    $myChoiceDegreeCertainty[$item[0]] = $item[2];
                                }
                            }
                            $s .= '<tr>';
                            $s .= Display::tag('td', $answer);

                            if (!empty($quizQuestionOptions)) {
                                foreach ($quizQuestionOptions as $id => $item) {
                                    if (isset($myChoice[$numAnswer]) && $id == $myChoice[$numAnswer]) {
                                        $attributes = ['checked' => 1, 'selected' => 1];
                                    } else {
                                        $attributes = [];
                                    }
                                    $attributes['onChange'] = 'RadioValidator('.$questionId.', '.$numAnswer.')';

                                    // radio button selection
                                    if (isset($myChoiceDegreeCertainty[$numAnswer]) &&
                                        $id == $myChoiceDegreeCertainty[$numAnswer]
                                    ) {
                                        $attributes1 = ['checked' => 1, 'selected' => 1];
                                    } else {
                                        $attributes1 = [];
                                    }

                                    $attributes1['onChange'] = 'RadioValidator('.$questionId.', '.$numAnswer.')';

                                    if ($debug_mark_answer) {
                                        if ($id == $answerCorrect) {
                                            $attributes['checked'] = 1;
                                            $attributes['selected'] = 1;
                                        }
                                    }

                                    if ($item['name'] == 'True' || $item['name'] == 'False') {
                                        $s .= Display::tag('td',
                                            Display::input('radio',
                                                'choice['.$questionId.']['.$numAnswer.']',
                                                $id,
                                                $attributes
                                            ),
                                            ['style' => 'text-align:center; background-color:#F7E1D7;',
                                                'onclick' => 'handleRadioRow(event, '.
                                                    $questionId.', '.
                                                    $numAnswer.')',
                                            ]
                                        );
                                    } else {
                                        $s .= Display::tag('td',
                                            Display::input('radio',
                                                'choiceDegreeCertainty['.$questionId.']['.$numAnswer.']',
                                                $id,
                                                $attributes1
                                            ),
                                            ['style' => 'text-align:center; background-color:#EFEFFC;',
                                                'onclick' => 'handleRadioRow(event, '.
                                                    $questionId.', '.
                                                    $numAnswer.')',
                                            ]
                                        );
                                    }
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
                        $input_id = 'choice-'.$questionId.'-'.$answerId;

                        if (in_array($numAnswer, $userChoiceList)) {
                            $attributes = [
                                'id' => $input_id,
                                'checked' => 1,
                                'selected' => 1,
                            ];
                        } else {
                            $attributes = ['id' => $input_id];
                        }

                        if ($debug_mark_answer) {
                            if ($answerCorrect) {
                                $attributes['checked'] = 1;
                                $attributes['selected'] = 1;
                            }
                        }

                        $answer = Security::remove_XSS($answer, STUDENT);
                        $answer_input = '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
                        $answer_input .= '<label class="checkbox">';
                        $answer_input .= Display::input(
                            'checkbox',
                            'choice['.$questionId.']['.$numAnswer.']',
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
                        $s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
                        $myChoice = [];
                        if (!empty($userChoiceList)) {
                            foreach ($userChoiceList as $item) {
                                $item = explode(':', $item);
                                if (isset($item[1]) && isset($item[0])) {
                                    $myChoice[$item[0]] = $item[1];
                                }
                            }
                        }
                        $answer = Security::remove_XSS($answer, STUDENT);
                        $s .= '<tr>';
                        $s .= Display::tag('td', $answer);
                        foreach ($objQuestionTmp->options as $key => $item) {
                            if (isset($myChoice[$numAnswer]) && $key == $myChoice[$numAnswer]) {
                                $attributes = [
                                    'checked' => 1,
                                    'selected' => 1,
                                ];
                            } else {
                                $attributes = [];
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
                                    'choice['.$questionId.']['.$numAnswer.']',
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
                        $listAnswerInfo = FillBlanks::getAnswerInfo($answer);
                        // Correct answers
                        $correctAnswerList = $listAnswerInfo['words'];
                        // Student's answer
                        $studentAnswerList = [];
                        if (isset($user_choice[0]['answer'])) {
                            $arrayStudentAnswer = FillBlanks::getAnswerInfo(
                                $user_choice[0]['answer'],
                                true
                            );
                            $studentAnswerList = $arrayStudentAnswer['student_answer'];
                        }

                        // If the question must be shown with the answer (in page exercise/admin.php)
                        // for teacher preview set the student-answer to the correct answer
                        if ($debug_mark_answer) {
                            $studentAnswerList = $correctAnswerList;
                            $displayForStudent = false;
                        }

                        if (!empty($correctAnswerList) && !empty($studentAnswerList)) {
                            $answer = '';
                            for ($i = 0; $i < count($listAnswerInfo['common_words']) - 1; $i++) {
                                // display the common word
                                $answer .= $listAnswerInfo['common_words'][$i];
                                // display the blank word
                                $correctItem = $listAnswerInfo['words'][$i];
                                if (isset($studentAnswerList[$i])) {
                                    // If student already started this test and answered this question,
                                    // fill the blank with his previous answers
                                    // may be "" if student viewed the question, but did not fill the blanks
                                    $correctItem = $studentAnswerList[$i];
                                }
                                $attributes['style'] = 'width:'.$listAnswerInfo['input_size'][$i].'px';
                                $answer .= FillBlanks::getFillTheBlankHtml(
                                    $current_item,
                                    $questionId,
                                    $correctItem,
                                    $attributes,
                                    $answer,
                                    $listAnswerInfo,
                                    $displayForStudent,
                                    $i
                                );
                            }
                            // display the last common word
                            $answer .= $listAnswerInfo['common_words'][$i];
                        } else {
                            // display empty [input] with the right width for student to fill it
                            $answer = '';
                            for ($i = 0; $i < count($listAnswerInfo['common_words']) - 1; $i++) {
                                // display the common words
                                $answer .= $listAnswerInfo['common_words'][$i];
                                // display the blank word
                                $attributes['style'] = 'width:'.$listAnswerInfo['input_size'][$i].'px';
                                $answer .= FillBlanks::getFillTheBlankHtml(
                                    $current_item,
                                    $questionId,
                                    '',
                                    $attributes,
                                    $answer,
                                    $listAnswerInfo,
                                    $displayForStudent,
                                    $i
                                );
                            }
                            // display the last common word
                            $answer .= $listAnswerInfo['common_words'][$i];
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
                            $exe_id = (int) $exe_id;
                            $trackAttempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
                            $sql = "SELECT answer FROM $trackAttempts
                                    WHERE exe_id = $exe_id AND question_id= $questionId";
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

                        // get student answer to display it if student go back
                        // to previous calculated answer question in a test
                        if (isset($user_choice[0]['answer'])) {
                            api_preg_match_all(
                                '/\[[^]]+\]/',
                                $answer,
                                $studentAnswerList
                            );
                            $studentAnswerListToClean = $studentAnswerList[0];
                            $studentAnswerList = [];

                            $maxStudents = count($studentAnswerListToClean);
                            for ($i = 0; $i < $maxStudents; $i++) {
                                $answerCorrected = $studentAnswerListToClean[$i];
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
                                $answerCorrected = '['.$answerCorrected.']';
                                $studentAnswerList[] = $answerCorrected;
                            }
                        }

                        // If display preview of answer in test view for exemple,
                        // set the student answer to the correct answers
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
                            ' '.$answer.' '
                        );
                        if (!empty($correctAnswerList) && !empty($studentAnswerList)) {
                            $answer = '';
                            $i = 0;
                            foreach ($studentAnswerList as $studentItem) {
                                // Remove surronding brackets
                                $studentResponse = api_substr(
                                    $studentItem,
                                    1,
                                    api_strlen($studentItem) - 2
                                );
                                $size = strlen($studentItem);
                                $attributes['class'] = self::detectInputAppropriateClass($size);
                                $answer .= $tabComments[$i].
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
                                $attributes['class'] = self::detectInputAppropriateClass($size);
                                if ($exercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_POPUP) {
                                    $attributes['id'] = "question_$questionId";
                                    $attributes['class'] .= ' checkCalculatedQuestionOnEnter ';
                                }

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
                            // the select boxes, who are correct = 0)
                            $s .= '<tr><td width="45%" valign="top">';
                            $parsed_answer = $answer;
                            // Left part questions
                            $s .= '<p class="indent">'.$lines_count.'.&nbsp;'.$parsed_answer.'</p></td>';
                            // Middle part (matches selects)
                            // Id of select is # question + # of option
                            $s .= '<td width="10%" valign="top" align="center">
                                <div class="select-matching">
                                <select 
                                    id="choice_id_'.$current_item.'_'.$lines_count.'" 
                                    name="choice['.$questionId.']['.$numAnswer.']">';

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
                                if (isset($user_choice_array_position[$numAnswer]) &&
                                    $val['id'] == $user_choice_array_position[$numAnswer]
                                ) {
                                    $selected = 'selected="selected"';
                                }
                                $s .= '<option value="'.$val['id'].'" '.$selected.'>'.$val['letter'].'</option>';
                            }

                            $s .= '</select></div></td><td width="5%" class="separate">&nbsp;</td>';
                            $s .= '<td width="40%" valign="top" >';
                            if (isset($select_items[$lines_count])) {
                                $s .= '<div class="text-right">
                                        <p class="indent">'.
                                    $select_items[$lines_count]['letter'].'.&nbsp; '.
                                    $select_items[$lines_count]['answer'].'
                                        </p>
                                        </div>';
                            } else {
                                $s .= '&nbsp;';
                            }
                            $s .= '</td>';
                            $s .= '</tr>';
                            $lines_count++;
                            // If the left side of the "matching" has been completely
                            // shown but the right side still has values to show...
                            if (($lines_count - 1) == $num_suggestions) {
                                // if it remains answers to shown at the right side
                                while (isset($select_items[$lines_count])) {
                                    $s .= '<tr>
                                      <td colspan="2"></td>
                                      <td valign="top">';
                                    $s .= '<b>'.$select_items[$lines_count]['letter'].'.</b> '.
                                        $select_items[$lines_count]['answer'];
                                    $s .= "</td>
                                </tr>";
                                    $lines_count++;
                                }
                            }
                            $matching_correct_answer++;
                        }
                        break;
                    case DRAGGABLE:
                        if ($answerCorrect) {
                            $windowId = $questionId.'_'.$lines_count;
                            $s .= '<li class="touch-items" id="'.$windowId.'">';
                            $s .= Display::div(
                                $answer,
                                [
                                    'id' => "window_$windowId",
                                    'class' => "window{$questionId}_question_draggable exercise-draggable-answer-option",
                                ]
                            );

                            $draggableSelectOptions = [];
                            $selectedValue = 0;
                            $selectedIndex = 0;

                            if ($user_choice) {
                                foreach ($user_choice as $chosen) {
                                    if ($answerCorrect != $chosen['answer']) {
                                        continue;
                                    }
                                    $selectedValue = $chosen['answer'];
                                }
                            }

                            foreach ($select_items as $key => $select_item) {
                                $draggableSelectOptions[$select_item['id']] = $select_item['letter'];
                            }

                            foreach ($draggableSelectOptions as $value => $text) {
                                if ($value == $selectedValue) {
                                    break;
                                }
                                $selectedIndex++;
                            }

                            $s .= Display::select(
                                "choice[$questionId][$numAnswer]",
                                $draggableSelectOptions,
                                $selectedValue,
                                [
                                    'id' => "window_{$windowId}_select",
                                    'class' => 'select_option hidden',
                                ],
                                false
                            );

                            if ($selectedValue && $selectedIndex) {
                                $s .= "
                                    <script>
                                        $(function() {
                                            DraggableAnswer.deleteItem(
                                                $('#{$questionId}_$lines_count'),
                                                $('#drop_{$questionId}_{$selectedIndex}')
                                            );
                                        });
                                    </script>
                                ";
                            }

                            if (isset($select_items[$lines_count])) {
                                $s .= Display::div(
                                    Display::tag(
                                        'b',
                                        $select_items[$lines_count]['letter']
                                    ).$select_items[$lines_count]['answer'],
                                    [
                                        'id' => "window_{$windowId}_answer",
                                        'class' => 'hidden',
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
                            $windowId = "{$questionId}_{$lines_count}";
                            $s .= <<<HTML
                            <tr>
                                <td width="45%">
                                    <div id="window_{$windowId}" 
                                        class="window window_left_question window{$questionId}_question">
                                        <strong>$lines_count.</strong> 
                                        $answer
                                    </div>
                                </td>
                                <td width="10%">
HTML;

                            $draggableSelectOptions = [];
                            $selectedValue = 0;
                            $selectedIndex = 0;

                            if ($user_choice) {
                                foreach ($user_choice as $chosen) {
                                    if ($numAnswer == $chosen['position']) {
                                        $selectedValue = $chosen['answer'];
                                        break;
                                    }
                                }
                            }

                            foreach ($select_items as $key => $selectItem) {
                                $draggableSelectOptions[$selectItem['id']] = $selectItem['letter'];
                            }

                            foreach ($draggableSelectOptions as $value => $text) {
                                if ($value == $selectedValue) {
                                    break;
                                }
                                $selectedIndex++;
                            }

                            $s .= Display::select(
                                "choice[$questionId][$numAnswer]",
                                $draggableSelectOptions,
                                $selectedValue,
                                [
                                    'id' => "window_{$windowId}_select",
                                    'class' => 'hidden',
                                ],
                                false
                            );

                            if (!empty($answerCorrect) && !empty($selectedValue)) {
                                // Show connect if is not freeze (question preview)
                                if (!$freeze) {
                                    $s .= "
                                        <script>
                                            $(function() {
                                                jsPlumb.ready(function() {
                                                    jsPlumb.connect({
                                                        source: 'window_$windowId',
                                                        target: 'window_{$questionId}_{$selectedIndex}_answer',
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
                                    ";
                                }
                            }

                            $s .= '</td><td width="45%">';
                            if (isset($select_items[$lines_count])) {
                                $s .= <<<HTML
                                <div id="window_{$windowId}_answer" class="window window_right_question">
                                    <strong>{$select_items[$lines_count]['letter']}.</strong> 
                                    {$select_items[$lines_count]['answer']}
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
            }

            if ($show_comment) {
                $s .= '</table>';
            } elseif (in_array(
                $answerType,
                [
                    MATCHING,
                    MATCHING_DRAGGABLE,
                    UNIQUE_ANSWER_NO_OPTION,
                    MULTIPLE_ANSWER_TRUE_FALSE,
                    MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE,
                    MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY,
                ]
            )) {
                $s .= '</table>';
            }

            if ($answerType == DRAGGABLE) {
                $isVertical = $objQuestionTmp->extra == 'v';
                $s .= "</ul>";
                $s .= "</div>";
                $counterAnswer = 1;
                $s .= $isVertical ? '' : '<div class="row">';
                for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                    $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                    $windowId = $questionId.'_'.$counterAnswer;
                    if ($answerCorrect) {
                        $s .= $isVertical ? '<div class="row">' : '';
                        $s .= '
                            <div class="'.($isVertical ? 'col-md-12' : 'col-xs-12 col-sm-4 col-md-3 col-lg-2').'">
                                <div class="droppable-item">
                                    <span class="number">'.$counterAnswer.'.</span>
                                    <div id="drop_'.$windowId.'" class="droppable">&nbsp;</div>
                                 </div>
                            </div>
                        ';
                        $s .= $isVertical ? '</div>' : '';
                        $counterAnswer++;
                    }
                }

                $s .= $isVertical ? '' : '</div>'; // row
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
            global $exe_id;
            // Question is a HOT_SPOT
            // Checking document/images visibility
            if (api_is_platform_admin() || api_is_course_admin()) {
                $doc_id = $objQuestionTmp->getPictureId();
                if (is_numeric($doc_id)) {
                    $images_folder_visibility = api_get_item_visibility(
                        $course,
                        'document',
                        $doc_id,
                        api_get_session_id()
                    );
                    if (!$images_folder_visibility) {
                        // Show only to the course/platform admin if the image is set to visibility = false
                        echo Display::return_message(
                            get_lang('Change the visibility of the current image'),
                            'warning'
                        );
                    }
                }
            }
            $questionDescription = $objQuestionTmp->selectDescription();

            // Get the answers, make a list
            $objAnswerTmp = new Answer($questionId, $course_id);
            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();

            // get answers of hotpost
            $answers_hotspot = [];
            for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                $answers = $objAnswerTmp->selectAnswerByAutoId(
                    $objAnswerTmp->selectAutoId($answerId)
                );
                $answers_hotspot[$answers['id']] = $objAnswerTmp->selectAnswer(
                    $answerId
                );
            }

            $answerList = '';
            $hotspotColor = 0;
            if ($answerType != HOT_SPOT_DELINEATION) {
                $answerList = '
                    <div class="well well-sm">
                        <h5 class="page-header">'.get_lang('Image zones').'</h5>
                        <ol>
                ';

                if (!empty($answers_hotspot)) {
                    Session::write("hotspot_ordered$questionId", array_keys($answers_hotspot));
                    foreach ($answers_hotspot as $value) {
                        $answerList .= '<li>';
                        if ($freeze) {
                            $answerList .= '<span class="hotspot-color-'.$hotspotColor
                                .' fa fa-square" aria-hidden="true"></span>'.PHP_EOL;
                        }
                        $answerList .= $value;
                        $answerList .= '</li>';
                        $hotspotColor++;
                    }
                }

                $answerList .= '
                        </ul>
                    </div>
                ';
                if ($freeze) {
                    $relPath = api_get_path(WEB_CODE_PATH);
                    echo "
                        <div class=\"row\">
                            <div class=\"col-sm-9\">
                                <div id=\"hotspot-preview-$questionId\"></div>                                
                            </div>
                            <div class=\"col-sm-3\">
                                $answerList
                            </div>
                        </div>
                        <script>
                            new ".($answerType == HOT_SPOT ? "HotspotQuestion" : "DelineationQuestion")."({
                                questionId: $questionId,
                                exerciseId: $exerciseId,
                                exeId: 0,
                                selector: '#hotspot-preview-$questionId',
                                for: 'preview',
                                relPath: '$relPath'
                            });
                        </script>
                    ";

                    return;
                }
            }

            if (!$only_questions) {
                if ($show_title) {
                    if ($exercise->display_category_name) {
                        TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    }
                    echo $objQuestionTmp->getTitleToDisplay($current_item);
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
            $s .= "<div class=\"col-sm-8 col-md-9\">
                   <div class=\"hotspot-image\"></div>
                    <script>
                        $(function() {
                            new ".($answerType == HOT_SPOT_DELINEATION ? 'DelineationQuestion' : 'HotspotQuestion')."({
                                questionId: $questionId,
                                exerciseId: $exerciseId,
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
        } elseif ($answerType == ANNOTATION) {
            global $exe_id;
            $relPath = api_get_path(WEB_CODE_PATH);
            if (api_is_platform_admin() || api_is_course_admin()) {
                $docId = DocumentManager::get_document_id($course, '/images/'.$pictureName);
                if ($docId) {
                    $images_folder_visibility = api_get_item_visibility(
                        $course,
                        'document',
                        $docId,
                        api_get_session_id()
                    );

                    if (!$images_folder_visibility) {
                        echo Display::return_message(get_lang('Change the visibility of the current image'), 'warning');
                    }
                }

                if ($freeze) {
                    echo Display::img(
                        api_get_path(WEB_COURSE_PATH).$course['path'].'/document/images/'.$pictureName,
                        $objQuestionTmp->selectTitle(),
                        ['width' => '600px']
                    );

                    return 0;
                }
            }

            if (!$only_questions) {
                if ($show_title) {
                    if ($exercise->display_category_name) {
                        TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    }
                    echo $objQuestionTmp->getTitleToDisplay($current_item);
                }
                echo '
                    <input type="hidden" name="hidden_hotspot_id" value="'.$questionId.'" />
                    <div class="exercise_questions">
                        '.$objQuestionTmp->selectDescription().'
                        <div class="row">
                            <div class="col-sm-8 col-md-9">
                                <div id="annotation-canvas-'.$questionId.'" class="annotation-canvas center-block">
                                </div>
                                <script>
                                    AnnotationQuestion({
                                        questionId: '.$questionId.',
                                        exerciseId: '.$exerciseId.',
                                        relPath: \''.$relPath.'\',
                                        courseId: '.$course_id.',
                                    });
                                </script>
                            </div>
                            <div class="col-sm-4 col-md-3">
                                <div class="well well-sm" id="annotation-toolbar-'.$questionId.'">
                                    <div class="btn-toolbar">
                                        <div class="btn-group" data-toggle="buttons">
                                            <label class="btn btn-default active"
                                                aria-label="'.get_lang('Add annotation path').'">
                                                <input 
                                                    type="radio" value="0" 
                                                    name="'.$questionId.'-options" autocomplete="off" checked>
                                                <span class="fa fa-pencil" aria-hidden="true"></span>
                                            </label>
                                            <label class="btn btn-default"
                                                aria-label="'.get_lang('Add annotation text').'">
                                                <input 
                                                    type="radio" value="1" 
                                                    name="'.$questionId.'-options" autocomplete="off">
                                                <span class="fa fa-font fa-fw" aria-hidden="true"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <ul class="list-unstyled"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                ';
            }
            $objAnswerTmp = new Answer($questionId);
            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
            unset($objAnswerTmp, $objQuestionTmp);
        }

        return $nbrAnswers;
    }

    /**
     * @param int $exeId
     *
     * @return array
     */
    public static function get_exercise_track_exercise_info($exeId)
    {
        $quizTable = Database::get_course_table(TABLE_QUIZ_TEST);
        $trackExerciseTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $exeId = (int) $exeId;
        $result = [];
        if (!empty($exeId)) {
            $sql = " SELECT q.*, tee.*
                FROM $quizTable as q
                INNER JOIN $trackExerciseTable as tee
                ON q.id = tee.exe_exo_id
                INNER JOIN $courseTable c
                ON c.id = tee.c_id
                WHERE tee.exe_id = $exeId
                AND q.c_id = c.id";

            $sqlResult = Database::query($sql);
            if (Database::num_rows($sqlResult)) {
                $result = Database::fetch_array($sqlResult, 'ASSOC');
                $result['duration_formatted'] = '';
                if (!empty($result['exe_duration'])) {
                    $time = api_format_time($result['exe_duration'], 'js');
                    $result['duration_formatted'] = $time;
                }
            }
        }

        return $result;
    }

    /**
     * Validates the time control key.
     *
     * @param int $exercise_id
     * @param int $lp_id
     * @param int $lp_item_id
     *
     * @return bool
     */
    public static function exercise_time_control_is_valid(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    ) {
        $course_id = api_get_course_int_id();
        $exercise_id = (int) $exercise_id;
        $table = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT expired_time FROM $table
                WHERE c_id = $course_id AND id = $exercise_id";
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        if (!empty($row['expired_time'])) {
            $current_expired_time_key = self::get_time_control_key(
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
            }

            return false;
        }

        return true;
    }

    /**
     * Deletes the time control token.
     *
     * @param int $exercise_id
     * @param int $lp_id
     * @param int $lp_item_id
     */
    public static function exercise_time_control_delete(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    ) {
        $current_expired_time_key = self::get_time_control_key(
            $exercise_id,
            $lp_id,
            $lp_item_id
        );
        unset($_SESSION['expired_time'][$current_expired_time_key]);
    }

    /**
     * Generates the time control key.
     *
     * @param int $exercise_id
     * @param int $lp_id
     * @param int $lp_item_id
     *
     * @return string
     */
    public static function get_time_control_key(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    ) {
        $exercise_id = (int) $exercise_id;
        $lp_id = (int) $lp_id;
        $lp_item_id = (int) $lp_item_id;

        return
            api_get_course_int_id().'_'.
            api_get_session_id().'_'.
            $exercise_id.'_'.
            api_get_user_id().'_'.
            $lp_id.'_'.
            $lp_item_id;
    }

    /**
     * Get session time control.
     *
     * @param int $exercise_id
     * @param int $lp_id
     * @param int $lp_item_id
     *
     * @return int
     */
    public static function get_session_time_control_key(
        $exercise_id,
        $lp_id = 0,
        $lp_item_id = 0
    ) {
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
     * Gets count of exam results.
     *
     * @param int    $exerciseId
     * @param array  $conditions
     * @param string $courseCode
     * @param bool   $showSession
     *
     * @return array
     */
    public static function get_count_exam_results($exerciseId, $conditions, $courseCode = '', $showSession = false)
    {
        $count = self::get_exam_results_data(
            null,
            null,
            null,
            null,
            $exerciseId,
            $conditions,
            true,
            $courseCode,
            $showSession
        );

        return $count;
    }

    /**
     * @param string $path
     *
     * @return int
     */
    public static function get_count_exam_hotpotatoes_results($path)
    {
        return self::get_exam_results_hotpotatoes_data(
            0,
            0,
            '',
            '',
            $path,
            true,
            ''
        );
    }

    /**
     * @param int    $in_from
     * @param int    $in_number_of_items
     * @param int    $in_column
     * @param int    $in_direction
     * @param string $in_hotpot_path
     * @param bool   $in_get_count
     * @param null   $where_condition
     *
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
    ) {
        $courseId = api_get_course_int_id();
        // by default in_column = 1 If parameters given, it is the name of the column witch is the bdd field name
        if ($in_column == 1) {
            $in_column = 'firstname';
        }
        $in_hotpot_path = Database::escape_string($in_hotpot_path);
        $in_direction = Database::escape_string($in_direction);
        $in_column = Database::escape_string($in_column);
        $in_number_of_items = intval($in_number_of_items);
        $in_from = (int) $in_from;

        $TBL_TRACK_HOTPOTATOES = Database::get_main_table(
            TABLE_STATISTIC_TRACK_E_HOTPOTATOES
        );
        $TBL_USER = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT *, thp.id AS thp_id 
                FROM $TBL_TRACK_HOTPOTATOES thp
                JOIN $TBL_USER u 
                ON thp.exe_user_id = u.user_id
                WHERE 
                    thp.c_id = $courseId AND 
                    exe_name LIKE '$in_hotpot_path%'";

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
        $result = [];
        $apiIsAllowedToEdit = api_is_allowed_to_edit();
        $urlBase = api_get_path(WEB_CODE_PATH).
            'exercise/hotpotatoes_exercise_report.php?action=delete&'.
            api_get_cidreq().'&id=';
        while ($data = Database::fetch_array($res)) {
            $actions = null;

            if ($apiIsAllowedToEdit) {
                $url = $urlBase.$data['thp_id'].'&path='.$data['exe_name'];
                $actions = Display::url(
                    Display::return_icon('delete.png', get_lang('Delete')),
                    $url
                );
            }

            $result[] = [
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'username' => $data['username'],
                'group_name' => implode(
                    '<br/>',
                    GroupManager::get_user_group_name($data['user_id'])
                ),
                'exe_date' => $data['exe_date'],
                'score' => $data['score'].' / '.$data['max_score'],
                'actions' => $actions,
            ];
        }

        return $result;
    }

    /**
     * @param string $exercisePath
     * @param int    $userId
     * @param int    $courseId
     * @param int    $sessionId
     *
     * @return array
     */
    public static function getLatestHotPotatoResult(
        $exercisePath,
        $userId,
        $courseId,
        $sessionId
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $exercisePath = Database::escape_string($exercisePath);
        $userId = (int) $userId;
        $courseId = (int) $courseId;

        $sql = "SELECT * FROM $table
                WHERE
                    c_id = $courseId AND
                    exe_name LIKE '$exercisePath%' AND
                    exe_user_id = $userId
                ORDER BY id
                LIMIT 1";
        $result = Database::query($sql);
        $attempt = [];
        if (Database::num_rows($result)) {
            $attempt = Database::fetch_array($result, 'ASSOC');
        }

        return $attempt;
    }

    /**
     * Gets the exam'data results.
     *
     * @todo this function should be moved in a library  + no global calls
     *
     * @param int    $from
     * @param int    $number_of_items
     * @param int    $column
     * @param string $direction
     * @param int    $exercise_id
     * @param null   $extra_where_conditions
     * @param bool   $get_count
     * @param string $courseCode
     * @param bool   $showSessionField
     * @param bool   $showExerciseCategories
     * @param array  $userExtraFieldsToAdd
     * @param bool   $useCommaAsDecimalPoint
     * @param bool   $roundValues
     *
     * @return array
     */
    public static function get_exam_results_data(
        $from,
        $number_of_items,
        $column,
        $direction,
        $exercise_id,
        $extra_where_conditions = null,
        $get_count = false,
        $courseCode = null,
        $showSessionField = false,
        $showExerciseCategories = false,
        $userExtraFieldsToAdd = [],
        $useCommaAsDecimalPoint = false,
        $roundValues = false
    ) {
        //@todo replace all this globals
        global $filter;
        $courseCode = empty($courseCode) ? api_get_course_id() : $courseCode;
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo)) {
            return [];
        }

        $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';

        $course_id = $courseInfo['real_id'];
        $sessionId = api_get_session_id();
        $exercise_id = (int) $exercise_id;

        $is_allowedToEdit =
            api_is_allowed_to_edit(null, true) ||
            api_is_allowed_to_edit(true) ||
            api_is_drh() ||
            api_is_student_boss() ||
            api_is_session_admin();
        $TBL_USER = Database::get_main_table(TABLE_MAIN_USER);
        $TBL_EXERCICES = Database::get_course_table(TABLE_QUIZ_TEST);
        $TBL_GROUP_REL_USER = Database::get_course_table(TABLE_GROUP_USER);
        $TBL_GROUP = Database::get_course_table(TABLE_GROUP);
        $TBL_TRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_TRACK_HOTPOTATOES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);

        $session_id_and = '';
        $sessionCondition = '';
        if (!$showSessionField) {
            $session_id_and = " AND te.session_id = $sessionId ";
            $sessionCondition = " AND ttte.session_id = $sessionId";
        }

        $exercise_where = '';
        if (!empty($exercise_id)) {
            $exercise_where .= ' AND te.exe_exo_id = '.$exercise_id.'  ';
        }

        $hotpotatoe_where = '';
        if (!empty($_GET['path'])) {
            $hotpotatoe_path = Database::escape_string($_GET['path']);
            $hotpotatoe_where .= ' AND exe_name = "'.$hotpotatoe_path.'"  ';
        }

        // sql for chamilo-type tests for teacher / tutor view
        $sql_inner_join_tbl_track_exercices = "
        (
            SELECT DISTINCT ttte.*, if(tr.exe_id,1, 0) as revised
            FROM $TBL_TRACK_EXERCICES ttte 
            LEFT JOIN $TBL_TRACK_ATTEMPT_RECORDING tr
            ON (ttte.exe_id = tr.exe_id)
            WHERE
                c_id = $course_id AND
                exe_exo_id = $exercise_id 
                $sessionCondition
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
                    ON (gru.user_id = u.user_id AND gru.c_id= $course_id )
                    INNER JOIN $TBL_GROUP g
                    ON (gru.group_id = g.id AND g.c_id= $course_id )
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
                ON ( gru.user_id = u.user_id AND gru.c_id= $course_id )
                LEFT OUTER JOIN $TBL_GROUP g
                ON (gru.group_id = g.id AND g.c_id = $course_id )
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
                WHERE u.status NOT IN(".api_get_users_status_ignored_in_reports('string').")
            )";
            }

            $sqlFromOption = " , $TBL_GROUP_REL_USER AS gru ";
            $sqlWhereOption = "  AND gru.c_id = $course_id AND gru.user_id = user.user_id ";
            $first_and_last_name = api_is_western_name_order() ? "firstname, lastname" : "lastname, firstname";

            if ($get_count) {
                $sql_select = 'SELECT count(te.exe_id) ';
            } else {
                $sql_select = "SELECT DISTINCT
                    user_id,
                    $first_and_last_name,
                    official_code,
                    ce.title,
                    username,
                    te.score,
                    te.max_score,
                    te.exe_date,
                    te.exe_id,
                    te.session_id,
                    email as exemail,
                    te.start_date,
                    ce.expired_time,
                    steps_counter,
                    exe_user_id,
                    te.exe_duration,
                    te.status as completion_status,
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
                    te.c_id = $course_id $session_id_and AND
                    ce.active <> -1 AND 
                    ce.c_id = $course_id
                    $exercise_where
                    $extra_where_conditions
                ";

            // sql for hotpotatoes tests for teacher / tutor view
            if ($get_count) {
                $hpsql_select = ' SELECT count(username) ';
            } else {
                $hpsql_select = " SELECT
                    $first_and_last_name ,
                    username,
                    official_code,
                    tth.exe_name,
                    tth.score ,
                    tth.max_score,
                    tth.exe_date";
            }

            $hpsql = " $hpsql_select
                FROM
                    $TBL_TRACK_HOTPOTATOES tth,
                    $TBL_USER user
                    $sqlFromOption
                WHERE
                    user.user_id=tth.exe_user_id
                    AND tth.c_id = $course_id
                    $hotpotatoe_where
                    $sqlWhereOption
                    AND user.status NOT IN (".api_get_users_status_ignored_in_reports('string').")
                ORDER BY tth.c_id ASC, tth.exe_date DESC ";
        }

        if (empty($sql)) {
            return false;
        }

        if ($get_count) {
            $resx = Database::query($sql);
            $rowx = Database::fetch_row($resx, 'ASSOC');

            return $rowx[0];
        }

        $teacher_list = CourseManager::get_teacher_list_from_course_code($courseCode);
        $teacher_id_list = [];
        if (!empty($teacher_list)) {
            foreach ($teacher_list as $teacher) {
                $teacher_id_list[] = $teacher['user_id'];
            }
        }

        $scoreDisplay = new ScoreDisplay();
        $decimalSeparator = '.';
        $thousandSeparator = ',';

        if ($useCommaAsDecimalPoint) {
            $decimalSeparator = ',';
            $thousandSeparator = '';
        }

        $listInfo = [];
        // Simple exercises
        if (empty($hotpotatoe_where)) {
            $column = !empty($column) ? Database::escape_string($column) : null;
            $from = (int) $from;
            $number_of_items = (int) $number_of_items;

            if (!empty($column)) {
                $sql .= " ORDER BY $column $direction ";
            }
            $sql .= " LIMIT $from, $number_of_items";

            $results = [];
            $resx = Database::query($sql);
            while ($rowx = Database::fetch_array($resx, 'ASSOC')) {
                $results[] = $rowx;
            }

            $group_list = GroupManager::get_group_list(null, $courseInfo);
            $clean_group_list = [];
            if (!empty($group_list)) {
                foreach ($group_list as $group) {
                    $clean_group_list[$group['id']] = $group['name'];
                }
            }

            $lp_list_obj = new LearnpathList(api_get_user_id());
            $lp_list = $lp_list_obj->get_flat_list();
            $oldIds = array_column($lp_list, 'lp_old_id', 'iid');

            if (is_array($results)) {
                $users_array_id = [];
                $from_gradebook = false;
                if (isset($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
                    $from_gradebook = true;
                }
                $sizeof = count($results);
                $locked = api_resource_is_locked_by_gradebook(
                    $exercise_id,
                    LINK_EXERCISE
                );

                $timeNow = strtotime(api_get_utc_datetime());
                // Looping results
                for ($i = 0; $i < $sizeof; $i++) {
                    $revised = $results[$i]['revised'];
                    if ($results[$i]['completion_status'] == 'incomplete') {
                        // If the exercise was incomplete, we need to determine
                        // if it is still into the time allowed, or if its
                        // allowed time has expired and it can be closed
                        // (it's "unclosed")
                        $minutes = $results[$i]['expired_time'];
                        if ($minutes == 0) {
                            // There's no time limit, so obviously the attempt
                            // can still be "ongoing", but the teacher should
                            // be able to choose to close it, so mark it as
                            // "unclosed" instead of "ongoing"
                            $revised = 2;
                        } else {
                            $allowedSeconds = $minutes * 60;
                            $timeAttemptStarted = strtotime($results[$i]['start_date']);
                            $secondsSinceStart = $timeNow - $timeAttemptStarted;
                            if ($secondsSinceStart > $allowedSeconds) {
                                $revised = 2; // mark as "unclosed"
                            } else {
                                $revised = 3; // mark as "ongoing"
                            }
                        }
                    }

                    if ($from_gradebook && ($is_allowedToEdit)) {
                        if (in_array(
                            $results[$i]['username'].$results[$i]['firstname'].$results[$i]['lastname'],
                            $users_array_id
                        )) {
                            continue;
                        }
                        $users_array_id[] = $results[$i]['username'].$results[$i]['firstname'].$results[$i]['lastname'];
                    }

                    $lp_obj = isset($results[$i]['orig_lp_id']) && isset($lp_list[$results[$i]['orig_lp_id']]) ? $lp_list[$results[$i]['orig_lp_id']] : null;
                    if (empty($lp_obj)) {
                        // Try to get the old id (id instead of iid)
                        $lpNewId = isset($results[$i]['orig_lp_id']) && isset($oldIds[$results[$i]['orig_lp_id']]) ? $oldIds[$results[$i]['orig_lp_id']] : null;
                        if ($lpNewId) {
                            $lp_obj = isset($lp_list[$lpNewId]) ? $lp_list[$lpNewId] : null;
                        }
                    }
                    $lp_name = null;
                    if ($lp_obj) {
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$results[$i]['orig_lp_id'];
                        $lp_name = Display::url(
                            $lp_obj['lp_name'],
                            $url,
                            ['target' => '_blank']
                        );
                    }

                    // Add all groups by user
                    $group_name_list = '';
                    if ($is_empty_sql_inner_join_tbl_user) {
                        $group_list = GroupManager::get_group_ids(
                            api_get_course_int_id(),
                            $results[$i]['user_id']
                        );

                        foreach ($group_list as $id) {
                            if (isset($clean_group_list[$id])) {
                                $group_name_list .= $clean_group_list[$id].'<br/>';
                            }
                        }
                        $results[$i]['group_name'] = $group_name_list;
                    }

                    $results[$i]['exe_duration'] = !empty($results[$i]['exe_duration']) ? round($results[$i]['exe_duration'] / 60) : 0;
                    $id = $results[$i]['exe_id'];
                    $dt = api_convert_and_format_date($results[$i]['max_score']);

                    // we filter the results if we have the permission to
                    $result_disabled = 0;
                    if (isset($results[$i]['results_disabled'])) {
                        $result_disabled = (int) $results[$i]['results_disabled'];
                    }
                    if ($result_disabled == 0) {
                        $my_res = $results[$i]['score'];
                        $my_total = $results[$i]['max_score'];
                        $results[$i]['start_date'] = api_get_local_time($results[$i]['start_date']);
                        $results[$i]['exe_date'] = api_get_local_time($results[$i]['exe_date']);

                        if (!$results[$i]['propagate_neg'] && $my_res < 0) {
                            $my_res = 0;
                        }

                        $score = self::show_score(
                            $my_res,
                            $my_total,
                            true,
                            true,
                            false,
                            false,
                            $decimalSeparator,
                            $thousandSeparator,
                            $roundValues
                        );

                        $actions = '<div class="pull-right">';
                        if ($is_allowedToEdit) {
                            if (isset($teacher_id_list)) {
                                if (in_array(
                                    $results[$i]['exe_user_id'],
                                    $teacher_id_list
                                )) {
                                    $actions .= Display::return_icon('teacher.png', get_lang('Trainer'));
                                }
                            }
                            $revisedLabel = '';
                            switch ($revised) {
                                case 0:
                                    $actions .= "<a href='exercise_show.php?".api_get_cidreq()."&action=qualify&id=$id'>".
                                        Display:: return_icon(
                                            'quiz.png',
                                            get_lang('Grade activity')
                                        );
                                    $actions .= '</a>';
                                    $revisedLabel = Display::label(
                                        get_lang('Not validated'),
                                        'info'
                                    );
                                    break;
                                case 1:
                                    $actions .= "<a href='exercise_show.php?".api_get_cidreq()."&action=edit&id=$id'>".
                                        Display:: return_icon(
                                            'edit.png',
                                            get_lang('Edit'),
                                            [],
                                            ICON_SIZE_SMALL
                                        );
                                    $actions .= '</a>';
                                    $revisedLabel = Display::label(
                                        get_lang('Validated'),
                                        'success'
                                    );
                                    break;
                                case 2: //finished but not marked as such
                                    $actions .= '<a href="exercise_report.php?'
                                        .api_get_cidreq()
                                        .'&exerciseId='
                                        .$exercise_id
                                        .'&a=close&id='
                                        .$id
                                        .'">'.
                                        Display:: return_icon(
                                            'lock.png',
                                            get_lang('Mark attempt as closed'),
                                            [],
                                            ICON_SIZE_SMALL
                                        );
                                    $actions .= '</a>';
                                    $revisedLabel = Display::label(
                                        get_lang('Unclosed'),
                                        'warning'
                                    );
                                    break;
                                case 3: //still ongoing
                                    $actions .= Display:: return_icon(
                                        'clock.png',
                                        get_lang('Attempt still going on. Please wait.'),
                                        [],
                                        ICON_SIZE_SMALL
                                    );
                                    $actions .= '';
                                    $revisedLabel = Display::label(
                                        get_lang('Ongoing'),
                                        'danger'
                                    );
                                    break;
                            }

                            if ($filter == 2) {
                                $actions .= ' <a href="exercise_history.php?'.api_get_cidreq().'&exe_id='.$id.'">'.
                                    Display:: return_icon(
                                        'history.png',
                                        get_lang('View changes history')
                                    ).'</a>';
                            }

                            // Admin can always delete the attempt
                            if (($locked == false || api_is_platform_admin()) && !api_is_student_boss()) {
                                $ip = Tracking::get_ip_from_user_event(
                                    $results[$i]['exe_user_id'],
                                    api_get_utc_datetime(),
                                    false
                                );
                                $actions .= '<a href="http://www.whatsmyip.org/ip-geo-location/?ip='.$ip.'" target="_blank">'
                                    .Display::return_icon('info.png', $ip)
                                    .'</a>';

                                $recalculateUrl = api_get_path(WEB_CODE_PATH).'exercise/recalculate.php?'.
                                    api_get_cidreq().'&'.
                                    http_build_query([
                                        'id' => $id,
                                        'exercise' => $exercise_id,
                                        'user' => $results[$i]['exe_user_id'],
                                    ]);
                                $actions .= Display::url(
                                    Display::return_icon('reload.png', get_lang('Recalculate results')),
                                    $recalculateUrl,
                                    [
                                        'data-exercise' => $exercise_id,
                                        'data-user' => $results[$i]['exe_user_id'],
                                        'data-id' => $id,
                                        'class' => 'exercise-recalculate',
                                    ]
                                );

                                $filterByUser = isset($_GET['filter_by_user']) ? (int) $_GET['filter_by_user'] : 0;
                                $delete_link = '<a href="exercise_report.php?'.api_get_cidreq().'&filter_by_user='.$filterByUser.'&filter='.$filter.'&exerciseId='.$exercise_id.'&delete=delete&did='.$id.'"
                                onclick="javascript:if(!confirm(\''.sprintf(
                                    addslashes(get_lang('Delete attempt?')),
                                    $results[$i]['username'],
                                    $dt
                                ).'\')) return false;">';
                                $delete_link .= Display::return_icon(
                                    'delete.png',
                                        addslashes(get_lang('Delete'))
                                ).'</a>';

                                if (api_is_drh() && !api_is_platform_admin()) {
                                    $delete_link = null;
                                }
                                if (api_is_session_admin()) {
                                    $delete_link = '';
                                }
                                if ($revised == 3) {
                                    $delete_link = null;
                                }
                                $actions .= $delete_link;
                            }
                        } else {
                            $attempt_url = api_get_path(WEB_CODE_PATH).'exercise/result.php?'.api_get_cidreq().'&id='.$results[$i]['exe_id'].'&id_session='.$sessionId;
                            $attempt_link = Display::url(
                                get_lang('Show'),
                                $attempt_url,
                                [
                                    'class' => 'ajax btn btn-default',
                                    'data-title' => get_lang('Show'),
                                ]
                            );
                            $actions .= $attempt_link;
                        }
                        $actions .= '</div>';

                        if (!empty($userExtraFieldsToAdd)) {
                            foreach ($userExtraFieldsToAdd as $variable) {
                                $extraFieldValue = new ExtraFieldValue('user');
                                $values = $extraFieldValue->get_values_by_handler_and_field_variable(
                                    $results[$i]['user_id'],
                                    $variable
                                );
                                if (isset($values['value'])) {
                                    $results[$i][$variable] = $values['value'];
                                }
                            }
                        }

                        $exeId = $results[$i]['exe_id'];
                        $results[$i]['id'] = $exeId;
                        $category_list = [];
                        if ($is_allowedToEdit) {
                            $sessionName = '';
                            $sessionStartAccessDate = '';
                            if (!empty($results[$i]['session_id'])) {
                                $sessionInfo = api_get_session_info($results[$i]['session_id']);
                                if (!empty($sessionInfo)) {
                                    $sessionName = $sessionInfo['name'];
                                    $sessionStartAccessDate = api_get_local_time($sessionInfo['access_start_date']);
                                }
                            }

                            $objExercise = new Exercise($course_id);
                            if ($showExerciseCategories) {
                                // Getting attempt info
                                $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
                                if (!empty($exercise_stat_info['data_tracking'])) {
                                    $question_list = explode(',', $exercise_stat_info['data_tracking']);
                                    if (!empty($question_list)) {
                                        foreach ($question_list as $questionId) {
                                            $objQuestionTmp = Question::read($questionId, $objExercise->course);
                                            // We're inside *one* question. Go through each possible answer for this question
                                            $result = $objExercise->manage_answer(
                                                $exeId,
                                                $questionId,
                                                null,
                                                'exercise_result',
                                                false,
                                                false,
                                                true,
                                                false,
                                                $objExercise->selectPropagateNeg(),
                                                null,
                                                true
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

                                            if (isset($objQuestionTmp->category_list) &&
                                                !empty($objQuestionTmp->category_list)
                                            ) {
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
                                        }
                                    }
                                }
                            }

                            foreach ($category_list as $categoryId => $result) {
                                $scoreToDisplay = self::show_score(
                                    $result['score'],
                                    $result['total'],
                                    true,
                                    true,
                                    false,
                                    false,
                                    $decimalSeparator,
                                    $thousandSeparator,
                                    $roundValues
                                );
                                $results[$i]['category_'.$categoryId] = $scoreToDisplay;
                                $results[$i]['category_'.$categoryId.'_score_percentage'] = self::show_score(
                                    $result['score'],
                                    $result['total'],
                                    true,
                                    true,
                                    true, // $show_only_percentage = false
                                    true, // hide % sign
                                    $decimalSeparator,
                                    $thousandSeparator,
                                    $roundValues
                                );
                                $results[$i]['category_'.$categoryId.'_only_score'] = $result['score'];
                                $results[$i]['category_'.$categoryId.'_total'] = $result['total'];
                            }
                            $results[$i]['session'] = $sessionName;
                            $results[$i]['session_access_start_date'] = $sessionStartAccessDate;
                            $results[$i]['status'] = $revisedLabel;
                            $results[$i]['score'] = $score;
                            $results[$i]['score_percentage'] = self::show_score(
                                $my_res,
                                $my_total,
                                true,
                                true,
                                true,
                                true,
                                $decimalSeparator,
                                $thousandSeparator,
                                $roundValues
                            );

                            if ($roundValues) {
                                $whole = floor($my_res); // 1
                                $fraction = $my_res - $whole; // .25
                                if ($fraction >= 0.5) {
                                    $onlyScore = ceil($my_res);
                                } else {
                                    $onlyScore = round($my_res);
                                }
                            } else {
                                $onlyScore = $scoreDisplay->format_score(
                                    $my_res,
                                    false,
                                    $decimalSeparator,
                                    $thousandSeparator
                                );
                            }

                            $results[$i]['only_score'] = $onlyScore;

                            if ($roundValues) {
                                $whole = floor($my_total); // 1
                                $fraction = $my_total - $whole; // .25
                                if ($fraction >= 0.5) {
                                    $onlyTotal = ceil($my_total);
                                } else {
                                    $onlyTotal = round($my_total);
                                }
                            } else {
                                $onlyTotal = $scoreDisplay->format_score(
                                    $my_total,
                                    false,
                                    $decimalSeparator,
                                    $thousandSeparator
                                );
                            }
                            $results[$i]['total'] = $onlyTotal;
                            $results[$i]['lp'] = $lp_name;
                            $results[$i]['actions'] = $actions;
                            $listInfo[] = $results[$i];
                        } else {
                            $results[$i]['status'] = $revisedLabel;
                            $results[$i]['score'] = $score;
                            $results[$i]['actions'] = $actions;
                            $listInfo[] = $results[$i];
                        }
                    }
                }
            }
        } else {
            $hpresults = [];
            $res = Database::query($hpsql);
            if ($res !== false) {
                $i = 0;
                while ($resA = Database::fetch_array($res, 'NUM')) {
                    for ($j = 0; $j < 6; $j++) {
                        $hpresults[$i][$j] = $resA[$j];
                    }
                    $i++;
                }
            }

            // Print HotPotatoes test results.
            if (is_array($hpresults)) {
                for ($i = 0; $i < count($hpresults); $i++) {
                    $hp_title = GetQuizName($hpresults[$i][3], $documentPath);
                    if ($hp_title == '') {
                        $hp_title = basename($hpresults[$i][3]);
                    }

                    $hp_date = api_get_local_time(
                        $hpresults[$i][6],
                        null,
                        date_default_timezone_get()
                    );
                    $hp_result = round(($hpresults[$i][4] / ($hpresults[$i][5] != 0 ? $hpresults[$i][5] : 1)) * 100, 2);
                    $hp_result .= '% ('.$hpresults[$i][4].' / '.$hpresults[$i][5].')';

                    if ($is_allowedToEdit) {
                        $listInfo[] = [
                            $hpresults[$i][0],
                            $hpresults[$i][1],
                            $hpresults[$i][2],
                            '',
                            $hp_title,
                            '-',
                            $hp_date,
                            $hp_result,
                            '-',
                        ];
                    } else {
                        $listInfo[] = [
                            $hp_title,
                            '-',
                            $hp_date,
                            $hp_result,
                            '-',
                        ];
                    }
                }
            }
        }

        return $listInfo;
    }

    /**
     * @param $score
     * @param $weight
     *
     * @return array
     */
    public static function convertScoreToPlatformSetting($score, $weight)
    {
        $maxNote = api_get_setting('exercise_max_score');
        $minNote = api_get_setting('exercise_min_score');

        if ($maxNote != '' && $minNote != '') {
            if (!empty($weight) && intval($weight) != 0) {
                $score = $minNote + ($maxNote - $minNote) * $score / $weight;
            } else {
                $score = $minNote;
            }
            $weight = $maxNote;
        }

        return ['score' => $score, 'weight' => $weight];
    }

    /**
     * Converts the score with the exercise_max_note and exercise_min_score
     * the platform settings + formats the results using the float_format function.
     *
     * @param float  $score
     * @param float  $weight
     * @param bool   $show_percentage       show percentage or not
     * @param bool   $use_platform_settings use or not the platform settings
     * @param bool   $show_only_percentage
     * @param bool   $hidePercentageSign    hide "%" sign
     * @param string $decimalSeparator
     * @param string $thousandSeparator
     * @param bool   $roundValues           This option rounds the float values into a int using ceil()
     *
     * @return string an html with the score modified
     */
    public static function show_score(
        $score,
        $weight,
        $show_percentage = true,
        $use_platform_settings = true,
        $show_only_percentage = false,
        $hidePercentageSign = false,
        $decimalSeparator = '.',
        $thousandSeparator = ',',
        $roundValues = false
    ) {
        if (is_null($score) && is_null($weight)) {
            return '-';
        }

        if ($use_platform_settings) {
            $result = self::convertScoreToPlatformSetting($score, $weight);
            $score = $result['score'];
            $weight = $result['weight'];
        }

        $percentage = (100 * $score) / ($weight != 0 ? $weight : 1);
        // Formats values
        $percentage = float_format($percentage, 1);
        $score = float_format($score, 1);
        $weight = float_format($weight, 1);
        if ($roundValues) {
            $whole = floor($percentage); // 1
            $fraction = $percentage - $whole; // .25

            // Formats values
            if ($fraction >= 0.5) {
                $percentage = ceil($percentage);
            } else {
                $percentage = round($percentage);
            }

            $whole = floor($score); // 1
            $fraction = $score - $whole; // .25
            if ($fraction >= 0.5) {
                $score = ceil($score);
            } else {
                $score = round($score);
            }

            $whole = floor($weight); // 1
            $fraction = $weight - $whole; // .25
            if ($fraction >= 0.5) {
                $weight = ceil($weight);
            } else {
                $weight = round($weight);
            }
        } else {
            // Formats values
            $percentage = float_format($percentage, 1, $decimalSeparator, $thousandSeparator);
            $score = float_format($score, 1, $decimalSeparator, $thousandSeparator);
            $weight = float_format($weight, 1, $decimalSeparator, $thousandSeparator);
        }

        if ($show_percentage) {
            $percentageSign = '%';
            if ($hidePercentageSign) {
                $percentageSign = '';
            }
            $html = $percentage."$percentageSign ($score / $weight)";
            if ($show_only_percentage) {
                $html = $percentage.$percentageSign;
            }
        } else {
            $html = $score.' / '.$weight;
        }

        // Over write score
        $scoreBasedInModel = self::convertScoreToModel($percentage);
        if (!empty($scoreBasedInModel)) {
            $html = $scoreBasedInModel;
        }

        // Ignore other formats and use the configuratio['exercise_score_format'] value
        // But also keep the round values settings.
        $format = api_get_configuration_value('exercise_score_format');
        if (!empty($format)) {
            $html = ScoreDisplay::instance()->display_score([$score, $weight], $format);
        }

        $html = Display::span($html, ['class' => 'score_exercise']);

        return $html;
    }

    /**
     * @param array $model
     * @param float $percentage
     *
     * @return string
     */
    public static function getModelStyle($model, $percentage)
    {
        $modelWithStyle = '<span class="'.$model['css_class'].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>';

        return $modelWithStyle;
    }

    /**
     * @param float $percentage value between 0 and 100
     *
     * @return string
     */
    public static function convertScoreToModel($percentage)
    {
        $model = self::getCourseScoreModel();
        if (!empty($model)) {
            $scoreWithGrade = [];
            foreach ($model['score_list'] as $item) {
                if ($percentage >= $item['min'] && $percentage <= $item['max']) {
                    $scoreWithGrade = $item;
                    break;
                }
            }

            if (!empty($scoreWithGrade)) {
                return self::getModelStyle($scoreWithGrade, $percentage);
            }
        }

        return '';
    }

    /**
     * @return array
     */
    public static function getCourseScoreModel()
    {
        $modelList = self::getScoreModels();
        if (empty($modelList)) {
            return [];
        }

        $courseInfo = api_get_course_info();
        if (!empty($courseInfo)) {
            $scoreModelId = api_get_course_setting('score_model_id');
            if ($scoreModelId != -1) {
                $modelIdList = array_column($modelList['models'], 'id');
                if (in_array($scoreModelId, $modelIdList)) {
                    foreach ($modelList['models'] as $item) {
                        if ($item['id'] == $scoreModelId) {
                            return $item;
                        }
                    }
                }
            }
        }

        return [];
    }

    /**
     * @return array
     */
    public static function getScoreModels()
    {
        return api_get_configuration_value('score_grade_model');
    }

    /**
     * @param float  $score
     * @param float  $weight
     * @param string $pass_percentage
     *
     * @return bool
     */
    public static function isSuccessExerciseResult($score, $weight, $pass_percentage)
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
     * @param FormValidator $form
     * @param string        $name
     * @param $weight
     * @param $selected
     *
     * @return bool
     */
    public static function addScoreModelInput(
        FormValidator $form,
        $name,
        $weight,
        $selected
    ) {
        $model = self::getCourseScoreModel();
        if (empty($model)) {
            return false;
        }

        /** @var HTML_QuickForm_select $element */
        $element = $form->createElement(
            'select',
            $name,
            get_lang('Score'),
            [],
            ['class' => 'exercise_mark_select']
        );

        foreach ($model['score_list'] as $item) {
            $i = api_number_format($item['score_to_qualify'] / 100 * $weight, 2);
            $label = self::getModelStyle($item, $i);
            $attributes = [
                'class' => $item['css_class'],
            ];
            if ($selected == $i) {
                $attributes['selected'] = 'selected';
            }
            $element->addOption($label, $i, $attributes);
        }
        $form->addElement($element);
    }

    /**
     * @return string
     */
    public static function getJsCode()
    {
        // Filling the scores with the right colors.
        $models = self::getCourseScoreModel();
        $cssListToString = '';
        if (!empty($models)) {
            $cssList = array_column($models['score_list'], 'css_class');
            $cssListToString = implode(' ', $cssList);
        }

        if (empty($cssListToString)) {
            return '';
        }
        $js = <<<EOT
        
        function updateSelect(element) {
            var spanTag = element.parent().find('span.filter-option');
            var value = element.val();
            var selectId = element.attr('id');
            var optionClass = $('#' + selectId + ' option[value="'+value+'"]').attr('class');
            spanTag.removeClass('$cssListToString');
            spanTag.addClass(optionClass);
        }
        
        $(function() {
            // Loading values
            $('.exercise_mark_select').on('loaded.bs.select', function() {
                updateSelect($(this));
            });
            // On change
            $('.exercise_mark_select').on('changed.bs.select', function() {
                updateSelect($(this));
            });
        });
EOT;

        return $js;
    }

    /**
     * @param float  $score
     * @param float  $weight
     * @param string $pass_percentage
     *
     * @return string
     */
    public static function showSuccessMessage($score, $weight, $pass_percentage)
    {
        $res = '';
        if (self::isPassPercentageEnabled($pass_percentage)) {
            $isSuccess = self::isSuccessExerciseResult(
                $score,
                $weight,
                $pass_percentage
            );

            if ($isSuccess) {
                $html = get_lang('Congratulations you passed the test!');
                $icon = Display::return_icon(
                    'completed.png',
                    get_lang('Correct'),
                    [],
                    ICON_SIZE_MEDIUM
                );
            } else {
                $html = get_lang('You didn\'t reach the minimum score');
                $icon = Display::return_icon(
                    'warning.png',
                    get_lang('Wrong'),
                    [],
                    ICON_SIZE_MEDIUM
                );
            }
            $html = Display::tag('h4', $html);
            $html .= Display::tag(
                'h5',
                $icon,
                ['style' => 'width:40px; padding:2px 10px 0px 0px']
            );
            $res = $html;
        }

        return $res;
    }

    /**
     * Return true if pass_pourcentage activated (we use the pass pourcentage feature
     * return false if pass_percentage = 0 (we don't use the pass pourcentage feature.
     *
     * @param $value
     *
     * @return bool
     *              In this version, pass_percentage and show_success_message are disabled if
     *              pass_percentage is set to 0
     */
    public static function isPassPercentageEnabled($value)
    {
        return $value > 0;
    }

    /**
     * Converts a numeric value in a percentage example 0.66666 to 66.67 %.
     *
     * @param $value
     *
     * @return float Converted number
     */
    public static function convert_to_percentage($value)
    {
        $return = '-';
        if ($value != '') {
            $return = float_format($value * 100, 1).' %';
        }

        return $return;
    }

    /**
     * Getting all active exercises from a course from a session
     * (if a session_id is provided we will show all the exercises in the course +
     * all exercises in the session).
     *
     * @param array  $course_info
     * @param int    $session_id
     * @param bool   $check_publication_dates
     * @param string $search                  Search exercise name
     * @param bool   $search_all_sessions     Search exercises in all sessions
     * @param   int 0 = only inactive exercises
     *                  1 = only active exercises,
     *                  2 = all exercises
     *                  3 = active <> -1
     *
     * @return array array with exercise data
     */
    public static function get_all_exercises(
        $course_info = null,
        $session_id = 0,
        $check_publication_dates = false,
        $search = '',
        $search_all_sessions = false,
        $active = 2
    ) {
        $course_id = api_get_course_int_id();

        if (!empty($course_info) && !empty($course_info['real_id'])) {
            $course_id = $course_info['real_id'];
        }

        if ($session_id == -1) {
            $session_id = 0;
        }

        $now = api_get_utc_datetime();
        $timeConditions = '';
        if ($check_publication_dates) {
            // Start and end are set
            $timeConditions = " AND ((start_time <> '' AND start_time < '$now' AND end_time <> '' AND end_time > '$now' )  OR ";
            // only start is set
            $timeConditions .= " (start_time <> '' AND start_time < '$now' AND end_time is NULL) OR ";
            // only end is set
            $timeConditions .= " (start_time IS NULL AND end_time <> '' AND end_time > '$now') OR ";
            // nothing is set
            $timeConditions .= ' (start_time IS NULL AND end_time IS NULL)) ';
        }

        $needle_where = !empty($search) ? " AND title LIKE '?' " : '';
        $needle = !empty($search) ? "%".$search."%" : '';

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
            $conditions = [
                'where' => [
                    $active_sql.' c_id = ? '.$needle_where.$timeConditions => [
                        $course_id,
                        $needle,
                    ],
                ],
                'order' => 'title',
            ];
        } else {
            if (empty($session_id)) {
                $conditions = [
                    'where' => [
                        $active_sql.' (session_id = 0 OR session_id IS NULL) AND c_id = ? '.$needle_where.$timeConditions => [
                            $course_id,
                            $needle,
                        ],
                    ],
                    'order' => 'title',
                ];
            } else {
                $conditions = [
                    'where' => [
                        $active_sql.' (session_id = 0 OR session_id IS NULL OR session_id = ? ) AND c_id = ? '.$needle_where.$timeConditions => [
                            $session_id,
                            $course_id,
                            $needle,
                        ],
                    ],
                    'order' => 'title',
                ];
            }
        }

        $table = Database::get_course_table(TABLE_QUIZ_TEST);

        return Database::select('*', $table, $conditions);
    }

    /**
     * Getting all exercises (active only or all)
     * from a course from a session
     * (if a session_id is provided we will show all the exercises in the
     * course + all exercises in the session).
     *
     * @param   array   course data
     * @param   int     session id
     * @param    int        course c_id
     * @param bool $only_active_exercises
     *
     * @return array array with exercise data
     *               modified by Hubert Borderiou
     */
    public static function get_all_exercises_for_course_id(
        $course_info = null,
        $session_id = 0,
        $course_id = 0,
        $only_active_exercises = true
    ) {
        $table = Database::get_course_table(TABLE_QUIZ_TEST);

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

        $params = [
            $session_id,
            $course_id,
        ];

        if (empty($session_id)) {
            $conditions = [
                'where' => ["$sql_active_exercises (session_id = 0 OR session_id IS NULL) AND c_id = ?" => [$course_id]],
                'order' => 'title',
            ];
        } else {
            // All exercises
            $conditions = [
                'where' => ["$sql_active_exercises (session_id = 0 OR session_id IS NULL OR session_id = ? ) AND c_id=?" => $params],
                'order' => 'title',
            ];
        }

        return Database::select('*', $table, $conditions);
    }

    /**
     * Gets the position of the score based in a given score (result/weight)
     * and the exe_id based in the user list
     * (NO Exercises in LPs ).
     *
     * @param float  $my_score      user score to be compared *attention*
     *                              $my_score = score/weight and not just the score
     * @param int    $my_exe_id     exe id of the exercise
     *                              (this is necessary because if 2 students have the same score the one
     *                              with the minor exe_id will have a best position, just to be fair and FIFO)
     * @param int    $exercise_id
     * @param string $course_code
     * @param int    $session_id
     * @param array  $user_list
     * @param bool   $return_string
     *
     * @return int the position of the user between his friends in a course
     *             (or course within a session)
     */
    public static function get_exercise_result_ranking(
        $my_score,
        $my_exe_id,
        $exercise_id,
        $course_code,
        $session_id = 0,
        $user_list = [],
        $return_string = true
    ) {
        //No score given we return
        if (is_null($my_score)) {
            return '-';
        }
        if (empty($user_list)) {
            return '-';
        }

        $best_attempts = [];
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
            $my_ranking = [];
            foreach ($best_attempts as $user_id => $result) {
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $my_ranking[$user_id] = $result['score'] / $result['max_score'];
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
                        if ($my_score == $ranking && isset($best_attempts[$user_id]['exe_id'])) {
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
            $return_value = [
                'position' => $position,
                'count' => count($my_ranking),
            ];

            if ($return_string) {
                if (!empty($position) && !empty($my_ranking)) {
                    $return_value = $position.'/'.count($my_ranking);
                } else {
                    $return_value = '-';
                }
            }

            return $return_value;
        }
    }

    /**
     * Gets the position of the score based in a given score (result/weight) and the exe_id based in all attempts
     * (NO Exercises in LPs ) old functionality by attempt.
     *
     * @param   float   user score to be compared attention => score/weight
     * @param   int     exe id of the exercise
     * (this is necessary because if 2 students have the same score the one
     * with the minor exe_id will have a best position, just to be fair and FIFO)
     * @param   int     exercise id
     * @param   string  course code
     * @param   int     session id
     * @param bool $return_string
     *
     * @return int the position of the user between his friends in a course (or course within a session)
     */
    public static function get_exercise_result_ranking_by_attempt(
        $my_score,
        $my_exe_id,
        $exercise_id,
        $courseId,
        $session_id = 0,
        $return_string = true
    ) {
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
        $position_data = [];
        if (empty($user_results)) {
            return 1;
        } else {
            $position = 1;
            $my_ranking = [];
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $my_ranking[$result['exe_id']] = $result['score'] / $result['max_score'];
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
            $return_value = [
                'position' => $position,
                'count' => count($my_ranking),
            ];

            if ($return_string) {
                if (!empty($position) && !empty($my_ranking)) {
                    return $position.'/'.count($my_ranking);
                }
            }

            return $return_value;
        }
    }

    /**
     * Get the best attempt in a exercise (NO Exercises in LPs ).
     *
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

        $best_score_data = [];
        $best_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) &&
                    intval($result['max_score']) != 0
                ) {
                    $score = $result['score'] / $result['max_score'];
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
     * Get the best score in a exercise (NO Exercises in LPs ).
     *
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
    ) {
        $user_results = Event::get_all_exercise_results(
            $exercise_id,
            $courseId,
            $session_id,
            false,
            $user_id
        );
        $best_score_data = [];
        $best_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $score = $result['score'] / $result['max_score'];
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
     * Get average score (NO Exercises in LPs ).
     *
     * @param    int    exercise id
     * @param int $courseId
     * @param    int    session id
     *
     * @return float Average score
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
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $score = $result['score'] / $result['max_score'];
                    $avg_score += $score;
                }
            }
            $avg_score = float_format($avg_score / count($user_results), 1);
        }

        return $avg_score;
    }

    /**
     * Get average score by score (NO Exercises in LPs ).
     *
     * @param int $courseId
     * @param    int    session id
     *
     * @return float Average score
     */
    public static function get_average_score_by_course($courseId, $session_id)
    {
        $user_results = Event::get_all_exercise_results_by_course(
            $courseId,
            $session_id,
            false
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) && intval(
                        $result['max_score']
                    ) != 0
                ) {
                    $score = $result['score'] / $result['max_score'];
                    $avg_score += $score;
                }
            }
            // We assume that all max_score
            $avg_score = $avg_score / count($user_results);
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
    ) {
        $user_results = Event::get_all_exercise_results_by_user(
            $user_id,
            $courseId,
            $session_id
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $score = $result['score'] / $result['max_score'];
                    $avg_score += $score;
                }
            }
            // We assume that all max_score
            $avg_score = ($avg_score / count($user_results));
        }

        return $avg_score;
    }

    /**
     * Get average score by score (NO Exercises in LPs ).
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     * @param int $user_count
     *
     * @return float Best average score
     */
    public static function get_best_average_score_by_exercise(
        $exercise_id,
        $courseId,
        $session_id,
        $user_count
    ) {
        $user_results = Event::get_best_exercise_results_by_user(
            $exercise_id,
            $courseId,
            $session_id
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $score = $result['score'] / $result['max_score'];
                    $avg_score += $score;
                }
            }
            // We asumme that all max_score
            if (!empty($user_count)) {
                $avg_score = float_format($avg_score / $user_count, 1) * 100;
            } else {
                $avg_score = 0;
            }
        }

        return $avg_score;
    }

    /**
     * Get average score by score (NO Exercises in LPs ).
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return float Best average score
     */
    public static function getBestScoreByExercise(
        $exercise_id,
        $courseId,
        $session_id
    ) {
        $user_results = Event::get_best_exercise_results_by_user(
            $exercise_id,
            $courseId,
            $session_id
        );
        $avg_score = 0;
        if (!empty($user_results)) {
            foreach ($user_results as $result) {
                if (!empty($result['max_score']) && intval($result['max_score']) != 0) {
                    $score = $result['score'] / $result['max_score'];
                    $avg_score += $score;
                }
            }
        }

        return $avg_score;
    }

    /**
     * @param string $course_code
     * @param int    $session_id
     *
     * @return array
     */
    public static function get_exercises_to_be_taken($course_code, $session_id)
    {
        $course_info = api_get_course_info($course_code);
        $exercises = self::get_all_exercises($course_info, $session_id);
        $result = [];
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
     * Get student results (only in completed exercises) stats by question.
     *
     * @param int    $question_id
     * @param int    $exercise_id
     * @param string $course_code
     * @param int    $session_id
     *
     * @return array
     */
    public static function get_student_stats_by_question(
        $question_id,
        $exercise_id,
        $course_code,
        $session_id
    ) {
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $question_id = (int) $question_id;
        $exercise_id = (int) $exercise_id;
        $course_code = Database::escape_string($course_code);
        $session_id = (int) $session_id;
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
        $return = [];
        if ($result) {
            $return = Database::fetch_array($result, 'ASSOC');
        }

        return $return;
    }

    /**
     * Get the correct answer count for a fill blanks question.
     *
     * @param int $question_id
     * @param int $exercise_id
     *
     * @return array
     */
    public static function getNumberStudentsFillBlanksAnswerCount(
        $question_id,
        $exercise_id
    ) {
        $listStudentsId = [];
        $listAllStudentInfo = CourseManager::get_student_list_from_course_code(
            api_get_course_id(),
            true
        );
        foreach ($listAllStudentInfo as $i => $listStudentInfo) {
            $listStudentsId[] = $listStudentInfo['user_id'];
        }

        $listFillTheBlankResult = FillBlanks::getFillTheBlankResult(
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
     * Get the number of questions with answers.
     *
     * @param int    $question_id
     * @param int    $exercise_id
     * @param string $course_code
     * @param int    $session_id
     * @param string $questionType
     *
     * @return int
     */
    public static function get_number_students_question_with_answer_count(
        $question_id,
        $exercise_id,
        $course_code,
        $session_id,
        $questionType = ''
    ) {
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $question_id = intval($question_id);
        $exercise_id = intval($exercise_id);
        $courseId = api_get_course_int_id($course_code);
        $session_id = intval($session_id);

        if ($questionType == FILL_IN_BLANKS) {
            $listStudentsId = [];
            $listAllStudentInfo = CourseManager::get_student_list_from_course_code(
                api_get_course_id(),
                true
            );
            foreach ($listAllStudentInfo as $i => $listStudentInfo) {
                $listStudentsId[] = $listStudentInfo['user_id'];
            }

            $listFillTheBlankResult = FillBlanks::getFillTheBlankResult(
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
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
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
     * Get number of answers to hotspot questions.
     *
     * @param int    $answer_id
     * @param int    $question_id
     * @param int    $exercise_id
     * @param string $courseId
     * @param int    $session_id
     *
     * @return int
     */
    public static function get_number_students_answer_hotspot_count(
        $answer_id,
        $question_id,
        $exercise_id,
        $courseId,
        $session_id
    ) {
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_hotspot = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $question_id = (int) $question_id;
        $answer_id = (int) $answer_id;
        $exercise_id = (int) $exercise_id;
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;

        if (empty($session_id)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id  = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON cu.c_id = c.id AND cu.user_id = exe_user_id";
            $courseConditionWhere = ' AND cu.status = 0 ';
        }

        $sql = "SELECT DISTINCT exe_user_id
    		FROM $track_exercises e
    		INNER JOIN $track_hotspot a
    		ON (a.hotspot_exe_id = e.exe_id)
    		INNER JOIN $courseTable c
    		ON (a.c_id = c.id)
    		$courseCondition
    		WHERE
    		    exe_exo_id              = $exercise_id AND
                a.c_id 	= $courseId AND
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
     * @param int    $answer_id
     * @param int    $question_id
     * @param int    $exercise_id
     * @param string $course_code
     * @param int    $session_id
     * @param string $question_type
     * @param string $correct_answer
     * @param string $current_answer
     *
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
    ) {
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $question_id = (int) $question_id;
        $answer_id = (int) $answer_id;
        $exercise_id = (int) $exercise_id;
        $courseId = api_get_course_int_id($course_code);
        $session_id = (int) $session_id;

        switch ($question_type) {
            case FILL_IN_BLANKS:
                $answer_condition = '';
                $select_condition = ' e.exe_id, answer ';
                break;
            case MATCHING:
            case MATCHING_DRAGGABLE:
            default:
                $answer_condition = " answer = $answer_id AND ";
                $select_condition = ' DISTINCT exe_user_id ';
        }

        if (empty($session_id)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id  = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON cu.c_id = a.c_id AND cu.user_id = exe_user_id";
            $courseConditionWhere = ' AND cu.status = 0 ';
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
                case MATCHING_DRAGGABLE:
                default:
                    $return = Database::num_rows($result);
            }
        }

        return $return;
    }

    /**
     * @param array  $answer
     * @param string $user_answer
     *
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
        if (isset($is_set_switchable[1]) && $is_set_switchable[1] == 1) {
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
        $user_tags = $correct_tags = $real_text = [];
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
        $chosen_list = [];
        $good_answer = [];

        for ($i = 0; $i < count($real_correct_tags); $i++) {
            if (!$switchable_answer_set) {
                //needed to parse ' and " characters
                $user_tags[$i] = stripslashes($user_tags[$i]);
                if ($correct_tags[$i] == $user_tags[$i]) {
                    $good_answer[$correct_tags[$i]] = 1;
                } elseif (!empty($user_tags[$i])) {
                    $good_answer[$correct_tags[$i]] = 0;
                } else {
                    $good_answer[$correct_tags[$i]] = 0;
                }
            } else {
                // switchable fill in the blanks
                if (in_array($user_tags[$i], $correct_tags)) {
                    $correct_tags = array_diff($correct_tags, $chosen_list);
                    $good_answer[$correct_tags[$i]] = 1;
                } elseif (!empty($user_tags[$i])) {
                    $good_answer[$correct_tags[$i]] = 0;
                } else {
                    $good_answer[$correct_tags[$i]] = 0;
                }
            }
            // adds the correct word, followed by ] to close the blank
            $answer .= ' / <font color="green"><b>'.$real_correct_tags[$i].'</b></font>]';
            if (isset($real_text[$i + 1])) {
                $answer .= $real_text[$i + 1];
            }
        }

        return $good_answer;
    }

    /**
     * @param int    $exercise_id
     * @param string $course_code
     * @param int    $session_id
     *
     * @return int
     */
    public static function get_number_students_finish_exercise(
        $exercise_id,
        $course_code,
        $session_id
    ) {
        $track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $exercise_id = (int) $exercise_id;
        $course_code = Database::escape_string($course_code);
        $session_id = (int) $session_id;

        $sql = "SELECT DISTINCT exe_user_id
                FROM $track_exercises e
                INNER JOIN $track_attempt a
                ON (a.exe_id = e.exe_id)
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
     * Return an HTML select menu with the student groups.
     *
     * @param string $name     is the name and the id of the <select>
     * @param string $default  default value for option
     * @param string $onchange
     *
     * @return string the html code of the <select>
     */
    public static function displayGroupMenu($name, $default, $onchange = "")
    {
        // check the default value of option
        $tabSelected = [$default => " selected='selected' "];
        $res = "";
        $res .= "<select name='$name' id='$name' onchange='".$onchange."' >";
        $res .= "<option value='-1'".$tabSelected["-1"].">-- ".get_lang(
                'AllGroups'
            )." --</option>";
        $res .= "<option value='0'".$tabSelected["0"].">- ".get_lang(
                'NotInAGroup'
            )." -</option>";
        $tabGroups = GroupManager::get_group_list();
        $currentCatId = 0;
        $countGroups = count($tabGroups);
        for ($i = 0; $i < $countGroups; $i++) {
            $tabCategory = GroupManager::get_category_from_group(
                $tabGroups[$i]['iid']
            );
            if ($tabCategory["id"] != $currentCatId) {
                $res .= "<option value='-1' disabled='disabled'>".$tabCategory["title"]."</option>";
                $currentCatId = $tabCategory["id"];
            }
            $res .= "<option ".$tabSelected[$tabGroups[$i]["id"]]."style='margin-left:40px' value='".
                $tabGroups[$i]["id"]."'>".
                $tabGroups[$i]["name"].
                "</option>";
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
            $_SESSION['current_exercises'] = [];
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
     * Display the exercise results.
     *
     * @param Exercise $objExercise
     * @param int      $exeId
     * @param bool     $save_user_result save users results (true) or just show the results (false)
     * @param string   $remainingMessage
     */
    public static function displayQuestionListByAttempt(
        $objExercise,
        $exeId,
        $save_user_result = false,
        $remainingMessage = ''
    ) {
        $origin = api_get_origin();
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();

        // Getting attempt info
        $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);

        // Getting question list
        $question_list = [];
        $studentInfo = [];
        if (!empty($exercise_stat_info['data_tracking'])) {
            $studentInfo = api_get_user_info($exercise_stat_info['exe_user_id']);
            $question_list = explode(',', $exercise_stat_info['data_tracking']);
        } else {
            // Try getting the question list only if save result is off
            if ($save_user_result == false) {
                $question_list = $objExercise->get_validated_question_list();
            }
            if (in_array(
                $objExercise->getFeedbackType(),
                [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP]
            )) {
                $question_list = $objExercise->get_validated_question_list();
            }
        }

        if ($objExercise->getResultAccess()) {
            if ($objExercise->hasResultsAccess($exercise_stat_info) === false) {
                echo Display::return_message(
                    sprintf(get_lang('YouPassedTheLimitOfXMinutesToSeeTheResults'), $objExercise->getResultsAccess())
                );

                return false;
            }

            if (!empty($objExercise->getResultAccess())) {
                $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id;
                echo $objExercise->returnTimeLeftDiv();
                echo $objExercise->showSimpleTimeControl(
                    $objExercise->getResultAccessTimeDiff($exercise_stat_info),
                    $url
                );
            }
        }

        $counter = 1;
        $total_score = $total_weight = 0;
        $exercise_content = null;

        // Hide results
        $show_results = false;
        $show_only_score = false;
        if (in_array($objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
            ]
        )) {
            $show_results = true;
        }

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_RANKING,
            ]
        )
        ) {
            $show_only_score = true;
        }

        // Not display expected answer, but score, and feedback
        $show_all_but_expected_answer = false;
        if ($objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_ONLY &&
            $objExercise->getFeedbackType() == EXERCISE_FEEDBACK_TYPE_END
        ) {
            $show_all_but_expected_answer = true;
            $show_results = true;
            $show_only_score = false;
        }

        $showTotalScoreAndUserChoicesInLastAttempt = true;
        $showTotalScore = true;
        $showQuestionScore = true;

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
            ])
        ) {
            $show_only_score = true;
            $show_results = true;
            $numberAttempts = 0;
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
                }

                if ($save_user_result) {
                    $numberAttempts++;
                }
                $showTotalScore = false;
                $showTotalScoreAndUserChoicesInLastAttempt = false;
                if ($numberAttempts >= $objExercise->attempts) {
                    $showTotalScore = true;
                    $show_results = true;
                    $show_only_score = false;
                    $showTotalScoreAndUserChoicesInLastAttempt = true;
                }
            }

            if ($objExercise->results_disabled ==
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK
            ) {
                $show_only_score = false;
                $show_results = true;
                $show_all_but_expected_answer = false;
                $showTotalScore = false;
                $showQuestionScore = false;
                if ($numberAttempts >= $objExercise->attempts) {
                    $showTotalScore = true;
                    $showQuestionScore = true;
                }
            }
        }

        if (($show_results || $show_only_score) && $origin !== 'embeddable') {
            if (isset($exercise_stat_info['exe_user_id'])) {
                if (!empty($studentInfo)) {
                    // Shows exercise header
                    echo $objExercise->showExerciseResultHeader(
                        $studentInfo,
                        $exercise_stat_info
                    );
                }
            }
        }

        // Display text when test is finished #4074 and for LP #4227
        $endOfMessage = $objExercise->getTextWhenFinished();
        if (!empty($endOfMessage)) {
            echo Display::return_message($endOfMessage, 'normal', false);
            echo "<div class='clear'>&nbsp;</div>";
        }

        $question_list_answers = [];
        $media_list = [];
        $category_list = [];
        $loadChoiceFromSession = false;
        $fromDatabase = true;
        $exerciseResult = null;
        $exerciseResultCoordinates = null;
        $delineationResults = null;

        if (in_array(
            $objExercise->getFeedbackType(),
            [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP]
        )) {
            $loadChoiceFromSession = true;
            $fromDatabase = false;
            $exerciseResult = Session::read('exerciseResult');
            $exerciseResultCoordinates = Session::read('exerciseResultCoordinates');
            $delineationResults = Session::read('hotspot_delineation_result');
            $delineationResults = isset($delineationResults[$objExercise->id]) ? $delineationResults[$objExercise->id] : null;
        }

        $countPendingQuestions = 0;
        $result = [];
        // Loop over all question to show results for each of them, one by one
        if (!empty($question_list)) {
            foreach ($question_list as $questionId) {
                // Creates a temporary Question object
                $objQuestionTmp = Question::read($questionId, $objExercise->course);
                // This variable came from exercise_submit_modal.php
                ob_start();
                $choice = null;
                $delineationChoice = null;
                if ($loadChoiceFromSession) {
                    $choice = isset($exerciseResult[$questionId]) ? $exerciseResult[$questionId] : null;
                    $delineationChoice = isset($delineationResults[$questionId]) ? $delineationResults[$questionId] : null;
                }

                // We're inside *one* question. Go through each possible answer for this question
                $result = $objExercise->manage_answer(
                    $exeId,
                    $questionId,
                    $choice,
                    'exercise_result',
                    $exerciseResultCoordinates,
                    $save_user_result,
                    $fromDatabase,
                    $show_results,
                    $objExercise->selectPropagateNeg(),
                    $delineationChoice,
                    $showTotalScoreAndUserChoicesInLastAttempt
                );

                if (empty($result)) {
                    continue;
                }

                $total_score += $result['score'];
                $total_weight += $result['weight'];

                $question_list_answers[] = [
                    'question' => $result['open_question'],
                    'answer' => $result['open_answer'],
                    'answer_type' => $result['answer_type'],
                    'generated_oral_file' => $result['generated_oral_file'],
                ];

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

                if ($objExercise->selectPropagateNeg() == 0 && $my_total_score < 0) {
                    $my_total_score = 0;
                }

                $comnt = null;
                if ($show_results) {
                    $comnt = Event::get_comments($exeId, $questionId);
                    $teacherAudio = self::getOralFeedbackAudio(
                        $exeId,
                        $questionId,
                        api_get_user_id()
                    );

                    if (!empty($comnt) || $teacherAudio) {
                        echo '<b>'.get_lang('Feedback').'</b>';
                    }

                    if (!empty($comnt)) {
                        echo self::getFeedbackText($comnt);
                    }

                    if ($teacherAudio) {
                        echo $teacherAudio;
                    }
                }

                $score = [];
                if ($show_results) {
                    $scorePassed = $my_total_score >= $my_total_weight;
                    if (function_exists('bccomp')) {
                        $compareResult = bccomp($my_total_score, $my_total_weight, 3);
                        $scorePassed = $compareResult === 1 || $compareResult === 0;
                    }
                    $score = [
                        'result' => self::show_score(
                            $my_total_score,
                            $my_total_weight,
                            false
                        ),
                        'pass' => $scorePassed,
                        'score' => $my_total_score,
                        'weight' => $my_total_weight,
                        'comments' => $comnt,
                        'user_answered' => $result['user_answered'],
                    ];
                }

                if (in_array($objQuestionTmp->type, [FREE_ANSWER, ORAL_EXPRESSION, ANNOTATION])) {
                    $reviewScore = [
                        'score' => $my_total_score,
                        'comments' => Event::get_comments($exeId, $questionId),
                    ];
                    $check = $objQuestionTmp->isQuestionWaitingReview($reviewScore);
                    if ($check === false) {
                        $countPendingQuestions++;
                    }
                }

                $contents = ob_get_clean();
                $question_content = '';
                if ($show_results) {
                    $question_content = '<div class="question_row_answer">';
                    if ($showQuestionScore == false) {
                        $score = [];
                    }

                    // Shows question title an description
                    $question_content .= $objQuestionTmp->return_header(
                        $objExercise,
                        $counter,
                        $score
                    );
                }
                $counter++;
                $question_content .= $contents;
                if ($show_results) {
                    $question_content .= '</div>';
                }
                if ($objExercise->showExpectedChoice()) {
                    $exercise_content .= Display::div(
                        Display::panel($question_content),
                        ['class' => 'question-panel']
                    );
                } else {
                    // $show_all_but_expected_answer should not happen at
                    // the same time as $show_results
                    if ($show_results && !$show_only_score) {
                        $exercise_content .= Display::div(
                            Display::panel($question_content),
                            ['class' => 'question-panel']
                        );
                    }
                }
            } // end foreach() block that loops over all questions
        }

        $totalScoreText = null;
        $certificateBlock = '';
        if (($show_results || $show_only_score) && $showTotalScore) {
            if ($result['answer_type'] == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                echo '<h1 style="text-align : center; margin : 20px 0;">'.get_lang('Your results').'</h1><br />';
            }
            $totalScoreText .= '<div class="question_row_score">';
            if ($result['answer_type'] == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                $totalScoreText .= self::getQuestionDiagnosisRibbon(
                    $objExercise,
                    $total_score,
                    $total_weight,
                    true
                );
            } else {
                $pluginEvaluation = QuestionOptionsEvaluationPlugin::create();

                if ('true' === $pluginEvaluation->get(QuestionOptionsEvaluationPlugin::SETTING_ENABLE)) {
                    $formula = $pluginEvaluation->getFormulaForExercise($objExercise->selectId());

                    if (!empty($formula)) {
                        $total_score = $pluginEvaluation->getResultWithFormula($exeId, $formula);
                        $total_weight = $pluginEvaluation->getMaxScore();
                    }
                }

                $totalScoreText .= self::getTotalScoreRibbon(
                    $objExercise,
                    $total_score,
                    $total_weight,
                    true,
                    $countPendingQuestions
                );
            }
            $totalScoreText .= '</div>';

            if (!empty($studentInfo)) {
                $certificateBlock = self::generateAndShowCertificateBlock(
                    $total_score,
                    $total_weight,
                    $objExercise,
                    $studentInfo['id'],
                    $courseCode,
                    $sessionId
                );
            }
        }

        if ($result['answer_type'] == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
            $chartMultiAnswer = MultipleAnswerTrueFalseDegreeCertainty::displayStudentsChartResults(
                $exeId,
                $objExercise
            );
            echo $chartMultiAnswer;
        }

        if (!empty($category_list) && ($show_results || $show_only_score)) {
            // Adding total
            $category_list['total'] = [
                'score' => $total_score,
                'total' => $total_weight,
            ];
            echo TestCategory::get_stats_table_by_attempt(
                $objExercise->id,
                $category_list
            );
        }

        if ($show_all_but_expected_answer) {
            $exercise_content .= Display::return_message(get_lang('Note: This test has been setup to hide the expected answers.'));
        }

        // Remove audio auto play from questions on results page - refs BT#7939
        $exercise_content = preg_replace(
            ['/autoplay[\=\".+\"]+/', '/autostart[\=\".+\"]+/'],
            '',
            $exercise_content
        );

        echo $totalScoreText;
        echo $certificateBlock;

        // Ofaj change BT#11784
        if (api_get_configuration_value('quiz_show_description_on_results_page') &&
            !empty($objExercise->description)
        ) {
            echo Display::div($objExercise->description, ['class' => 'exercise_description']);
        }

        echo $exercise_content;

        if (!$show_only_score) {
            echo $totalScoreText;
        }

        if ($save_user_result) {
            // Tracking of results
            if ($exercise_stat_info) {
                $learnpath_id = $exercise_stat_info['orig_lp_id'];
                $learnpath_item_id = $exercise_stat_info['orig_lp_item_id'];
                $learnpath_item_view_id = $exercise_stat_info['orig_lp_item_view_id'];

                if (api_is_allowed_to_session_edit()) {
                    Event::updateEventExercise(
                        $exercise_stat_info['exe_id'],
                        $objExercise->selectId(),
                        $total_score,
                        $total_weight,
                        api_get_session_id(),
                        $learnpath_id,
                        $learnpath_item_id,
                        $learnpath_item_view_id,
                        $exercise_stat_info['exe_duration'],
                        $question_list
                    );

                    $allowStats = api_get_configuration_value('allow_gradebook_stats');
                    if ($allowStats) {
                        $objExercise->generateStats(
                            $objExercise->selectId(),
                            api_get_course_info(),
                            api_get_session_id()
                        );
                    }
                }
            }

            // Send notification at the end
            if (!api_is_allowed_to_edit(null, true) &&
                !api_is_excluded_user_type()
            ) {
                $objExercise->send_mail_notification_for_exam(
                    'end',
                    $question_list_answers,
                    $origin,
                    $exeId,
                    $total_score,
                    $total_weight
                );
            }
        }

        if (in_array(
            $objExercise->selectResultsDisabled(),
            [RESULT_DISABLE_RANKING, RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING]
        )) {
            echo Display::page_header(get_lang('Ranking'), null, 'h4');
            echo self::displayResultsInRanking(
                $objExercise->iId,
                api_get_user_id(),
                api_get_course_int_id(),
                api_get_session_id()
            );
        }

        if (!empty($remainingMessage)) {
            echo Display::return_message($remainingMessage, 'normal', false);
        }
    }

    /**
     * Display the ranking of results in a exercise.
     *
     * @param int $exerciseId
     * @param int $currentUserId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return string
     */
    public static function displayResultsInRanking($exerciseId, $currentUserId, $courseId, $sessionId = 0)
    {
        $data = self::exerciseResultsInRanking($exerciseId, $courseId, $sessionId);

        $table = new HTML_Table(['class' => 'table table-hover table-bordered']);
        $table->setHeaderContents(0, 0, get_lang('Position'), ['class' => 'text-right']);
        $table->setHeaderContents(0, 1, get_lang('Username'));
        $table->setHeaderContents(0, 2, get_lang('Score'), ['class' => 'text-right']);
        $table->setHeaderContents(0, 3, get_lang('Date'), ['class' => 'text-center']);

        foreach ($data as $r => $item) {
            if (!isset($item[1])) {
                continue;
            }
            $selected = $item[1]->getId() == $currentUserId;

            foreach ($item as $c => $value) {
                $table->setCellContents($r + 1, $c, $value);

                $attrClass = '';

                if (in_array($c, [0, 2])) {
                    $attrClass = 'text-right';
                } elseif (3 == $c) {
                    $attrClass = 'text-center';
                }

                if ($selected) {
                    $attrClass .= ' warning';
                }

                $table->setCellAttributes($r + 1, $c, ['class' => $attrClass]);
            }
        }

        return $table->toHtml();
    }

    /**
     * Get the ranking for results in a exercise.
     * Function used internally by ExerciseLib::displayResultsInRanking.
     *
     * @param int $exerciseId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return array
     */
    public static function exerciseResultsInRanking($exerciseId, $courseId, $sessionId = 0)
    {
        $em = Database::getManager();

        $dql = 'SELECT DISTINCT te.exeUserId FROM ChamiloCoreBundle:TrackEExercises te WHERE te.exeExoId = :id AND te.cId = :cId';
        $dql .= api_get_session_condition($sessionId, true, false, 'te.sessionId');

        $result = $em
            ->createQuery($dql)
            ->setParameters(['id' => $exerciseId, 'cId' => $courseId])
            ->getScalarResult();

        $data = [];

        /** @var TrackEExercises $item */
        foreach ($result as $item) {
            $bestAttemp = self::get_best_attempt_by_user($item['exeUserId'], $exerciseId, $courseId, $sessionId = 0);

            $data[] = $bestAttemp;
        }

        usort(
            $data,
            function ($a, $b) {
                if ($a['score'] != $b['score']) {
                    return $a['score'] > $b['score'] ? -1 : 1;
                }

                if ($a['exe_date'] != $b['exe_date']) {
                    return $a['exe_date'] < $b['exe_date'] ? -1 : 1;
                }

                return 0;
            }
        );

        // flags to display the same position in case of tie
        $lastScore = $data[0]['score'];
        $position = 1;

        $data = array_map(
            function ($item) use (&$lastScore, &$position) {
                if ($item['score'] < $lastScore) {
                    $position++;
                }

                $lastScore = $item['score'];

                return [
                    $position,
                    api_get_user_entity($item['exe_user_id']),
                    self::show_score($item['score'], $item['max_score'], true, true, true),
                    api_convert_and_format_date($item['exe_date'], DATE_TIME_FORMAT_SHORT),
                ];
            },
            $data
        );

        return $data;
    }

    /**
     * Get a special ribbon on top of "degree of certainty" questions (
     * variation from getTotalScoreRibbon() for other question types).
     *
     * @param Exercise $objExercise
     * @param float    $score
     * @param float    $weight
     * @param bool     $checkPassPercentage
     *
     * @return string
     */
    public static function getQuestionDiagnosisRibbon($objExercise, $score, $weight, $checkPassPercentage = false)
    {
        $displayChartDegree = true;
        $ribbon = $displayChartDegree ? '<div class="ribbon">' : '';

        if ($checkPassPercentage) {
            $isSuccess = self::isSuccessExerciseResult(
                $score, $weight, $objExercise->selectPassPercentage()
            );
            // Color the final test score if pass_percentage activated
            $ribbonTotalSuccessOrError = '';
            if (self::isPassPercentageEnabled($objExercise->selectPassPercentage())) {
                if ($isSuccess) {
                    $ribbonTotalSuccessOrError = ' ribbon-total-success';
                } else {
                    $ribbonTotalSuccessOrError = ' ribbon-total-error';
                }
            }
            $ribbon .= $displayChartDegree ? '<div class="rib rib-total '.$ribbonTotalSuccessOrError.'">' : '';
        } else {
            $ribbon .= $displayChartDegree ? '<div class="rib rib-total">' : '';
        }

        if ($displayChartDegree) {
            $ribbon .= '<h3>'.get_lang('Score for the test').':&nbsp;';
            $ribbon .= self::show_score($score, $weight, false, true);
            $ribbon .= '</h3>';
            $ribbon .= '</div>';
        }

        if ($checkPassPercentage) {
            $ribbon .= self::showSuccessMessage(
                $score,
                $weight,
                $objExercise->selectPassPercentage()
            );
        }

        $ribbon .= $displayChartDegree ? '</div>' : '';

        return $ribbon;
    }

    /**
     * @param Exercise $objExercise
     * @param float    $score
     * @param float    $weight
     * @param bool     $checkPassPercentage
     * @param int      $countPendingQuestions
     *
     * @return string
     */
    public static function getTotalScoreRibbon(
        Exercise $objExercise,
        $score,
        $weight,
        $checkPassPercentage = false,
        $countPendingQuestions = 0
    ) {
        $hide = (int) $objExercise->getPageConfigurationAttribute('hide_total_score');
        if ($hide === 1) {
            return '';
        }

        $passPercentage = $objExercise->selectPassPercentage();
        $ribbon = '<div class="title-score">';
        if ($checkPassPercentage) {
            $isSuccess = self::isSuccessExerciseResult(
                $score,
                $weight,
                $passPercentage
            );
            // Color the final test score if pass_percentage activated
            $class = '';
            if (self::isPassPercentageEnabled($passPercentage)) {
                if ($isSuccess) {
                    $class = ' ribbon-total-success';
                } else {
                    $class = ' ribbon-total-error';
                }
            }
            $ribbon .= '<div class="total '.$class.'">';
        } else {
            $ribbon .= '<div class="total">';
        }
        $ribbon .= '<h3>'.get_lang('Score for the test').':&nbsp;';
        $ribbon .= self::show_score($score, $weight, false, true);
        $ribbon .= '</h3>';
        $ribbon .= '</div>';
        if ($checkPassPercentage) {
            $ribbon .= self::showSuccessMessage(
                $score,
                $weight,
                $passPercentage
            );
        }
        $ribbon .= '</div>';

        if (!empty($countPendingQuestions)) {
            $ribbon .= '<br />';
            $ribbon .= Display::return_message(
                sprintf(
                    get_lang('Temporary score: %s open question(s) not corrected yet.'),
                    $countPendingQuestions
                ),
                'warning'
            );
        }

        return $ribbon;
    }

    /**
     * @param int $countLetter
     *
     * @return mixed
     */
    public static function detectInputAppropriateClass($countLetter)
    {
        $limits = [
            0 => 'input-mini',
            10 => 'input-mini',
            15 => 'input-medium',
            20 => 'input-xlarge',
            40 => 'input-xlarge',
            60 => 'input-xxlarge',
            100 => 'input-xxlarge',
            200 => 'input-xxlarge',
        ];

        foreach ($limits as $size => $item) {
            if ($countLetter <= $size) {
                return $item;
            }
        }

        return $limits[0];
    }

    /**
     * @param int    $senderId
     * @param array  $course_info
     * @param string $test
     * @param string $url
     *
     * @return string
     */
    public static function getEmailNotification($senderId, $course_info, $test, $url)
    {
        $teacher_info = api_get_user_info($senderId);
        $fromName = api_get_person_name(
            $teacher_info['firstname'],
            $teacher_info['lastname'],
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );

        $params = [
            'course_title' => Security::remove_XSS($course_info['name']),
            'test_title' => Security::remove_XSS($test),
            'url' => $url,
            'teacher_name' => $fromName,
        ];

        return Container::getTwig()->render(
            '@ChamiloTheme/Mailer/Exercise/result_alert_body.html.twig',
            $params
        );
    }

    /**
     * @return string
     */
    public static function getNotCorrectedYetText()
    {
        return Display::return_message(get_lang('This answer has not yet been corrected. Meanwhile, your score for this question is set to 0, affecting the total score.'), 'warning');
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public static function getFeedbackText($message)
    {
        return Display::return_message($message, 'warning', false);
    }

    /**
     * Get the recorder audio component for save a teacher audio feedback.
     *
     * @param int $attemptId
     * @param int $questionId
     * @param int $userId
     *
     * @return string
     */
    public static function getOralFeedbackForm($attemptId, $questionId, $userId)
    {
        $view = new Template('', false, false, false, false, false, false);
        $view->assign('user_id', $userId);
        $view->assign('question_id', $questionId);
        $view->assign('directory', "/../exercises/teacher_audio/$attemptId/");
        $view->assign('file_name', "{$questionId}_{$userId}");
        $template = $view->get_template('exercise/oral_expression.tpl');

        return $view->fetch($template);
    }

    /**
     * Get the audio componen for a teacher audio feedback.
     *
     * @param int $attemptId
     * @param int $questionId
     * @param int $userId
     *
     * @return string
     */
    public static function getOralFeedbackAudio($attemptId, $questionId, $userId)
    {
        $courseInfo = api_get_course_info();
        $sessionId = api_get_session_id();
        $groupId = api_get_group_id();
        $sysCourseDir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'];
        $webCourseDir = api_get_path(WEB_COURSE_PATH).$courseInfo['path'];
        $fileName = "{$questionId}_{$userId}".DocumentManager::getDocumentSuffix($courseInfo, $sessionId, $groupId);
        $filePath = null;

        $relFilePath = "/exercises/teacher_audio/$attemptId/$fileName";

        if (file_exists($sysCourseDir.$relFilePath.'.ogg')) {
            $filePath = $webCourseDir.$relFilePath.'.ogg';
        } elseif (file_exists($sysCourseDir.$relFilePath.'.wav.wav')) {
            $filePath = $webCourseDir.$relFilePath.'.wav.wav';
        } elseif (file_exists($sysCourseDir.$relFilePath.'.wav')) {
            $filePath = $webCourseDir.$relFilePath.'.wav';
        }

        if (!$filePath) {
            return '';
        }

        return Display::tag(
            'audio',
            null,
            ['src' => $filePath]
        );
    }

    /**
     * @return array
     */
    public static function getNotificationSettings()
    {
        $emailAlerts = [
            2 => get_lang('SendEmailToTrainerWhenStudentStartQuiz'),
            1 => get_lang('SendEmailToTrainerWhenStudentEndQuiz'), // default
            3 => get_lang('SendEmailToTrainerWhenStudentEndQuizOnlyIfOpenQuestion'),
            4 => get_lang('SendEmailToTrainerWhenStudentEndQuizOnlyIfOralQuestion'),
        ];

        return $emailAlerts;
    }

    /**
     * Get the additional actions added in exercise_additional_teacher_modify_actions configuration.
     *
     * @param int $exerciseId
     * @param int $iconSize
     *
     * @return string
     */
    public static function getAdditionalTeacherActions($exerciseId, $iconSize = ICON_SIZE_SMALL)
    {
        $additionalActions = api_get_configuration_value('exercise_additional_teacher_modify_actions') ?: [];
        $actions = [];

        foreach ($additionalActions as $additionalAction) {
            $actions[] = call_user_func(
                $additionalAction,
                $exerciseId,
                $iconSize
            );
        }

        return implode(PHP_EOL, $actions);
    }

    /**
     * @param DateTime $time
     * @param int      $userId
     * @param int      $courseId
     * @param int      $sessionId
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return int
     */
    public static function countAnsweredQuestionsByUserAfterTime(DateTime $time, $userId, $courseId, $sessionId)
    {
        $em = Database::getManager();

        $time = api_get_utc_datetime($time->format('Y-m-d H:i:s'), false, true);

        $result = $em
            ->createQuery('
                SELECT COUNT(ea) FROM ChamiloCoreBundle:TrackEAttempt ea
                WHERE ea.userId = :user AND ea.cId = :course AND ea.sessionId = :session
                    AND ea.tms > :time
            ')
            ->setParameters(['user' => $userId, 'course' => $courseId, 'session' => $sessionId, 'time' => $time])
            ->getSingleScalarResult();

        return $result;
    }

    /**
     * @param int $userId
     * @param int $numberOfQuestions
     * @param int $courseId
     * @param int $sessionId
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return bool
     */
    public static function isQuestionsLimitPerDayReached($userId, $numberOfQuestions, $courseId, $sessionId)
    {
        $questionsLimitPerDay = (int) api_get_course_setting('quiz_question_limit_per_day');

        if ($questionsLimitPerDay <= 0) {
            return false;
        }

        $midnightTime = ChamiloApi::getServerMidnightTime();

        $answeredQuestionsCount = self::countAnsweredQuestionsByUserAfterTime(
            $midnightTime,
            $userId,
            $courseId,
            $sessionId
        );

        return ($answeredQuestionsCount + $numberOfQuestions) > $questionsLimitPerDay;
    }

    /**
     * Check if an exercise complies with the requirements to be embedded in the mobile app or a video.
     * By making sure it is set on one question per page and it only contains unique-answer or multiple-answer questions
     * or unique-answer image. And that the exam does not have immediate feedback.
     *
     * @param array $exercise Exercise info
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return bool
     */
    public static function isQuizEmbeddable(array $exercise)
    {
        $em = Database::getManager();

        if (ONE_PER_PAGE != $exercise['type'] ||
            in_array($exercise['feedback_type'], [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])
        ) {
            return false;
        }

        $countAll = $em
            ->createQuery('SELECT COUNT(qq)
                FROM ChamiloCourseBundle:CQuizQuestion qq
                INNER JOIN ChamiloCourseBundle:CQuizRelQuestion qrq
                   WITH qq.iid = qrq.questionId
                WHERE qrq.exerciceId = :id'
            )
            ->setParameter('id', $exercise['iid'])
            ->getSingleScalarResult();

        $countOfAllowed = $em
            ->createQuery('SELECT COUNT(qq)
                FROM ChamiloCourseBundle:CQuizQuestion qq
                INNER JOIN ChamiloCourseBundle:CQuizRelQuestion qrq
                   WITH qq.iid = qrq.questionId
                WHERE qrq.exerciceId = :id AND qq.type IN (:types)'
            )
            ->setParameters(
                [
                    'id' => $exercise['iid'],
                    'types' => [UNIQUE_ANSWER, MULTIPLE_ANSWER, UNIQUE_ANSWER_IMAGE],
                ]
            )
            ->getSingleScalarResult();

        return $countAll === $countOfAllowed;
    }

    /**
     * Generate a certificate linked to current quiz and.
     * Return the HTML block with links to download and view the certificate.
     *
     * @param float    $totalScore
     * @param float    $totalWeight
     * @param Exercise $objExercise
     * @param int      $studentId
     * @param string   $courseCode
     * @param int      $sessionId
     *
     * @return string
     */
    public static function generateAndShowCertificateBlock(
        $totalScore,
        $totalWeight,
        Exercise $objExercise,
        $studentId,
        $courseCode,
        $sessionId = 0
    ) {
        if (!api_get_configuration_value('quiz_generate_certificate_ending') ||
            !self::isSuccessExerciseResult($totalScore, $totalWeight, $objExercise->selectPassPercentage())
        ) {
            return '';
        }

        /** @var Category $category */
        $category = Category::load(null, null, $courseCode, null, null, $sessionId, 'ORDER By id');

        if (empty($category)) {
            return '';
        }

        /** @var Category $category */
        $category = $category[0];
        $categoryId = $category->get_id();
        $link = LinkFactory::load(
            null,
            null,
            $objExercise->selectId(),
            null,
            $courseCode,
            $categoryId
        );

        if (empty($link)) {
            return '';
        }

        $resourceDeletedMessage = $category->show_message_resource_delete($courseCode);

        if (false !== $resourceDeletedMessage || api_is_allowed_to_edit() || api_is_excluded_user_type()) {
            return '';
        }

        $certificate = Category::generateUserCertificate($categoryId, $studentId);

        if (!is_array($certificate)) {
            return '';
        }

        return Category::getDownloadCertificateBlock($certificate);
    }
}
