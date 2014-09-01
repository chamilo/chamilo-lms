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

    /**
     * @var int the question category if any, 0 by default
     */
    public $question_category;

    /**
     * Create a new QuizQuestion
     * @param string $question
     * @param string $description
     * @param int $ponderation
     * @param int $type
     * @param int $position
     */
    public function QuizQuestion(
        $id,
        $question,
        $description,
        $ponderation,
        $type,
        $position,
        $picture,
        $level,
        $extra,
        $question_category = 0
    ) {
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
        $this->question_category = $question_category;
    }

    /**
     * Add an answer to this QuizQuestion
     */
    public function add_answer(
        $answer_id,
        $answer_text,
        $correct,
        $comment,
        $ponderation,
        $position,
        $hotspot_coordinates,
        $hotspot_type
    ) {
        $answer = array();
        $answer['id'] = $answer_id;
        $answer['answer'] = $answer_text;
        $answer['correct'] = $correct;
        $answer['comment'] = $comment;
        $answer['ponderation'] = $ponderation;
        $answer['position'] = $position;
        $answer['hotspot_coordinates'] = $hotspot_coordinates;
        $answer['hotspot_type'] = $hotspot_type;
        $this->answers[] = $answer;
    }

    public function add_option($option_obj)
    {
        $this->question_options[$option_obj->obj->id] = $option_obj;
    }

    /**
     * Show this question
     */
    public function show()
    {
        parent::show();
        echo $this->question;
    }
}
