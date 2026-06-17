<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait ForumGradebookGuardTrait
{
    private function isForumThreadLockedByGradebook(
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        Security $security,
        Course $course,
        CForumThread $thread,
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if (!$this->isGradebookLockingEnabled($settingsManager)) {
            return false;
        }

        if (null === $thread->getIid()) {
            return false;
        }

        return null !== $entityManager->getRepository(GradebookLink::class)->findOneBy([
            'course' => $course,
            'type' => $this->getForumThreadGradebookLinkType(),
            'refId' => (int) $thread->getIid(),
            'locked' => 1,
        ]);
    }

    private function assertForumThreadNotLockedByGradebook(
        EntityManagerInterface $entityManager,
        SettingsManager $settingsManager,
        Security $security,
        Course $course,
        CForumThread $thread,
    ): void {
        if ($this->isForumThreadLockedByGradebook($entityManager, $settingsManager, $security, $course, $thread)) {
            throw new AccessDeniedHttpException($this->getForumThreadGradebookLockedMessage());
        }
    }

    private function getForumThreadGradebookLockedMessage(): string
    {
        return 'This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.';
    }

    private function isGradebookLockingEnabled(SettingsManager $settingsManager): bool
    {
        $value = $settingsManager->getSetting('gradebook.gradebook_locking_enabled', true);
        if (null === $value || '' === trim((string) $value)) {
            $value = $settingsManager->getSetting('gradebook_locking_enabled', true);
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function getForumThreadGradebookLinkType(): int
    {
        return 5;
    }
}
