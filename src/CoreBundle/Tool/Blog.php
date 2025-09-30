<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CBlog;

class Blog extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'blog';
    }

    public function getIcon(): string
    {
        return 'mdi-notebook-outline';
    }

    public function getLink(): string
    {
        return '/resources/blogs/:nodeId/';
    }

    public function getCategory(): string
    {
        return 'admin';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'blog' => CBlog::class,
        ];
    }
}
