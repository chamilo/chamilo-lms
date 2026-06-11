<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadsByForum;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<array<int, array<string, mixed>>|ForumThreadsByForum>
 */
final class ForumThreadCollectionStateProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumRepository $forumRepository,
        private readonly Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>|ForumThreadsByForum
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|ForumThreadsByForum
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            if ('get_forum_threads_by_forum' === $operation->getName()) {
                return ForumThreadsByForum::fromItems((int) ($uriVariables['forumId'] ?? 0), []);
            }

            return [];
        }

        $forumId = (int) ($uriVariables['forumId'] ?? 0);
        if ($forumId <= 0) {
            $forumId = $this->parseApiId($request->query->get('forum'));
        }

        $items = $this->getThreadsForForum($forumId, $request);
        if ('get_forum_threads_by_forum' === $operation->getName()) {
            return ForumThreadsByForum::fromItems($forumId, $items);
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getThreadsForForum(int $forumId, Request $request): array
    {
        $this->assertForumMemberAccess($this->security, 'You are not allowed to access this forum.');

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $forum = $this->forumRepository->find($forumId);
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        if (null === $forum->getResourceNode() || !$this->security->isGranted('VIEW', $forum->getResourceNode())) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum.');
        }

        $category = $forum->getForumCategory();
        if ($category instanceof CForumCategory && null !== $category->getResourceNode() && !$this->security->isGranted('VIEW', $category->getResourceNode())) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum category.');
        }

        $showHidden = $this->canManageForumsInCurrentView($this->security, $request);
        $user = $this->getCurrentUser();
        $canSubscribe = !$this->areForumPostNotificationsHidden($course);
        $threads = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(CForumThread::class, 't')
            ->andWhere('IDENTITY(t.forum) = :forumId')
            ->setParameter('forumId', $forum->getIid(), Types::INTEGER)
            ->orderBy('t.threadSticky', 'DESC')
            ->addOrderBy('t.threadDate', 'DESC')
            ->addOrderBy('t.iid', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($threads as $thread) {
            if (!$thread instanceof CForumThread) {
                continue;
            }

            $visible = $this->isForumResourceVisible($thread, $course, $session);
            $visiblePostCount = $this->countVisiblePosts($thread);
            if (!$showHidden && (!$visible || 0 === $visiblePostCount)) {
                continue;
            }

            $items[] = $this->serializeThread(
                $thread,
                $visible,
                $showHidden,
                $showHidden ? $this->countPendingPosts($thread) : 0,
                $canSubscribe && $this->isSubscribedToThread($course, $user, (int) $thread->getIid()),
                $canSubscribe,
            );
        }

        return $items;
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
        if (!\function_exists('api_get_course_setting')) {
            return false;
        }

        return 1 === (int) api_get_course_setting('hide_forum_notifications', $course);
    }

    private function isSubscribedToThread(Course $course, User $user, int $threadId): bool
    {
        return null !== $this->entityManager->getRepository(CForumNotification::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'userId' => (int) $user->getId(),
            'threadId' => $threadId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeThread(
        CForumThread $thread,
        bool $visible,
        bool $canManage,
        int $pendingPostCount,
        bool $subscribed,
        bool $canSubscribe,
    ): array {
        return [
            '@id' => '/api/forum_threads/'.$thread->getIid(),
            '@type' => 'ForumThread',
            'iid' => $thread->getIid(),
            'title' => $thread->getTitle(),
            'locked' => $thread->getLocked(),
            'threadDate' => $this->formatDate($thread->getThreadDate()),
            'threadReplies' => $thread->getThreadReplies(),
            'threadViews' => $thread->getThreadViews(),
            'threadSticky' => $thread->getThreadSticky(),
            'threadVisible' => $visible,
            'threadTitleQualify' => $thread->getThreadTitleQualify(),
            'threadQualifyMax' => $thread->getThreadQualifyMax(),
            'threadWeight' => $thread->getThreadWeight(),
            'threadPeerQualify' => $thread->isThreadPeerQualify(),
            'gradebookEnabled' => $thread->getThreadQualifyMax() > 0,
            'posterFullName' => $thread->getPosterFullName(),
            'pendingPostCount' => $pendingPostCount,
            'subscribed' => $subscribed,
            'canSubscribe' => $canSubscribe,
            'canEdit' => $canManage,
            'canDelete' => $canManage,
            'canToggleLock' => $canManage,
            'canToggleSticky' => $canManage,
            'canToggleVisibility' => $canManage,
        ];
    }

    private function countVisiblePosts(CForumThread $thread): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.status = :validatedStatus OR p.status IS NULL')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('visible', true, Types::BOOLEAN)
            ->setParameter('validatedStatus', CForumPost::STATUS_VALIDATED, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function countPendingPosts(CForumThread $thread): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('p.status = :pendingStatus')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('pendingStatus', CForumPost::STATUS_WAITING_MODERATION, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
