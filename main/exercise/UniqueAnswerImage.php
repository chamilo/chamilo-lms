<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * UniqueAnswerImage
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class UniqueAnswerImage extends UniqueAnswer
{
    public static $typePicture = 'uaimg.png';
    public static $explanationLangVar = 'UniqueAnswerImage';

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
     * @inheritdoc
     * @throws Exception
     */
    public function createAnswersForm($form)
    {
        $objExercise = Session::read('objExercise');
        $editorConfig = array(
            'ToolbarSet' => 'TestFreeAnswer',
            'Width' => '100%',
            'Height' => '125'
        );

        //this line defines how many questions by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $numberAnswers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 4;
        $numberAnswers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $feedbackTitle = '';

        if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
            //Scenario
            $commentTitle = '<th>'.get_lang('Comment').'</th>';
            $feedbackTitle = '<th>'.get_lang('Scenario').'</th>';
        } else {
            $commentTitle = '<th >'.get_lang('Comment').'</th>';
        }

        $html = '<div class="alert alert-success" role="alert">'.get_lang('UniqueAnswerImagePreferredSize200x150').'</div>';
        $html .= '<table class="table table-striped table-hover">
            <thead>
                <tr style="text-align: center;">
                    <th width="10">' . get_lang('Number').'</th>
                    <th>' . get_lang('True').'</th>
                    <th>' . get_lang('Answer').'</th>
                        ' . $commentTitle.'
                        ' . $feedbackTitle.'
                    <th width="15">' . get_lang('Weighting').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('Answers'));
        $form->addHtml($html);

        $defaults = array();
        $correct = 0;

        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();

            if (count($answer->nbrAnswers) > 0 && !$form->isSubmitted()) {
                $numberAnswers = $answer->nbrAnswers;
            }
        }

        $form->addElement('hidden', 'nb_answers');

        //Feedback SELECT
        $questionList = $objExercise->selectQuestionList();
        $selectQuestion = array();
        $selectQuestion[0] = get_lang('SelectTargetQuestion');

        if (is_array($questionList)) {
            foreach ($questionList as $key => $questionid) {
                //To avoid warning messages
                if (!is_numeric($questionid)) {
                    continue;
                }

                $question = Question::read($questionid);
                $selectQuestion[$questionid] = 'Q'.$key.' :'.cut(
                    $question->selectTitle(), 20
                );
            }
        }

        $selectQuestion[-1] = get_lang('ExitTest');

        $list = new LearnpathList(api_get_user_id());
        $flatList = $list->get_flat_list();
        $selectLpId = array();
        $selectLpId[0] = get_lang('SelectTargetLP');

        foreach ($flatList as $id => $details) {
            $selectLpId[$id] = cut($details['lp_name'], 20);
        }

        $tempScenario = array();

        if ($numberAnswers < 1) {
            $numberAnswers = 1;
            echo Display::return_message(get_lang('YouHaveToCreateAtLeastOneAnswer'));
        }

        for ($i = 1; $i <= $numberAnswers; ++$i) {
            $form->addHtml('<tr>');
            if (isset($answer) && is_object($answer)) {
                if ($answer->correct[$i]) {
                    $correct = $i;
                }

                $defaults['answer['.$i.']'] = $answer->answer[$i];
                $defaults['comment['.$i.']'] = $answer->comment[$i];
                $defaults['weighting['.$i.']'] = float_format(
                    $answer->weighting[$i],
                    1
                );

                $itemList = explode('@@', $answer->destination[$i]);

                $try = $itemList[0];
                $lp = $itemList[1];
                $listDestination = $itemList[2];
                $url = $itemList[3];

                $try = 0;
                if ($try != 0) {
                    $tryResult = 1;
                }

                $urlResult = '';
                if ($url != 0) {
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

                $tempScenario['destination'.$i] = array('0');
                $tempScenario['lp'.$i] = array('0');
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
                '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}</td>',
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
            $form->addHtmlEditor('answer['.$i.']', null, null, true, $editorConfig);

            $form->addRule('answer['.$i.']', get_lang('ThisFieldIsRequired'), 'required');

            if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_DIRECT) {
                $form->addHtmlEditor(
                    'comment['.$i.']',
                    null,
                    null,
                    false,
                    $editorConfig
                );
                // Direct feedback
                // Adding extra feedback fields
                $group = array();
                $group['try'.$i] = $form->createElement('checkbox', 'try'.$i, null, get_lang('TryAgain'));
                $group['lp'.$i] = $form->createElement(
                    'select',
                    'lp'.$i,
                    get_lang('SeeTheory').': ',
                    $selectLpId
                );
                $group['destination'.$i] = $form->createElement(
                    'select',
                    'destination'.$i,
                    get_lang('GoToQuestion').': ',
                    $selectQuestion
                );
                $group['url'.$i] = $form->createElement(
                    'text', 'url'.$i,
                    get_lang('Other').': ',
                    array(
                        'class' => 'col-md-2',
                        'placeholder' => get_lang('Other')
                    )
                );
                $form->addGroup($group, 'scenario');

                $renderer->setElementTemplate(
                    '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}',
                    'scenario'
                );
            } else {
                $form->addHtmlEditor('comment['.$i.']', null, null, false, $editorConfig);
            }
            $form->addText('weighting['.$i.']', null, null, array('class' => "col-md-1", 'value' => '0'));
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody>');
        $form->addHtml('</table>');

        global $text;
        $buttonGroup = [];
        if ($objExercise->edit_exercise_in_lp == true) {
            //setting the save button here and not in the question class.php
            $buttonGroup[] = $form->addButtonDelete(get_lang('LessAnswer'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('PlusAnswer'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButtonSave($text, 'submitQuestion', true);
            $form->addGroup($buttonGroup);
        }

        // We check the first radio button to be sure a radio button will be check
        if ($correct == 0) {
            $correct = 1;
        }

        $defaults['correct'] = $correct;

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                // Default sample content.
                $form->setDefaults($defaults);
            } else {
                $form->setDefaults(array('correct' => 1));
            }
        }

        $form->setConstants(array('nb_answers' => $numberAnswers));
    }

    /**
     * @inheritdoc
     */
    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = $nbrGoodAnswers = 0;
        $correct = $form->getSubmitValue('correct');
        $objAnswer = new Answer($this->id);
        $numberAnswers = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $numberAnswers; $i++) {
            $answer = trim(str_replace(['<p>', '</p>'], '', $form->getSubmitValue('answer['.$i.']')));
            $comment = trim(str_replace(['<p>', '</p>'], '', $form->getSubmitValue('comment['.$i.']')));
            $weighting = trim($form->getSubmitValue('weighting['.$i.']'));

            $scenario = $form->getSubmitValue('scenario');

            //$listDestination = $form -> getSubmitValue('destination'.$i);
            //$destinationStr = $form -> getSubmitValue('destination'.$i);

            $try = $scenario['try'.$i];
            $lp = $scenario['lp'.$i];
            $destination = $scenario['destination'.$i];
            $url = trim($scenario['url'.$i]);

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
