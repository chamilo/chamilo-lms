<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * A course description.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class CourseDescription extends Resource
{
    /**
     * The title.
     */
    public $title;

    /**
     * The content.
     */
    public $content;

    /**
     * The description type.
     */
    public $description_type;

    /**
     * Create a new course description.
     *
     * @param int    $id
     * @param string $title
     * @param string $content
     * @param string $descriptionType
     */
    public function __construct($id, $title, $content, $descriptionType)
    {
        parent::__construct($id, RESOURCE_COURSEDESCRIPTION);
        $this->title = $title;
        $this->content = $content;
        $this->description_type = $descriptionType;
    }

    /**
     * Show this Event.
     */
    public function show()
    {
        parent::show();
        echo $this->title;
    }
}
