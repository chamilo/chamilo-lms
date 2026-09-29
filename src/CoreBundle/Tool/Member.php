<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

class Member extends AbstractTool implements ToolInterface
{
    public function getTitle(): string
    {
        return 'member';
    }

    public function getIcon(): string
    {
        return 'mdi-account';
    }

    public function getLink(): string
    {
        return '/resources/course-users/:nodeId/';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }
}
