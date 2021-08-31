<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;

class Forum extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'forum';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getLink(): string
    {
        return '/main/forum/index.php';
    }

    public function getIcon(): string
    {
        return 'mdi-comment-quote';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'forums' => CForum::class,
            'forum_attachments' => CForumAttachment::class,
            'forum_categories' => CForumCategory::class,
            'forum_posts' => CForumPost::class,
            'forum_threads' => CForumThread::class,
        ];
    }
}
