<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AiHelper;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
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

        if (EXERCISE_FEEDBACK_TYPE_END != $exercise->getFeedbackType()) {
            $show_comment = false;
        }

        $answerType = $objQuestionTmp->selectType();

        if (MEDIA_QUESTION === $answerType) {
            $mediaHtml = $objQuestionTmp->selectDescription();
            if (!empty($mediaHtml)) {
                echo '<div class="media-content wysiwyg">'. $mediaHtml .'</div>';
            }
            return 0;
        }

        if (PAGE_BREAK === $answerType) {
            $description = $objQuestionTmp->selectDescription();
            if (!$only_questions && !empty($description)) {
                echo '<div class="page-break-content wysiwyg">'
                    . $description .
                    '</div>';
            }
            return 0;
        }

        $s = '';
        if (HOT_SPOT != $answerType &&
            HOT_SPOT_DELINEATION != $answerType &&
             HOT_SPOT_COMBINATION != $answerType &&
            ANNOTATION != $answerType
        ) {
            // Question is not a hotspot
            if (!$only_questions) {
                $questionDescription = $objQuestionTmp->selectDescription();
                if ($show_title) {
                    if ($exercise->display_category_name) {
                        TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    }
                    $titleToDisplay = $objQuestionTmp->getTitleToDisplay($exercise, $current_item);
                    if (READING_COMPREHENSION == $answerType) {
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

                if (!empty($questionDescription) && READING_COMPREHENSION != $answerType) {
                    echo Display::div(
                        $questionDescription,
                        ['class' => 'question_description wysiwyg']
                    );
                }
            }

            if (in_array($answerType, [FREE_ANSWER, ORAL_EXPRESSION, UPLOAD_ANSWER]) && $freeze) {
                return '';
            }

            echo '<div class="question_options type-'.$answerType.'">';
            // construction of the Answer object (also gets all answers details)
            $objAnswerTmp = new Answer($questionId, $course_id, $exercise);
            $nbrAnswers = $objAnswerTmp->selectNbrAnswers();
            $quizQuestionOptions = Question::readQuestionOption($questionId, $course_id);
            $selectableOptions = [];

            for ($i = 1; $i <= $objAnswerTmp->nbrAnswers; $i++) {
                $selectableOptions[$objAnswerTmp->iid[$i]] = $objAnswerTmp->answer[$i];
            }

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
                case MATCHING_COMBINATION:
                case DRAGGABLE:
                case MATCHING_DRAGGABLE:
                case MATCHING_DRAGGABLE_COMBINATION:
                    if (DRAGGABLE == $answerType) {
                        $isVertical = 'v' === $objQuestionTmp->extra;
                        $s .= '<p class="small">'
                            .get_lang('Sort the following options from the list as you see fit by dragging them to the lower areas. You can put them back in this area to modify your answer.')
                            .'</p>
                            <div class="w-full ui-widget ui-helper-clearfix">
                                <div class="clearfix">
                                    <ul class="exercise-draggable-answer '.($isVertical ? 'vertical' : 'list-inline w-full').'"
                                        id="question-'.$questionId.'" data-question="'.$questionId.'">
                            ';
                    } else {
                        $s .= '<div id="drag'.$questionId.'_question" class="drag_question">
                               <table class="table table-hover table-striped data_table">';
                    }

                    // Iterate through answers.
                    $x = 1;
                    // Mark letters for each answer.
                    $letter = 'A';
                    $answer_matching = [];
                    $cpt1 = [];
                    for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                        $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                        $numAnswer = $objAnswerTmp->selectAutoId($answerId);
                        if (0 == $answerCorrect) {
                            // options (A, B, C, ...) that will be put into the list-box
                            // have the "correct" field set to 0 because they are answer
                            $cpt1[$x] = $letter;
                            $answer_matching[$x] = $objAnswerTmp->selectAnswerByAutoId($numAnswer);
                            $x++;
                            $letter++;
                        }
                    }

                    $i = 1;
                    $select_items[0]['id'] = 0;
                    $select_items[0]['letter'] = '--';
                    $select_items[0]['answer'] = '';
                    foreach ($answer_matching as $id => $value) {
                        $select_items[$i]['id'] = $value['iid'];
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
                case UPLOAD_ANSWER:
                    $url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=upload_answer&question_id='.$questionId;
                    $multipleForm = new FormValidator(
                        'drag_drop',
                        'post',
                        '#',
                        '',
                        ['enctype' => 'multipart/form-data', 'id' => 'drag_drop']
                    );

                    $iconDelete = Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL);
                    $multipleForm->addMultipleUpload($url);

                    $s .= '<script>
                        function setRemoveLink(dataContext) {
                            var removeLink = $("<a>", {
                                html: "&nbsp;'.addslashes($iconDelete).'",
                                href: "#",
                                click: function(e) {
                                  e.preventDefault();
                                  dataContext.parent().remove();
                                }
                            });
                            dataContext.append(removeLink);
                        }

                        $(function() {
                            $("#input_file_upload").bind("fileuploaddone", function (e, data) {
                                $.each(data.result.files, function (index, file) {
                                    // El backend ahora devuelve asset_id y url
                                    if (file.asset_id) {
                                        var input = $("<input>", {
                                            type: "hidden",
                                            name: "uploadAsset['.$questionId.'][]",
                                            value: file.asset_id
                                        });
                                        $(data.context.children()[index]).parent().append(input);
                                        // set the remove link
                                        setRemoveLink($(data.context.children()[index]).parent());
                                    }
                                });
                            });
                        });
                    </script>';
                    $sessionKey = 'upload_answer_assets_'.$questionId;
                    $assetIds = (array) ChamiloSession::read($sessionKey);

                    if (!empty($assetIds)) {
                        $icon = Display::return_icon('file_txt.gif');
                        $default = "";
                        $assetRepo = Container::getAssetRepository();
                        $basePath = rtrim(api_get_path(WEB_PATH), "/");

                        foreach ($assetIds as $id) {
                            try {
                                $asset = $assetRepo->find(\Symfony\Component\Uid\Uuid::fromRfc4122((string)$id));
                            } catch (\Throwable $e) {
                                $asset = null;
                            }
                            if (!$asset) { continue; }

                            $title = Security::remove_XSS($asset->getTitle());
                            $urlAsset = $basePath.$assetRepo->getAssetUrl($asset);

                            $default .= Display::tag(
                                "a",
                                Display::div(
                                    Display::div($icon, ['class' => 'col-sm-4'])
                                    . Display::div($title, ['class' => 'col-sm-5 file_name'])
                                    . Display::tag("input", "", [
                                        "type" => "hidden",
                                        "name" => "uploadAsset['.$questionId.'][]",
                                        "value" => (string)$id
                                    ])
                                    . Display::div("", ["class" => "col-sm-3"]),
                                    ["class" => "row"]
                                ),
                                ["target" => "_blank", "class" => "panel-image", "href" => $urlAsset]
                            );
                        }

                        $s .= '<script>
                            $(function() {
                                if ($("#files").length > 0) {
                                    $("#files").html("'.addslashes($default).'");
                                    var links = $("#files").children();
                                    links.each(function(index) {
                                        var dataContext = $(links[index]).find(".row");
                                        setRemoveLink(dataContext);
                                    });
                                }
                            });
                        </script>';
                    }

                    $s .= $multipleForm->returnForm();
                    break;
                case ORAL_EXPRESSION:
                    // Add nanog
                    //@todo pass this as a parameter
                    global $exercise_stat_info;
                    if (!empty($exercise_stat_info)) {
                        echo $objQuestionTmp->returnRecorder((int) $exercise_stat_info['exe_id']);
                        $generatedFile = self::getOralFileAudio($exercise_stat_info['exe_id'], $questionId);
                        if (!empty($generatedFile)) {
                            echo $generatedFile;
                        }
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
                case MULTIPLE_ANSWER_DROPDOWN:
                case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                    if ($debug_mark_answer) {
                        $s .= '<p><strong>'
                            .(
                                MULTIPLE_ANSWER_DROPDOWN == $answerType
                                    ? '<span class="pull-right">'.get_lang('Score').'</span>'
                                    : ''
                            )
                            .get_lang('Correct answer').'</strong></p>';
                    }
                    break;
            }

            // Now navigate through the possible answers, using the max number of
            // answers for the question as a limiter
            $lines_count = 1; // a counter for matching-type answers
            if (MULTIPLE_ANSWER_TRUE_FALSE == $answerType ||
                MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE == $answerType
            ) {
                $header = Display::tag('th', get_lang('Options'));
                foreach ($objQuestionTmp->options as $item) {
                    if (MULTIPLE_ANSWER_TRUE_FALSE == $answerType) {
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
            } elseif (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType) {
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
                        if ('Answers' === $item) {
                            $properties['colspan'] = 2;
                            $properties['style'] = 'background-color: #F56B2A; color: #ffffff;';
                        } elseif ('DegreeOfCertaintyThatMyAnswerIsCorrect' === $item) {
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

                $s .= '<table class="table table-hover table-striped data_table">';
                $s .= Display::tag('tr', $header, ['style' => 'text-align:left;']);

                // ajout de la 2eme ligne d'entête pour true/falss et les pourcentages de certitude
                $header1 = Display::tag('th', '&nbsp;');
                $cpt1 = 0;
                foreach ($objQuestionTmp->options as $item) {
                    $colorBorder1 = ($cpt1 == (count($objQuestionTmp->options) - 1))
                        ? '' : 'border-right: solid #FFFFFF 1px;';
                    if ('True' === $item || 'False' === $item) {
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
                    if ('True' === $item || 'False' === $item) {
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
                    if (EXERCISE_FEEDBACK_TYPE_END == $exercise->getFeedbackType()) {
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
            if (READING_COMPREHENSION == $answerType) {
                /** @var ReadingComprehension */
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
                                $attributes['checked'] = 'checked';
                                $attributes['selected'] = 1;
                            }
                        }

                        if ($show_comment) {
                            $s .= '<tr><td>';
                        }

                        if (UNIQUE_ANSWER_IMAGE == $answerType) {
                            if ($show_comment) {
                                if (empty($comment)) {
                                    $s .= '<div id="answer'.$questionId.$numAnswer.'"
                                            class="exercise-unique-answer-image text-center">';
                                } else {
                                    $s .= '<div id="answer'.$questionId.$numAnswer.'"
                                            class="exercise-unique-answer-image col-xs-6 col-sm-12 text-center">';
                                }
                            } else {
                                $s .= '<div id="answer'.$questionId.$numAnswer.'"
                                        class="exercise-unique-answer-image col-xs-6 col-md-3 text-center">';
                            }
                        }

                        if (UNIQUE_ANSWER_IMAGE != $answerType) {
                            $userStatus = STUDENT;
                            // Allows to do a remove_XSS in question of exersice with user status COURSEMANAGER
                            // see BT#18242
                            if (api_get_configuration_value('question_exercise_html_strict_filtering')) {
                                $userStatus = COURSEMANAGERLOWSECURITY;
                            }
                            $answer = Security::remove_XSS($answer, $userStatus);
                        }
                        $s .= Display::input(
                            'hidden',
                            'choice2['.$questionId.']',
                            '0'
                        );

                        $answer_input = null;
                        if (UNIQUE_ANSWER_IMAGE == $answerType) {
                            $attributes['style'] = 'display: none;';
                            $answer = '<div class="thumbnail">'.$answer.'</div>';
                        }

                        $answer_input .= '<label class="flex gap-2 items-center '.$hidingClass.'">';
                        $answer_input .= Display::input(
                            'radio',
                            'choice['.$questionId.']',
                            $numAnswer,
                            $attributes
                        );
                        $answer_input .= $answer;
                        $answer_input .= '</label>';

                        if (UNIQUE_ANSWER_IMAGE == $answerType) {
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
                        $userStatus = STUDENT;
                        // Allows to do a remove_XSS in question of exersice with user status COURSEMANAGER
                        // see BT#18242
                        if (api_get_setting('exercise.question_exercise_html_strict_filtering')) {
                            $userStatus = COURSEMANAGERLOWSECURITY;
                        }
                        $answer = Security::remove_XSS($answer, $userStatus);

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
                                $attributes['checked'] = 'checked';
                                $attributes['selected'] = 1;
                            }
                        }

                        if (MULTIPLE_ANSWER == $answerType || GLOBAL_MULTIPLE_ANSWER == $answerType) {
                            $s .= '<input type="hidden" name="choice2['.$questionId.']" value="0" />';
                            $answer_input = '<label class="flex gap-2 items-center">';
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
                        } elseif (MULTIPLE_ANSWER_TRUE_FALSE == $answerType) {
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
                                $j = 1;
                                foreach ($quizQuestionOptions as $id => $item) {
                                    if (isset($myChoice[$numAnswer]) && $item['iid'] == $myChoice[$numAnswer]) {
                                        $attributes = [
                                            'checked' => 1,
                                            'selected' => 1,
                                        ];
                                    } else {
                                        $attributes = [];
                                    }

                                    if ($debug_mark_answer) {
                                        if ($j == $answerCorrect) {
                                            $attributes['checked'] = 'checked';
                                            $attributes['selected'] = 1;
                                        }
                                    }
                                    $s .= Display::tag(
                                        'td',
                                        Display::input(
                                            'radio',
                                            'choice['.$questionId.']['.$numAnswer.']',
                                            $item['iid'],
                                            $attributes
                                        ),
                                        ['style' => '']
                                    );
                                    $j++;
                                }
                            }

                            if ($show_comment) {
                                $s .= '<td>';
                                $s .= $comment;
                                $s .= '</td>';
                            }
                            $s .= '</tr>';
                        } elseif (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $answerType) {
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
                                $j = 1;
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
                                        if ($j == $answerCorrect) {
                                            $attributes['checked'] = 'checked';
                                            $attributes['selected'] = 1;
                                        }
                                    }

                                    if ('True' == $item['title'] || 'False' == $item['title']) {
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
                                    $j++;
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
                                $attributes['checked'] = 'checked';
                                $attributes['selected'] = 1;
                            }
                        }

                        $userStatus = STUDENT;
                        // Allows to do a remove_XSS in question of exersice with user status COURSEMANAGER
                        // see BT#18242
                        if (api_get_configuration_value('question_exercise_html_strict_filtering')) {
                            $userStatus = COURSEMANAGERLOWSECURITY;
                        }
                        $answer = Security::remove_XSS($answer, $userStatus);
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
                        $userStatus = STUDENT;
                        // Allows to do a remove_XSS in question of exersice with user status COURSEMANAGER
                        // see BT#18242
                        if (api_get_configuration_value('question_exercise_html_strict_filtering')) {
                            $userStatus = COURSEMANAGERLOWSECURITY;
                        }
                        $answer = Security::remove_XSS($answer, $userStatus);
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
                                    $attributes['checked'] = 'checked';
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
                    case FILL_IN_BLANKS_COMBINATION:
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
                        if (null !== $origin) {
                            global $exe_id;
                            $exe_id = (int) $exe_id;
                            $trackAttempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
                            $sql = "SELECT answer FROM $trackAttempts
                                    WHERE exe_id = $exe_id AND question_id= $questionId";
                            $rsLastAttempt = Database::query($sql);
                            $rowLastAttempt = Database::fetch_array($rsLastAttempt);

                            $answer = null;
                            if (isset($rowLastAttempt['answer'])) {
                                $answer = $rowLastAttempt['answer'];
                            }

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

                        [$answer] = explode('@@', $answer);
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
                                if (EXERCISE_FEEDBACK_TYPE_POPUP == $exercise->getFeedbackType()) {
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
                        if (null !== $origin) {
                            $s = $answer;
                            break;
                        } else {
                            $s .= $answer;
                        }
                        break;
                    case MATCHING:
                    case MATCHING_COMBINATION:
                        // matching type, showing suggestions and answers
                        // TODO: replace $answerId by $numAnswer
                        if (0 != $answerCorrect) {
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
                                    class="form-control"
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
                                foreach ($user_choice as $userChoiceKey => $chosen) {
                                    $userChoiceKey++;
                                    if ($lines_count != $userChoiceKey) {
                                        continue;
                                    }
                                    /*if ($answerCorrect != $chosen['answer']) {
                                        continue;
                                    }*/
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
                    case MATCHING_DRAGGABLE_COMBINATION:
                        if (1 == $answerId) {
                            echo $objAnswerTmp->getJs();
                        }
                        if (0 != $answerCorrect) {
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
                                                MatchingDraggable.instances['$questionId'].connect({
                                                    source: 'window_$windowId',
                                                    target: 'window_{$questionId}_{$selectedIndex}_answer',
                                                    endpoint: ['Dot', {radius: 12}],
                                                    anchors: ['RightMiddle', 'LeftMiddle'],
                                                    paintStyle: {stroke: '#8A8888', strokeWidth: 8},
                                                    connector: [
                                                        MatchingDraggable.connectorType,
                                                        {curvines: MatchingDraggable.curviness}
                                                    ]
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
                    case MULTIPLE_ANSWER_DROPDOWN:
                    case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                        if ($debug_mark_answer && $answerCorrect) {
                            $s .= '<p>'
                                .(
                                    MULTIPLE_ANSWER_DROPDOWN == $answerType
                                        ? '<span class="pull-right">'.$objAnswerTmp->weighting[$answerId].'</span>'
                                        : ''
                                )
                                .Display::returnFontAwesomeIcon('check-square-o', '', true);
                            $s .= Security::remove_XSS($objAnswerTmp->answer[$answerId]).'</p>';
                        }
                        break;
                }
            }

            if (in_array($answerType, [MULTIPLE_ANSWER_DROPDOWN, MULTIPLE_ANSWER_DROPDOWN_COMBINATION]) && !$debug_mark_answer) {
                $userChoiceList = array_unique($userChoiceList);
                $input_id = "choice-$questionId";
                $clear_id = "clear-$questionId";

                $s .= Display::input('hidden', "choice2[$questionId]", '0')
                    .'<div class="mb-4">'
                    .'<div class="flex items-center justify-between mb-2">'
                    .'<label for="'.$input_id.'" class="text-sm font-medium text-gray-90">'
                    .get_lang('Please select an option')
                    .'</label>'
                    .'<button type="button" id="'.$clear_id.'" '
                    .'class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium '
                    .'bg-primary text-white hover:opacity-90 border border-primary">'
                    .'<span class="fa fa-times" aria-hidden="true"></span>'
                    .'<span>'.get_lang('Clear').'</span>'
                    .'</button>'
                    .'</div>'
                    .Display::select(
                        "choice[$questionId][]",
                        $selectableOptions,
                        $userChoiceList,
                        [
                            'id'       => $input_id,
                            'multiple' => 'multiple',
                            'class'    => 'w-full', // full width before Select2 mounts
                        ],
                        false
                    )
                    .'</div>'
                    .'<script>
            $(function () {
                var $el = $("#'.$input_id.'");
                if (!$.fn.select2) return;

                $el.select2({
                    width: "100%",
                    placeholder: { id: "-2", text: "'.get_lang('None').'" },
                    allowClear: true,
                    selectOnClose: false,
                    containerCssClass: "select2-tw",
                    selectionCssClass: "select2-tw",
                    dropdownCssClass: "select2-tw-dd"
                });

                $("#'.$clear_id.'").on("click", function(e){
                    e.preventDefault();
                    $el.val(null).trigger("change");
                });
            });
        </script>';
            }

            if ($show_comment) {
                $s .= '</table>';
            } elseif (in_array(
                $answerType,
                [
                    MATCHING,
                    MATCHING_COMBINATION,
                    MATCHING_DRAGGABLE,
                    MATCHING_DRAGGABLE_COMBINATION,
                    UNIQUE_ANSWER_NO_OPTION,
                    MULTIPLE_ANSWER_TRUE_FALSE,
                    MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE,
                    MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY,
                ]
            )) {
                $s .= '</table>';
            }

            if (DRAGGABLE == $answerType) {
                $isVertical = 'v' == $objQuestionTmp->extra;
                $s .= "</ul></div>";
                $counterAnswer = 1;
                $s .= '<div class="question-answer__items question-answer__items--'.($isVertical ? 'vertical' : 'horizontal').'">';
                for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
                    $answerCorrect = $objAnswerTmp->isCorrect($answerId);
                    $windowId = $questionId.'_'.$counterAnswer;
                    if ($answerCorrect) {
                        $s .= '<div class="droppable-item '.($isVertical ? 'w-full' : '').' flex items-center justify-between p-4 mb-4 bg-gray-200 rounded-md">';
                        $s .= '<span class="number text-lg font-bold">'.$counterAnswer.'</span>';
                        $s .= '<div id="drop_'.$windowId.'" class="droppable border-2 border-dashed border-gray-400 p-4 bg-white rounded-md"></div>';
                        $s .= '</div>';
                        $counterAnswer++;
                    }
                }

                $s .= '</div>';
//                $s .= '</div>';
            }

            if (in_array($answerType, [MATCHING, MATCHING_COMBINATION, MATCHING_DRAGGABLE, MATCHING_DRAGGABLE_COMBINATION])) {
                $s .= '</div>'; //drag_question
            }

            $s .= '</div>'; //question_options row

            // destruction of the Answer object
            unset($objAnswerTmp);
            // destruction of the Question object
            unset($objQuestionTmp);
            if ('export' == $origin) {
                return $s;
            }
            echo $s;
        } elseif (in_array($answerType, [HOT_SPOT, HOT_SPOT_DELINEATION, HOT_SPOT_COMBINATION])) {
            global $exe_id;
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
                $answers_hotspot[$answers['iid']] = $objAnswerTmp->selectAnswer(
                    $answerId
                );
            }

            $answerList = '';
            $hotspotColor = 0;
            if (HOT_SPOT_DELINEATION != $answerType) {
                $answerList = '
        <div class="card p-4 rounded-md border border-gray-25">
            <h5 class="font-bold text-lg mb-2 text-primary">'.get_lang('Image zones').'</h5>
            <ol class="list-decimal ml-6 space-y-2 text-primary">
        ';

                if (!empty($answers_hotspot)) {
                    Session::write("hotspot_ordered$questionId", array_keys($answers_hotspot));
                    foreach ($answers_hotspot as $value) {
                        $answerList .= '<li class="flex items-center space-x-2">';
                        if ($freeze) {
                            $answerList .= '<span class="text-support-5 fa fa-square" aria-hidden="true"></span>';
                        }
                        $answerList .= '<span>'.$value.'</span>';
                        $answerList .= '</li>';
                        $hotspotColor++;
                    }
                }

                $answerList .= '
                        </ol>
                    </div>
                ';
            }
            if ($freeze) {
                $relPath = api_get_path(WEB_CODE_PATH);
                echo "
        <div class=\"w-100\">
                $answerList
            </div>
        <div class=\"flex space-x-4\">
            <div class=\"w-100\">
                <div id=\"hotspot-preview-$questionId\" class=\"bg-gray-10 w-full bg-center bg-no-repeat bg-contain border border-gray-25\"></div>
            </div>
        </div>
        <script>
            new ".(in_array($answerType, [HOT_SPOT, HOT_SPOT_COMBINATION]) ? "HotspotQuestion" : "DelineationQuestion")."({
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

            if (!$only_questions) {
                if ($show_title) {
                    if ($exercise->display_category_name) {
                        TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    }
                    echo $objQuestionTmp->getTitleToDisplay($exercise, $current_item);
                }

                //@todo I need to the get the feedback type
                echo <<<HOTSPOT
        <input type="hidden" name="hidden_hotspot_id" value="$questionId" />
        <div class="exercise_questions">
            $questionDescription
            <div class="mb-4">
              $answerList
            </div>
            <div class="flex space-x-4">
HOTSPOT;
            }

            $relPath = api_get_path(WEB_CODE_PATH);
            $s .= "<div>
           <div class=\"hotspot-image bg-gray-10 border border-gray-25 bg-center bg-no-repeat bg-contain\"></div>
            <script>
                $(function() {
                    new ".(HOT_SPOT_DELINEATION == $answerType ? 'DelineationQuestion' : 'HotspotQuestion')."({
                        questionId: $questionId,
                        exerciseId: $exerciseId,
                        exeId: 0,
                        selector: '#question_div_' + $questionId + ' .hotspot-image',
                        for: 'user',
                        relPath: '$relPath'
                    });
                });
            </script>
        </div>
    ";

            echo <<<HOTSPOT
        $s
    </div>
</div>
HOTSPOT;
        } elseif (ANNOTATION == $answerType) {
            global $exe_id;
            $relPath = api_get_path(WEB_CODE_PATH);
            if (api_is_platform_admin() || api_is_course_admin()) {
                $questionRepo = Container::getQuestionRepository();
                $questionEntity = $questionRepo->find($questionId);
                if ($freeze) {
                    echo '
            <div id="annotation-canvas-'.$questionId.'" class="annotation-canvas center-block"></div>
            <script>
                AnnotationQuestion({
                    questionId: '.(int)$questionId.',
                    exerciseId: 0,
                    relPath: \''.$relPath.'\',
                    courseId: '.(int)$course_id.',
                    mode: "preview"
                });
            </script>
        ';
                    return 0;
                }
            }

            if (!$only_questions) {
                if ($show_title) {
                    if ($exercise->display_category_name) {
                        TestCategory::displayCategoryAndTitle($objQuestionTmp->id);
                    }
                    echo $objQuestionTmp->getTitleToDisplay($exercise, $current_item);
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
                                            <label class="btn btn--plain active"
                                                aria-label="'.get_lang('Add annotation path').'">
                                                <input
                                                    type="radio" value="0"
                                                    name="'.$questionId.'-options" autocomplete="off" checked>
                                                <span class="fas fa-pencil-alt" aria-hidden="true"></span>
                                            </label>
                                            <label class="btn btn--plain"
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
     * Displays a table listing the quizzes where a question is used.
     */
    public static function showTestsWhereQuestionIsUsed(int $questionId, int $excludeTestId = 0): void
    {
        $em = Database::getManager();
        $quizRepo = $em->getRepository(CQuiz::class);
        $quizzes = $quizRepo->findQuizzesUsingQuestion($questionId, $excludeTestId);

        if (empty($quizzes)) {
            echo '';
            return;
        }

        $result = [];

        foreach ($quizzes as $quiz) {
            $link = $quiz->getFirstResourceLink();
            $course = $link?->getCourse();
            $session = $link?->getSession();
            $courseId = $course?->getId() ?? 0;
            $sessionId = $session?->getId() ?? 0;

            $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.
                'cid='.$courseId.'&sid='.$sessionId.'&gid=0&gradebook=0&origin='.
                '&exerciseId='.$quiz->getIid().'&r=1';


            $result[] = [
                $course?->getTitle() ?? '-',
                $session?->getTitle() ?? '-',
                $quiz->getTitle(),
                '<a href="'.$url.'">'.Display::getMdiIcon(
                    'order-bool-ascending-variant',
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Edit')
                ).'</a>',
            ];
        }

        $headers = [
            get_lang('Course'),
            get_lang('Session'),
            get_lang('Test'),
            get_lang('Link to test edition'),
        ];

        $title = Display::div(
            get_lang('Question also used in the following tests'),
            ['class' => 'section-title', 'style' => 'margin-top: 25px; border-bottom: none']
        );

        echo $title.Display::table($headers, $result);
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
                ON q.iid = tee.exe_exo_id
                WHERE
                    tee.exe_id = $exeId";

            $sqlResult = Database::query($sql);
            if (Database::num_rows($sqlResult)) {
                $result = Database::fetch_assoc($sqlResult);
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
     * @param int $lp_id
     * @param int $lp_item_id
     *
     * @return bool
     */
    public static function exercise_time_control_is_valid(Exercise $exercise, $lp_id = 0, $lp_item_id = 0)
    {
        $exercise_id = $exercise->getId();
        $expiredTime = $exercise->expired_time;

        // If the exercise has no time control configured, it's valid.
        if (empty($expiredTime)) {
            return true;
        }

        // Build a stable session key for the LP/exercise context
        $current_expired_time_key = self::get_time_control_key(
            $exercise_id,
            $lp_id,
            $lp_item_id
        );

        // If the key isn't present, time control cannot be validated -> not valid
        if (!isset($_SESSION['expired_time'][$current_expired_time_key])) {
            return false;
        }

        // Normalize the stored value (can be DateTime, unix timestamp, or string)
        $raw = $_SESSION['expired_time'][$current_expired_time_key];
        if ($raw instanceof \DateTimeInterface) {
            $expiredAtStr = $raw->format('Y-m-d H:i:s');
        } elseif (is_int($raw) || ctype_digit((string) $raw)) {
            // Treat numeric as unix timestamp (UTC)
            $expiredAtStr = gmdate('Y-m-d H:i:s', (int) $raw);
        } else {
            // Assume parsable datetime string
            $expiredAtStr = (string) $raw;
        }

        // Compute remaining time (defensive: handle parse failure as already expired)
        $expired_time = api_strtotime($expiredAtStr, 'UTC');
        if ($expired_time === false || $expired_time === null) {
            return false;
        }

        $current_time = time();
        $total_time_allowed = $expired_time + 30; // small grace period

        if ($total_time_allowed < $current_time) {
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
            if ($_SESSION['expired_time'][$time_control_key] instanceof DateTimeInterface) {
                $return_value = $_SESSION['expired_time'][$time_control_key]->format('Y-m-d H:i:s');
            } else {
                $return_value = $_SESSION['expired_time'][$time_control_key];
            }
        }

        return $return_value;
    }

    /**
     * Gets count of exam results.
     *
     * @param int   $exerciseId
     * @param array $conditions
     * @param int   $courseId
     * @param bool  $showSession
     *
     * @return array
     */
    public static function get_count_exam_results($exerciseId, $conditions, $courseId, $showSession = false)
    {
        $count = self::get_exam_results_data(
            null,
            null,
            null,
            null,
            $exerciseId,
            $conditions,
            true,
            $courseId,
            $showSession
        );

        return $count;
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
     * @param int    $courseId
     * @param bool   $showSessionField
     * @param bool   $showExerciseCategories
     * @param array  $userExtraFieldsToAdd
     * @param bool   $useCommaAsDecimalPoint
     * @param bool   $roundValues
     * @param bool   $getOnyIds
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
        $courseId = null,
        $showSessionField = false,
        $showExerciseCategories = false,
        $userExtraFieldsToAdd = [],
        $useCommaAsDecimalPoint = false,
        $roundValues = false,
        $getOnyIds = false
    ) {
        //@todo replace all this globals
        global $filter;
        $courseId = (int) $courseId;
        $course = api_get_course_entity($courseId);
        if (null === $course) {
            return [];
        }

        $sessionId = api_get_session_id();
        $exercise_id = (int) $exercise_id;

        $is_allowedToEdit =
            api_is_allowed_to_edit(null, true) ||
            api_is_allowed_to_edit(true) ||
            api_is_drh() ||
            api_is_student_boss() ||
            api_is_session_admin();
        $TBL_USER = Database::get_main_table(TABLE_MAIN_USER);
        $TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
        $TBL_GROUP_REL_USER = Database::get_course_table(TABLE_GROUP_USER);
        $TBL_GROUP = Database::get_course_table(TABLE_GROUP);
        $TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tblTrackAttemptQualify = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_QUALIFY);

        $session_id_and = '';
        $sessionCondition = '';
        if (!$showSessionField) {
            $session_id_and = api_get_session_condition($sessionId, true, false, 'te.session_id');
            $sessionCondition = api_get_session_condition($sessionId, true, false, 'ttte.session_id');
        }

        $exercise_where = '';
        if (!empty($exercise_id)) {
            $exercise_where .= ' AND te.exe_exo_id = '.$exercise_id.'  ';
        }

        // sql for chamilo-type tests for teacher / tutor view
        $sql_inner_join_tbl_track_exercices = "
        (
            SELECT DISTINCT ttte.*, if(tr.exe_id,1, 0) as revised
            FROM $TBL_TRACK_EXERCISES ttte
            LEFT JOIN $tblTrackAttemptQualify tr
            ON (ttte.exe_id = tr.exe_id) AND tr.author > 0
            WHERE
                c_id = $courseId AND
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
                        u.id as user_id,
                        firstname,
                        lastname,
                        official_code,
                        email,
                        username,
                        g.name as group_name,
                        g.id as group_id
                    FROM $TBL_USER u
                    INNER JOIN $TBL_GROUP_REL_USER gru
                    ON (gru.user_id = u.id AND gru.c_id= $courseId )
                    INNER JOIN $TBL_GROUP g
                    ON (gru.group_id = g.id AND g.c_id= $courseId )
                    WHERE u.active <> ".USER_SOFT_DELETED."
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
                        u.id as user_id,
                        firstname,
                        lastname,
                        official_code,
                        email,
                        username,
                        '' as group_name,
                        '' as group_id
                    FROM $TBL_USER u
                    WHERE u.active <> ".USER_SOFT_DELETED."
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
                    u.id as user_id,
                    firstname,
                    lastname,
                    official_code,
                    email,
                    username,
                    g.name as group_name,
                    g.iid as group_id
                FROM $TBL_USER u
                LEFT OUTER JOIN $TBL_GROUP_REL_USER gru
                ON (gru.user_id = u.id AND gru.c_id= $courseId )
                LEFT OUTER JOIN $TBL_GROUP g
                ON (gru.group_id = g.id AND g.c_id = $courseId )
                WHERE u.active <> ".USER_SOFT_DELETED."
            )";
            }

            // All
            $is_empty_sql_inner_join_tbl_user = false;
            if (empty($sql_inner_join_tbl_user)) {
                $is_empty_sql_inner_join_tbl_user = true;
                $sql_inner_join_tbl_user = "
            (
                SELECT u.id as user_id, firstname, lastname, email, username, ' ' as group_name, '' as group_id, official_code
                FROM $TBL_USER u
                WHERE u.active <> ".USER_SOFT_DELETED." AND u.status NOT IN(".api_get_users_status_ignored_in_reports('string').")
            )";
            }

            $sqlFromOption = " , $TBL_GROUP_REL_USER AS gru ";
            $sqlWhereOption = "  AND gru.c_id = $courseId AND gru.user_id = user.id ";
            $first_and_last_name = api_is_western_name_order() ? "firstname, lastname" : "lastname, firstname";

            if ($get_count) {
                $sql_select = 'SELECT count(te.exe_id) ';
            } else {
                $sql_select = "SELECT DISTINCT
                user.user_id,
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
                user.group_id AS group_id,
                orig_lp_id,
                te.user_ip";
            }

            $sql = " $sql_select
            FROM $TBL_EXERCISES AS ce
            INNER JOIN $sql_inner_join_tbl_track_exercices AS te
            ON (te.exe_exo_id = ce.iid)
            INNER JOIN $sql_inner_join_tbl_user AS user
            ON (user.user_id = exe_user_id)
            INNER JOIN resource_node rn
                ON rn.id = ce.resource_node_id
            INNER JOIN resource_link rl
                ON rl.resource_node_id = rn.id
            WHERE
                te.c_id = $courseId $session_id_and AND
                rl.deleted_at IS NULL
                $exercise_where
                $extra_where_conditions
            ";
        }

        if (empty($sql)) {
            return false;
        }

        if ($get_count) {
            $resx = Database::query($sql);
            $rowx = Database::fetch_row($resx, 'ASSOC');

            return $rowx[0];
        }

        $teacher_list = CourseManager::get_teacher_list_from_course_code($course->getCode());
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
        $column = !empty($column) ? Database::escape_string($column) : null;
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;
        $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;

        if (!empty($column)) {
            $sql .= " ORDER BY `$column` $direction ";
        }

        if (!$getOnyIds) {
            $sql .= " LIMIT $from, $number_of_items";
        }

        $results = [];
        $resx = Database::query($sql);
        while ($rowx = Database::fetch_assoc($resx)) {
            $results[] = $rowx;
        }

        $group_list = GroupManager::get_group_list(null, $course);
        $clean_group_list = [];
        if (!empty($group_list)) {
            foreach ($group_list as $group) {
                $clean_group_list[$group['iid']] = $group['title'];
            }
        }

        $lp_list_obj = new LearnpathList(api_get_user_id());
        $lp_list = $lp_list_obj->get_flat_list();
        $oldIds = array_column($lp_list, 'lp_old_id', 'iid');

        if (is_array($results)) {
            $users_array_id = [];
            $from_gradebook = false;
            if (isset($_GET['gradebook']) && 'view' === $_GET['gradebook']) {
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
                if ('incomplete' === $results[$i]['completion_status']) {
                    // If the exercise was incomplete, we need to determine
                    // if it is still into the time allowed, or if its
                    // allowed time has expired and it can be closed
                    // (it's "unclosed")
                    $minutes = $results[$i]['expired_time'];
                    if (0 == $minutes) {
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
                    $url = api_get_path(WEB_CODE_PATH).
                        'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$results[$i]['orig_lp_id'];
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
                if (0 == $result_disabled) {
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
                                $actions .= Display::getMdiIcon('human-male-board', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Trainer'));
                            }
                        }
                        $revisedLabel = '';
                        switch ($revised) {
                            case 0:
                                $actions .= "<a href='exercise_show.php?".api_get_cidreq()."&action=qualify&id=$id'>".
                                    Display::getMdiIcon(ActionIcon::GRADE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Grade activity')
                                    );
                                $actions .= '</a>';
                                $revisedLabel = Display::label(
                                    get_lang('Not validated'),
                                    'info'
                                );
                                break;
                            case 1:
                                $actions .= "<a href='exercise_show.php?".api_get_cidreq()."&action=edit&id=$id'>".
                                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit'));
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
                                    Display::getMdiIcon(ActionIcon::LOCK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Mark attempt as closed'));
                                $actions .= '</a>';
                                $revisedLabel = Display::label(
                                    get_lang('Unclosed'),
                                    'warning'
                                );
                                break;
                            case 3: //still ongoing
                                $actions .= Display::getMdiIcon('clock', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Attempt still going on. Please wait.'));
                                $actions .= '';
                                $revisedLabel = Display::label(
                                    get_lang('Ongoing'),
                                    'danger'
                                );
                                break;
                        }

                        if (2 == $filter) {
                            $actions .= ' <a href="exercise_history.php?'.api_get_cidreq().'&exe_id='.$id.'">'.
                                Display::getMdiIcon('clipboard-text-clock', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('View changes history')
                                ).'</a>';
                        }

                        // Admin can always delete the attempt
                        if ((false == $locked || api_is_platform_admin()) && !api_is_student_boss()) {
                            $ip = Tracking::get_ip_from_user_event(
                                $results[$i]['exe_user_id'],
                                api_get_utc_datetime(),
                                false
                            );
                            $actions .= '<a href="http://www.whatsmyip.org/ip-geo-location/?ip='.$ip.'" target="_blank">'
                                .Display::getMdiIcon('information', 'ch-tool-icon', null, ICON_SIZE_SMALL, $ip)
                                .'</a>';

                            $recalculateUrl = api_get_path(WEB_CODE_PATH).'exercise/recalculate.php?'.
                                api_get_cidreq().'&'.
                                http_build_query([
                                    'id' => $id,
                                    'exercise' => $exercise_id,
                                    'user' => $results[$i]['exe_user_id'],
                                ]);
                            $actions .= Display::url(
                                Display::getMdiIcon(ActionIcon::REFRESH, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Recalculate results')),
                                $recalculateUrl,
                                [
                                    'data-exercise' => $exercise_id,
                                    'data-user' => $results[$i]['exe_user_id'],
                                    'data-id' => $id,
                                    'class' => 'exercise-recalculate',
                                ]
                            );

                            $exportPdfUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.
                                api_get_cidreq().'&exerciseId='.$exercise_id.'&action=export_pdf&attemptId='.$id.'&userId='.(int) $results[$i]['exe_user_id'];
                            $actions .= '<a href="'.$exportPdfUrl.'" target="_blank">'
                                .Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Export to PDF'))
                                .'</a>';

                            $sendMailUrl =  api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.api_get_cidreq().'&action=send_email&exerciseId='.$exercise_id.'&attemptId='.$results[$i]['exe_id'];
                            $emailLink = '<a href="'.$sendMailUrl.'">'
                                .Display::getMdiIcon(ActionIcon::SEND_SINGLE_EMAIL, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Send by e-mail'))
                                .'</a>';

                            $filterByUser = isset($_GET['filter_by_user']) ? (int) $_GET['filter_by_user'] : 0;
                            $delete_link = '<a
                            href="exercise_report.php?'.api_get_cidreq().'&filter_by_user='.$filterByUser.'&filter='.$filter.'&exerciseId='.$exercise_id.'&delete=delete&did='.$id.'"
                            onclick=
                            "javascript:if(!confirm(\''.sprintf(addslashes(get_lang('Delete attempt?')), $results[$i]['username'], $dt).'\')) return false;"
                            >';
                            $delete_link .= Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, addslashes(get_lang('Delete'))).'</a>';

                            if (api_is_drh() && !api_is_platform_admin()) {
                                $delete_link = null;
                            }
                            if (api_is_session_admin()) {
                                $delete_link = '';
                            }
                            if (3 == $revised) {
                                $delete_link = null;
                            }
                            if (1 !== $revised) {
                                $emailLink = '';
                            }
                            $actions .= $delete_link;
                            $actions .= $emailLink;
                        }
                    } else {
                        $attempt_url = api_get_path(WEB_CODE_PATH).'exercise/result.php?'.api_get_cidreq().'&id='.$results[$i]['exe_id'].'&sid='.$sessionId;
                        $attempt_link = Display::url(
                            get_lang('Show'),
                            $attempt_url,
                            [
                                'class' => 'ajax btn btn--plain',
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
                                $sessionName = $sessionInfo['title'];
                                $sessionStartAccessDate = api_get_local_time($sessionInfo['access_start_date']);
                            }
                        }

                        $objExercise = new Exercise($courseId);
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
                                        if (false == $category_was_added_for_this_test) {
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

        return $listInfo;
    }

    /**
     * Returns email content for a specific attempt.
     */
    public static function getEmailContentForAttempt(int $attemptId): array
    {
        $trackExerciseInfo = self::get_exercise_track_exercise_info($attemptId);

        if (empty($trackExerciseInfo)) {
            return [
                'to' => '',
                'subject' => 'No exercise info found',
                'message' => 'Attempt ID not found or invalid.',
            ];
        }

        $studentId = $trackExerciseInfo['exe_user_id'];
        $courseInfo = api_get_course_info();
        $teacherId = api_get_user_id();

        if (
            empty($trackExerciseInfo['orig_lp_id']) ||
            empty($trackExerciseInfo['orig_lp_item_id'])
        ) {
            $url = api_get_path(WEB_CODE_PATH).'exercise/result.php?id='.$trackExerciseInfo['exe_id'].'&'.api_get_cidreq()
                .'&show_headers=1&id_session='.api_get_session_id();
        } else {
            $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=view&item_id='
                .$trackExerciseInfo['orig_lp_item_id'].'&lp_id='.$trackExerciseInfo['orig_lp_id'].'&'.api_get_cidreq()
                .'&id_session='.api_get_session_id();
        }

        $message = self::getEmailNotification(
            $teacherId,
            $courseInfo,
            $trackExerciseInfo['title'],
            $url
        );

        return [
            'to' => $studentId,
            'subject' => get_lang('Corrected test result'),
            'message' => $message,
        ];
    }

    /**
     * Sends the exercise result email to the student.
     */
    public static function sendExerciseResultByEmail(int $attemptId): void
    {
        $content = self::getEmailContentForAttempt($attemptId);

        if (empty($content['to'])) {
            return;
        }

        MessageManager::send_message_simple(
            $content['to'],
            $content['subject'],
            $content['message'],
            api_get_user_id()
        );
    }

    /**
     * Returns all reviewed attempts for a given exercise and session.
     */
    public static function getReviewedAttemptsInfo(int $exerciseId, int $sessionId): array
    {
        $courseId = api_get_course_int_id();
        $trackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $qualifyTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_QUALIFY);

        $sessionCondition = api_get_session_condition($sessionId, true, false, 't.session_id');

        $sql = "
            SELECT DISTINCT t.exe_id
            FROM $trackTable t
            INNER JOIN $qualifyTable q ON (t.exe_id = q.exe_id AND q.author > 0)
            WHERE
                t.c_id = $courseId AND
                t.exe_exo_id = $exerciseId
                $sessionCondition
        ";

        return Database::store_result(Database::query($sql));
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

        if ('' != $maxNote && '' != $minNote) {
            if (!empty($weight) && (float) $weight !== (float) 0) {
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
     * @param bool   $removeEmptyDecimals
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
        $roundValues = false,
        $removeEmptyDecimals = false
    ) {
        if (is_null($score) && is_null($weight)) {
            return '-';
        }

        $decimalSeparator = empty($decimalSeparator) ? '.' : $decimalSeparator;
        $thousandSeparator = empty($thousandSeparator) ? ',' : $thousandSeparator;

        if ($use_platform_settings) {
            $result = self::convertScoreToPlatformSetting($score, $weight);
            $score = $result['score'];
            $weight = $result['weight'];
        }

        // Keep a raw numeric percentage for model mapping BEFORE string formatting
        $percentageRaw = (100 * (float) $score) / ((0 != (float) $weight) ? (float) $weight : 1);

        // Formats values
        $percentage = float_format($percentageRaw, 1);
        $score      = float_format($score, 1);
        $weight     = float_format($weight, 1);

        if ($roundValues) {
            $whole = floor($percentage);
            $fraction = $percentage - $whole;
            $percentage = ($fraction >= 0.5) ? ceil($percentage) : round($percentage);

            $whole = floor($score);
            $fraction = $score - $whole;
            $score = ($fraction >= 0.5) ? ceil($score) : round($score);

            $whole = floor($weight);
            $fraction = $weight - $whole;
            $weight = ($fraction >= 0.5) ? ceil($weight) : round($weight);
        } else {
            $percentage = float_format($percentage, 1, $decimalSeparator, $thousandSeparator);
            $score      = float_format($score, 1, $decimalSeparator, $thousandSeparator);
            $weight     = float_format($weight, 1, $decimalSeparator, $thousandSeparator);
        }

        // Build base HTML (percentage or score/weight)
        if ($show_percentage) {
            $percentageSign = $hidePercentageSign ? '' : ' %';
            $html = $show_only_percentage
                ? ($percentage . $percentageSign)
                : ($percentage . $percentageSign . ' (' . $score . ' / ' . $weight . ')');
        } else {
            if ($removeEmptyDecimals && ScoreDisplay::hasEmptyDecimals($weight)) {
                $weight = round($weight);
            }
            $html = $score . ' / ' . $weight;
        }

        $bucket = self::convertScoreToModel($percentageRaw);
        if ($bucket !== null) {
            $html = self::getModelStyle($bucket, $percentageRaw);
        }

        // If the platform forces a format, it overrides everything (including the model badge)
        $format = (int) api_get_setting('exercise.exercise_score_format');
        if (!empty($format)) {
            $html = ScoreDisplay::instance()->display_score([$score, $weight], $format);
        }

        return Display::span($html, ['class' => 'score_exercise']);
    }

    /**
     * @param array $model
     * @param float $percentage
     *
     * @return string
     */
    public static function getModelStyle($bucket, $percentage)
    {
        $rawClass = (string) ($bucket['css_class'] ?? '');
        $twClass  = self::mapScoreCssClass($rawClass);

        // Accept both 'name' and 'variable'
        $key   = isset($bucket['name']) ? 'name' : (isset($bucket['variable']) ? 'variable' : null);
        $raw   = $key ? (string) $bucket[$key] : '';
        $label = $raw !== '' ? get_lang($raw) : '';
        $show  = (int) ($bucket['display_score_name'] ?? 0) === 1;

        $base = 'inline-block px-2 py-1 rounded';

        if ($show && $label !== '') {
            return '<span class="' . htmlspecialchars($base . ' ' . $twClass) . '">' .
                htmlspecialchars($label) . '</span>';
        }

        return '<span class="' . htmlspecialchars($base . ' ' . $twClass) . '" ' .
            'title="' . htmlspecialchars($label) . '" aria-label="' . htmlspecialchars($label) . '">' .
            '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
            '</span>';
    }

    /**
     * Map legacy css_class (e.g., "btn-danger") to Tailwind utility classes
     * defined in Chamilo 2's theme (danger/success/warning/info).
     * If a Tailwind class list is already provided, pass-through.
     */
    private static function mapScoreCssClass(string $cssClass): string
    {
        $cssClass = trim($cssClass);

        // Legacy → Tailwind mapping
        $map = [
            'btn-success' => 'bg-success text-success-button-text',
            'btn-warning' => 'bg-warning text-warning-button-text',
            'btn-danger'  => 'bg-danger text-danger-button-text',
            'btn-info'    => 'bg-info text-info-button-text',

            // Also accept short tokens if someone uses "success" directly
            'success' => 'bg-success text-success-button-text',
            'warning' => 'bg-warning text-warning-button-text',
            'danger'  => 'bg-danger text-danger-button-text',
            'info'    => 'bg-info text-info-button-text',
        ];

        if (isset($map[$cssClass])) {
            return $map[$cssClass];
        }

        // If it already looks like Tailwind utility classes, keep as-is
        if (strpos($cssClass, ' ') !== false || preg_match('/[a-z]+-[a-z0-9\-]+/i', $cssClass)) {
            return $cssClass;
        }

        // Neutral fallback
        return 'bg-gray-20 text-gray-90';
    }

    /**
     * @param float $percentage value between 0 and 100
     *
     * @return string
     */
    public static function convertScoreToModel($percentage): ?array
    {
        $model = self::getCourseScoreModel();
        if (empty($model) || empty($model['score_list'])) {
            return null;
        }

        foreach ($model['score_list'] as $bucket) {
            $min = (float) ($bucket['min'] ?? 0);
            $max = (float) ($bucket['max'] ?? 0);

            if ($percentage >= $min && $percentage <= $max) {
                // Propagate the model flag to the bucket
                $bucket['display_score_name'] = (int) ($model['display_score_name'] ?? 0);
                // Precompute label for convenience (optional)
                $bucket['label'] = self::scoreLabel($bucket);
                return $bucket;
            }
        }

        return null;
    }

    private static function scoreLabel(array $row): string
    {
        $key = isset($row['name']) ? 'name' : (isset($row['variable']) ? 'variable' : null);
        if (!$key) {
            return '';
        }
        $value = (string) $row[$key];
        return get_lang($value);
    }

    /**
     * @return array
     */
    public static function getCourseScoreModel(): array
    {
        $modelList = self::getScoreModels();
        if (empty($modelList) || empty($modelList['models'])) {
            return [];
        }

        // Read the configured model id from course settings
        $scoreModelId = (int) api_get_course_setting('score_model_id');

        // first available model
        $selected = $modelList['models'][0];

        if ($scoreModelId !== -1) {
            foreach ($modelList['models'] as $m) {
                if ((int) ($m['id'] ?? 0) === $scoreModelId) {
                    $selected = $m;
                    break;
                }
            }
        }

        // do NOT show name unless explicitly enabled
        $selected['display_score_name'] = (int) ($selected['display_score_name'] ?? 0);

        return $selected;
    }

    /**
     * @return array
     */
    public static function getScoreModels()
    {
        return api_get_setting('exercise.score_grade_model', true);
    }

    /**
     * @param float  $score
     * @param float  $weight
     * @param string $passPercentage
     *
     * @return bool
     */
    public static function isSuccessExerciseResult($score, $weight, $passPercentage)
    {
        $percentage = float_format(
            ($score / (0 != $weight ? $weight : 1)) * 100,
            1
        );
        if (isset($passPercentage) && !empty($passPercentage)) {
            if ($percentage >= $passPercentage) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
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
                $icon = Display::getMdiIcon('check-circle', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Correct'));
            } else {
                $html = get_lang('You didn\'t reach the minimum score');
                $icon = Display::getMdiIcon('alert', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Wrong'));
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
        if ('' != $value) {
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
     * @param   int     0 = only inactive exercises
     *                  1 = only active exercises,
     *                  2 = all exercises
     *                  3 = active <> -1
     *
     * @return CQuiz[]
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

        if (-1 == $session_id) {
            $session_id = 0;
        }
        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($session_id);

        if (null === $course) {
            return [];
        }

        $repo = Container::getQuizRepository();

        return $repo->findAllByCourse($course, $session, (string) $search, $active)
            ->getQuery()
            ->getResult();
    }

    /**
     * Getting all exercises (active only or all)
     * from a course from a session
     * (if a session_id is provided we will show all the exercises in the
     * course + all exercises in the session).
     */
    public static function get_all_exercises_for_course_id(
        int $courseId,
        int $sessionId = 0,
        bool $onlyActiveExercises = true
    ): array {
        if ($courseId <= 0) {
            return [];
        }

        $course  = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);

        $repo = Container::getQuizRepository();

        $qb = $repo->getResourcesByCourse($course, $session);

        $qb->andWhere('links.deletedAt IS NULL');
        $qb->andWhere('links.endVisibilityAt IS NULL');
        if ($onlyActiveExercises) {
            $qb->andWhere('links.visibility = 2');
        } else {
            $qb->andWhere('links.visibility IN (0,2)');
        }

        $qb->orderBy('resource.title', 'ASC');

        $exercises = $qb->getQuery()->getResult();

        $exerciseList = [];
        foreach ($exercises as $exercise) {
            $exerciseList[] = [
                'iid' => $exercise->getIid(),
                'title' => $exercise->getTitle(),
            ];
        }

        return $exerciseList;
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
                if (!empty($result['max_score']) && 0 != intval($result['max_score'])) {
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
                if (!empty($result['max_score']) && 0 != intval($result['max_score'])) {
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
                    0 != intval($result['max_score'])
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
                if (!empty($result['max_score']) && 0 != (float) $result['max_score']) {
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
                if (!empty($result['max_score']) && 0 != intval($result['max_score'])) {
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
                if (!empty($result['max_score']) && 0 != intval(
                        $result['max_score']
                    )
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
                if (!empty($result['max_score']) && 0 != intval($result['max_score'])) {
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
                if (!empty($result['max_score']) && 0 != intval($result['max_score'])) {
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
                if (!empty($result['max_score']) && 0 != intval($result['max_score'])) {
                    $score = $result['score'] / $result['max_score'];
                    $avg_score += $score;
                }
            }
        }

        return $avg_score;
    }

    /**
     * Get student results (only in completed exercises) stats by question.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getStudentStatsByQuestion(
        int $questionId,
        int $exerciseId,
        string $courseCode,
        int $sessionId,
        bool $onlyStudent = false
    ): array
    {
        $trackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $trackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $questionId = (int) $questionId;
        $exerciseId = (int) $exerciseId;
        $courseCode = Database::escape_string($courseCode);
        $sessionId = (int) $sessionId;
        $courseId = api_get_course_int_id($courseCode);

        $sql = "SELECT MAX(marks) as max, MIN(marks) as min, AVG(marks) as average
                FROM $trackExercises e ";
        $sessionCondition = api_get_session_condition($sessionId, true, false, 'e.session_id');
        if ($onlyStudent) {
            $courseCondition = '';
            if (empty($sessionId)) {
                $courseCondition = "
                INNER JOIN $courseUser c
                ON (
                    e.exe_user_id = c.user_id AND
                    e.c_id = c.c_id AND
                    c.status = ".STUDENT." AND
                    relation_type <> 2
                )";
            } else {
                $sessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                $courseCondition = "
            INNER JOIN $sessionRelCourse sc
            ON (
                        e.exe_user_id = sc.user_id AND
                        e.c_id = sc.c_id AND
                        e.session_id = sc.session_id AND
                        sc.status = ".SessionEntity::STUDENT."
                )";
            }
            $sql .= $courseCondition;
        }
        $sql .= "
    		INNER JOIN $trackAttempt a
    		ON (
    		    a.exe_id = e.exe_id
            )
    		WHERE
    		    exe_exo_id 	= $exerciseId AND
                e.c_id = $courseId AND
                question_id = $questionId AND
                e.status = ''
                $sessionCondition
            LIMIT 1";
        $result = Database::query($sql);
        $return = [];
        if ($result) {
            $return = Database::fetch_assoc($result);
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
                $count = (0 === $count) ? 1 : 0;
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
        $track_exercises   = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempt     = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseUser        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable       = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $question_id = (int) $question_id;
        $exercise_id = (int) $exercise_id;
        $courseId    = (int) api_get_course_int_id($course_code);
        $session_id  = (int) $session_id;

        if (in_array($questionType, [FILL_IN_BLANKS, FILL_IN_BLANKS_COMBINATION], true)) {
            $listStudentsId     = [];
            $listAllStudentInfo = CourseManager::get_student_list_from_course_code(api_get_course_id(), true);
            foreach ($listAllStudentInfo as $listStudentInfo) {
                $listStudentsId[] = (int) $listStudentInfo['user_id'];
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
                ON cu.c_id = c.id AND cu.user_id = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
                ON (cu.c_id = c.id AND cu.user_id = e.exe_user_id AND e.session_id = cu.session_id)";
            $courseConditionWhere = ' AND cu.status = '.SessionEntity::STUDENT;
        }

        $sessionCondition = api_get_session_condition($session_id, true, false, 'e.session_id');
        $sql = "SELECT DISTINCT exe_user_id
            FROM $track_exercises e
            INNER JOIN $track_attempt a
                ON (a.exe_id = e.exe_id AND a.c_id = e.c_id)
            INNER JOIN $courseTable c
                ON c.id = e.c_id
            $courseCondition
            WHERE
                exe_exo_id  = $exercise_id AND
                e.c_id      = $courseId AND
                question_id = $question_id AND
                answer <> '0' AND
                e.status = ''
                $courseConditionWhere
                $sessionCondition
    ";

        $result = Database::query($sql);

        return $result ? (int) Database::num_rows($result) : 0;
    }

    /**
     * Get number of answers to hotspot questions.
     */
    public static function getNumberStudentsAnswerHotspotCount(
        int    $answerId,
        int    $questionId,
        int    $exerciseId,
        string $courseCode,
        int $sessionId
    ): int
    {
        $trackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $trackHotspot = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $questionId = (int) $questionId;
        $answerId = (int) $answerId;
        $exerciseId = (int) $exerciseId;
        $courseId = api_get_course_int_id($courseCode);
        $sessionId = (int) $sessionId;

        if (empty($sessionId)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id  = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON (cu.c_id = c.id AND cu.user_id = e.exe_user_id AND e.session_id = cu.session_id)";
            $courseConditionWhere = ' AND cu.status = '.SessionEntity::STUDENT;
        }

        $sessionCondition = api_get_session_condition($sessionId, true, false, 'e.session_id');
        $sql = "SELECT DISTINCT exe_user_id
                FROM $trackExercises e
                INNER JOIN $trackHotspot a
                ON (a.hotspot_exe_id = e.exe_id)
                INNER JOIN $courseTable c
                ON (a.c_id = c.id)
                $courseCondition
                WHERE
                    exe_exo_id              = $exerciseId AND
                    a.c_id 	= $courseId AND
                    hotspot_answer_id       = $answerId AND
                    hotspot_question_id     = $questionId AND
                    hotspot_correct         =  1 AND
                    e.status                = ''
                    $courseConditionWhere
                    $sessionCondition
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
        $track_attempt   = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseTable     = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUser      = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $question_id = (int) $question_id;
        $answer_id   = (int) $answer_id;
        $exercise_id = (int) $exercise_id;
        $courseId    = (int) api_get_course_int_id($course_code);
        $session_id  = (int) $session_id;

        switch ($question_type) {
            case FILL_IN_BLANKS:
            case FILL_IN_BLANKS_COMBINATION:
                $answer_condition = '';
                $select_condition = ' e.exe_id, answer ';
                break;
            case MATCHING:
            case MATCHING_COMBINATION:
            case MATCHING_DRAGGABLE:
            case MATCHING_DRAGGABLE_COMBINATION:
            default:
                $answer_condition = " answer = $answer_id AND ";
                $select_condition = ' DISTINCT exe_user_id ';
        }

        if (empty($session_id)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
                ON cu.c_id = e.c_id AND cu.user_id = e.exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
                ON (cu.c_id = e.c_id AND cu.user_id = e.exe_user_id AND e.session_id = cu.session_id)";
            $courseConditionWhere = ' AND cu.status = '.SessionEntity::STUDENT;
        }

        $sessionCondition = api_get_session_condition($session_id, true, false, 'e.session_id');
        $sql = "SELECT $select_condition
            FROM $track_exercises e
            INNER JOIN $track_attempt a
                ON (a.exe_id = e.exe_id AND a.c_id = e.c_id)
            INNER JOIN $courseTable c
                ON c.id = e.c_id
            $courseCondition
            WHERE
                exe_exo_id = $exercise_id AND
                e.c_id = $courseId AND
                $answer_condition
                question_id = $question_id AND
                e.status = ''
                $courseConditionWhere
                $sessionCondition
    ";

        $result = Database::query($sql);
        $return = 0;
        if ($result) {
            switch ($question_type) {
                case FILL_IN_BLANKS:
                case FILL_IN_BLANKS_COMBINATION:
                    $good_answers = 0;
                    while ($row = Database::fetch_assoc($result)) {
                        $fill_blank = self::check_fill_in_blanks(
                            $correct_answer,
                            $row['answer'],
                            $current_answer
                        );
                        if (isset($fill_blank[$current_answer]) && 1 == (int) $fill_blank[$current_answer]) {
                            $good_answers++;
                        }
                    }

                    return $good_answers;

                case MATCHING:
                case MATCHING_COMBINATION:
                case MATCHING_DRAGGABLE:
                case MATCHING_DRAGGABLE_COMBINATION:
                default:
                    $return = Database::num_rows($result);
            }
        }

        return $return;
    }

    /**
     * Get the number of times an answer was selected.
     */
    public static function getCountOfAnswers(
        int $answerId,
        int $questionId,
        int $exerciseId,
        string $courseCode,
        int $sessionId,
        $questionType = null,
    ): int
    {
        $trackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $trackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseUserSession = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $answerId = (int) $answerId;
        $questionId = (int) $questionId;
        $exerciseId = (int) $exerciseId;
        $courseId = api_get_course_int_id($courseCode);
        $sessionId = (int) $sessionId;
        $return = 0;

        $answerCondition = match ($questionType) {
            FILL_IN_BLANKS => '',
            default => " answer = $answerId AND ",
        };

        if (empty($sessionId)) {
            $courseCondition = "
            INNER JOIN $courseUser cu
            ON cu.c_id = c.id AND cu.user_id = exe_user_id";
            $courseConditionWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        } else {
            $courseCondition = "
            INNER JOIN $courseUserSession cu
            ON (cu.c_id = a.c_id AND cu.user_id = e.exe_user_id AND e.session_id = cu.session_id)";
            $courseConditionWhere = ' AND cu.status = '.SessionEntity::STUDENT;
        }

        $sessionCondition = api_get_session_condition($sessionId, true, false, 'e.session_id');
        $sql = "SELECT count(a.answer) as total
                FROM $trackExercises e
                INNER JOIN $trackAttempt a
                ON (
                    a.exe_id = e.exe_id
                )
                INNER JOIN $courseTable c
                ON c.id = e.c_id
                $courseCondition
                WHERE
                    exe_exo_id = $exerciseId AND
                    e.c_id = $courseId AND
                    $answerCondition
                    question_id = $questionId AND
                    e.status = ''
                    $courseConditionWhere
                    $sessionCondition
            ";
        $result = Database::query($sql);
        if ($result) {
            $count = Database::fetch_array($result);
            $return = (int) $count['total'];
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
        if (isset($is_set_switchable[1]) && 1 == $is_set_switchable[1]) {
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
            if (false === ($pos = api_strpos($temp, '['))) {
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
            if (false === ($pos = api_strpos($temp, ']'))) {
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
                if (3 == $i) {
                    break;
                }
            }
            $tmp = api_strrpos($choice[$j], ' / ');

            if (false !== $tmp) {
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
        $res = "<select name='$name' id='$name' onchange='".$onchange."' >";
        $res .= "<option value='-1'".$tabSelected["-1"].">-- ".get_lang('All groups')." --</option>";
        $res .= "<option value='0'".$tabSelected["0"].">- ".get_lang('Not in a group')." -</option>";
        $groups = GroupManager::get_group_list();
        $currentCatId = 0;
        $countGroups = count($groups);
        for ($i = 0; $i < $countGroups; $i++) {
            $category = GroupManager::get_category_from_group($groups[$i]['iid']);
            if ($category['id'] != $currentCatId) {
                $res .= "<option value='-1' disabled='disabled'>".$category['title']."</option>";
                $currentCatId = $category['id'];
            }
            $res .= "<option ".$tabSelected[$groups[$i]['id']]."style='margin-left:40px' value='".
                $groups[$i]["iid"]."'>".
                $groups[$i]["name"].
                "</option>";
        }
        $res .= "</select>";

        return $res;
    }

    public static function create_chat_exercise_session($exe_id)
    {
        $exeId = (int) $exe_id;
        if ($exeId <= 0) {
            return;
        }

        if (!isset($_SESSION['current_exercises']) || !is_array($_SESSION['current_exercises'])) {
            $_SESSION['current_exercises'] = [];
        }
        $_SESSION['current_exercises'][$exeId] = true;

        try {
            /** @var AiHelper $aiHelper */
            $aiHelper = Container::$container->get(AiHelper::class);
            $aiHelper->markUserInTest((int) $exeId);
        } catch (\Throwable $e) {
            // Ignore on legacy context (no hard dependency).
        }
    }

    public static function delete_chat_exercise_session($exe_id)
    {
        $exeId = (int) $exe_id;
        if ($exeId <= 0) {
            return;
        }

        if (isset($_SESSION['current_exercises']) && is_array($_SESSION['current_exercises'])) {
            unset($_SESSION['current_exercises'][$exeId]);

            if (empty($_SESSION['current_exercises'])) {
                unset($_SESSION['current_exercises']);
            }
        }

        try {
            /** @var AiHelper $aiHelper */
            $aiHelper = Container::$container->get(AiHelper::class);
            $aiHelper->clearUserInTest((int) $exeId);
        } catch (\Throwable $e) {
            // Ignore on legacy context (no hard dependency).
        }
    }

    /**
     * Display the exercise results.
     *
     * @param Exercise $objExercise
     * @param int      $exeId
     * @param bool     $save_user_result save users results (true) or just show the results (false)
     * @param string   $remainingMessage
     * @param bool     $allowSignature
     * @param bool     $allowExportPdf
     * @param bool     $isExport
     */
    public static function displayQuestionListByAttempt(
        $objExercise,
        $exeId,
        $save_user_result = false,
        $remainingMessage = '',
        $allowSignature = false,
        $allowExportPdf = false,
        $isExport = false
    ) {
        $origin = api_get_origin();
        $courseId = api_get_course_int_id();
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
            if (false == $save_user_result) {
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
            if (false === $objExercise->hasResultsAccess($exercise_stat_info)) {
                echo Display::return_message(
                    sprintf(get_lang('You have passed the %s minutes limit to see the results.'), $objExercise->getResultsAccess())
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
        $exerciseContent = null;

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
        if (RESULT_DISABLE_SHOW_SCORE_ONLY == $objExercise->results_disabled &&
            EXERCISE_FEEDBACK_TYPE_END == $objExercise->getFeedbackType()
        ) {
            $show_all_but_expected_answer = true;
            $show_results = true;
            $show_only_score = false;
        }

        $showTotalScoreAndUserChoicesInLastAttempt = true;
        $showTotalScore = true;
        $showQuestionScore = true;
        $attemptResult = [];

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
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
                    $courseId,
                    $sessionId,
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
                if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT == $objExercise->results_disabled) {
                    $showTotalScore = true;
                }
                $showTotalScoreAndUserChoicesInLastAttempt = false;
                if ($numberAttempts >= $objExercise->attempts) {
                    $showTotalScore = true;
                    $show_results = true;
                    $show_only_score = false;
                    $showTotalScoreAndUserChoicesInLastAttempt = true;
                }

                if (RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK == $objExercise->results_disabled) {
                    $showTotalScore = true;
                    $show_results = true;
                    $show_only_score = false;
                    $showTotalScoreAndUserChoicesInLastAttempt = false;
                    if ($numberAttempts >= $objExercise->attempts) {
                        $showTotalScoreAndUserChoicesInLastAttempt = true;
                    }

                    // Check if the current attempt is the last.
                    if (false === $save_user_result && !empty($attempts)) {
                        $showTotalScoreAndUserChoicesInLastAttempt = false;
                        $position = 1;
                        foreach ($attempts as $attempt) {
                            if ($exeId == $attempt['exe_id']) {
                                break;
                            }
                            $position++;
                        }

                        if ($position == $objExercise->attempts) {
                            $showTotalScoreAndUserChoicesInLastAttempt = true;
                        }
                    }
                }
            }

            if (RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK ==
                $objExercise->results_disabled
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

        // When exporting to PDF hide feedback/comment/score show warning in hotspot.
        if ($allowExportPdf && $isExport) {
            $showTotalScore = false;
            $showQuestionScore = false;
            $objExercise->feedback_type = 2;
            $objExercise->hideComment = true;
            $objExercise->hideNoAnswer = true;
            $objExercise->results_disabled = 0;
            $objExercise->hideExpectedAnswer = true;
            $show_results = true;
        }

        if ('embeddable' !== $origin &&
            !empty($exercise_stat_info['exe_user_id']) &&
            !empty($studentInfo)
        ) {
            // Shows exercise header.
            echo $objExercise->showExerciseResultHeader(
                $studentInfo,
                $exercise_stat_info,
                $save_user_result,
                $allowSignature,
                $allowExportPdf
            );
        }

        $question_list_answers = [];
        $category_list = [];
        $loadChoiceFromSession = false;
        $fromDatabase = true;
        $exerciseResult = null;
        $exerciseResultCoordinates = null;
        $delineationResults = null;
        if (true === $save_user_result && in_array(
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
        $panelsByParent = [];
        $finalOrder = [];
        if (!empty($question_list)) {
            $parentMap = [];
            $mediaChildren = []; // pid => ['first_idx'=>int, 'children'=>int[]]
            foreach ($question_list as $idx => $qid) {
                $q = Question::read($qid, $objExercise->course);
                $pid = (int) ($q->parent_id ?: 0);
                $parentMap[$qid] = $pid;
                if ($pid > 0) {
                    if (!isset($mediaChildren[$pid])) {
                        $mediaChildren[$pid] = ['first_idx' => $idx, 'children' => []];
                    }
                    $mediaChildren[$pid]['children'][] = $qid;
                }
            }

            // build finalOrder, emitting each media group once.
            $groupEmitted = [];
            foreach ($question_list as $idx => $qid) {
                $pid = $parentMap[$qid] ?? 0;
                if ($pid === 0) {
                    $finalOrder[] = ['type' => 'single', 'qid' => $qid];
                } else {
                    if (empty($groupEmitted[$pid])) {
                        $groupEmitted[$pid] = true;
                        $finalOrder[] = [
                            'type'     => 'group',
                            'parent'   => $pid,
                            'children' => $mediaChildren[$pid]['children'] ?? [$qid],
                        ];
                    }
                    // If already emitted, skip the child here (it will be in the group).
                }
            }
        }

        $orderedOutputHtml = '';
        $renderSingle = function (int $questionId) use (
            &$objExercise,
            $exeId,
            $loadChoiceFromSession,
            &$exerciseResult,
            &$delineationResults,
            &$exerciseResultCoordinates,
            &$save_user_result,
            &$fromDatabase,
            &$show_results,
            &$total_score,
            &$total_weight,
            &$question_list_answers,
            &$showQuestionScore,
            &$counter,
            &$attemptResult,
            &$category_list
        ) {
            // Start buffering rendering for this question
            ob_start();

            // Load choices from session if needed
            $choice = null;
            $delineationChoice = null;
            if ($loadChoiceFromSession) {
                $choice = $exerciseResult[$questionId] ?? null;
                $delineationChoice = $delineationResults[$questionId] ?? null;
            }

            // Compute result for the given question
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
                true // keep user choices in last attempt when applicable
            );

            if (empty($result)) {
                ob_end_clean();
                return [null, null]; // nothing to add
            }

            $total_score  += $result['score'];
            $total_weight += $result['weight'];

            $question_list_answers[] = [
                'question'            => $result['open_question'],
                'answer'              => $result['open_answer'],
                'answer_type'         => $result['answer_type'],
                'generated_oral_file' => $result['generated_oral_file'],
            ];

            $my_total_score  = $result['score'];
            $my_total_weight = $result['weight'];
            $scorePassed     = self::scorePassed($my_total_score, $my_total_weight);

            // Category aggregation
            $objQuestionTmp = Question::read($questionId, $objExercise->course);
            $category_was_added_for_this_test = false;
            if (isset($objQuestionTmp->category) && !empty($objQuestionTmp->category)) {
                $cid = $objQuestionTmp->category;
                $category_list[$cid]['score']           = ($category_list[$cid]['score'] ?? 0) + $my_total_score;
                $category_list[$cid]['total']           = ($category_list[$cid]['total'] ?? 0) + $my_total_weight;
                $category_list[$cid]['total_questions'] = ($category_list[$cid]['total_questions'] ?? 0) + 1;
                if ($scorePassed) {
                    if (!empty($my_total_score)) {
                        $category_list[$cid]['passed'] = ($category_list[$cid]['passed'] ?? 0) + 1;
                    }
                } else {
                    if ($result['user_answered']) {
                        $category_list[$cid]['wrong'] = ($category_list[$cid]['wrong'] ?? 0) + 1;
                    } else {
                        $category_list[$cid]['no_answer'] = ($category_list[$cid]['no_answer'] ?? 0) + 1;
                    }
                }
                $category_was_added_for_this_test = true;
            }
            if (!empty($objQuestionTmp->category_list)) {
                foreach ($objQuestionTmp->category_list as $cid) {
                    $category_list[$cid]['score'] = ($category_list[$cid]['score'] ?? 0) + $my_total_score;
                    $category_list[$cid]['total'] = ($category_list[$cid]['total'] ?? 0) + $my_total_weight;
                    $category_was_added_for_this_test = true;
                }
            }
            if (!$category_was_added_for_this_test) {
                $category_list['none']['score'] = ($category_list['none']['score'] ?? 0) + $my_total_score;
                $category_list['none']['total'] = ($category_list['none']['total'] ?? 0) + $my_total_weight;
            }

            if (0 == $objExercise->selectPropagateNeg() && $my_total_score < 0) {
                $my_total_score = 0;
            }

            $comnt = null;
            if ($show_results) {
                $comnt = Event::get_comments($exeId, $questionId);
                $teacherAudio = self::getOralFeedbackAudio($exeId, $questionId);

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

            $calculatedScore = [
                'result'        => self::show_score($my_total_score, $my_total_weight, false),
                'pass'          => $scorePassed,
                'score'         => $my_total_score,
                'weight'        => $my_total_weight,
                'comments'      => $comnt,
                'user_answered' => $result['user_answered'],
            ];

            $scoreCol = $show_results ? $calculatedScore : [];

            $contents = ob_get_clean();
            $questionContent = '';
            if ($show_results) {
                $questionContent = '<div class="question-answer-result">';
                if (false === $showQuestionScore) {
                    $scoreCol = [];
                }

                // Numbered header (media parents are not rendered here)
                $questionContent .= $objQuestionTmp->return_header(
                    $objExercise,
                    $counter,
                    $scoreCol
                );
            }
            // Count only real questions
            $counter++;
            $questionContent .= $contents;
            if ($show_results) {
                $questionContent .= '</div>';
            }

            $calculatedScore['question_content'] = $questionContent;
            $attemptResult[] = $calculatedScore;

            return [$questionContent, $result];
        };

        // Render entries
        if (!empty($finalOrder)) {
            foreach ($finalOrder as $entry) {
                if ($entry['type'] === 'single') {
                    [$html, $resultLast] = $renderSingle((int)$entry['qid']);
                    if ($html) {
                        $panelsByParent[0][] = Display::panel($html);
                        if ($show_results) {
                            $orderedOutputHtml .= Display::panel($html);
                        }
                        if ($resultLast) {
                            $result = $resultLast; // keep last result for later checks (like chart)
                        }
                    }
                } else {
                    $pid = (int)$entry['parent'];
                    $children = (array)$entry['children'];

                    if ($show_results) {
                        // Open media wrapper
                        $orderedOutputHtml .= '<div class="media-group">';

                        // Render media stem (no numbering)
                        $orderedOutputHtml .= '<div class="media-content">';
                        ob_start();
                        $objExercise->manage_answer(
                            $exeId,
                            $pid,
                            null,
                            'exercise_show',
                            [],
                            false,
                            true,
                            $show_results,
                            $objExercise->selectPropagateNeg()
                        );
                        $orderedOutputHtml .= ob_get_clean();
                        $orderedOutputHtml .= '</div>';

                        $mediaQ = Question::read($pid, $objExercise->course);
                        if (!empty($mediaQ->description)) {
                            $orderedOutputHtml .= '<div class="media-description">'.$mediaQ->description.'</div>';
                        }

                        $orderedOutputHtml .= '<div class="media-children">';
                    }

                    // Render all children contiguously
                    foreach ($children as $cid) {
                        [$html, $resultLast] = $renderSingle((int)$cid);
                        if ($html) {
                            $panelsByParent[$pid][] = Display::panel($html);
                            if ($show_results) {
                                $orderedOutputHtml .= Display::panel($html);
                            }
                            if ($resultLast) {
                                $result = $resultLast;
                            }
                        }
                    }

                    if ($show_results) {
                        // Close media wrapper
                        $orderedOutputHtml .= '</div></div>';
                    }
                }
            }
        }

        // Print output
        if ($show_results) {
            echo $orderedOutputHtml;
        } else {
            // Fallback (no wrappers when results are not shown)
            foreach ($panelsByParent as $pid => $panels) {
                foreach ($panels as $panelHtml) {
                    echo $panelHtml;
                }
            }
        }

        // Display text when test is finished #4074 and for LP #4227
        $endOfMessage = $objExercise->getFinishText($total_score, $total_weight);
        if (!empty($endOfMessage)) {
            echo Display::div(
                $endOfMessage,
                ['id' => 'quiz_end_message']
            );
        }

        $totalScoreText = null;
        $certificateBlock = '';
        if (($show_results || $show_only_score) && $showTotalScore) {
            if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == ($result['answer_type'] ?? null)) {
                echo '<h1 style="text-align : center; margin : 20px 0;">'.get_lang('Your results').'</h1><br />';
            }
            $totalScoreText .= '<div class="question_row_score">';
            if (!empty($result) && MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == ($result['answer_type'] ?? null)) {
                $totalScoreText .= self::getQuestionDiagnosisRibbon(
                    $objExercise,
                    $total_score,
                    $total_weight,
                    true
                );
            } else {
                $pluginEvaluation = QuestionOptionsEvaluationPlugin::create();
                if ('true' === $pluginEvaluation->get(QuestionOptionsEvaluationPlugin::SETTING_ENABLE)) {
                    $formula = $pluginEvaluation->getFormulaForExercise($objExercise->getId());

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
                    $courseId,
                    $sessionId
                );
            }
        }

        if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == ($result['answer_type'] ?? null)) {
            $chartMultiAnswer = MultipleAnswerTrueFalseDegreeCertainty::displayStudentsChartResults(
                $exeId,
                $objExercise
            );
            echo $chartMultiAnswer;
        }

        if (!empty($category_list) &&
            ($show_results || $show_only_score || RESULT_DISABLE_RADAR == $objExercise->results_disabled)
        ) {
            // Adding total
            $category_list['total'] = [
                'score' => $total_score,
                'total' => $total_weight,
            ];
            echo TestCategory::get_stats_table_by_attempt($objExercise, $category_list);
        }

        if ($show_all_but_expected_answer) {
            $exerciseContent .= Display::return_message(get_lang('Note: This test has been setup to hide the expected answers.'));
        }

        // Remove audio auto play from questions on results page - refs BT#7939
        $exerciseContent = preg_replace(
            ['/autoplay[\=\".+\"]+/', '/autostart[\=\".+\"]+/'],
            '',
            $exerciseContent
        );

        echo $certificateBlock;

        // Ofaj change BT#11784
        if (('true' === api_get_setting('exercise.quiz_show_description_on_results_page')) &&
            !empty($objExercise->description)
        ) {
            echo Display::div($objExercise->description, ['class' => 'exercise_description']);
        }

        echo $exerciseContent;
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
                        $objExercise->getId(),
                        $total_score,
                        $total_weight,
                        $sessionId,
                        $learnpath_id,
                        $learnpath_item_id,
                        $learnpath_item_view_id,
                        $exercise_stat_info['exe_duration'],
                        $question_list
                    );

                    $allowStats = ('true' === api_get_setting('gradebook.allow_gradebook_stats'));
                    if ($allowStats) {
                        $objExercise->generateStats(
                            $objExercise->getId(),
                            api_get_course_info(),
                            $sessionId
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
                $objExercise,
                api_get_user_id(),
                $courseId,
                $sessionId
            );
        }

        if (!empty($remainingMessage)) {
            echo Display::return_message($remainingMessage, 'normal', false);
        }

        $failedAnswersCount = 0;
        $wrongQuestionHtml = '';
        $all = '';
        foreach ($attemptResult as $item) {
            if (false === $item['pass']) {
                $failedAnswersCount++;
                $wrongQuestionHtml .= $item['question_content'].'<br />';
            }
            $all .= $item['question_content'].'<br />';
        }

        $passed = self::isPassPercentageAttemptPassed(
            $objExercise,
            $total_score,
            $total_weight
        );

        $percentage = 0;
        if (!empty($total_weight)) {
            $percentage = ($total_score / $total_weight) * 100;
        }

        return [
            'category_list' => $category_list,
            'attempts_result_list' => $attemptResult, // array of results
            'exercise_passed' => $passed, // boolean
            'total_answers_count' => count($attemptResult), // int
            'failed_answers_count' => $failedAnswersCount, // int
            'failed_answers_html' => $wrongQuestionHtml,
            'all_answers_html' => $all,
            'total_score' => $total_score,
            'total_weight' => $total_weight,
            'total_percentage' => $percentage,
            'count_pending_questions' => $countPendingQuestions,
        ];
    }

    /**
     * Display the ranking of results in a exercise.
     *
     * @param Exercise $exercise
     * @param int      $currentUserId
     * @param int      $courseId
     * @param int      $sessionId
     *
     * @return string
     */
    public static function displayResultsInRanking($exercise, $currentUserId, $courseId, $sessionId = 0)
    {
        $exerciseId = $exercise->iId;
        $data = self::exerciseResultsInRanking($exerciseId, $courseId, $sessionId);

        $table = new HTML_Table(['class' => 'table table-hover table-striped table-bordered']);
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

        $dql = 'SELECT DISTINCT u.id FROM ChamiloCoreBundle:TrackEExercise te JOIN te.user u WHERE te.quiz = :id AND te.course = :cId';
        $dql .= api_get_session_condition($sessionId, true, false, 'te.session');

        $result = $em
            ->createQuery($dql)
            ->setParameters(['id' => $exerciseId, 'cId' => $courseId])
            ->getScalarResult();

        $data = [];

        foreach ($result as $item) {
            $attempt = self::get_best_attempt_by_user($item['id'], $exerciseId, $courseId, $sessionId);
            if (!empty($attempt) && isset($attempt['score']) && isset($attempt['exe_date'])) {
                $data[] = $attempt;
            }
        }

        if (empty($data)) {
            return [];
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
            $passPercentage = $objExercise->selectPassPercentage();
            $isSuccess = self::isSuccessExerciseResult($score, $weight, $passPercentage);
            // Color the final test score if pass_percentage activated
            $ribbonTotalSuccessOrError = '';
            if (self::isPassPercentageEnabled($passPercentage)) {
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

    public static function isPassPercentageAttemptPassed($objExercise, $score, $weight)
    {
        $passPercentage = $objExercise->selectPassPercentage();

        return self::isSuccessExerciseResult($score, $weight, $passPercentage);
    }

    /**
     * @param float $score
     * @param float $weight
     * @param bool  $checkPassPercentage
     * @param int   $countPendingQuestions
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
        if (1 === $hide) {
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
            '@ChamiloCore/Mailer/Exercise/result_alert_body.html.twig',
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
     *
     * @return string
     */
    public static function getOralFeedbackForm($attemptId, $questionId)
    {
        $view = new Template('', false, false, false, false, false, false);

        $view->assign('type', OralExpression::RECORDING_TYPE_FEEDBACK);
        $view->assign('question_id', $questionId);
        $view->assign('t_exercise_id', $attemptId);

        $template = $view->get_template('exercise/oral_expression.html.twig');

        return $view->fetch($template);
    }

    /**
     * Get oral file audio for a given exercise attempt and question.
     *
     * If $returnUrls is true, returns an array of URLs.
     * Otherwise returns the HTML string with <audio> players.
     *
     * @param int  $trackExerciseId
     * @param int  $questionId
     * @param bool $returnUrls
     *
     * @return array|string
     */
    public static function getOralFileAudio(
        int $trackExerciseId,
        int $questionId,
        bool $returnUrls = false
    ) {
        /** @var TrackEExercise|null $trackExercise */
        $trackExercise = Container::getTrackEExerciseRepository()->find($trackExerciseId);

        if (null === $trackExercise) {
            return $returnUrls ? [] : '';
        }

        $questionAttempt = $trackExercise->getAttemptByQuestionId($questionId);

        if (null === $questionAttempt) {
            return $returnUrls ? [] : '';
        }

        $attemptId = method_exists($questionAttempt, 'getId')
            ? (int) $questionAttempt->getId()
            : 0;

        // Collect feedback ResourceNode IDs to avoid duplicate players
        $feedbackNodeIds = [];
        if (method_exists($questionAttempt, 'getAttemptFeedbacks')) {
            foreach ($questionAttempt->getAttemptFeedbacks() as $feedback) {
                if (null === $feedback) {
                    continue;
                }

                $feedbackNode = method_exists($feedback, 'getResourceNode')
                    ? $feedback->getResourceNode()
                    : null;

                if (null === $feedbackNode) {
                    continue;
                }

                if (method_exists($feedbackNode, 'getId')) {
                    $feedbackNodeIds[] = (int) $feedbackNode->getId();
                }
            }

            $feedbackNodeIds = array_unique($feedbackNodeIds);
        }

        $filesCollection = $questionAttempt->getAttemptFiles();
        $filesCount = is_countable($filesCollection) ? count($filesCollection) : 0;

        if (0 === $filesCount) {
            return $returnUrls ? [] : '';
        }

        $urls = [];

        foreach ($filesCollection as $attemptFile) {
            if (!$attemptFile) {
                continue;
            }

            $attemptFileId = method_exists($attemptFile, 'getId')
                ? (string) $attemptFile->getId()
                : 'n/a';

            $resourceNode = method_exists($attemptFile, 'getResourceNode')
                ? $attemptFile->getResourceNode()
                : null;

            if (null === $resourceNode) {
                continue;
            }

            $nodeId = method_exists($resourceNode, 'getId')
                ? (int) $resourceNode->getId()
                : 0;

            // Skip files whose ResourceNode is used by feedback (avoid duplicate players)
            if (!empty($feedbackNodeIds) && in_array($nodeId, $feedbackNodeIds, true)) {
                continue;
            }

            $url = self::getPublicUrlForResourceNode($resourceNode);
            if (empty($url)) {
                continue;
            }

            $urls[] = $url;
        }

        if (empty($urls)) {
            return $returnUrls ? [] : '';
        }

        if ($returnUrls) {
            return $urls;
        }

        // Build HTML <audio> tags using the resolved URLs (student attempts only)
        $html = '';

        foreach ($urls as $url) {
            $html .= Display::tag(
                'audio',
                '',
                [
                    'src' => $url,
                    'controls' => '',
                ]
            );
        }

        return $html;
    }

    /**
     * Returns the HTML audio player for the latest oral feedback
     * of a given question attempt.
     *
     * @param int  $attemptId   TrackEExercise id (exercise attempt)
     * @param int  $questionId  Question id inside the attempt
     * @param bool $wrap        Kept for backward compatibility (currently unused)
     *
     * @return string           HTML <audio> tag or empty string if none
     */
    public static function getOralFeedbackAudio(
        int $attemptId,
        int $questionId,
        bool $wrap = true
    ): string {
        /** @var TrackEExercise|null $exercise */
        $exercise = Container::getTrackEExerciseRepository()->find($attemptId);

        if (null === $exercise) {
            return '';
        }

        $attempt = $exercise->getAttemptByQuestionId($questionId);
        if (null === $attempt) {
            return '';
        }

        $html = '';

        // We keep only the latest feedback to avoid duplicated players.
        foreach ($attempt->getAttemptFeedbacks() as $feedback) {
            $node = $feedback->getResourceNode();

            if (null === $node) {
                // Old data might still be asset-based; migration can handle that later.
                continue;
            }

            $url = self::getPublicUrlForResourceNode($node);

            if ('' === $url) {
                // URL could not be generated (missing file or routing issue).
                continue;
            }

            // Override previous HTML so that only the last feedback is rendered.
            $html = Display::tag(
                'audio',
                '',
                [
                    'src' => $url,
                    'controls' => '',
                ]
            );
        }

        return $html;
    }

    /**
     * Get uploaded answer files (resource-based) for a given attempt/question.
     *
     * If $returnUrls is true, returns an array of URLs.
     * Otherwise returns a simple HTML list of links.
     *
     * @param int  $trackExerciseId
     * @param int  $questionId
     * @param bool $returnUrls
     *
     * @return array|string
     */
    public static function getUploadAnswerFiles(int $trackExerciseId, int $questionId, bool $returnUrls = false)
    {
        /** @var TrackEExercise|null $trackExercise */
        $trackExercise = Container::getTrackEExerciseRepository()->find($trackExerciseId);

        if (null === $trackExercise) {
            return $returnUrls ? [] : '';
        }

        $attempt = $trackExercise->getAttemptByQuestionId($questionId);

        if (null === $attempt) {
            return $returnUrls ? [] : '';
        }

        $urls = [];

        // Loop over AttemptFile and use their ResourceNode to get public URLs
        foreach ($attempt->getAttemptFiles() as $attemptFile) {
            $resourceNode = $attemptFile->getResourceNode();
            $url = self::getPublicUrlForResourceNode($resourceNode);

            if (!empty($url)) {
                $urls[] = $url;
            }
        }

        if ($returnUrls) {
            return $urls;
        }

        // Legacy simple HTML (used by some views)
        $html = '';

        foreach ($urls as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            $name = $path ? basename($path) : $url;

            $html .= Display::url($name, $url, ['target' => '_blank']).'<br />';
        }

        return $html;
    }

    public static function getNotificationSettings(): array
    {
        return [
            2 => get_lang('Paranoid: E-mail teacher when a student starts an exercise'),
            1 => get_lang('Aware: E-mail teacher when a student ends an exercise'), // default
            3 => get_lang('Relaxed open: E-mail teacher when a student ends an exercise, only if an open question is answered'),
            4 => get_lang('Relaxed audio: E-mail teacher when a student ends an exercise, only if an oral question is answered'),
        ];
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
        $additionalActions = api_get_setting('exercise.exercise_additional_teacher_modify_actions', true) ?: [];
        $actions = [];

        if (is_array($additionalActions)) {
            foreach ($additionalActions as $additionalAction) {
                $actions[] = call_user_func(
                    $additionalAction,
                    $exerciseId,
                    $iconSize
                );
            }
        }

        return implode(PHP_EOL, $actions);
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return int
     */
    public static function countAnsweredQuestionsByUserAfterTime(DateTime $time, $userId, $courseId, $sessionId)
    {
        $em = Database::getManager();

        if (empty($sessionId)) {
            $sessionId = null;
        }

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

        $midnightTime = ChamiloHelper::getServerMidnightTime();

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
     * @return bool
     */
    public static function isQuizEmbeddable(CQuiz $exercise)
    {
        $em = Database::getManager();

        if (ONE_PER_PAGE != $exercise->getType() ||
            in_array($exercise->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])
        ) {
            return false;
        }

        $countAll = $em
            ->createQuery('SELECT COUNT(qq)
                FROM ChamiloCourseBundle:CQuizQuestion qq
                INNER JOIN ChamiloCourseBundle:CQuizRelQuestion qrq
                   WITH qq.iid = qrq.question
                WHERE qrq.quiz = :id'
            )
            ->setParameter('id', $exercise->getIid())
            ->getSingleScalarResult();

        $countOfAllowed = $em
            ->createQuery('SELECT COUNT(qq)
                FROM ChamiloCourseBundle:CQuizQuestion qq
                INNER JOIN ChamiloCourseBundle:CQuizRelQuestion qrq
                   WITH qq.iid = qrq.question
                WHERE qrq.quiz = :id AND qq.type IN (:types)'
            )
            ->setParameters(
                [
                    'id' => $exercise->getIid(),
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
     * @param float $totalScore
     * @param float $totalWeight
     * @param int   $studentId
     * @param int   $courseId
     * @param int   $sessionId
     *
     * @return string
     */
    public static function generateAndShowCertificateBlock(
        $totalScore,
        $totalWeight,
        Exercise $objExercise,
        $studentId,
        $courseId,
        $sessionId = 0
    ) {
        if (('true' !== api_get_setting('exercise.quiz_generate_certificate_ending')) ||
            !self::isSuccessExerciseResult($totalScore, $totalWeight, $objExercise->selectPassPercentage())
        ) {
            return '';
        }

        $repo = Container::getGradeBookCategoryRepository();
        /** @var GradebookCategory $category */
        $category = $repo->findOneBy(
            ['course' => $courseId, 'session' => $sessionId]
        );

        if (null === $category) {
            return '';
        }

        /*$category = Category::load(null, null, $courseCode, null, null, $sessionId, 'ORDER By id');
        if (empty($category)) {
            return '';
        }*/
        $categoryId = $category->getId();
        /*$link = LinkFactory::load(
            null,
            null,
            $objExercise->getId(),
            null,
            $courseCode,
            $categoryId
        );*/

        if (empty($category->getLinks()->count())) {
            return '';
        }

        $resourceDeletedMessage = Category::show_message_resource_delete($courseId);
        if (!empty($resourceDeletedMessage) || api_is_allowed_to_edit() || api_is_excluded_user_type()) {
            return '';
        }

        $certificate = Category::generateUserCertificate($category, $studentId);
        if (!is_array($certificate)) {
            return '';
        }

        return Category::getDownloadCertificateBlock($certificate);
    }

    /**
     * @param int $exerciseId
     */
    public static function getExerciseTitleById($exerciseId)
    {
        $em = Database::getManager();

        return $em
            ->createQuery('SELECT cq.title
                FROM ChamiloCourseBundle:CQuiz cq
                WHERE cq.iid = :iid'
            )
            ->setParameter('iid', $exerciseId)
            ->getSingleScalarResult();
    }

    /**
     * @param int $exeId      ID from track_e_exercises
     * @param int $userId     User ID
     * @param int $exerciseId Exercise ID
     * @param int $courseId   Optional. Coure ID.
     *
     * @return TrackEExercise|null
     */
    public static function recalculateResult($exeId, $userId, $exerciseId, $courseId = 0)
    {
        if (empty($userId) || empty($exerciseId)) {
            return null;
        }

        $em = Database::getManager();
        /** @var TrackEExercise $trackedExercise */
        $trackedExercise = $em->getRepository(TrackEExercise::class)->find($exeId);

        if (empty($trackedExercise)) {
            return null;
        }

        if ($trackedExercise->getUser()->getId() != $userId ||
            $trackedExercise->getQuiz()?->getIid() != $exerciseId
        ) {
            return null;
        }

        $questionList = $trackedExercise->getDataTracking();

        if (empty($questionList)) {
            return null;
        }

        $questionList = explode(',', $questionList);

        $exercise = new Exercise($courseId);
        $courseInfo = $courseId ? api_get_course_info_by_id($courseId) : [];

        if (false === $exercise->read($exerciseId)) {
            return null;
        }

        $totalScore = 0;
        $totalWeight = 0;

        $pluginEvaluation = QuestionOptionsEvaluationPlugin::create();

        $formula = 'true' === $pluginEvaluation->get(QuestionOptionsEvaluationPlugin::SETTING_ENABLE)
            ? $pluginEvaluation->getFormulaForExercise($exerciseId)
            : 0;

        if (empty($formula)) {
            foreach ($questionList as $questionId) {
                $question = Question::read($questionId, $courseInfo);

                if (false === $question) {
                    continue;
                }

                $totalWeight += $question->selectWeighting();

                // We're inside *one* question. Go through each possible answer for this question
                $result = $exercise->manage_answer(
                    $exeId,
                    $questionId,
                    [],
                    'exercise_result',
                    [],
                    false,
                    true,
                    false,
                    $exercise->selectPropagateNeg(),
                    [],
                    [],
                    true
                );

                //  Adding the new score.
                $totalScore += $result['score'];
            }
        } else {
            $totalScore = $pluginEvaluation->getResultWithFormula($exeId, $formula);
            $totalWeight = $pluginEvaluation->getMaxScore();
        }

        $trackedExercise
            ->setScore($totalScore)
            ->setMaxScore($totalWeight);

        $em->persist($trackedExercise);
        $em->flush();
        $lpItemId = $trackedExercise->getOrigLpItemId();
        $lpId = $trackedExercise->getOrigLpId();
        $lpItemViewId = $trackedExercise->getOrigLpItemViewId();
        if ($lpId && $lpItemId && $lpItemViewId) {
            $lpItem = $em->getRepository(CLpItem::class)->find($lpItemId);
            if ($lpItem && 'quiz' === $lpItem->getItemType()) {
                $lpItemView = $em->getRepository(CLpItemView::class)->find($lpItemViewId);
                if ($lpItemView) {
                    $lpItemView->setScore($totalScore);
                    $em->persist($lpItemView);
                    $em->flush();
                }
            }
        }

        return $trackedExercise;
    }

    public static function getTotalQuestionAnswered($courseId, $exerciseId, $questionId, $onlyStudents = false): int
    {
        $courseId = (int) $courseId;
        $exerciseId = (int) $exerciseId;
        $questionId = (int) $questionId;

        $attemptTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $trackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseUserJoin = "";
        $studentsWhere = "";
        if ($onlyStudents) {
            $courseUserJoin = "
            INNER JOIN $courseUser cu
            ON cu.c_id = te.c_id AND cu.user_id = exe_user_id";
            $studentsWhere = " AND relation_type <> 2 AND cu.status = ".STUDENT;
        }

        $sql = "SELECT count(distinct (te.exe_id)) total
            FROM $attemptTable t
            INNER JOIN $trackTable te
            ON (t.exe_id = te.exe_id)
            $courseUserJoin
            WHERE
                te.c_id = $courseId AND
                exe_exo_id = $exerciseId AND
                t.question_id = $questionId AND
                te.status != 'incomplete'
                $studentsWhere
        ";
        $queryTotal = Database::query($sql);
        $totalRow = Database::fetch_assoc($queryTotal);
        $total = 0;
        if ($totalRow) {
            $total = (int) $totalRow['total'];
        }

        return $total;
    }

    public static function getWrongQuestionResults($courseId, $exerciseId, $sessionId = 0, $limit = 10)
    {
        $courseId = (int) $courseId;
        $exerciseId = (int) $exerciseId;
        $limit = (int) $limit;

        $questionTable = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $attemptTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $trackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sessionCondition = '';
        if (!empty($sessionId)) {
            $sessionCondition = api_get_session_condition($sessionId, true, false, 'te.session_id');
        }

        $sql = "SELECT q.question, question_id, count(q.iid) count
                FROM $attemptTable t
                INNER JOIN $questionTable q
                ON (q.iid = t.question_id)
                INNER JOIN $trackTable te
                ON (t.exe_id = te.exe_id)
                WHERE
                    te.c_id = $courseId AND
                    t.marks != q.ponderation AND
                    exe_exo_id = $exerciseId AND
                    status != 'incomplete'
                    $sessionCondition
                GROUP BY q.iid
                ORDER BY count DESC
                LIMIT $limit
        ";

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    public static function getExerciseResultsCount($type, $courseId, $exerciseId, $sessionId = 0)
    {
        $courseId = (int) $courseId;
        $exerciseId = (int) $exerciseId;

        $trackTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sessionCondition = '';
        if (!empty($sessionId)) {
            $sessionCondition = api_get_session_condition($sessionId, true, false, 'te.session_id');
        }

        $selectCount = 'count(DISTINCT te.exe_id)';
        $scoreCondition = '';
        switch ($type) {
            case 'correct_student':
                $selectCount = 'count(DISTINCT te.exe_user_id)';
                $scoreCondition = ' AND score = max_score ';
                break;
            case 'wrong_student':
                $selectCount = 'count(DISTINCT te.exe_user_id)';
                $scoreCondition = ' AND score != max_score ';
                break;
            case 'correct':
                $scoreCondition = ' AND score = max_score ';
                break;
            case 'wrong':
                $scoreCondition = ' AND score != max_score ';
                break;
        }

        $sql = "SELECT $selectCount count
                FROM $trackTable te
                WHERE
                    c_id = $courseId AND
                    exe_exo_id = $exerciseId AND
                    status != 'incomplete'
                    $scoreCondition
                    $sessionCondition
        ";
        $result = Database::query($sql);
        $totalRow = Database::fetch_assoc($result);
        $total = 0;
        if ($totalRow) {
            $total = (int) $totalRow['count'];
        }

        return $total;
    }

    public static function parseContent($content, $stats, Exercise $exercise, $trackInfo, $currentUserId = 0)
    {
        $wrongAnswersCount = $stats['failed_answers_count'];
        $attemptDate = substr($trackInfo['exe_date'], 0, 10);
        $exerciseId = $exercise->iId;
        $resultsStudentUrl = api_get_path(WEB_CODE_PATH).
            'exercise/result.php?id='.$exerciseId.'&'.api_get_cidreq();
        $resultsTeacherUrl = api_get_path(WEB_CODE_PATH).
            'exercise/exercise_show.php?action=edit&id='.$exerciseId.'&'.api_get_cidreq();

        $content = str_replace(
            [
                '((exercise_error_count))',
                '((all_answers_html))',
                '((all_answers_teacher_html))',
                '((exercise_title))',
                '((exercise_attempt_date))',
                '((link_to_test_result_page_student))',
                '((link_to_test_result_page_teacher))',
            ],
            [
                $wrongAnswersCount,
                $stats['all_answers_html'],
                $stats['all_answers_teacher_html'],
                $exercise->get_formated_title(),
                $attemptDate,
                $resultsStudentUrl,
                $resultsTeacherUrl,
            ],
            $content
        );

        $currentUserId = empty($currentUserId) ? api_get_user_id() : (int) $currentUserId;

        $content = AnnouncementManager::parseContent(
            $currentUserId,
            $content,
            api_get_course_id(),
            api_get_session_id()
        );

        return $content;
    }

    public static function sendNotification(
        $currentUserId,
        $objExercise,
        $exercise_stat_info,
        $courseInfo,
        $attemptCountToSend,
        $stats,
        $statsTeacher
    ) {
        $notifications = api_get_configuration_value('exercise_finished_notification_settings');
        if (empty($notifications)) {
            return false;
        }

        $studentId = $exercise_stat_info['exe_user_id'];
        $exerciseExtraFieldValue = new ExtraFieldValue('exercise');
        $wrongAnswersCount = $stats['failed_answers_count'];
        $exercisePassed = $stats['exercise_passed'];
        $countPendingQuestions = $stats['count_pending_questions'];
        $stats['all_answers_teacher_html'] = $statsTeacher['all_answers_html'];

        // If there are no pending questions (Open questions).
        if (0 === $countPendingQuestions) {
            /*$extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                $objExercise->iId,
                'signature_mandatory'
            );

            if ($extraFieldData && isset($extraFieldData['value']) && 1 === (int) $extraFieldData['value']) {
                if (ExerciseSignaturePlugin::exerciseHasSignatureActivated($objExercise)) {
                    $signature = ExerciseSignaturePlugin::getSignature($studentId, $exercise_stat_info);
                    if (false !== $signature) {
                        //return false;
                    }
                }
            }*/

            // Notifications.
            $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                $objExercise->iId,
                'notifications'
            );
            $exerciseNotification = '';
            if ($extraFieldData && isset($extraFieldData['value'])) {
                $exerciseNotification = $extraFieldData['value'];
            }

            $subject = sprintf(get_lang('Failure on attempt %s at %s'), $attemptCountToSend, $courseInfo['title']);
            if ($exercisePassed) {
                $subject = sprintf(get_lang('Validation of exercise at %s'), $courseInfo['title']);
            }

            if ($exercisePassed) {
                $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                    $objExercise->iId,
                    'MailSuccess'
                );
            } else {
                $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                    $objExercise->iId,
                    'MailAttempt'.$attemptCountToSend
                );
            }

            // Blocking exercise.
            $blockPercentageExtra = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                $objExercise->iId,
                'blocking_percentage'
            );
            $blockPercentage = false;
            if ($blockPercentageExtra && isset($blockPercentageExtra['value']) && $blockPercentageExtra['value']) {
                $blockPercentage = $blockPercentageExtra['value'];
            }
            if ($blockPercentage) {
                $passBlock = $stats['total_percentage'] > $blockPercentage;
                if (false === $passBlock) {
                    $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                        $objExercise->iId,
                        'MailIsBlockByPercentage'
                    );
                }
            }

            $extraFieldValueUser = new ExtraFieldValue('user');

            if ($extraFieldData && isset($extraFieldData['value'])) {
                $content = $extraFieldData['value'];
                $content = self::parseContent($content, $stats, $objExercise, $exercise_stat_info, $studentId);
                //if (false === $exercisePassed) {
                if (0 !== $wrongAnswersCount) {
                    $content .= $stats['failed_answers_html'];
                }

                $sendMessage = true;
                if (!empty($exerciseNotification)) {
                    foreach ($notifications as $name => $notificationList) {
                        if ($exerciseNotification !== $name) {
                            continue;
                        }
                        foreach ($notificationList as $notificationName => $attemptData) {
                            if ('student_check' === $notificationName) {
                                $sendMsgIfInList = isset($attemptData['send_notification_if_user_in_extra_field']) ? $attemptData['send_notification_if_user_in_extra_field'] : '';
                                if (!empty($sendMsgIfInList)) {
                                    foreach ($sendMsgIfInList as $skipVariable => $skipValues) {
                                        $userExtraFieldValue = $extraFieldValueUser->get_values_by_handler_and_field_variable(
                                            $studentId,
                                            $skipVariable
                                        );

                                        if (empty($userExtraFieldValue)) {
                                            $sendMessage = false;
                                            break;
                                        } else {
                                            $sendMessage = false;
                                            if (isset($userExtraFieldValue['value']) &&
                                                in_array($userExtraFieldValue['value'], $skipValues)
                                            ) {
                                                $sendMessage = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                }

                // Send to student.
                if ($sendMessage) {
                    MessageManager::send_message($currentUserId, $subject, $content);
                }
            }

            if (!empty($exerciseNotification)) {
                foreach ($notifications as $name => $notificationList) {
                    if ($exerciseNotification !== $name) {
                        continue;
                    }
                    foreach ($notificationList as $attemptData) {
                        $skipNotification = false;
                        $skipNotificationList = isset($attemptData['send_notification_if_user_in_extra_field']) ? $attemptData['send_notification_if_user_in_extra_field'] : [];
                        if (!empty($skipNotificationList)) {
                            foreach ($skipNotificationList as $skipVariable => $skipValues) {
                                $userExtraFieldValue = $extraFieldValueUser->get_values_by_handler_and_field_variable(
                                    $studentId,
                                    $skipVariable
                                );

                                if (empty($userExtraFieldValue)) {
                                    $skipNotification = true;
                                    break;
                                } else {
                                    if (isset($userExtraFieldValue['value'])) {
                                        if (!in_array($userExtraFieldValue['value'], $skipValues)) {
                                            $skipNotification = true;
                                            break;
                                        }
                                    } else {
                                        $skipNotification = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($skipNotification) {
                            continue;
                        }

                        $email = isset($attemptData['email']) ? $attemptData['email'] : '';
                        $emailList = explode(',', $email);
                        if (empty($emailList)) {
                            continue;
                        }
                        $attempts = isset($attemptData['attempts']) ? $attemptData['attempts'] : [];
                        foreach ($attempts as $attempt) {
                            $sendMessage = false;
                            if (isset($attempt['attempt']) && $attemptCountToSend !== (int) $attempt['attempt']) {
                                continue;
                            }

                            if (!isset($attempt['status'])) {
                                continue;
                            }

                            if ($blockPercentage && isset($attempt['is_block_by_percentage'])) {
                                if ($attempt['is_block_by_percentage']) {
                                    if ($passBlock) {
                                        continue;
                                    }
                                } else {
                                    if (false === $passBlock) {
                                        continue;
                                    }
                                }
                            }

                            switch ($attempt['status']) {
                                case 'passed':
                                    if ($exercisePassed) {
                                        $sendMessage = true;
                                    }
                                    break;
                                case 'failed':
                                    if (false === $exercisePassed) {
                                        $sendMessage = true;
                                    }
                                    break;
                                case 'all':
                                    $sendMessage = true;
                                    break;
                            }

                            if ($sendMessage) {
                                $attachments = [];
                                if (isset($attempt['add_pdf']) && $attempt['add_pdf']) {
                                    // Get pdf content
                                    $pdfExtraData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                                        $objExercise->iId,
                                        $attempt['add_pdf']
                                    );

                                    if ($pdfExtraData && isset($pdfExtraData['value'])) {
                                        $pdfContent = self::parseContent(
                                            $pdfExtraData['value'],
                                            $stats,
                                            $objExercise,
                                            $exercise_stat_info,
                                            $studentId
                                        );

                                        @$pdf = new PDF();
                                        $filename = get_lang('Test');
                                        $pdfPath = @$pdf->content_to_pdf(
                                            "<html><body>$pdfContent</body></html>",
                                            null,
                                            $filename,
                                            api_get_course_id(),
                                            'F',
                                            false,
                                            null,
                                            false,
                                            true
                                        );
                                        $attachments[] = ['filename' => $filename, 'path' => $pdfPath];
                                    }
                                }

                                $content = isset($attempt['content_default']) ? $attempt['content_default'] : '';
                                if (isset($attempt['content'])) {
                                    $extraFieldData = $exerciseExtraFieldValue->get_values_by_handler_and_field_variable(
                                        $objExercise->iId,
                                        $attempt['content']
                                    );
                                    if ($extraFieldData && isset($extraFieldData['value']) && !empty($extraFieldData['value'])) {
                                        $content = $extraFieldData['value'];
                                    }
                                }

                                if (!empty($content)) {
                                    $content = self::parseContent(
                                        $content,
                                        $stats,
                                        $objExercise,
                                        $exercise_stat_info,
                                        $studentId
                                    );
                                    foreach ($emailList as $email) {
                                        if (empty($email)) {
                                            continue;
                                        }
                                        api_mail_html(
                                            null,
                                            $email,
                                            $subject,
                                            $content,
                                            null,
                                            null,
                                            [],
                                            $attachments
                                        );
                                    }
                                }

                                if (isset($attempt['post_actions'])) {
                                    foreach ($attempt['post_actions'] as $action => $params) {
                                        switch ($action) {
                                            case 'subscribe_student_to_courses':
                                                foreach ($params as $code) {
                                                    $courseInfo = api_get_course_info($code);
                                                    CourseManager::subscribeUser(
                                                        $currentUserId,
                                                        $courseInfo['real_id']
                                                    );
                                                    break;
                                                }
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Delete an exercise attempt.
     *
     * Log the exe_id deleted with the exe_user_id related.
     *
     * @param int $exeId
     */
    public static function deleteExerciseAttempt($exeId)
    {
        $exeId = (int) $exeId;

        $trackExerciseInfo = self::get_exercise_track_exercise_info($exeId);

        if (empty($trackExerciseInfo)) {
            return;
        }

        $tblTrackExercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $tblTrackAttempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        Database::query("DELETE FROM $tblTrackAttempt WHERE exe_id = $exeId");
        Database::query("DELETE FROM $tblTrackExercises WHERE exe_id = $exeId");

        Event::addEvent(
            LOG_EXERCISE_ATTEMPT_DELETE,
            LOG_EXERCISE_ATTEMPT,
            $exeId,
            api_get_utc_datetime()
        );
        Event::addEvent(
            LOG_EXERCISE_ATTEMPT_DELETE,
            LOG_EXERCISE_AND_USER_ID,
            $exeId.'-'.$trackExerciseInfo['exe_user_id'],
            api_get_utc_datetime()
        );
    }

    public static function scorePassed($score, $total)
    {
        $compareResult = bccomp($score, $total, 3);
        $scorePassed = 1 === $compareResult || 0 === $compareResult;
        if (false === $scorePassed) {
            $epsilon = 0.00001;
            if (abs($score - $total) < $epsilon) {
                $scorePassed = true;
            }
        }

        return $scorePassed;
    }

    /**
     * Returns the HTML for a specific exercise attempt, ready for PDF generation.
     */
    public static function getAttemptPdfHtml(int $exeId, int $courseId, int $sessionId): string
    {
        $_GET = [
            'id'           => $exeId,
            'action'       => 'export',
            'export_type'  => 'all_results',
            'cid'          => $courseId,
            'sid'          => $sessionId,
            'gid'          => 0,
            'gradebook'    => 0,
            'origin'       => '',
        ];
        $_REQUEST = $_GET + $_REQUEST;

        ob_start();
        include __DIR__ . '/../../exercise/exercise_show.php';
        return ob_get_clean();
    }

    /**
     * Generates and saves a PDF for a single exercise attempt
     */
    public static function saveFileExerciseResultPdfDirect(
        int    $exeId,
        int    $courseId,
        int    $sessionId,
        string $exportFolderPath
    ): void {
        // Retrieve the HTML for this attempt and convert it to PDF
        $html = self::getAttemptPdfHtml($exeId, $courseId, $sessionId);

        // Determine filename and path based on user information
        $track   = self::get_exercise_track_exercise_info($exeId);
        $userId  = $track['exe_user_id'] ?? 0;
        $user    = api_get_user_info($userId);
        $pdfName = api_replace_dangerous_char(
            ($user['firstname'] ?? 'user') . '_' .
            ($user['lastname']  ?? 'unknown') .
            '_attemptId' . $exeId . '.pdf'
        );
        $filePath = rtrim($exportFolderPath, '/') . '/' . $pdfName;

        if (file_exists($filePath)) {
            return;
        }

        // Ensure the directory exists
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Use Chamilo's PDF class to generate and save the file
        $params = [
            'filename'    => $pdfName,
            'course_code' => api_get_course_id(),
        ];
        $pdf = new PDF('A4', 'P', $params);
        $pdf->html_to_pdf_with_template(
            $html,
            true,
            false,
            true,
            [],
            'F',
            $filePath
        );
    }

    /**
     * Exports all results of an exercise to a ZIP archive by generating PDFs on disk and then sending the ZIP to the browser.
     */
    public static function exportExerciseAllResultsZip(
        int   $sessionId,
        int   $courseId,
        int   $exerciseId,
        array $filterDates = [],
        string $mainPath    = ''
    ) {
        $em = Container::getEntityManager();

        /** @var CourseEntity|null $course */
        $course = $em->getRepository(CourseEntity::class)->find($courseId);
        /** @var CQuiz|null $quiz */
        $quiz   = $em->getRepository(CQuiz::class)->findOneBy(['iid' => $exerciseId]);
        $session = null;

        if (!$course) {
            Display::addFlash(Display::return_message(get_lang('Course not found'), 'warning', false));
            return false;
        }
        if (!$quiz) {
            Display::addFlash(Display::return_message(get_lang('Test not found or not visible'), 'warning', false));
            return false;
        }
        if ($sessionId > 0) {
            $session = $em->getRepository(SessionEntity::class)->find($sessionId);
            if (!$session) {
                Display::addFlash(Display::return_message(get_lang('Session not found'), 'warning', false));
                return false;
            }
        }

        // Fetch exe_ids with Doctrine, accepting NULL/0 session when $sessionId == 0
        $exeIds = self::findAttemptExeIdsForExport($course, $quiz, $session, $filterDates);

        // Optional: hard fallback with native SQL to catch legacy session_id=0 rows if needed
        if (empty($exeIds) && $sessionId === 0) {
            $exeIds = self::findAttemptExeIdsFallbackSql($courseId, $exerciseId, $filterDates);
        }

        if (empty($exeIds)) {
            Display::addFlash(
                Display::return_message(
                    get_lang('No result found for export in this test.'),
                    'warning',
                    false
                )
            );
            return false;
        }

        // Prepare a temporary folder for the PDFs
        $exportName       = 'S' . (int)($sessionId) . '-C' . (int)($courseId) . '-T' . (int)($exerciseId);
        $baseDir          = api_get_path(SYS_ARCHIVE_PATH);
        $exportFolderPath = $baseDir . 'pdfexport-' . $exportName;
        if (is_dir($exportFolderPath)) {
            rmdirr($exportFolderPath);
        }
        mkdir($exportFolderPath, 0755, true);

        // Generate a PDF for each attempt
        foreach ($exeIds as $exeId) {
            self::saveFileExerciseResultPdfDirect(
                (int)$exeId,
                (int)$courseId,
                (int)$sessionId,
                $exportFolderPath
            );
        }

        // Create the ZIP archive containing all generated PDFs
        $zipFilePath = $baseDir . 'pdfexport-' . $exportName . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($exportFolderPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();
                $relativePath = substr($filePath, strlen($exportFolderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        rmdirr($exportFolderPath);

        // Send the ZIP file to the browser or move it to mainPath
        if (!empty($mainPath)) {
            @rename($zipFilePath, $mainPath . '/pdfexport-' . $exportName . '.zip');
            return true;
        }

        session_write_close();
        while (ob_get_level()) {
            @ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="pdfexport-' . $exportName . '.zip"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zipFilePath));

        readfile($zipFilePath);
        @unlink($zipFilePath);
        exit;
    }

    /**
     * Return exe_ids for export using Doctrine (handles NULL/0 sessions safely).
     */
    private static function findAttemptExeIdsForExport(
        CourseEntity $course,
        CQuiz $quiz,
        ?SessionEntity $session,
        array $filterDates
    ): array {
        $em = Container::getEntityManager();

        $qb = $em->createQueryBuilder()
            ->select('te.exeId AS exeId')
            ->from(TrackEExercise::class, 'te')
            ->where('te.course = :course')
            ->andWhere('te.quiz = :quiz')
            ->setParameter('course', $course)
            ->setParameter('quiz', $quiz);

        // Session filter:
        if ($session) {
            $qb->andWhere('te.session = :session')->setParameter('session', $session);
        } else {
            // Accept both NULL and legacy "0" values
            // IDENTITY() extracts the FK raw value to match 0 if present
            $qb->andWhere('(te.session IS NULL OR IDENTITY(te.session) = 0)');
        }

        // Date filters on exeDate
        if (!empty($filterDates['start_date'])) {
            $qb->andWhere('te.exeDate >= :start')
                ->setParameter('start', new DateTime($filterDates['start_date']));
        }
        if (!empty($filterDates['end_date'])) {
            $qb->andWhere('te.exeDate <= :end')
                ->setParameter('end', new DateTime($filterDates['end_date']));
        }

        $qb->orderBy('te.exeDate', 'DESC')->setMaxResults(5000);

        $rows = $qb->getQuery()->getScalarResult();
        $exeIds = array_map(static fn($r) => (int)$r['exeId'], $rows);


        return array_values(array_unique($exeIds));
    }

    /**
     * Fallback with native SQL for very legacy rows (session_id=0 and column names).
     */
    private static function findAttemptExeIdsFallbackSql(
        int $courseId,
        int $quizIid,
        array $filterDates
    ): array {
        $conn = Container::getEntityManager()->getConnection();

        $sql = 'SELECT te.exe_id
            FROM track_e_exercises te
            WHERE te.c_id = :cid
              AND te.exe_exo_id = :iid
              AND (te.session_id IS NULL OR te.session_id = 0)';

        $params = ['cid' => $courseId, 'iid' => $quizIid];
        $types  = [];

        if (!empty($filterDates['start_date'])) {
            $sql .= ' AND te.exe_date >= :start';
            $params['start'] = $filterDates['start_date'];
        }
        if (!empty($filterDates['end_date'])) {
            $sql .= ' AND te.exe_date <= :end';
            $params['end'] = $filterDates['end_date'];
        }

        $sql .= ' ORDER BY te.exe_date DESC LIMIT 5000';

        $rows = $conn->fetchAllAssociative($sql, $params, $types);
        $exeIds = array_map(static fn($r) => (int)$r['exe_id'], $rows);

        return $exeIds;
    }

    /**
     * Calculates the overall score for Combination-type questions.
     */
    public static function getUserQuestionScoreGlobal(
        int   $answerType,
        array $listCorrectAnswers,
        int   $exeId,
        int   $questionId,
        float $questionWeighting,
        array $choice = [],
        int $nbrAnswers = 0
    ): float
    {
        $nbrCorrect = 0;
        $nbrOptions = 0;
        $choice = is_array($choice) ? $choice : [];
        switch ($answerType) {
            case FILL_IN_BLANKS_COMBINATION:
                if (!empty($listCorrectAnswers)) {
                    if (!empty($listCorrectAnswers['student_score']) && is_array($listCorrectAnswers['student_score'])) {
                        foreach ($listCorrectAnswers['student_score'] as $val) {
                            if ((int) $val === 1) {
                                $nbrCorrect++;
                            }
                        }
                    }
                    if (!empty($listCorrectAnswers['words_count'])) {
                        $nbrOptions = (int) $listCorrectAnswers['words_count'];
                    } elseif (!empty($listCorrectAnswers['words']) && is_array($listCorrectAnswers['words'])) {
                        $nbrOptions = count($listCorrectAnswers['words']);
                    }
                }
                break;

            case HOT_SPOT_COMBINATION:
                if (!empty($listCorrectAnswers) && is_array($listCorrectAnswers) && is_array($choice)) {
                    foreach ($listCorrectAnswers as $idx => $val) {
                        if (isset($choice[$idx]) && (int) $choice[$idx] === 1) {
                            $nbrCorrect++;
                        }
                    }
                } else {
                    $TBL_TRACK_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
                    $exeIdEsc = Database::escape_string($exeId);
                    $qIdEsc   = Database::escape_string($questionId);
                    $sql = "SELECT COUNT(hotspot_id) AS ct
                        FROM $TBL_TRACK_HOTSPOT
                        WHERE hotspot_exe_id = '$exeIdEsc'
                          AND hotspot_question_id = '$qIdEsc'
                          AND hotspot_correct = 1";
                    $result = Database::query($sql);
                    $nbrCorrect = (int) Database::result($result, 0, 0);
                }
                $nbrOptions = (int) $nbrAnswers;
                break;

            case MATCHING_COMBINATION:
            case MATCHING_DRAGGABLE_COMBINATION:
                if (isset($listCorrectAnswers['form_values'])) {
                    if (isset($listCorrectAnswers['form_values']['correct'])) {
                        $nbrCorrect = count($listCorrectAnswers['form_values']['correct']);
                        $nbrOptions = (int) $listCorrectAnswers['form_values']['count_options'];
                    }
                } else {
                    if (isset($listCorrectAnswers['from_database'])) {
                        if (isset($listCorrectAnswers['from_database']['correct'])) {
                            $nbrCorrect = count($listCorrectAnswers['from_database']['correct']);
                            $nbrOptions = (int) $listCorrectAnswers['from_database']['count_options'];
                        }
                    }
                }
                break;
        }

        $questionScore = 0.0;
        if ($nbrOptions > 0 && $nbrCorrect === $nbrOptions) {
            $questionScore = (float) $questionWeighting;
        }

        return $questionScore;
    }

    /**
     * Build a public URL for a ResourceNode file used in exercises.
     * Returns an empty string when the node is null or when the underlying
     * file/route cannot be resolved (we do not want to break the exercise view).
     *
     * @param ResourceNode|null $resourceNode
     * @param array                                        $extraParams
     *
     * @return string
     */
    public static function getPublicUrlForResourceNode(?ResourceNode $resourceNode): string
    {
        if (null === $resourceNode) {
            return '';
        }

        try {
            /** @var ResourceNodeRepository $resourceNodeRepo */
            $resourceNodeRepo = Container::getResourceNodeRepository();
            $resourceType = $resourceNode->getResourceType();
            $tool         = $resourceType?->getTool();
            $url = $resourceNodeRepo->getResourceFileUrl($resourceNode);

            return $url;
        } catch (Throwable $e) {
            error_log(sprintf(
                '[ORAL_FILE_AUDIO][node=%s] Exception in getPublicUrlForResourceNode(): %s (%s) at %s:%d',
                $resourceNode?->getId() ?? 'null',
                $e->getMessage(),
                get_class($e),
                $e->getFile(),
                $e->getLine()
            ));

            return '';
        }
    }

    /**
     * Normalize the attempt question list:
     * - Media questions are containers and must NOT be counted as real questions.
     * - When random questions are enabled ($objExercise->random > 0),
     *   ensure we have exactly N answerable questions by topping up from the exercise pool.
     *
     * @param Exercise $objExercise
     * @param int[]    $ids
     *
     * @return int[]
     */
    public static function normalizeAttemptQuestionList(Exercise $objExercise, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        $randomCount = isset($objExercise->random) ? (int) $objExercise->random : 0;

        // Remove media questions from the list (and page breaks only in random mode).
        $normalized = [];
        foreach ($ids as $qid) {
            $q = Question::read((int) $qid);
            if (!$q) {
                continue;
            }

            // Media questions are not answerable.
            if ((int) $q->type === MEDIA_QUESTION) {
                continue;
            }

            // Random selection applies to answerable questions, not structural breaks.
            if ($randomCount > 0 && (int) $q->type === PAGE_BREAK) {
                continue;
            }

            $normalized[] = (int) $qid;
        }

        if ($randomCount <= 0) {
            return $normalized;
        }

        // Trim if we somehow have too many.
        if (count($normalized) > $randomCount) {
            return array_slice($normalized, 0, $randomCount);
        }

        // Top up if we have too few after removing media questions.
        if (count($normalized) < $randomCount) {
            $pool = [];
            foreach ((array) $objExercise->getQuestionOrderedList() as $qid) {
                $qid = (int) $qid;
                $q = Question::read($qid);
                if (!$q) {
                    continue;
                }

                if ((int) $q->type === MEDIA_QUESTION || (int) $q->type === PAGE_BREAK) {
                    continue;
                }

                $pool[] = $qid;
            }

            // Remove already selected.
            $pool = array_values(array_diff($pool, $normalized));

            if (!empty($pool)) {
                shuffle($pool);
            }

            $needed = $randomCount - count($normalized);
            if ($needed > 0) {
                $normalized = array_merge($normalized, array_slice($pool, 0, $needed));
            }
        }

        return $normalized;
    }

    /**
     * Persist corrected data_tracking back to DB so the attempt stays stable.
     *
     * @param int   $exeId
     * @param int[] $questionList
     * @param array $exerciseStatInfo
     */
    public static function updateAttemptDataTrackingIfNeeded(int $exeId, array $questionList, array &$exerciseStatInfo): void
    {
        if ($exeId <= 0) {
            return;
        }

        $newTracking = implode(',', array_map('intval', $questionList));
        $oldTracking = isset($exerciseStatInfo['data_tracking']) ? (string) $exerciseStatInfo['data_tracking'] : '';

        if ($newTracking === $oldTracking) {
            return;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $sql = "UPDATE $table
            SET data_tracking = '".Database::escape_string($newTracking)."'
            WHERE exe_id = ".(int) $exeId;
        Database::query($sql);

        $exerciseStatInfo['data_tracking'] = $newTracking;
    }
}
