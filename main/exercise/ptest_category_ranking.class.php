<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use ChamiloSession as Session;

/**
 * Class PtestCategoryRanking.
 *
 * This class allows to instantiate an object of type QUESTION_PT_TYPE_CATEGORY_RANKING
 * (Choose a word (or sentence) from a set of possible answers),
 * extending the class question
 *
 * @author Jose Angel Ruiz (NOSOLORED)
 *
 * @package chamilo.exercise
 */
class PtestCategoryRanking extends Question
{
    public $typePicture = 'mcua.png';
    public $explanationLangVar = 'PtestCategoryRanking';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = QUESTION_PT_TYPE_CATEGORY_RANKING;
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

        // Categories options select
        $category = new PTestCategory();
        $categoriesList = $category->getCategoryListInfo($obj_ex->selectId());
        $categoriesOptions = [null => get_lang('None')];
        foreach ($categoriesList as $categoryItem) {
            $categoriesOptions[$categoryItem->id] = (string) $categoryItem->name;
        }

        //this line defines how many questions by default appear when creating a choice question
        // The previous default value was 2. See task #1759.
        $nb_answers = isset($_POST['nb_answers']) ? (int) $_POST['nb_answers'] : count($categoriesList);
        $nb_answers += (isset($_POST['lessAnswers']) ? -1 : (isset($_POST['moreAnswers']) ? 1 : 0));

        $html = '<table class="table table-striped table-hover">
            <thead>
                <tr style="text-align: center;">
                    <th width="5%">'.get_lang('Number').'</th>
                    <th width="50%">'.get_lang('Answer').'</th>
                    <th width="45%">'.get_lang('PtestCategory').'</th>
                </tr>
            </thead>
            <tbody>';

        $form->addHeader(get_lang('Answers'));
        $form->addHtml($html);

        $defaults = [];
        if (!empty($this->id)) {
            $answer = new Answer($this->id);
            $answer->read();
            if ($answer->nbrAnswers > 0 && !$form->isSubmitted()) {
                $nb_answers = $answer->nbrAnswers;
            }
        }
        $form->addElement('hidden', 'nb_answers');

        //$temp_scenario = [];
        if ($nb_answers < 1) {
            $nb_answers = 1;
            echo Display::return_message(
                get_lang('YouHaveToCreateAtLeastOneAnswer')
            );
        }

        for ($i = 1; $i <= $nb_answers; $i++) {
            $form->addHtml('<tr>');
            if (isset($answer) && is_object($answer)) {
                $defaults['answer['.$i.']'] = isset($answer->answer[$i]) ? $answer->answer[$i] : '';
                $defaults['ptest_category['.$i.']'] = isset($answer->ptest_category[$i]) ? $answer->ptest_category[$i] : 0;
            }

            $renderer = $form->defaultRenderer();

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
                'ptest_category['.$i.']'
            );

            $answer_number = $form->addElement(
                'text',
                'counter['.$i.']',
                null,
                ' value = "'.$i.'"'
            );
            $answer_number->freeze();

            $form->addHtmlEditor('answer['.$i.']', null, null, false, $editor_config);

            $form->addRule(
                'answer['.$i.']',
                get_lang('ThisFieldIsRequired'),
                'required'
            );

            $form->addSelect(
                'ptest_category['.$i.']',
                null,
                $categoriesOptions
            );

            $form->addHtml('</tr>');
        }

        $form->addHtml('</tbody>');
        $form->addHtml('</table>');

        global $text;
        $buttonGroup = [];

        if ($obj_ex->edit_exercise_in_lp == true ||
            (empty($this->exerciseList) && empty($obj_ex->id))
        ) {
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

        if (!empty($this->id)) {
            $form->setDefaults($defaults);
        } else {
            if ($this->isContent == 1) {
                // Default sample content.
                $form->setDefaults($defaults);
            }
        }
        $form->setConstants(['nb_answers' => $nb_answers]);
    }

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        $objAnswer = new Answer($this->id);
        $nb_answers = $form->getSubmitValue('nb_answers');

        for ($i = 1; $i <= $nb_answers; $i++) {
            $answer = trim($form->getSubmitValue('answer['.$i.']'));
            $goodAnswer = false;
            $comment = '';
            $weighting = 0;
            $ptestCategory = (int) $form->getSubmitValue('ptest_category['.$i.']');
            $dest = '';

            $objAnswer->createAnswer(
                $answer,
                $goodAnswer,
                $comment,
                $weighting,
                $i,
                null,
                null,
                $dest,
                $ptestCategory
            );
        }

        // saves the answers into the data base
        $objAnswer->save();

        $this->save($exercise);
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        $header = parent::return_header($exercise, $counter); //, $score);
        $header .= '<table class="'.$this->question_table_class.'"><tr>';

        $header .= '<th style="width:1px;white-space:nowrap;">'.get_lang('Choice').'</th>';
        $header .= '<th>'.get_lang('Answer').'</th>';
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
        $question_id = intval($question_id);
        $score = floatval($score);
        $correct = intval($correct);
        $title = Database::escape_string($title);
        $comment = Database::escape_string($comment);
        // Get the max position.
        $sql = "SELECT max(position) as max_position
                FROM $tbl_quiz_answer
                WHERE
                    c_id = $course_id AND
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
            ->setDestination('0@@0@@0@@0');

        $em->persist($quizAnswer);
        $em->flush();

        $id = $quizAnswer->getIid();

        if ($id) {
            $quizAnswer
                ->setId($id);

            $em->merge($quizAnswer);
            $em->flush();
        }

        if ($correct) {
            $sql = "UPDATE $tbl_quiz_question
                    SET ponderation = (ponderation + $score)
                    WHERE c_id = $course_id AND id = ".$question_id;
            Database::query($sql);
        }
    }
}
