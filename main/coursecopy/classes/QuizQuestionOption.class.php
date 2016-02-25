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
     * QuizQuestionOption constructor.
     * @param mixed $obj
     */
    public function __construct($obj)
    {
        parent::__construct($obj->id, RESOURCE_QUIZQUESTION);
        $this->obj = $obj;
    }
}
