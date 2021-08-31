<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CThematic;

class CourseProgress extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'course_progress';
    }

    public function getIcon(): string
    {
        return 'mdi-file-tree';
    }

    public function getLink(): string
    {
        return '/main/course_progress/index.php';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'thematics' => CThematic::class,
        ];
    }
}
