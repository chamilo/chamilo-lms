<?php

/* For licensing terms, see /license.txt */
/**
 * Exercises questions backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * An QuizQuestion
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class QuizQuestionOption extends Resource
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
    function QuizQuestionOption($obj) {
        parent::Resource($obj->id, RESOURCE_QUIZQUESTION);
        $this->obj = $obj;
    }
}