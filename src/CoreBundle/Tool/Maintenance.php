<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Maintenance extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'course_maintenance';
    }

    public function getIcon(): string
    {
        return 'mdi-wrench-cog';
    }

    public function getLink(): string
    {
        return '/resources/course_maintenance/:nodeId/';
    }

    public function getCategory(): string
    {
        return 'admin';
    }
}
