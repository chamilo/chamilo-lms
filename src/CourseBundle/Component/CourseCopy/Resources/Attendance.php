<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Attendance backup script.
 */
class Attendance extends Resource
{
    public $params = [];
    public $attendance_calendar = [];

    /**
     * Create a new Thematic.
     *
     * @param array $params
     */
    public function __construct($params)
    {
        parent::__construct($params['id'], RESOURCE_ATTENDANCE);
        $this->params = $params;
    }

    public function show(): void
    {
        parent::show();
        echo $this->params['name'];
    }

    public function add_attendance_calendar($data): void
    {
        $this->attendance_calendar[] = $data;
    }
}
