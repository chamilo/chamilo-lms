<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CAttendance;

class Attendance extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'attendance';
    }

    public function getLink(): string
    {
        return '/main/attendance/index.php';
    }

    public function getIcon(): string
    {
        return 'mdi-av-timer';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'attendances' => CAttendance::class,
        ];
    }
}
