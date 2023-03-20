<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\TicketMessageAttachment;

class User extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'user';
    }

    public function getNameToShow(): string
    {
        return 'Users';
    }

    public function getIcon(): string
    {
        return 'mdi-user';
    }

    public function getLink(): string
    {
        return '/';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'files' => PersonalFile::class,
            'message_attachments' => MessageAttachment::class,
            'ticket_message_attachments' => TicketMessageAttachment::class,
        ];
    }
}
