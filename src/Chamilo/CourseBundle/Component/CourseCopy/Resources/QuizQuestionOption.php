<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class QuizQuestionOption.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class QuizQuestionOption extends Resource
{
    public $obj; //question_option

    /**
     * QuizQuestionOption constructor.
     *
     * @param mixed $obj
     */
    public function __construct($obj)
    {
        parent::__construct($obj->iid, RESOURCE_QUIZQUESTION);
        $this->obj = $obj;
    }
}
