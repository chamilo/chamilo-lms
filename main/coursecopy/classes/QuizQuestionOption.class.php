<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Class QuizQuestionOption
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class QuizQuestionOption extends Coursecopy\Resource
{
    public $obj; //question_option

    /**
     * Create a new QuizQuestion
     * @param string $question
     * @param string $description
     * @param int $ponderation
     * @param int $type
     * @param int $position
     */
    public function __construct($obj)
    {
        parent::__construct($obj->id, RESOURCE_QUIZQUESTION);
        $this->obj = $obj;
    }
}
