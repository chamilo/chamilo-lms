<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<CForum>
 */
final class ForumCollectionStateProvider implements ProviderInterface
{
    use ForumCourseSettingHelperTrait;
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumRepository $forumRepository,
        private readonly CForumCategoryRepository $forumCategoryRepository,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly CidReqHelper $cidReqHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getForums($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getForumsFromCurrentRequest(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getForums($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getForums(Request $request): array
    {
        $this->assertForumMemberAccess($this->security, 'You are not allowed to access forums.');

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getGroup($this->entityManager, $this->cidReqHelper);
        $parentNode = $this->getParentNode($this->entityManager, $request);
        $showHidden = $this->isAllowedToEditHelper->check(coach: true);
        $user = $this->getCurrentUser();
        $displayGroupForums = $this->shouldDisplayGroupForumsInGeneralTool($this->cidReqHelper);

        $categoryIds = $this->getCategoryIdsBelowParent(
            $course,
            $session,
            $group,
            $parentNode,
            $showHidden,
        );

        // Forums are children of their category resource node. Query the whole
        // course context, then keep only direct forums and forums belonging to
        // categories displayed below the requested forum tool node.
        $queryBuilder = $this->forumRepository->getResourcesByCourse(
            $course,
            $session,
            $group,
            null,
            !$showHidden,
            true,
        );

        $forums = [];
        foreach ($queryBuilder->getQuery()->getResult() as $forum) {
            if (
                !$forum instanceof CForum
                || !$this->forumBelongsToRequestedParent($forum, $parentNode->getId(), $categoryIds)
                || !$this->canListForumWithCurrentSettings($forum, $this->cidReqHelper, $displayGroupForums)
            ) {
                continue;
            }

            $forums[] = $forum;
        }

        $canSubscribe = !$this->areForumPostNotificationsHidden($course);
        $subscribedForumIds = $canSubscribe ? $this->getSubscribedForumIds($course, $user, $forums) : [];
        $forumCounts = $this->getForumCounts($forums);

        return array_map(
            fn (CForum $forum): array => $this->normalizeForum(
                $forum,
                $course,
                $session,
                $canSubscribe,
                isset($subscribedForumIds[(int) $forum->getIid()]),
                $forumCounts[(int) $forum->getIid()] ?? ['threads' => 0, 'posts' => 0],
            ),
            $forums,
        );
    }

    /**
     * @return array<int, true>
     */
    private function getCategoryIdsBelowParent(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        ResourceNode $parentNode,
        bool $showHidden,
    ): array {
        $queryBuilder = $this->forumCategoryRepository->getResourcesByCourse(
            $course,
            $session,
            $group,
            $parentNode,
            !$showHidden,
            true,
        );

        $categoryIds = [];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if (!$category instanceof CForumCategory) {
                continue;
            }

            $categoryId = $category->getIid();
            if (null === $categoryId) {
                continue;
            }

            $categoryIds[$categoryId] = true;
        }

        return $categoryIds;
    }

    /**
     * @param array<int, true> $categoryIds
     */
    private function forumBelongsToRequestedParent(CForum $forum, ?int $parentNodeId, array $categoryIds): bool
    {
        $categoryId = $forum->getForumCategory()?->getIid();
        if (null !== $categoryId) {
            return isset($categoryIds[$categoryId]);
        }

        return $parentNodeId === $forum->getResourceNode()?->getParent()?->getId();
    }

    private function shouldDisplayGroupForumsInGeneralTool(CidReqHelper $cidReqHelper): bool
    {
        if ((int) ($cidReqHelper->getGroupId() ?? 0) > 0) {
            return true;
        }

        return 'false' !== (string) $this->settingsManager->getSetting('forum.display_groups_forum_in_general_tool', true);
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function areForumPostNotificationsHidden(Course $course): bool
    {
        return $this->isCourseSettingEnabled($this->entityManager, $course, 'hide_forum_notifications');
    }

    /**
     * @param array<int, CForum> $forums
     *
     * @return array<int, true>
     */
    private function getSubscribedForumIds(Course $course, User $user, array $forums): array
    {
        $forumIds = array_values(array_filter(array_map(
            static fn (CForum $forum): int => (int) $forum->getIid(),
            $forums,
        )));
        if ([] === $forumIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('notification.forumId AS forumId')
            ->from(CForumNotification::class, 'notification')
            ->andWhere('notification.cId = :courseId')
            ->andWhere('notification.userId = :userId')
            ->andWhere('notification.forumId IN (:forumIds)')
            ->setParameter('courseId', (int) $course->getId())
            ->setParameter('userId', (int) $user->getId())
            ->setParameter('forumIds', $forumIds)
            ->getQuery()
            ->getArrayResult()
        ;

        $subscribedForumIds = [];
        foreach ($rows as $row) {
            $forumId = (int) ($row['forumId'] ?? 0);
            if ($forumId > 0) {
                $subscribedForumIds[$forumId] = true;
            }
        }

        return $subscribedForumIds;
    }

    /**
     * @param array<int, CForum> $forums
     *
     * @return array<int, array{threads: int, posts: int}>
     */
    private function getForumCounts(array $forums): array
    {
        $forumIds = array_values(array_filter(array_map(
            static fn (CForum $forum): int => (int) $forum->getIid(),
            $forums,
        )));
        if ([] === $forumIds) {
            return [];
        }

        $counts = [];
        foreach ($forumIds as $forumId) {
            $counts[$forumId] = ['threads' => 0, 'posts' => 0];
        }

        $threadRows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(thread.forum) AS forumId', 'COUNT(thread.iid) AS total')
            ->from(CForumThread::class, 'thread')
            ->andWhere('IDENTITY(thread.forum) IN (:forumIds)')
            ->setParameter('forumIds', $forumIds)
            ->groupBy('thread.forum')
            ->getQuery()
            ->getArrayResult()
        ;

        foreach ($threadRows as $row) {
            $forumId = (int) ($row['forumId'] ?? 0);
            if ($forumId > 0 && isset($counts[$forumId])) {
                $counts[$forumId]['threads'] = (int) ($row['total'] ?? 0);
            }
        }

        $postRows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(thread.forum) AS forumId', 'COUNT(post.iid) AS total')
            ->from(CForumPost::class, 'post')
            ->innerJoin('post.thread', 'thread')
            ->andWhere('IDENTITY(thread.forum) IN (:forumIds)')
            ->setParameter('forumIds', $forumIds)
            ->groupBy('thread.forum')
            ->getQuery()
            ->getArrayResult()
        ;

        foreach ($postRows as $row) {
            $forumId = (int) ($row['forumId'] ?? 0);
            if ($forumId > 0 && isset($counts[$forumId])) {
                $counts[$forumId]['posts'] = (int) ($row['total'] ?? 0);
            }
        }

        return $counts;
    }

    /**
     * @param array{threads: int, posts: int} $counts
     *
     * @return array<string, mixed>
     */
    private function normalizeForum(
        CForum $forum,
        Course $course,
        ?Session $session,
        bool $canSubscribe,
        bool $subscribed,
        array $counts,
    ): array {
        $category = $forum->getForumCategory();

        return [
            '@id' => '/api/forums/'.$forum->getIid(),
            '@type' => 'Forum',
            'iid' => $forum->getIid(),
            'title' => $forum->getTitle(),
            'forumComment' => $forum->getForumComment(),
            'forumImage' => $this->getForumImageUrl($forum),
            'forumThreads' => $counts['threads'],
            'forumPosts' => $counts['posts'],
            'forumCategory' => null === $category ? null : '/api/forum_categories/'.$category->getIid(),
            'allowAnonymous' => $forum->getAllowAnonymous(),
            'allowEdit' => $forum->getAllowEdit(),
            'approvalDirectPost' => $forum->getApprovalDirectPost(),
            'allowAttachments' => $forum->getAllowAttachments(),
            'allowNewThreads' => $forum->getAllowNewThreads(),
            'defaultView' => $forum->getDefaultView(),
            'startTime' => $this->formatDate($forum->getStartTime()),
            'endTime' => $this->formatDate($forum->getEndTime()),
            'availabilityStatus' => $this->getForumAvailabilityStatus($forum),
            'forumOfGroup' => $forum->getForumOfGroup(),
            'forumGroupPublicPrivate' => $forum->getForumGroupPublicPrivate(),
            'locked' => $forum->getLocked(),
            'moderated' => $forum->isModerated(),
            'forumVisible' => $forum->isVisible($course, $session),
            'position' => $forum->getResourceNode()?->getResourceLinkByContext($course, $session)?->getDisplayOrder()
                ?? $forum->getResourceNode()?->getResourceLinkByContext($course)?->getDisplayOrder()
                ?? 0,
            'subscribed' => $canSubscribe && $subscribed,
            'canSubscribe' => $canSubscribe,
        ];
    }

    private function getForumImageUrl(CForum $forum): string
    {
        return trim($forum->getForumImage());
    }
}
