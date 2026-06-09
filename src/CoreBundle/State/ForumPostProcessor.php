<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Handles forum post reply/update/delete operations.
 *
 * @implements ProcessorInterface<mixed, JsonResponse>
 */
final class ForumPostProcessor implements ProcessorInterface
{
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;
    use ForumNotificationHelperTrait;
    use ForumActionStateHelperTrait;

    public function __construct(
        private readonly CForumRepository $forumRepository,
        private readonly CForumThreadRepository $threadRepository,
        private readonly CForumPostRepository $postRepository,
        private readonly CForumAttachmentRepository $attachmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UploadFilenamePolicy $uploadFilenamePolicy,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->getCurrentRequest();
        $operationName = (string) $operation->getName();

        return match ($operationName) {
            'create_forum_reply' => $this->createReply($request),
            'update_forum_post' => $this->updatePost($request, $data),
            'toggle_forum_post_visibility' => $this->togglePostVisibility($request, $data),
            'approve_forum_post' => $this->approvePost($request, $data),
            'reject_forum_post' => $this->rejectPost($request, $data),
            'move_forum_post' => $this->movePost($request, $data),
            'ask_forum_post_revision' => $this->askPostRevision($request, $data),
            'report_forum_post' => $this->reportPost($request, $data),
            'delete_forum_post' => $this->deletePost($request, $data),
            default => throw new BadRequestHttpException('Unsupported forum post operation.'),
        };
    }

    private function createReply(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $data['csrfToken'] ?? null);

        $forum = $this->forumRepository->find($this->getRequiredInt($data, 'forumId'));
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $thread = $this->threadRepository->find($this->getRequiredInt($data, 'threadId'));
        if (!$thread instanceof CForumThread || $thread->getForum()?->getIid() !== $forum->getIid()) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        if (null === $forum->getResourceNode() || !$this->security->isGranted('VIEW', $forum->getResourceNode())) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum.');
        }

        if (null === $thread->getResourceNode() || !$this->security->isGranted('VIEW', $thread->getResourceNode())) {
            throw new AccessDeniedHttpException('You are not allowed to access this thread.');
        }

        $parentPost = $this->findParentPost($data, $forum, $thread);

        $uploadedFiles = $this->getUploadedFiles($request);
        $this->assertAttachmentsAllowed($forum, $uploadedFiles);

        $isTeacher = $this->isTeacher($this->security);
        $this->assertReplyAllowed($forum, $thread, $isTeacher);

        $title = $this->getRequiredText($data, 'title', 250);
        $text = $this->getRequiredHtmlText($data, 'text');
        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $visible = !$this->requiresModeration($forum, $isTeacher);
        $status = $visible ? CForumPost::STATUS_VALIDATED : CForumPost::STATUS_WAITING_MODERATION;

        $post = (new CForumPost())
            ->setTitle($title)
            ->setPostText($text)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($user)
            ->setPostDate($now)
            ->setPostNotification($this->shouldStorePostNotification($course, $this->getBoolean($data, 'postNotification')))
            ->setVisible($visible)
            ->setStatus($status)
            ->setPostParent($parentPost)
            ->setParent($thread)
            ->addCourseLink($course, $session, $group)
        ;

        $this->postRepository->create($post);

        $attachments = $this->storeAttachments($uploadedFiles, $post, $course, $session, $group);
        $this->storeReplyRevisionMetadata($post, $parentPost, $data);

        if ($visible) {
            $thread->setThreadReplies(max(0, (int) $thread->getThreadReplies()) + 1);
            $thread->setThreadLastPost($post);
            $forum->setForumLastPost($post);
            $forum->setForumPosts(($forum->getForumPosts() ?? 0) + 1);
        }

        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        if ($post->getPostNotification()) {
            $this->setThreadSubscription($this->entityManager, $course, $user, (int) $thread->getIid(), true);
            $this->entityManager->flush();
        }

        if ($visible) {
            $this->sendForumSubscriptionNotifications($this->entityManager, $request, $course, $session, $forum, $thread, $post, $user);
        }

        $this->registerForumEventLog('reply-thread', 'post', (string) $post->getIid());

        return new JsonResponse([
            'postId' => $post->getIid(),
            'threadId' => $thread->getIid(),
            'attachments' => $attachments,
            'requiresApproval' => !$visible,
            'message' => $visible ? 'Reply added.' : 'Your message has to be approved before people can view it.',
        ], Response::HTTP_CREATED);
    }

    private function updatePost(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        $thread = $data->getThread();
        $forum = $data->getForum();
        if (!$thread instanceof CForumThread || !$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $this->assertVisibleResource($data->getResourceNode());
        $this->assertVisibleResource($thread->getResourceNode());
        $this->assertPostCanBeEdited($data, $forum, $thread);

        $title = $this->getRequiredText($payload, 'title', 250);
        $text = trim((string) ($payload['text'] ?? ''));
        if ('' === trim(strip_tags($text))) {
            throw new BadRequestHttpException('Missing text.');
        }

        $data->setTitle($title);
        $data->setPostText($text);

        if ($this->isFirstPost($data, $thread)) {
            $thread->setTitle($title);
            $this->entityManager->persist($thread);
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->registerForumEventLog('edit-post', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'threadId' => $thread->getIid(),
            'title' => $data->getTitle(),
            'postText' => $data->getPostText(),
            'threadTitle' => $thread->getTitle(),
            'message' => 'Post updated.',
        ]);
    }

    private function deletePost(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        $thread = $data->getThread();
        $forum = $data->getForum();
        if (!$thread instanceof CForumThread || !$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $this->assertVisibleResource($data->getResourceNode());
        $this->assertVisibleResource($thread->getResourceNode());
        $this->assertPostCanBeDeleted($data, $forum, $thread);

        $postId = (int) $data->getIid();
        $postCount = $this->countThreadPosts($thread);
        if (1 >= $postCount) {
            $this->assertTeacher($this->security);
            $forum->setForumThreads(max(0, (int) ($forum->getForumThreads() ?? 0) - 1));
            $forum->setForumPosts(max(0, (int) ($forum->getForumPosts() ?? 0) - 1));
            if ($forum->getForumLastPost()?->getIid() === $data->getIid()) {
                $forum->setForumLastPost(null);
            }

            $threadId = (int) $thread->getIid();
            $this->threadRepository->delete($thread);
            $this->entityManager->persist($forum);
            $this->entityManager->flush();

            $this->registerForumEventLog('delete-thread-from-post', 'thread', (string) $threadId);

            return new JsonResponse([
                'postId' => $postId,
                'threadId' => $threadId,
                'threadDeleted' => true,
                'message' => 'Thread deleted.',
            ]);
        }

        foreach ($data->getChildren() as $child) {
            if ($child instanceof CForumPost) {
                $child->setPostParent($data->getPostParent());
                $this->entityManager->persist($child);
            }
        }

        $lastPost = $this->findLastPostExcluding($thread, $data);
        if ($lastPost instanceof CForumPost) {
            $thread->setThreadLastPost($lastPost);
            $forum->setForumLastPost($lastPost);
        }

        $thread->setThreadReplies(max(0, $postCount - 2));
        $forum->setForumPosts(max(0, (int) ($forum->getForumPosts() ?? 0) - 1));

        $threadId = (int) $thread->getIid();
        $this->postRepository->delete($data);
        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->registerForumEventLog('delete-post', 'post', (string) $postId);

        return new JsonResponse([
            'postId' => $postId,
            'threadId' => $threadId,
            'threadDeleted' => false,
            'message' => 'Post deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */

    private function togglePostVisibility(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        [$forum, $thread] = $this->getPostContext($data);
        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($thread->getResourceNode(), $this->security);
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $targetVisible = \array_key_exists('visible', $payload)
            ? filter_var($payload['visible'], FILTER_VALIDATE_BOOLEAN)
            : !$data->getVisible();
        $data->setVisible($targetVisible);
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->registerForumEventLog('update-post-visibility', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'visible' => $data->getVisible(),
            'message' => $data->getVisible() ? 'Post shown.' : 'Post hidden.',
        ]);
    }

    private function approvePost(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        [$forum, $thread] = $this->getPostContext($data);
        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($thread->getResourceNode(), $this->security);
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $wasVisible = $data->getVisible();
        $data
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
        ;

        if (!$wasVisible && !$this->isFirstPost($data, $thread)) {
            $thread->setThreadReplies($thread->getThreadReplies() + 1);
            $forum->setForumPosts(($forum->getForumPosts() ?? 0) + 1);
        }

        $this->entityManager->persist($data);
        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->refreshLastValidatedPost($thread, $forum);

        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $author = $data->getUser();
        if (!$wasVisible && $author instanceof User) {
            $this->sendForumSubscriptionNotifications($this->entityManager, $request, $course, $session, $forum, $thread, $data, $author);
        }

        $this->registerForumEventLog('approve-post', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'threadId' => $thread->getIid(),
            'visible' => $data->getVisible(),
            'status' => $data->getStatus(),
            'message' => 'Post approved.',
        ]);
    }

    private function rejectPost(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        [$forum, $thread] = $this->getPostContext($data);
        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($thread->getResourceNode(), $this->security);
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $wasVisible = $data->getVisible();
        $data
            ->setVisible(false)
            ->setStatus(CForumPost::STATUS_REJECTED)
        ;

        if ($wasVisible && !$this->isFirstPost($data, $thread)) {
            $thread->setThreadReplies(max(0, $thread->getThreadReplies() - 1));
            $forum->setForumPosts(max(0, (int) ($forum->getForumPosts() ?? 0) - 1));
        }

        $this->entityManager->persist($data);
        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->refreshLastValidatedPost($thread, $forum);

        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->registerForumEventLog('reject-post', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'threadId' => $thread->getIid(),
            'visible' => $data->getVisible(),
            'status' => $data->getStatus(),
            'message' => 'Post rejected.',
        ]);
    }

    private function askPostRevision(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        if (!$this->areForumPostRevisionsEnabled()) {
            throw new AccessDeniedHttpException('Forum post revisions are disabled.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $data->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Only the post author can ask for a revision.');
        }

        [$forum, $thread] = $this->getPostContext($data);
        $this->assertVisibleResource($data->getResourceNode());
        $this->assertVisibleResource($thread->getResourceNode());
        $this->assertVisibleResource($forum->getResourceNode());

        $requested = !$this->postNeedsRevision($data);
        $this->saveExtraFieldValue('forum_post', (int) $data->getIid(), 'ask_for_revision', $requested ? 1 : null);

        $this->registerForumEventLog($requested ? 'ask-revision' : 'cancel-revision-request', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'revisionRequested' => $requested,
            'message' => $requested ? 'Revision requested.' : 'Revision request removed.',
        ]);
    }

    private function reportPost(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        [$forum, $thread] = $this->getPostContext($data);
        $this->assertVisibleResource($data->getResourceNode());
        $this->assertVisibleResource($thread->getResourceNode());
        $this->assertVisibleResource($forum->getResourceNode());

        $course = $this->getCourse($this->entityManager, $request);
        if (!$this->isReportAvailable((int) $course->getId())) {
            throw new AccessDeniedHttpException('Forum post reporting is disabled.');
        }

        $recipientIds = $this->getReportRecipientIds($course);
        $currentUser = $this->security->getUser();
        $currentUserName = $currentUser instanceof User ? $currentUser->getFullName() : 'Unknown user';
        $subject = 'Post reported';
        $content = sprintf(
            'User %s has reported the message %s in the forum %s',
            $currentUserName,
            $data->getTitle(),
            $forum->getTitle(),
        );

        foreach ($recipientIds as $recipientId) {
            if (\class_exists('MessageManager')) {
                \MessageManager::send_message_simple($recipientId, $subject, $content);
            }
        }

        $this->registerForumEventLog('report-post', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'recipients' => \count($recipientIds),
            'message' => 'Post reported.',
        ]);
    }

    private function movePost(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumPost) {
            throw new NotFoundHttpException('Forum post not found.');
        }

        [$sourceForum, $sourceThread] = $this->getPostContext($data);
        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($sourceThread->getResourceNode(), $this->security);
        $this->assertEditableForumResource($sourceForum->getResourceNode(), $this->security);

        if ($this->isFirstPost($data, $sourceThread)) {
            throw new BadRequestHttpException('The first post of a thread cannot be moved. Move the thread instead.');
        }

        $targetThreadId = $this->getOptionalInt($payload, 'targetThreadId');
        if (0 === $targetThreadId) {
            $course = $this->getCourse($this->entityManager, $request);
            $session = $this->getSession($this->entityManager, $request);
            $group = $this->getGroup($this->entityManager, $request);

            $targetThread = (new CForumThread())
                ->setTitle($data->getTitle())
                ->setForum($sourceForum)
                ->setUser($data->getUser())
                ->setThreadDate($data->getPostDate())
                ->setThreadLastPost($data)
                ->setThreadTitleQualify('')
                ->setThreadQualifyMax(0)
                ->setThreadWeight(0)
                ->setThreadPeerQualify(false)
                ->setParent($sourceForum)
                ->addCourseLink($course, $session, $group)
            ;

            $this->threadRepository->create($targetThread);
            $targetForum = $sourceForum;
            $sourceForum->setForumThreads(($sourceForum->getForumThreads() ?? 0) + 1);
        } else {
            $targetThread = $this->threadRepository->find($targetThreadId);
            if (!$targetThread instanceof CForumThread) {
                throw new NotFoundHttpException('Target forum thread not found.');
            }

            $targetForum = $targetThread->getForum();
            if (!$targetForum instanceof CForum) {
                throw new NotFoundHttpException('Target forum not found.');
            }

            $this->assertEditableForumResource($targetThread->getResourceNode(), $this->security);
            $this->assertEditableForumResource($targetForum->getResourceNode(), $this->security);

            if ($targetThread->getIid() === $sourceThread->getIid()) {
                throw new BadRequestHttpException('The post is already in the selected thread.');
            }
        }

        foreach ($data->getChildren() as $child) {
            if ($child instanceof CForumPost) {
                $child->setPostParent(null);
                $this->entityManager->persist($child);
            }
        }

        $data
            ->setThread($targetThread)
            ->setForum($targetForum)
            ->setPostParent(null)
            ->setParent($targetThread)
        ;

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->refreshThreadCounters($sourceThread);
        $this->refreshThreadCounters($targetThread);
        $this->refreshForumPostCount($sourceForum);
        if ($sourceForum->getIid() !== $targetForum->getIid()) {
            $this->refreshForumPostCount($targetForum);
        }
        $this->refreshLastValidatedPost($sourceThread, $sourceForum);
        $this->refreshLastValidatedPost($targetThread, $targetForum);

        $this->entityManager->persist($sourceThread);
        $this->entityManager->persist($targetThread);
        $this->entityManager->persist($sourceForum);
        $this->entityManager->persist($targetForum);
        $this->entityManager->flush();

        $this->registerForumEventLog('move-post', 'post', (string) $data->getIid());

        return new JsonResponse([
            'postId' => $data->getIid(),
            'sourceThreadId' => $sourceThread->getIid(),
            'targetThreadId' => $targetThread->getIid(),
            'targetForumId' => $targetForum->getIid(),
            'message' => 'Post moved.',
        ]);
    }

    /**
     * @return array{0: CForum, 1: CForumThread}
     */
    private function getPostContext(CForumPost $post): array
    {
        $thread = $post->getThread();
        $forum = $post->getForum();
        if (!$thread instanceof CForumThread || !$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        return [$forum, $thread];
    }

    private function refreshLastValidatedPost(CForumThread $thread, CForum $forum): void
    {
        $lastPost = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.status = :status OR p.status IS NULL')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('visible', true, Types::BOOLEAN)
            ->setParameter('status', CForumPost::STATUS_VALIDATED, Types::INTEGER)
            ->orderBy('p.postDate', 'DESC')
            ->addOrderBy('p.iid', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $thread->setThreadLastPost($lastPost instanceof CForumPost ? $lastPost : null);
        if ($forum->getIid() === $thread->getForum()?->getIid()) {
            $forum->setForumLastPost($lastPost instanceof CForumPost ? $lastPost : null);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function findParentPost(array $data, CForum $forum, CForumThread $thread): ?CForumPost
    {
        $parentPostId = (int) ($data['parentPostId'] ?? $data['postParentId'] ?? $data['post_parent_id'] ?? 0);
        if (0 >= $parentPostId) {
            return null;
        }

        $parentPost = $this->postRepository->find($parentPostId);
        if (!$parentPost instanceof CForumPost
            || $parentPost->getThread()?->getIid() !== $thread->getIid()
            || $parentPost->getForum()?->getIid() !== $forum->getIid()
        ) {
            throw new NotFoundHttpException('Parent forum post not found.');
        }

        if (null === $parentPost->getResourceNode() || !$this->security->isGranted('VIEW', $parentPost->getResourceNode())) {
            throw new AccessDeniedHttpException('You are not allowed to access the parent forum post.');
        }

        return $parentPost;
    }

    private function refreshThreadCounters(CForumThread $thread): void
    {
        $postCount = $this->countThreadPosts($thread);
        $thread->setThreadReplies(max(0, $postCount - 1));
    }

    private function refreshForumPostCount(CForum $forum): void
    {
        $postCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.forum) = :forumId')
            ->setParameter('forumId', $forum->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $forum->setForumPosts($postCount);
    }

    private function getRequestData(Request $request): array
    {
        if (str_starts_with((string) $request->headers->get('Content-Type'), 'multipart/form-data')) {
            return $request->request->all();
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        if (!\is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        return $data;
    }

    private function getRequiredHtmlText(array $data, string $key): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ('' === trim(strip_tags($value))) {
            throw new BadRequestHttpException('Missing '.$key.'.');
        }

        return $value;
    }

    private function assertReplyAllowed(CForum $forum, CForumThread $thread, bool $isTeacher): void
    {
        if ($isTeacher) {
            return;
        }

        $this->assertForumOpenForParticipation($forum);

        $category = $forum->getForumCategory();
        if (null !== $category && 0 !== $category->getLocked()) {
            throw new AccessDeniedHttpException('The forum category is locked.');
        }

        if (0 !== $forum->getLocked()) {
            throw new AccessDeniedHttpException('The forum is locked.');
        }

        if (0 !== $thread->getLocked()) {
            throw new AccessDeniedHttpException('The thread is locked.');
        }
    }

    private function requiresModeration(CForum $forum, bool $isTeacher): bool
    {
        if ($isTeacher) {
            return false;
        }

        return '1' === (string) $forum->getApprovalDirectPost() || $forum->isModerated();
    }

    /**
     * @return UploadedFile[]
     */
    private function getUploadedFiles(Request $request): array
    {
        $files = $request->files->get('attachments') ?? $request->files->get('user_upload');
        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (!\is_array($files)) {
            return [];
        }

        $uploadedFiles = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $file;
            }
        }

        return $uploadedFiles;
    }

    /**
     * @param UploadedFile[] $uploadedFiles
     */
    private function assertAttachmentsAllowed(CForum $forum, array $uploadedFiles): void
    {
        if ([] === $uploadedFiles) {
            return;
        }

        if (1 !== (int) ($forum->getAllowAttachments() ?? 0)) {
            throw new AccessDeniedHttpException('Attachments are not allowed in this forum.');
        }
    }

    /**
     * @param UploadedFile[] $uploadedFiles
     *
     * @return array<int, array<string, mixed>>
     */
    private function storeAttachments(
        array $uploadedFiles,
        CForumPost $post,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $attachments = [];

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid()) {
                throw new BadRequestHttpException('Invalid attachment upload.');
            }

            $policy = $this->uploadFilenamePolicy->filter($file->getClientOriginalName());
            if (false === $policy['allowed']) {
                throw new BadRequestHttpException('File upload failed: this file extension or file type is prohibited.');
            }

            $filename = $policy['filename'];
            $attachment = (new CForumAttachment())
                ->setCId($course->getId())
                ->setComment('')
                ->setFilename($filename)
                ->setPath($filename)
                ->setPost($post)
                ->setSize((int) ($file->getSize() ?? 0))
                ->setParent($post)
                ->addCourseLink($course, $session, $group)
            ;

            $this->attachmentRepository->create($attachment);
            $this->attachmentRepository->addFile($attachment, $file);
            $this->attachmentRepository->update($attachment);

            $attachments[] = [
                'id' => $attachment->getIid(),
                'filename' => $attachment->getFilename(),
                'size' => $attachment->getSize(),
                'url' => $this->attachmentRepository->getResourceFileUrl($attachment),
                'downloadUrl' => $this->attachmentRepository->getResourceFileDownloadUrl($attachment),
            ];
        }

        return $attachments;
    }

    private function assertVisibleResource(?ResourceNode $resourceNode): void
    {
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum resource.');
        }
    }

    private function assertPostCanBeEdited(CForumPost $post, CForum $forum, CForumThread $thread): void
    {
        if ($this->isTeacher($this->security)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $post->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to edit this forum post.');
        }

        if (1 !== (int) ($forum->getAllowEdit() ?? 0)) {
            throw new AccessDeniedHttpException('Editing posts is not allowed in this forum.');
        }

        $category = $forum->getForumCategory();
        if ((null !== $category && 0 !== $category->getLocked()) || 0 !== $forum->getLocked() || 0 !== $thread->getLocked()) {
            throw new AccessDeniedHttpException('The forum or thread is locked.');
        }
    }

    private function assertPostCanBeDeleted(CForumPost $post, CForum $forum, CForumThread $thread): void
    {
        if ($this->isTeacher($this->security)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $post->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You are not allowed to delete this forum post.');
        }

        if ($this->isFirstPost($post, $thread)) {
            throw new AccessDeniedHttpException('Only teachers can delete the first post of a thread.');
        }

        if (1 !== (int) ($forum->getAllowEdit() ?? 0)) {
            throw new AccessDeniedHttpException('Deleting posts is not allowed in this forum.');
        }

        $category = $forum->getForumCategory();
        if ((null !== $category && 0 !== $category->getLocked()) || 0 !== $forum->getLocked() || 0 !== $thread->getLocked()) {
            throw new AccessDeniedHttpException('The forum or thread is locked.');
        }
    }

    private function countThreadPosts(CForumThread $thread): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
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

    private function findLastPostExcluding(CForumThread $thread, CForumPost $postToExclude): ?CForumPost
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('p.iid != :postId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('postId', $postToExclude->getIid(), Types::INTEGER)
            ->orderBy('p.postDate', 'DESC')
            ->addOrderBy('p.iid', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function storeReplyRevisionMetadata(CForumPost $post, ?CForumPost $parentPost, array $data): void
    {
        if (!$this->getBoolean($data, 'giveRevision')) {
            return;
        }

        $revisionLanguage = trim((string) ($data['revisionLanguage'] ?? '1'));
        if ('' === $revisionLanguage) {
            $revisionLanguage = '1';
        }

        $this->saveExtraFieldValue('forum_post', (int) $post->getIid(), 'revision_language', $revisionLanguage);

        if ($parentPost instanceof CForumPost) {
            $this->saveExtraFieldValue('forum_post', (int) $parentPost->getIid(), 'ask_for_revision', null);
        }
    }

    private function areForumPostRevisionsEnabled(): bool
    {
        if (!\function_exists('api_get_setting')) {
            return false;
        }

        return $this->isTruthySetting(\api_get_setting('forum.allow_forum_post_revisions'));
    }

    private function isTruthySetting(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function postNeedsRevision(CForumPost $post): bool
    {
        return 1 === (int) $this->getExtraFieldValue('forum_post', (int) $post->getIid(), 'ask_for_revision');
    }

    private function isReportAvailable(int $courseId): bool
    {
        return 1 === (int) $this->getExtraFieldValue('course', $courseId, 'allow_forum_report_button');
    }

    private function getExtraFieldValue(string $itemType, int $itemId, string $variable): mixed
    {
        $databaseValue = $this->getExtraFieldValueFromDatabase($itemType, $itemId, $variable);
        if (null !== $databaseValue) {
            return $databaseValue;
        }

        if (!\class_exists('ExtraFieldValue')) {
            return null;
        }

        $extraFieldValue = new \ExtraFieldValue($itemType);
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($itemId, $variable);

        return \is_array($value) ? ($value['value'] ?? null) : null;
    }

    private function saveExtraFieldValue(string $itemType, int $itemId, string $variable, mixed $value): void
    {
        if ($this->saveExtraFieldValueInDatabase($itemType, $itemId, $variable, $value)) {
            return;
        }

        if (!\class_exists('ExtraFieldValue')) {
            return;
        }

        $extraFieldValue = new \ExtraFieldValue($itemType);
        $params = ['item_id' => $itemId];
        if (null !== $value && '' !== (string) $value) {
            $params['extra_'.$variable] = ['extra_'.$variable => $value];
        }

        $extraFieldValue->saveFieldValues($params, true, false, [$variable]);
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

    private function saveExtraFieldValueInDatabase(string $itemType, int $itemId, string $variable, mixed $value): bool
    {
        $fieldId = $this->getExtraFieldId($itemType, $variable);
        if (null === $fieldId) {
            return false;
        }

        $connection = $this->entityManager->getConnection();
        if (null === $value || '' === (string) $value) {
            $connection->delete(
                'extra_field_values',
                [
                    'field_id' => $fieldId,
                    'item_id' => $itemId,
                ],
                [
                    Types::INTEGER,
                    Types::INTEGER,
                ],
            );

            return true;
        }

        $existingId = $connection->fetchOne(
            'SELECT id FROM extra_field_values WHERE field_id = :fieldId AND item_id = :itemId ORDER BY id DESC LIMIT 1',
            [
                'fieldId' => $fieldId,
                'itemId' => $itemId,
            ],
            [
                'fieldId' => Types::INTEGER,
                'itemId' => Types::INTEGER,
            ],
        );

        $columns = $connection->createSchemaManager()->listTableColumns('extra_field_values');
        $now = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        if (false === $existingId) {
            $insertData = [
                'field_id' => $fieldId,
                'item_id' => $itemId,
                'field_value' => (string) $value,
            ];
            $insertTypes = [
                Types::INTEGER,
                Types::INTEGER,
                Types::STRING,
            ];

            if (isset($columns['created_at'])) {
                $insertData['created_at'] = $now;
                $insertTypes[] = Types::STRING;
            }

            if (isset($columns['updated_at'])) {
                $insertData['updated_at'] = $now;
                $insertTypes[] = Types::STRING;
            }

            $connection->insert('extra_field_values', $insertData, $insertTypes);

            return true;
        }

        $updateData = ['field_value' => (string) $value];
        $updateTypes = [Types::STRING];

        if (isset($columns['updated_at'])) {
            $updateData['updated_at'] = $now;
            $updateTypes[] = Types::STRING;
        }

        $connection->update(
            'extra_field_values',
            $updateData,
            ['id' => (int) $existingId],
            $updateTypes,
        );

        return true;
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

    /**
     * @return int[]
     */
    private function getReportRecipientIds(Course $course): array
    {
        $recipientsValue = (string) ($this->getExtraFieldValue('course', (int) $course->getId(), 'forum_report_recipients') ?? '');
        $recipientTypes = array_filter(array_map('trim', explode(';', $recipientsValue)));
        $recipientIds = [];

        foreach ($recipientTypes as $recipientType) {
            if ('teachers' === $recipientType && \class_exists('CourseManager')) {
                $teachers = \CourseManager::get_teacher_list_from_course_code($course->getCode());
                foreach ($teachers as $teacher) {
                    $recipientIds[] = (int) ($teacher['user_id'] ?? 0);
                }
            }

            if ('admins' === $recipientType && \class_exists('UserManager')) {
                $admins = \UserManager::get_all_administrators();
                foreach ($admins as $admin) {
                    $recipientIds[] = (int) ($admin['user_id'] ?? $admin['id'] ?? 0);
                }
            }

            if ('community_managers' === $recipientType && \function_exists('api_get_setting')) {
                $managers = \api_get_setting('forum.community_managers_user_list', true);
                if (\is_array($managers) && isset($managers['users']) && \is_array($managers['users'])) {
                    foreach ($managers['users'] as $managerId) {
                        $recipientIds[] = (int) $managerId;
                    }
                }
            }
        }

        if ([] === $recipientIds && \class_exists('CourseManager')) {
            $teachers = \CourseManager::get_teacher_list_from_course_code($course->getCode());
            foreach ($teachers as $teacher) {
                $recipientIds[] = (int) ($teacher['user_id'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($recipientIds)));
    }

    private function getCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        return $request;
    }
}
