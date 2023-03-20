<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Maintenance extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'course_maintenance';
    }

    public function getIcon(): string
    {
        return 'mdi';
    }

    public function getLink(): string
    {
        return '/main/course_info/maintenance.php';
    }

    public function getCategory(): string
    {
        return 'admin';
    }
}
