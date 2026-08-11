<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CForumThread;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait ForumGradebookGuardTrait
{
    private function isForumThreadLockedByGradebook(
        GradebookLinkManager $gradebookLinkManager,
        Course $course,
        ?Session $session,
        CForumThread $thread,
    ): bool {
        if (null === $thread->getIid()) {
            return false;
        }

        return $gradebookLinkManager->isResourceLockedForTypes(
            $course,
            $session,
            [
                GradebookLinkResourceResolver::LINK_FORUM_THREAD,
                GradebookLinkResourceResolver::LINK_FORUM_PARTICIPATION,
            ],
            (int) $thread->getIid(),
        );
    }

    private function assertForumThreadNotLockedByGradebook(
        GradebookLinkManager $gradebookLinkManager,
        Course $course,
        ?Session $session,
        CForumThread $thread,
    ): void {
        if ($this->isForumThreadLockedByGradebook($gradebookLinkManager, $course, $session, $thread)) {
            throw new AccessDeniedHttpException($this->getForumThreadGradebookLockedMessage());
        }
    }

    private function getForumThreadGradebookLockedMessage(): string
    {
        return 'This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.';
    }
}
