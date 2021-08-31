<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CTool;

class CourseTool extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'course_tool';
    }

    public function getLink(): string
    {
        return '/resources/course_tool/links';
    }

    public function getIcon(): string
    {
        return 'mdi-file-link';
    }

    public function getCategory(): string
    {
        return 'admin';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'links' => CTool::class,
        ];
    }
}
