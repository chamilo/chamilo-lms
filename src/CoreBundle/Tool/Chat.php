<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CChatConversation;

class Chat extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'chat';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getIcon(): string
    {
        return 'mdi-';
    }

    public function getLink(): string
    {
        return '/resources/chat';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'conversations' => CChatConversation::class,
        ];
    }
}
