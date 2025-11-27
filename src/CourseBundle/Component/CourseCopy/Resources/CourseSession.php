<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class CourseSession.
 *
 * @author Jhon Hinojosa <jhon.hinojosa@beeznest.com>
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
    public function show(): void
    {
        parent::show();
        echo $this->title;
    }
}
