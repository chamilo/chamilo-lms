<?php

/* For licensing terms, see /license.txt */
/**
 * Exercises backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * An Quiz
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class Quiz extends Resource
{
    /**
     * Create a new Quiz
     * @param string $title
     * @param string $description
     * @param int $random
     * @param int $type
     * @param int $active
     */
    public $obj; //question

    function Quiz($obj) {
        $this->obj = $obj;
        $this->obj->quiz_type = $this->obj->type;
        parent::Resource($obj->id, RESOURCE_QUIZ);
    }

    /**
     * Add a question to this Quiz
     */
    function add_question($id, $question_order) {
        $this->obj->question_ids[] = $id;
        $this->obj->question_orders[] = $question_order;
    }

    /**
     * Show this question
     */
    function show() {
        parent::show();
        echo $this->obj->title;
    }
}