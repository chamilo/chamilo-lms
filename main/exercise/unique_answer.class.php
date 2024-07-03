<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use ChamiloSession as Session;

/**
 * Class UniqueAnswer.
 *
 * This class allows to instantiate an object of type UNIQUE_ANSWER
 * (MULTIPLE CHOICE, UNIQUE ANSWER),
 * extending the class question
 *
 * @author Eric Marguin
 * @author Julio Montoya
 */
class UniqueAnswer extends Question
{
    public $typePicture = 'mcua.png';
    public $explanationLangVar = 'UniqueSelect';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = UNIQUE_ANSWER;
        $this->isContent = $this->getIsContent();
    }

    /**
     * {@inheritdoc}
     */
    public function createAnswersForm($form)
    {
        // Getting the exercise list
        /** @var Exercise $obj_ex */
        $obj_ex = Session::read('objExercise');

        $editor_config = [
            'ToolbarSet' => 'TestProposedAnswer',
            'Width' => '100%',
            'Height' => '125',
        ];

        //this line defines how many questions by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $nb_answers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : 4;
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $feedback_title = '';
        switch ($obj_ex->getFeedbackType()) {
            case EXERCISE_FEEDBACK_TYPE_DIRECT:
                // Scenario
                $comment_title = '<th width="20%">'.get_lang('Comment').'</th>';
                $feedback_title = '<th width="20%">'.get_lang('Scenario').'</th>';
                break;
            case EXERCISE_FEEDBACK_TYPE_POPUP:
                $comment_title = '<th width="20%">'.get_lang('Comment').'</th>';
                break;
            default:
                $comment_title = '<th width="40%">'.get_lang('Comment').'</th>';
                break;
        }

        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr style="text-align: center;">
                    <th width="5%">'.get_lang('Number').'</th>
                    <th width="5%"> '.get_lang('True').'</th>
                    <th width="40%">'.get_lang('Answer').'</th>
                        '.$comment_title.'
                        '.$feedback_title.'
                    <th width="10%">'.get_lang('Weighting').'</th>
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
                $nb_answers = $answer->nbrAnswers;
            }
        }
        $form->addElement('hidden', 'nb_answers');

        $obj_ex->setQuestionList(true);
        $question_list = $obj_ex->getQuestionList();
        $select_question = [];
        $select_question[0] = get_lang('SelectTargetQuestion');
        if (is_array($question_list)) {
            foreach ($question_list as $key => $questionid) {
                //To avoid warning messages
                if (!is_numeric($questionid)) {
                    continue;
                }
                $question = Question::read($questionid);
                $questionTitle = strip_tags($question->selectTitle());
                $select_question[$questionid] = "Q$key: $questionTitle";
            }
        }
        $select_question[-1] = get_lang('ExitTest');

        $list = new LearnpathList(api_get_user_id());
        $flat_list = $list->get_flat_list();
        $select_lp_id = [];
        $select_lp_id[0] = get_lang('SelectTargetLP');

        foreach ($flat_list as $id => $details) {
            $select_lp_id[$id] = cut($details['lp_name'], 20);
        }

        $temp_scenario = [];
        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(
                get_lang('YouHaveToCreateAtLeastOneAnswer')
            );
        }

        for ($i = 1; $i <= $nb_answers; $i++) {
            $form->addHtml('<tr>');
            if (isset($answer) && is_object($answer)) {
                if (isset($answer->correct[$i]) && $answer->correct[$i]) {
                    $correct = $i;
                }
                $defaults['answer['.$i.']'] = isset($answer->answer[$i]) ? $answer->answer[$i] : '';
                $defaults['comment['.$i.']'] = isset($answer->comment[$i]) ? $answer->comment[$i] : '';
                $defaults['weighting['.$i.']'] = isset($answer->weighting[$i]) ? float_format($answer->weighting[$i], 1) : 0;
                $item_list = [];
                if (isset($answer->destination[$i])) {
                    $item_list = explode('@@', $answer->destination[$i]);
                }
                $try = isset($item_list[0]) ? $item_list[0] : '';
                $lp = isset($item_list[1]) ? $item_list[1] : '';
                $list_dest = isset($item_list[2]) ? $item_list[2] : '';
                $url = isset($item_list[3]) ? $item_list[3] : '';

                if ($try == 0) {
                    $try_result = 0;
                } else {
                    $try_result = 1;
                }
                if ($url == 0) {
                    $url_result = '';
                } else {
                    $url_result = $url;
                }

                $temp_scenario['url'.$i] = $url_result;
                $temp_scenario['try'.$i] = $try_result;
                $temp_scenario['lp'.$i] = $lp;
                $temp_scenario['destination'.$i] = $list_dest;
            } else {
                $defaults['answer[1]'] = get_lang('DefaultUniqueAnswer1');
                $defaults['weighting[1]'] = 10;
                $defaults['answer[2]'] = get_lang('DefaultUniqueAnswer2');
                $defaults['weighting[2]'] = 0;
                $temp_scenario['destination'.$i] = ['0'];
                $temp_scenario['lp'.$i] = ['0'];
            }
            $defaults['scenario'] = $temp_scenario;

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

            $answer_number = $form->addElement(
                'text',
                'counter['.$i.']',
                null,
                ' value = "'.$i.'"'
            );
            $answer_number->freeze();
            $form->addElement(
                'radio',
                'correct',
                null,
                null,
                $i,
                'class="checkbox"'
            );

            $form->addHtmlEditor('answer['.$i.']', null, null, false, $editor_config);

            $form->addRule(
                'answer['.$i.']',
                get_lang('ThisFieldIsRequired'),
                'required'
            );

            switch ($obj_ex->getFeedbackType()) {
                case EXERCISE_FEEDBACK_TYPE_DIRECT:
                    $this->setDirectOptions($i, $form, $renderer, $select_lp_id, $select_question);
                    break;
                case EXERCISE_FEEDBACK_TYPE_POPUP:
                default:
                    $form->addHtmlEditor('comment['.$i.']', null, null, false, $editor_config);
                    break;
            }
            $form->addText('weighting['.$i.']', null, null, ['value' => '0']);
            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody>');
        $form->addHtml('</table>');

        global $text;
        $buttonGroup = [];

        if (true === $obj_ex->edit_exercise_in_lp || (empty($this->exerciseList) && empty($obj_ex->iid))) {
            //setting the save button here and not in the question class.php
            $buttonGroup[] = $form->addButtonDelete(get_lang('LessAnswer'), 'lessAnswers', true);
            $buttonGroup[] = $form->addButtonCreate(get_lang('PlusAnswer'), 'moreAnswers', true);
            $buttonGroup[] = $form->addButton(
                'submitQuestion',
                $text,
                'check',
                'primary',
                'default',
                null,
                ['id' => 'submit-question'],
                true
            );
            $form->addGroup($buttonGroup);
        }

        // We check the first radio button to be sure a radio button will be check
        if ($correct == 0) {
            $correct = 1;
        }

        if (isset($_POST) && isset($_POST['correct'])) {
            $correct = (int) $_POST['correct'];
        }

        $defaults['correct'] = $correct;

        if (!empty($this->iid)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                // Default sample content.
                $form->setDefaults($defaults);
            } else {
                $correct = 1;
                if (isset($_POST) && isset($_POST['correct'])) {
                    $correct = (int) $_POST['correct'];
                }

                $form->setDefaults(['correct' => $correct]);
            }
        }
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    public function setDirectOptions($i, FormValidator $form, $renderer, $select_lp_id, $select_question)
    {
        $editor_config = [
            'ToolbarSet' => 'TestProposedAnswer',
            'Width' => '100%',
            'Height' => '125',
        ];

        $form->addHtmlEditor(
            'comment['.$i.']',
            null,
            null,
            false,
            $editor_config
        );
        // Direct feedback
        //Adding extra feedback fields
        $group = [];
        $group['try'.$i] = $form->createElement(
            'checkbox',
            'try'.$i,
            null,
            get_lang('TryAgain')
        );
        $group['lp'.$i] = $form->createElement(
            'select',
            'lp'.$i,
            get_lang('SeeTheory').': ',
            $select_lp_id
        );
        $group['destination'.$i] = $form->createElement(
            'select',
            'destination'.$i,
            get_lang('GoToQuestion').': ',
            $select_question
        );
        $group['url'.$i] = $form->createElement(
            'text',
            'url'.$i,
            get_lang('Other').': ',
            [
                'class' => 'col-md-2',
                'placeholder' => get_lang('Other'),
            ]
        );
        $form->addGroup($group, 'scenario');

        $renderer->setElementTemplate(
            '<td><!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --><br/>{element}',
            'scenario'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $questionWeighting = $nbrGoodAnswers = 0;
        $correct = $form->getSubmitValue('correct');
        $objAnswer = new Answer($this->iid);
        $nb_answers = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $comment = trim($form->getSubmitValue('comment['.$i.']'));
            $weighting = trim($form->getSubmitValue('weighting['.$i.']'));
            $scenario = $form->getSubmitValue('scenario');

            $try = null;
            $lp = null;
            $destination = null;
            $url = null;
            if (isset($scenario['try'.$i])) {
                $try = !empty($scenario['try'.$i]);
            }

            if (isset($scenario['lp'.$i])) {
                $lp = $scenario['lp'.$i];
            }

            if (isset($scenario['destination'.$i])) {
                $destination = $scenario['destination'.$i];
            }

            if (isset($scenario['url'.$i])) {
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

            $destination_str='';
            foreach ($list_destination as $destination_id)
            {
                $destination_str.=$destination_id.';';
            }*/

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

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';

        $header .= '<th>'.get_lang('Choice').'</th>';
        if ($exercise->showExpectedChoiceColumn()) {
            $header .= '<th>'.get_lang('ExpectedChoice').'</th>';
        }

        $header .= '<th>'.get_lang('Answer').'</th>';
        if ($exercise->showExpectedChoice()) {
            $header .= '<th>'.get_lang('Status').'</th>';
        }
        if (false === $exercise->hideComment) {
            $header .= '<th>'.get_lang('Comment').'</th>';
        }
        $header .= '</tr>';

        return $header;
    }

    /**
     * Saves one answer to the database.
     *
     * @param int    $id          The ID of the answer (has to be calculated for this course)
     * @param int    $question_id The question ID (to which the answer is attached)
     * @param string $title       The text of the answer
     * @param string $comment     The feedback for the answer
     * @param float  $score       The score you get when picking this answer
     * @param int    $correct     Whether this answer is considered *the* correct one (this is the unique answer type)
     */
    public function addAnswer(
        $id,
        $question_id,
        $title,
        $comment,
        $score = 0.0,
        $correct = 0
    ) {
        $em = Database::getManager();
        $tbl_quiz_answer = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $tbl_quiz_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $course_id = api_get_course_int_id();
        $question_id = (int) $question_id;
        $score = floatval($score);
        $correct = intval($correct);
        $title = Database::escape_string($title);
        $comment = Database::escape_string($comment);
        // Get the max position.
        $sql = "SELECT max(position) as max_position
                FROM $tbl_quiz_answer
                WHERE
                    question_id = $question_id";
        $rs_max = Database::query($sql);
        $row_max = Database::fetch_object($rs_max);
        $position = $row_max->max_position + 1;

        // Insert a new answer
        $quizAnswer = new CQuizAnswer();
        $quizAnswer
            ->setCId($course_id)
            ->setId($id)
            ->setQuestionId($question_id)
            ->setAnswer($title)
            ->setCorrect($correct)
            ->setComment($comment)
            ->setPonderation($score)
            ->setPosition($position)
            ->setDestination(CQuizAnswer::DEFAULT_DESTINATION);

        $em->persist($quizAnswer);
        $em->flush();

        $id = $quizAnswer->getId();

        if ($id) {
            $quizAnswer
                ->setId($id);

            $em->merge($quizAnswer);
            $em->flush();
        }

        if ($correct) {
            $sql = "UPDATE $tbl_quiz_question
                    SET ponderation = (ponderation + $score)
                    WHERE iid = $question_id";
            Database::query($sql);
        }
    }
}
