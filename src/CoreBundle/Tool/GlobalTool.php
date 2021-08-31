<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;

class GlobalTool extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'global';
    }

    public function getIcon(): string
    {
        return 'mdi-';
    }

    public function getLink(): string
    {
        return '/resources/chat';
    }

    public function getCategory(): string
    {
        return 'admin';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'urls' => AccessUrl::class,
            'courses' => Course::class,
            'users' => User::class,
        ];
    }
}
