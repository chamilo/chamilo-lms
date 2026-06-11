<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadPosts;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use ExtraFieldValue;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @implements ProviderInterface<ForumThreadPosts>
 */
final class ForumThreadPostsStateProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumThreadRepository $threadRepository,
        private readonly CForumAttachmentRepository $attachmentRepository,
        private readonly Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumThreadPosts
    {
        $request = $this->requestStack->getCurrentRequest();
        $threadId = $this->resolveThreadId($uriVariables, $request);

        if (null === $request) {
            return ForumThreadPosts::fromArray($threadId, []);
        }

        return ForumThreadPosts::fromArray($threadId, $this->getThreadPosts($threadId, $request));
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function resolveThreadId(array $uriVariables, ?Request $request): int
    {
        $threadId = $uriVariables['threadId'] ?? null;

        if ($threadId instanceof CForumThread) {
            return (int) $threadId->getIid();
        }

        if (is_numeric($threadId)) {
            return (int) $threadId;
        }

        $requestThreadId = $request?->attributes->get('threadId');
        if (is_numeric($requestThreadId)) {
            return (int) $requestThreadId;
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getThreadPosts(int $threadId, Request $request): array
    {
        $this->assertForumMemberAccess($this->security, 'You are not allowed to access this forum thread.');

        $thread = $this->threadRepository->find($threadId);
        if (!$thread instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $thread->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $requestedForumId = $request->query->getInt('forumId');
        if ($requestedForumId > 0 && $requestedForumId !== $forum->getIid()) {
            throw new BadRequestHttpException('Forum does not match the requested thread.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $canManage = $this->canManageForumsInCurrentView($this->security, $request);
        $user = $this->getCurrentUser();
        $canSubscribe = !$this->areForumPostNotificationsHidden($course);

        if (!$canManage) {
            $this->assertResourceIsVisible($thread->getResourceNode());
            $this->assertResourceIsVisible($forum->getResourceNode());

            if (!$this->isForumResourceVisible($thread, $course, $session)) {
                throw new AccessDeniedHttpException('You are not allowed to access this forum thread.');
            }
        }

        $postsQueryBuilder = $this->entityManager->createQueryBuilder()
            ->select('p', 'a')
            ->from(CForumPost::class, 'p')
            ->leftJoin('p.attachments', 'a')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('IDENTITY(p.forum) = :forumId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('forumId', $forum->getIid(), Types::INTEGER)
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.iid', 'ASC')
        ;

        if (!$canManage) {
            $postsQueryBuilder
                ->andWhere('p.visible = :visible')
                ->andWhere('p.status = :validatedStatus OR p.status IS NULL')
                ->setParameter('visible', true, Types::BOOLEAN)
                ->setParameter('validatedStatus', CForumPost::STATUS_VALIDATED, Types::INTEGER)
            ;
        }

        $posts = $postsQueryBuilder->getQuery()->getResult();

        return [
            'forum' => $this->serializeForum($forum),
            'thread' => $this->serializeThread(
                $thread,
                $course,
                $session,
                $canManage,
                $canSubscribe && $this->isSubscribedToThread($course, $user, (int) $thread->getIid()),
                $canSubscribe,
            ),
            'canReply' => $this->canReply($forum, $thread),
            'canManageThread' => $canManage,
            'posts' => array_map(
                fn (CForumPost $post): array => $this->serializePost($post, $forum, $thread, $canManage),
                $posts,
            ),
        ];
    }

    private function assertResourceIsVisible(?ResourceNode $resourceNode): void
    {
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this resource.');
        }
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
    private function serializeForum(CForum $forum): array
    {
        return [
            'iid' => $forum->getIid(),
            'title' => $forum->getTitle(),
            'locked' => $forum->getLocked(),
            'allowAttachments' => $forum->getAllowAttachments(),
            'allowEdit' => $forum->getAllowEdit(),
            'allowNewThreads' => $forum->getAllowNewThreads(),
            'moderated' => $forum->isModerated(),
            'approvalDirectPost' => $forum->getApprovalDirectPost(),
            'defaultView' => $forum->getDefaultView() ?: 'flat',
            'startTime' => $this->formatDate($forum->getStartTime()),
            'endTime' => $this->formatDate($forum->getEndTime()),
            'availabilityStatus' => $this->getForumAvailabilityStatus($forum),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeThread(
        CForumThread $thread,
        Course $course,
        ?Session $session,
        bool $canManage,
        bool $subscribed,
        bool $canSubscribe,
    ): array {
        $visible = $this->isForumResourceVisible($thread, $course, $session);

        return [
            'iid' => $thread->getIid(),
            'title' => $thread->getTitle(),
            'locked' => $thread->getLocked(),
            'threadDate' => $this->formatDate($thread->getThreadDate()),
            'threadSticky' => $thread->getThreadSticky(),
            'threadVisible' => $visible,
            'threadReplies' => $thread->getThreadReplies(),
            'posterFullName' => $thread->getPosterFullName(),
            'subscribed' => $subscribed,
            'canSubscribe' => $canSubscribe,
            'canEdit' => $canManage,
            'canDelete' => $canManage,
            'canToggleLock' => $canManage,
            'canToggleSticky' => $canManage,
            'canToggleVisibility' => $canManage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePost(CForumPost $post, CForum $forum, CForumThread $thread, bool $canManage): array
    {
        $canEdit = $this->canEditPost($post, $forum, $thread, $canManage);
        $attachments = [];

        foreach ($post->getAttachments() as $attachment) {
            $attachments[] = [
                'iid' => $attachment->getIid(),
                'filename' => $attachment->getFilename(),
                'path' => $attachment->getPath(),
                'size' => $attachment->getSize(),
                'downloadUrl' => $this->attachmentRepository->getResourceFileDownloadUrl($attachment),
                'canDelete' => $canEdit,
            ];
        }

        $status = $post->getStatus() ?? ($post->getVisible() ? CForumPost::STATUS_VALIDATED : CForumPost::STATUS_WAITING_MODERATION);

        $currentUser = $this->security->getUser();
        $isAuthor = $currentUser instanceof User && $post->getUser()->getId() === $currentUser->getId();
        $revisionRequested = $this->postNeedsRevision($post);
        $revisionLanguage = $this->getPostRevisionLanguage($post);

        return [
            'iid' => $post->getIid(),
            'title' => $post->getTitle(),
            'postText' => $post->getPostText(),
            'postDate' => $this->formatDate($post->getPostDate()),
            'postParentId' => $post->getPostParent()?->getIid(),
            'visible' => $post->getVisible(),
            'status' => $status,
            'statusLabel' => $this->getPostStatusLabel($status),
            'posterFullName' => $post->getPosterFullName(),
            'canEdit' => $canEdit,
            'canDelete' => $canEdit,
            'canApprove' => $canManage && CForumPost::STATUS_VALIDATED !== $status,
            'canReject' => $canManage && CForumPost::STATUS_REJECTED !== $status,
            'canToggleVisibility' => $canManage,
            'canReplyToPost' => $this->canReply($forum, $thread),
            'canQuote' => $this->canReply($forum, $thread),
            'canMove' => $canManage && !$this->isFirstPost($post, $thread),
            'revisionRequested' => $revisionRequested,
            'revisionLanguage' => $revisionLanguage,
            'canAskRevision' => $isAuthor && $this->areForumPostRevisionsEnabled(),
            'canGiveRevision' => !$isAuthor && $revisionRequested && $this->canReply($forum, $thread),
            'canReport' => $this->isReportAvailableForCurrentRequest(),
            'attachments' => $attachments,
        ];
    }

    private function areForumPostRevisionsEnabled(): bool
    {
        if (!\function_exists('api_get_setting')) {
            return false;
        }

        return $this->isTruthySetting(api_get_setting('forum.allow_forum_post_revisions'));
    }

    private function isTruthySetting(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function postNeedsRevision(CForumPost $post): bool
    {
        return 1 === (int) $this->getExtraFieldValue('forum_post', (int) $post->getIid(), 'ask_for_revision');
    }

    private function getPostRevisionLanguage(CForumPost $post): string
    {
        return (string) ($this->getExtraFieldValue('forum_post', (int) $post->getIid(), 'revision_language') ?? '');
    }

    private function isReportAvailableForCurrentRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        try {
            $course = $this->getCourse($this->entityManager, $request);
        } catch (Throwable) {
            return false;
        }

        return 1 === (int) $this->getExtraFieldValue('course', (int) $course->getId(), 'allow_forum_report_button');
    }

    private function getExtraFieldValue(string $itemType, int $itemId, string $variable): mixed
    {
        $databaseValue = $this->getExtraFieldValueFromDatabase($itemType, $itemId, $variable);
        if (null !== $databaseValue) {
            return $databaseValue;
        }

        if (!class_exists('ExtraFieldValue')) {
            return null;
        }

        $extraFieldValue = new ExtraFieldValue($itemType);
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($itemId, $variable);

        return \is_array($value) ? ($value['value'] ?? null) : null;
    }

    private function getExtraFieldValueFromDatabase(string $itemType, int $itemId, string $variable): mixed
    {
        $fieldId = $this->getExtraFieldId($itemType, $variable);
        if (null === $fieldId) {
            return null;
        }

        $value = $this->entityManager->getConnection()->fetchOne(
            'SELECT field_value FROM extra_field_values WHERE field_id = :fieldId AND item_id = :itemId ORDER BY id DESC LIMIT 1',
            [
                'fieldId' => $fieldId,
                'itemId' => $itemId,
            ],
            [
                'fieldId' => Types::INTEGER,
                'itemId' => Types::INTEGER,
            ],
        );

        return false === $value ? null : $value;
    }

    private function getExtraFieldId(string $itemType, string $variable): ?int
    {
        $itemTypeId = $this->getExtraFieldItemTypeId($itemType);
        if (null === $itemTypeId) {
            return null;
        }

        $fieldId = $this->entityManager->getConnection()->fetchOne(
            'SELECT id FROM extra_field WHERE item_type = :itemType AND variable = :variable ORDER BY id DESC LIMIT 1',
            [
                'itemType' => $itemTypeId,
                'variable' => $variable,
            ],
            [
                'itemType' => Types::INTEGER,
                'variable' => Types::STRING,
            ],
        );

        return false === $fieldId ? null : (int) $fieldId;
    }

    private function getExtraFieldItemTypeId(string $itemType): ?int
    {
        return match ($itemType) {
            'course' => 2,
            'forum_post' => 16,
            default => null,
        };
    }

    private function getPostStatusLabel(int $status): string
    {
        return match ($status) {
            CForumPost::STATUS_VALIDATED => 'Validated',
            CForumPost::STATUS_WAITING_MODERATION => 'Waiting for moderation',
            CForumPost::STATUS_REJECTED => 'Rejected',
            default => 'Waiting for moderation',
        };
    }

    private function isFirstPost(CForumPost $post, CForumThread $thread): bool
    {
        $firstPostId = $this->entityManager->createQueryBuilder()
            ->select('p.iid')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.iid', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $firstPostId === $post->getIid();
    }

    private function canReply(CForum $forum, CForumThread $thread): bool
    {
        if ($this->isTeacher($this->security)) {
            return true;
        }

        return $this->isForumOpenForParticipation($forum)
            && 0 === $forum->getLocked()
            && 0 === $thread->getLocked();
    }

    private function canEditPost(CForumPost $post, CForum $forum, CForumThread $thread, bool $canManage): bool
    {
        if ($canManage) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $post->getUser()->getId() !== $user->getId()) {
            return false;
        }

        $category = $forum->getForumCategory();

        return 1 === (int) ($forum->getAllowEdit() ?? 0)
            && (null === $category || 0 === $category->getLocked())
            && 0 === $forum->getLocked()
            && 0 === $thread->getLocked();
    }
}
