<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Exercises questions backup script
 * Class QuizQuestion
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class QuizQuestion extends Resource
{
    /**
     * The question
     */
    public $question;

    /**
     * The description
     */
    public $description;

    /**
     * Ponderation
     */
    public $ponderation;

    /**
     * Type
     */
    public $quiz_type;

    /**
     * Position
     */
    public $position;

    /**
     * Level
     */
    public $level;

    /**
     * Answers
     */
    public $answers;

    /**
     * Picture
     */
    public $picture;
    public $extra;
    public $categories;
    public $parent_info;

    /**
     * * Create a new QuizQuestion
     * @param $id
     * @param $question
     * @param $description
     * @param $ponderation
     * @param $type
     * @param $position
     * @param $picture
     * @param $level
     * @param $extra
     * @param $parent_info
     * @param $categories
     */
    function QuizQuestion($id, $question, $description, $ponderation, $type, $position, $picture, $level, $extra, $parent_info, $categories)
    {
        parent::Resource($id, RESOURCE_QUIZQUESTION);

        $this->question = $question;
        $this->description = $description;
        $this->ponderation = $ponderation;
        $this->quiz_type = $type;
        $this->position = $position;
        $this->picture = $picture;
        $this->level = $level;
        $this->answers = array();
        $this->extra = $extra;
        $this->parent_info = $parent_info;
        $this->categories = $categories;
    }

    /**
     * Add an answer to this QuizQuestion
     */
    function add_answer($answer_id, $answer_text, $correct, $comment, $ponderation, $position, $hotspot_coordinates, $hotspot_type)
    {
        $answer = array();
        $answer['iid'] = $answer_id;
        $answer['answer'] = $answer_text;
        $answer['correct'] = $correct;
        $answer['comment'] = $comment;
        $answer['ponderation'] = $ponderation;
        $answer['position'] = $position;
        $answer['hotspot_coordinates'] = $hotspot_coordinates;
        $answer['hotspot_type'] = $hotspot_type;
        $this->answers[] = $answer;
    }

    /**
     * @param $option_obj
     */
    function add_option($option_obj)
    {
        $this->question_options[$option_obj->obj->id] = $option_obj;
    }

    /**
     * Show this question
     */
    function show()
    {
        parent::show();
        echo $this->question;
    }
}
