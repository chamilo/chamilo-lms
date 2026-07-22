<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\LpAdvancedAccessHelper;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class LearningPathAccessChecker
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private LpAdvancedAccessHelper $advancedAccessHelper,
        private CLpRepository $learningPathRepository,
    ) {}

    public function isLearningPathVisibleOnCourseHome(
        CLp $learningPath,
        Course $course,
        ?Session $session,
        ?User $user,
        bool $showInvisibleLearningPaths,
    ): bool {
        if (!$user instanceof User) {
            return false;
        }

        return null === $this->getLearningPathAccessDenialReason(
            $learningPath,
            $course,
            $session,
            null,
            $user,
            allowInvisible: $showInvisibleLearningPaths,
            checkResourcePermission: !$showInvisibleLearningPaths,
        );
    }

    public function isCategoryVisibleOnCourseHome(
        CLpCategory $category,
        Course $course,
        ?Session $session,
        ?User $user,
        bool $showInvisibleLearningPaths,
    ): bool {
        if (!$user instanceof User) {
            return false;
        }

        $resourceNode = $category->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        if (!$showInvisibleLearningPaths && !$this->security->isGranted('VIEW', $resourceNode)) {
            return false;
        }

        $resourceLink = $this->getContextResourceLink($category, $course, $session, null);
        if (!$resourceLink instanceof ResourceLink) {
            return false;
        }

        return $showInvisibleLearningPaths
            || ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility();
    }

    public function getLearningPathAccessDenialReason(
        CLp $learningPath,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        bool $canManage = false,
        bool $allowInvisible = false,
        bool $checkResourcePermission = true,
    ): ?string {
        $resourceNode = $learningPath->getResourceNode();
        if (null === $resourceNode
            || ($checkResourcePermission && !$this->security->isGranted('VIEW', $resourceNode))
        ) {
            return 'The learning path is not available in this context.';
        }

        $resourceLink = $this->getContextResourceLink($learningPath, $course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            return 'The learning path is not linked to this context.';
        }

        if ($canManage) {
            return null;
        }

        if (!$allowInvisible && ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility()) {
            return 'The learning path is not visible.';
        }

        if (!$this->advancedAccessHelper->isAllowed($course, $learningPath, $session, $user)) {
            return 'The learning path is not available for this user.';
        }

        if (!$this->isCurrentlyAvailable($learningPath)) {
            return 'The learning path is not currently available.';
        }

        $category = $learningPath->getCategory();
        if ($category instanceof CLpCategory) {
            $categoryLink = $this->getContextResourceLink($category, $course, $session, $group);
            if (!$categoryLink instanceof ResourceLink) {
                return 'The learning path category is not visible.';
            }

            if (!$allowInvisible && ResourceLink::VISIBILITY_PUBLISHED !== $categoryLink->getVisibility()) {
                return 'The learning path category is not visible.';
            }
        }

        $prerequisiteId = $learningPath->getPrerequisite();
        if ($prerequisiteId <= 0) {
            return null;
        }

        $prerequisite = $this->learningPathRepository->find($prerequisiteId);
        if (!$prerequisite instanceof CLp) {
            return 'The learning path prerequisite is not available.';
        }

        $latestView = $this->findLatestView($prerequisite, $course, $session, $user);
        if (!$latestView instanceof CLpView || (int) $latestView->getProgress() < 100) {
            return 'The learning path prerequisite is not completed.';
        }

        return null;
    }

    private function getContextResourceLink(
        AbstractResource $resource,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?ResourceLink {
        $resourceNode = $resource->getResourceNode();
        if (null === $resourceNode) {
            return null;
        }

        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $groupId = (int) ($group?->getIid() ?? 0);
        $baseLink = null;

        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if (!$resourceLink instanceof ResourceLink
                || (int) ($resourceLink->getCourse()?->getId() ?? 0) !== $courseId
                || (int) ($resourceLink->getGroup()?->getIid() ?? 0) !== $groupId
                || null !== $resourceLink->getUserGroup()
                || null !== $resourceLink->getUser()
            ) {
                continue;
            }

            $resourceSessionId = (int) ($resourceLink->getSession()?->getId() ?? 0);
            if ($resourceSessionId === $sessionId) {
                return $resourceLink;
            }

            if ($sessionId > 0 && 0 === $resourceSessionId) {
                $baseLink = $resourceLink;
            }
        }

        return $baseLink;
    }

    private function isCurrentlyAvailable(CLp $learningPath): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $publishedOn = $learningPath->getPublishedOn();
        $expiredOn = $learningPath->getExpiredOn();

        if ($publishedOn instanceof DateTimeInterface && $publishedOn > $now) {
            return false;
        }

        return !($expiredOn instanceof DateTimeInterface) || $expiredOn >= $now;
    }

    private function findLatestView(
        CLp $learningPath,
        Course $course,
        ?Session $session,
        User $user,
    ): ?CLpView {
        /** @var CLpView|null $view */
        $view = $this->entityManager->getRepository(CLpView::class)->findOneBy(
            [
                'lp' => $learningPath,
                'course' => $course,
                'session' => $session,
                'user' => $user,
            ],
            [
                'viewCount' => 'DESC',
                'iid' => 'DESC',
            ],
        );

        return $view;
    }
}
