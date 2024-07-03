<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * UniqueAnswerImage.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class UniqueAnswerImage extends UniqueAnswer
{
    public $typePicture = 'uaimg.png';
    public $explanationLangVar = 'UniqueAnswerImage';

    /**
     * UniqueAnswerImage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = UNIQUE_ANSWER_IMAGE;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function createAnswersForm($form)
    {
        $objExercise = Session::read('objExercise');
        $editorConfig = [
            'ToolbarSet' => 'TestProposedAnswer',
            'Width' => '100%',
            'Height' => '125',
        ];

        //this line defines how many questions by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $numberAnswers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 4;
        $numberAnswers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $feedbackTitle = '';
        switch ($objExercise->getFeedbackType()) {
            case EXERCISE_FEEDBACK_TYPE_DIRECT:
                // Scenario
                $commentTitle = '<th width="20%">'.get_lang('Comment').'</th>';
                $feedbackTitle = '<th width="20%">'.get_lang('Scenario').'</th>';
                break;
            case EXERCISE_FEEDBACK_TYPE_POPUP:
                $commentTitle = '<th width="20%">'.get_lang('Comment').'</th>';
                break;
            default:
                $commentTitle = '<th width="40%">'.get_lang('Comment').'</th>';
                break;
        }

        $html = '<div class="alert alert-success" role="alert">'.
                get_lang('UniqueAnswerImagePreferredSize200x150').'</div>';

        $zoomOptions = api_get_configuration_value('quiz_image_zoom');
        if (isset($zoomOptions['options'])) {
            $finderFolder = api_get_path(WEB_PATH).'vendor/studio-42/elfinder/';
            $html .= '<!-- elFinder CSS (REQUIRED) -->';
            $html .= '<link rel="stylesheet" type="text/css" media="screen"
                href="'.$finderFolder.'css/elfinder.full.css">';
            $html .= '<link rel="stylesheet" type="text/css" media="screen" href="'.$finderFolder.'css/theme.css">';
            $html .= '<!-- elFinder JS (REQUIRED) -->';
            $html .= '<script type="text/javascript" src="'.$finderFolder.'js/elfinder.full.js"></script>';
            $html .= '<!-- elFinder translation (OPTIONAL) -->';
            $language = 'en';
            $platformLanguage = api_get_interface_language();
            $iso = api_get_language_isocode($platformLanguage);
            $filePart = "vendor/studio-42/elfinder/js/i18n/elfinder.$iso.js";
            $file = api_get_path(SYS_PATH).$filePart;
            $includeFile = '';
            if (file_exists($file)) {
                $includeFile = '<script type="text/javascript" src="'.api_get_path(WEB_PATH).$filePart.'"></script>';
                $language = $iso;
            }
            $html .= $includeFile;

            $html .= '<script type="text/javascript" charset="utf-8">
            $(function() {
                $(".add_img_link").on("click", function(e){
                    e.preventDefault();
                    e.stopPropagation();

                    var name = $(this).prop("name");
                    var id = parseInt(name.match(/[0-9]+/));

                    $([document.documentElement, document.body]).animate({
                        scrollTop: $("#elfinder").offset().top
                    }, 1000);

                    var elf = $("#elfinder").elfinder({
                        url : "'.api_get_path(WEB_LIBRARY_PATH).'elfinder/connectorAction.php?'.api_get_cidreq().'",
                        getFileCallback: function(file) {
                            var filePath = file; //file contains the relative url.
                            var imageZoom = filePath.url;
                            var iname = "answer["+id+"]";

                            CKEDITOR.instances[iname].insertHtml(\'
                                <img
                                    id="zoom_picture"
                                    class="zoom_picture"
                                    src="\'+imageZoom+\'"
                                    data-zoom-image="\'+imageZoom+\'"
                                    width="200px"
                                    height="150px"
                                />\');

                            $("#elfinder").elfinder("destroy"); //close the window after image is selected
                        },
                        startPathHash: "l2_Lw", // Sets the course driver as default
                        resizable: false,
                        lang: "'.$language.'"
                    }).elfinder("instance"+id);
                });
            });
            </script>';
            $html .= '<div id="elfinder"></div>';
        }

        $html .= '<table class="table table-striped table-hover">
            <thead>
                <tr style="text-align: center;">
                    <th width="10">'.get_lang('Number').'</th>
                    <th>'.get_lang('True').'</th>
                    <th>'.get_lang('Answer').'</th>
                        '.$commentTitle.'
                        '.$feedbackTitle.'
                    <th width="15">'.get_lang('Weighting').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('Answers'));
        $form->addHtml($html);

        $defaults = [];
        $correct = 0;

        if (!empty($this->iid)) {
            $answer = new Answer($this->iid);
            $answer->read();

            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $numberAnswers = $answer->nbrAnswers;
            }
        }

        $form->addElement('hidden', 'nb_answers');

        //Feedback SELECT
        $questionList = $objExercise->selectQuestionList();
        $selectQuestion = [];
        $selectQuestion[0] = get_lang('SelectTargetQuestion');

        if (is_array($questionList)) {
            foreach ($questionList as $key => $questionid) {
                //To avoid warning messages
                if (!is_numeric($questionid)) {
                    continue;
                }

                $question = Question::read($questionid);
                $questionTitle = strip_tags($question->selectTitle());
                $selectQuestion[$questionid] = "Q$key: $questionTitle";
            }
        }

        $selectQuestion[-1] = get_lang('ExitTest');

        $list = new LearnpathList(api_get_user_id());
        $flatList = $list->get_flat_list();
        $selectLpId = [];
        $selectLpId[0] = get_lang('SelectTargetLP');

        foreach ($flatList as $id => $details) {
            $selectLpId[$id] = cut($details['lp_name'], 20);
        }

        $tempScenario = [];
        if ($numberAnswers < 1) {
            $numberAnswers = 1;
            echo Display::return_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $numberAnswers; $i++) {
            $form->addHtml('<tr>');
            if (isset($answer) && is_object($answer)) {
                if (isset($answer->correct[$i]) && $answer->correct[$i]) {
                    $correct = $i;
                }

                $defaults['answer['.$i.']'] = $answer->answer[$i] ?? '';
                $defaults['comment['.$i.']'] = $answer->comment[$i] ?? '';
                $defaults['weighting['.$i.']'] = isset($answer->weighting[$i]) ? float_format($answer->weighting[$i], 1) : 0;

                $itemList = [];
                if (isset($answer->destination[$i])) {
                    $itemList = explode('@@', $answer->destination[$i]);
                }

                $try = $itemList[0] ?? '';
                $lp = $itemList[1] ?? '';
                $listDestination = $itemList[2] ?? '';
                $url = $itemList[3] ?? '';

                $tryResult = 0;
                if (0 != $try) {
                    $tryResult = 1;
                }

                $urlResult = '';
                if (0 != $url) {
                    $urlResult = $url;
                }

                $tempScenario['url'.$i] = $urlResult;
                $tempScenario['try'.$i] = $tryResult;
                $tempScenario['lp'.$i] = $lp;
                $tempScenario['destination'.$i] = $listDestination;
            } else {
                $defaults['answer[1]'] = get_lang('DefaultUniqueAnswer1');
                $defaults['weighting[1]'] = 10;
                $defaults['answer[2]'] = get_lang('DefaultUniqueAnswer2');
                $defaults['weighting[2]'] = 0;

                $tempScenario['destination'.$i] = ['0'];
                $tempScenario['lp'.$i] = ['0'];
            }

            $defaults['scenario'] = $tempScenario;
            $renderer = $form->defaultRenderer();
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'correct'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'counter['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}'.
                    (isset($zoomOptions['options']) ?
                    '<br><div class="form-group ">
                        <label for="question_admin_form_btn_add_img['.$i.']" class="col-sm-2 control-label"></label>
                        <div class="col-sm-8">
                            <button class="add_img_link btn btn-info btn-sm"
                                name="btn_add_img['.$i.']"
                                type="submit"
                                id="question_admin_form_btn_add_img['.$i.']">
                                <em class="fa fa-plus"></em> '.get_lang('AddImageWithZoom').'
                            </button>
                        </div>
                        <div class="col-sm-2"></div>
                    </div>' : '').'</td>',
                'answer['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'comment['.$i.']'
            );
            $renderer->setElementTemplate(
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
                'weighting['.$i.']'
            );

            $answerNumber = $form->addElement('text', 'counter['.$i.']', null, ' value = "'.$i.'"');
            $answerNumber->freeze();

            $form->addElement('radio', 'correct', null, null, $i, 'class="checkbox"');
            $form->addHtmlEditor('answer['.$i.']', null, null, false, $editorConfig);

            $form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

            switch ($objExercise->getFeedbackType()) {
                case EXERCISE_FEEDBACK_TYPE_DIRECT:
                    $this->setDirectOptions($i, $form, $renderer, $selectLpId, $selectQuestion);
                    break;
                case EXERCISE_FEEDBACK_TYPE_POPUP:
                default:
                    $form->addHtmlEditor('comment['.$i.']', null, null, false, $editorConfig);
                    break;
            }

            $form->addText('weighting['.$i.']', null, null, ['class' => 'col-md-1', 'value' => '0']);
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody>');
        $form->addHtml('</table>');

        global $text;
        $buttonGroup = [];
        if ($objExercise->edit_exercise_in_lp == true ||
            (empty($this->exerciseList) && empty($objExercise->iid))
        ) {
            //setting the save button here and not in the question class.php
            $buttonGroup[] = $form->addButtonDelete(get_lang('LessAnswer'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('PlusAnswer'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButtonSave($text, 'submitQuestion', true);
            $form->addGroup($buttonGroup);
        }

        // We check the first radio button to be sure a radio button will be check
        if (0 == $correct) {
            $correct = 1;
        }

        $defaults['correct'] = $correct;

        if (!empty($this->iid)) {
            $form->setDefaults($defaults);
        } else {
            if (1 == $this->isContent) {
                // Default sample content.
                $form->setDefaults($defaults);
            } else {
                $form->setDefaults(['correct' => 1]);
            }
        }

        $form->setConstants(['nb_answers' => $numberAnswers]);
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = $nbrGoodAnswers = 0;
        $correct = $form->getSubmitValue('correct');
        $objAnswer = new Answer($this->iid);
        $numberAnswers = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $numberAnswers; $i++) {
            $answer = trim(str_replace(['<p>', '</p>'], '', $form->getSubmitValue('answer['.$i.']')));
            $comment = trim(str_replace(['<p>', '</p>'], '', $form->getSubmitValue('comment['.$i.']')));
            $weighting = trim($form->getSubmitValue('weighting['.$i.']'));

            $scenario = $form->getSubmitValue('scenario');

            $try = null;
            $lp = null;
            $destination = null;
            $url = null;
            //$listDestination = $form -> getSubmitValue('destination'.$i);
            //$destinationStr = $form -> getSubmitValue('destination'.$i);
            if (!empty($scenario)) {
                $try = $scenario['try'.$i];
                $lp = $scenario['lp'.$i];
                $destination = $scenario['destination'.$i];
                $url = trim($scenario['url'.$i]);
            }

            /*
              How we are going to parse the destination value

              here we parse the destination value which is a string
              1@@3@@2;4;4;@@http://www.chamilo.org

              where: try_again@@lp_id@@selected_questions@@url

              try_again = is 1 || 0
              lp_id = id of a learning path (0 if dont select)
              selected_questions= ids of questions
              url= an url

              $destinationStr='';
              foreach ($listDestination as $destination_id)
              {
              $destinationStr.=$destination_id.';';
              } */
            $goodAnswer = $correct == $i ? true : false;
            if ($goodAnswer) {
                $nbrGoodAnswers++;
                $weighting = abs($weighting);

                if ($weighting > 0) {
                    $questionWeighting += $weighting;
                }
            }

            if (empty($try)) {
                $try = 0;
            }

            if (empty($lp)) {
                $lp = 0;
            }

            if (empty($destination)) {
                $destination = 0;
            }

            if ($url == '') {
                $url = 0;
            }

            //1@@1;2;@@2;4;4;@@http://www.chamilo.org
            $dest = $try.'@@'.$lp.'@@'.$destination.'@@'.$url;

            $objAnswer->createAnswer(
                $answer,
                $goodAnswer,
                $comment,
                $weighting,
                $i,
                null,
                null,
                $dest
            );
        }

        // saves the answers into the data base
        $objAnswer->save();

        // sets the total weighting of the question
        $this->updateWeighting($questionWeighting);
        $this->save($exercise);
    }
}
