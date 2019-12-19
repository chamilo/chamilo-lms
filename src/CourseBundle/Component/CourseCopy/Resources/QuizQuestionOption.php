<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class QuizQuestionOption.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class QuizQuestionOption extends Resource
{
    public $obj; //question_option

    /**
     * QuizQuestionOption constructor.
     */
    public function __construct($obj)
    {
        parent::__construct($obj->id, RESOURCE_QUIZQUESTION);
        $this->obj = $obj;
    }
}
