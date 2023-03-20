<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;

class Announcement extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'announcement';
    }

    public function getIcon(): string
    {
        return 'mdi-bullhorn';
    }

    public function getLink(): string
    {
        return '/main/announcements/announcements.php';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'announcements' => CAnnouncement::class,
            'announcements_attachments' => CAnnouncementAttachment::class,
        ];
    }
}
