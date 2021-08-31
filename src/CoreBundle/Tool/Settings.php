<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Settings extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'course_setting';
    }

    public function getIcon(): string
    {
        return 'mdi-cog';
    }

    public function getLink(): string
    {
        return '/main/course_info/infocours.php';
    }

    public function getCategory(): string
    {
        return 'admin';
    }
}
