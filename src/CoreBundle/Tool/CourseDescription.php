<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CCourseDescription;

class CourseDescription extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'course_description';
    }

    public function getLink(): string
    {
        return '/main/course_description/index.php';
    }

    public function getIcon(): string
    {
        return 'mdi-apple-safari';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'course_descriptions' => CCourseDescription::class,
        ];
    }
}
