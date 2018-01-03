<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * An Quiz
 * Exercises backup script
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

    /**
     * Quiz constructor.
     * @param int $obj
     */
    public function __construct($obj)
    {
        $this->obj = $obj;
        $this->obj->quiz_type = $this->obj->type;
        parent::__construct($obj->id, RESOURCE_QUIZ);
    }

    /**
     * Add a question to this Quiz
     */
    public function add_question($id, $question_order)
    {
        $this->obj->question_ids[] = $id;
        $this->obj->question_orders[] = $question_order;
    }

    /**
     * Show this question
     */
    public function show()
    {
        parent::show();
        echo $this->obj->title;
    }
}
