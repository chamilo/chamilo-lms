<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class CourseSession.
 *
 * @author Jhon Hinojosa <jhon.hinojosa@beeznest.com>
 *
 * @package chamilo.backup
 */
class CourseSession extends Resource
{
    // The title session
    public $title;

    /**
     * Create a new Session.
     *
     * @param int    $id
     * @param string $title
     */
    public function __construct($id, $title)
    {
        parent::__construct($id, RESOURCE_SESSION_COURSE);
        $this->title = $title;
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
