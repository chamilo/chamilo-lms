<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * A course description
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class CourseDescription extends Resource
{
    /**
     * The title
     */
    public $title;

    /**
     * The content
     */
    public $content;

    /**
     * The description type
     */
    public $description_type;

    /**
     * Create a new course description
     * @param int $id
     * @param string $title
     * @param string $content
     */
    public function __construct($id, $title, $content, $description_type)
    {
        parent::__construct($id, RESOURCE_COURSEDESCRIPTION);
        $this->title = $title;
        $this->content = $content;
        $this->description_type = $description_type;
    }

    /**
     * Show this Event
     */
    public function show()
    {
        parent::show();
        echo $this->title;
    }
}
