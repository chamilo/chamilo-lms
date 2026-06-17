<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
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

use const JSON_THROW_ON_ERROR;

/**
 * Handles forum thread create/update/delete operations.
 *
 * @implements ProcessorInterface<mixed, JsonResponse>
 */
final class ForumThreadProcessor implements ProcessorInterface
{
    use ForumActionStateHelperTrait;
    use ForumGradebookGuardTrait;
    use ForumNotificationHelperTrait;
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;
    private const LINK_FORUM_THREAD = 5;

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
        private readonly SettingsManager $settingsManager,
        private readonly MessageHelper $messageHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->getCurrentRequest();
        $operationName = (string) $operation->getName();

        return match ($operationName) {
            'create_forum_thread' => $this->createThread($request),
            'update_forum_thread' => $this->updateThread($request, $data),
            'toggle_forum_thread_lock' => $this->toggleThreadLock($request, $data),
            'toggle_forum_thread_sticky' => $this->toggleThreadSticky($request, $data),
            'toggle_forum_thread_visibility' => $this->toggleThreadVisibility($request, $data),
            'move_forum_thread' => $this->moveThread($request, $data),
            'toggle_forum_thread_subscription' => $this->toggleThreadSubscription($request, $data),
            'delete_forum_thread' => $this->deleteThread($request, $data),
            default => throw new BadRequestHttpException('Unsupported forum thread operation.'),
        };
    }

    private function createThread(Request $request): JsonResponse
    {
        $data = $this->getRequestData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $data['csrfToken'] ?? null);

        $forum = $this->forumRepository->find($this->getRequiredInt($data, 'forumId'));
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $resourceNode = $forum->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum.');
        }

        $uploadedFiles = $this->getUploadedFiles($request);
        $this->assertAttachmentsAllowed($forum, $uploadedFiles);

        $isTeacher = $this->isTeacher($this->security);
        $this->assertThreadCreationAllowed($forum, $isTeacher);

        $title = $this->getRequiredText($data, 'title', 250);
        $text = $this->getRequiredHtmlText($data, 'text');
        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $this->assertResourceNodeInForumContext(
            $forum->getResourceNode(),
            $course,
            $session,
            $group,
            'The selected forum does not belong to this context.',
        );
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $visible = !$this->requiresModeration($forum, $isTeacher);
        $status = $visible ? CForumPost::STATUS_VALIDATED : CForumPost::STATUS_WAITING_MODERATION;

        $thread = (new CForumThread())
            ->setTitle($title)
            ->setForum($forum)
            ->setUser($user)
            ->setThreadDate($now)
            ->setThreadSticky($this->getBoolean($data, 'threadSticky'))
            ->setThreadTitleQualify('')
            ->setThreadQualifyMax(0)
            ->setThreadWeight(0)
            ->setThreadPeerQualify(false)
            ->setParent($forum)
            ->addCourseLink($course, $session, $group)
        ;

        if ($isTeacher) {
            $this->applyThreadGradingSettings($thread, $course, $data);
        }

        $this->threadRepository->create($thread);
        if ($isTeacher) {
            $this->syncThreadGradebookLink($thread, $course, $data);
        }

        $post = (new CForumPost())
            ->setTitle($title)
            ->setPostText($text)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($user)
            ->setPostDate($now)
            ->setPostNotification($this->shouldStorePostNotification($this->entityManager, $course, $this->getBoolean($data, 'postNotification')))
            ->setVisible($visible)
            ->setStatus($status)
            ->setParent($thread)
            ->addCourseLink($course, $session, $group)
        ;

        $this->postRepository->create($post);

        $attachments = $this->storeAttachments($uploadedFiles, $post, $course, $session, $group);

        $thread->setThreadLastPost($post);
        $forum->setForumLastPost($post);
        $forum->setForumThreads(($forum->getForumThreads() ?? 0) + 1);
        $forum->setForumPosts(($forum->getForumPosts() ?? 0) + 1);

        $this->entityManager->persist($thread);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        if ($post->getPostNotification()) {
            $this->setThreadSubscription($this->entityManager, $course, $user, (int) $thread->getIid(), true);
            $this->entityManager->flush();
        }

        if ($visible) {
            $this->sendForumSubscriptionNotifications($this->entityManager, $request, $course, $session, $forum, $thread, $post, $user, $this->messageHelper);
        }

        $this->registerForumEventLog('new-thread', 'thread', (string) $thread->getIid());

        return new JsonResponse([
            'threadId' => $thread->getIid(),
            'postId' => $post->getIid(),
            'attachments' => $attachments,
            'requiresApproval' => !$visible,
            'message' => $visible ? 'Thread created.' : 'Your message has to be approved before people can view it.',
        ], Response::HTTP_CREATED);
    }

    private function updateThread(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $data->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $this->assertEditableResource($data->getResourceNode(), 'edit');
        $this->assertEditableResource($forum->getResourceNode(), 'edit');

        $course = $this->getCourse($this->entityManager, $request);
        $this->assertForumThreadNotLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $data);

        $data->setTitle($this->getRequiredText($payload, 'title', 250));
        if (\array_key_exists('gradebookEnabled', $payload)) {
            $this->applyThreadGradingSettings($data, $course, $payload);
            $this->syncThreadGradebookLink($data, $course, $payload);
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->registerForumEventLog('edit-thread', 'thread', (string) $data->getIid());

        return new JsonResponse([
            'threadId' => $data->getIid(),
            'title' => $data->getTitle(),
            'message' => 'Thread updated.',
        ]);
    }

    private function deleteThread(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $data->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $this->assertEditableResource($data->getResourceNode(), 'delete');
        $this->assertEditableResource($forum->getResourceNode(), 'delete');

        $course = $this->getCourse($this->entityManager, $request);
        $this->assertForumThreadNotLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $data);

        $threadId = (int) $data->getIid();
        $postCount = $data->getPosts()->count();
        $forum->setForumThreads(max(0, (int) ($forum->getForumThreads() ?? 0) - 1));
        $forum->setForumPosts(max(0, (int) ($forum->getForumPosts() ?? 0) - $postCount));

        if ($forum->getForumLastPost()?->getThread()?->getIid() === $data->getIid()) {
            $forum->setForumLastPost(null);
        }

        $gradebookLink = $this->findThreadGradebookLink($data, $course);
        if ($gradebookLink instanceof GradebookLink) {
            $this->entityManager->remove($gradebookLink);
        }

        $this->threadRepository->delete($data);
        $this->entityManager->persist($forum);
        $this->entityManager->flush();

        $this->registerForumEventLog('delete-thread', 'thread', (string) $threadId);

        return new JsonResponse([
            'threadId' => $threadId,
            'deleted' => true,
            'message' => 'Thread deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toggleThreadLock(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $data->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);
        $course = $this->getCourse($this->entityManager, $request);
        $this->assertForumThreadNotLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $data);

        $data->setLocked(0 === $data->getLocked() ? 1 : 0);
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->registerForumEventLog(0 === $data->getLocked() ? 'open-thread' : 'close-thread', 'thread', (string) $data->getIid());

        return new JsonResponse([
            'threadId' => $data->getIid(),
            'locked' => $data->getLocked(),
            'message' => 0 === $data->getLocked() ? 'Thread opened.' : 'Thread closed.',
        ]);
    }

    private function toggleThreadSticky(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $data->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);
        $course = $this->getCourse($this->entityManager, $request);
        $this->assertForumThreadNotLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $data);

        $data->setThreadSticky(!$data->getThreadSticky());
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->registerForumEventLog($data->getThreadSticky() ? 'sticky-thread' : 'unsticky-thread', 'thread', (string) $data->getIid());

        return new JsonResponse([
            'threadId' => $data->getIid(),
            'threadSticky' => $data->getThreadSticky(),
            'message' => $data->getThreadSticky() ? 'Thread marked as sticky.' : 'Thread unmarked as sticky.',
        ]);
    }

    private function toggleThreadVisibility(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $data->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($forum->getResourceNode(), $this->security);

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $this->assertForumThreadNotLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $data);
        $targetVisible = $this->getTargetVisibility($payload, $data, $course, $session);
        $visible = $this->setForumResourceVisibility($data, $this->threadRepository, $course, $session, $targetVisible);
        $this->entityManager->flush();

        $this->registerForumEventLog($visible ? 'show-thread' : 'hide-thread', 'thread', (string) $data->getIid());

        return new JsonResponse([
            'threadId' => $data->getIid(),
            'visible' => $visible,
            'message' => $visible ? 'Thread shown.' : 'Thread hidden.',
        ]);
    }

    private function moveThread(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $sourceForum = $data->getForum();
        if (!$sourceForum instanceof CForum) {
            throw new NotFoundHttpException('Source forum not found.');
        }

        $targetForum = $this->forumRepository->find($this->getRequiredInt($payload, 'targetForumId'));
        if (!$targetForum instanceof CForum) {
            throw new NotFoundHttpException('Target forum not found.');
        }

        if ($sourceForum->getIid() === $targetForum->getIid()) {
            throw new BadRequestHttpException('The thread is already in the selected forum.');
        }

        $this->assertEditableForumResource($data->getResourceNode(), $this->security);
        $this->assertEditableForumResource($sourceForum->getResourceNode(), $this->security);
        $this->assertEditableForumResource($targetForum->getResourceNode(), $this->security);

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $this->assertForumBelongsToCurrentContext($targetForum, $course, $session, $group);
        $this->assertForumThreadNotLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $data);

        $postCount = $this->moveThreadPosts($data, $targetForum);
        $data->setForum($targetForum);
        $data->setParent($targetForum);
        $this->moveThreadResourceNode($data, $targetForum);
        $this->updateForumCounters($sourceForum, $targetForum, $postCount);
        $this->refreshForumLastPost($sourceForum);
        $this->refreshForumLastPost($targetForum);

        $this->entityManager->persist($data);
        $this->entityManager->persist($sourceForum);
        $this->entityManager->persist($targetForum);
        $this->entityManager->flush();

        $this->registerForumEventLog('move-thread', 'thread', (string) $data->getIid());

        return new JsonResponse([
            'threadId' => $data->getIid(),
            'sourceForumId' => $sourceForum->getIid(),
            'targetForumId' => $targetForum->getIid(),
            'message' => 'Thread moved.',
        ]);
    }

    private function toggleThreadSubscription(Request $request, mixed $data): JsonResponse
    {
        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);

        $course = $this->getCourse($this->entityManager, $request);
        if ($this->areForumPostNotificationsHidden($this->entityManager, $course)) {
            throw new AccessDeniedHttpException('Forum notifications are disabled for this course.');
        }

        if (!$data instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $data->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $resourceNode = $data->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('VIEW', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this thread.');
        }

        $forumResourceNode = $forum->getResourceNode();
        if (null === $forumResourceNode || !$this->security->isGranted('VIEW', $forumResourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to access this forum.');
        }

        $user = $this->getCurrentForumUser($this->security);
        $threadId = (int) $data->getIid();
        $currentState = $this->isSubscribedToThread($this->entityManager, $course, $user, $threadId);
        $subscribed = $this->setThreadSubscription(
            $this->entityManager,
            $course,
            $user,
            $threadId,
            $this->getRequestedSubscriptionState($payload, $currentState),
        );

        $this->entityManager->flush();

        return new JsonResponse([
            'threadId' => $threadId,
            'subscribed' => $subscribed,
            'message' => $subscribed ? 'Thread notifications enabled.' : 'Thread notifications disabled.',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyThreadGradingSettings(CForumThread $thread, Course $course, array $data): void
    {
        if (!$this->getBoolean($data, 'gradebookEnabled')) {
            $thread
                ->setThreadTitleQualify('')
                ->setThreadQualifyMax(0)
                ->setThreadWeight(0)
                ->setThreadPeerQualify(false)
            ;

            return;
        }

        $categoryId = $this->getRequiredInt($data, 'gradebookCategoryId');
        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory || $category->getCourse()->getId() !== $course->getId()) {
            throw new BadRequestHttpException('Invalid gradebook category.');
        }

        $maxScore = $this->getPositiveFloat($data, 'threadQualifyMax');
        $weight = $this->getPositiveFloat($data, 'threadWeight');
        $title = $this->getOptionalText($data, 'threadTitleQualify', 250);
        if ('' === $title) {
            $title = $thread->getTitle();
        }

        $thread
            ->setThreadTitleQualify($title)
            ->setThreadQualifyMax($maxScore)
            ->setThreadWeight($weight)
            ->setThreadPeerQualify($this->getBoolean($data, 'threadPeerQualify'))
        ;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function syncThreadGradebookLink(CForumThread $thread, Course $course, array $data): void
    {
        $existingLink = $this->findThreadGradebookLink($thread, $course);
        if (!$this->getBoolean($data, 'gradebookEnabled')) {
            if ($existingLink instanceof GradebookLink) {
                $this->entityManager->remove($existingLink);
            }

            return;
        }

        $categoryId = $this->getRequiredInt($data, 'gradebookCategoryId');
        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory || $category->getCourse()->getId() !== $course->getId()) {
            throw new BadRequestHttpException('Invalid gradebook category.');
        }

        $link = $existingLink ?? new GradebookLink();
        $link
            ->setType(self::LINK_FORUM_THREAD)
            ->setRefId((int) $thread->getIid())
            ->setCourse($course)
            ->setCategory($category)
            ->setWeight($thread->getThreadWeight())
            ->setVisible(1)
            ->setLocked(0)
        ;

        $this->entityManager->persist($link);
    }

    private function findThreadGradebookLink(CForumThread $thread, Course $course): ?GradebookLink
    {
        if (null === $thread->getIid()) {
            return null;
        }

        return $this->entityManager->getRepository(GradebookLink::class)->findOneBy([
            'course' => $course,
            'type' => self::LINK_FORUM_THREAD,
            'refId' => (int) $thread->getIid(),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getPositiveFloat(array $data, string $key): float
    {
        $value = (float) ($data[$key] ?? 0);
        if ($value <= 0) {
            throw new BadRequestHttpException('Invalid '.$key.'.');
        }

        return $value;
    }

    private function assertForumBelongsToCurrentContext(CForum $forum, Course $course, ?Session $session, ?CGroup $group): void
    {
        $resourceNode = $forum->getResourceNode();
        $link = $resourceNode?->getResourceLinkByContext($course, $session, $group);
        $link ??= $resourceNode?->getResourceLinkByContext($course, $session);
        $link ??= $resourceNode?->getResourceLinkByContext($course);

        if (null === $link) {
            throw new AccessDeniedHttpException('The target forum does not belong to this context.');
        }
    }

    private function moveThreadResourceNode(CForumThread $thread, CForum $targetForum): void
    {
        $threadResourceNode = $thread->getResourceNode();
        $targetResourceNode = $targetForum->getResourceNode();

        if (!$threadResourceNode instanceof ResourceNode || !$targetResourceNode instanceof ResourceNode) {
            return;
        }

        $threadResourceNode->setParent($targetResourceNode);
    }

    private function moveThreadPosts(CForumThread $thread, CForum $targetForum): int
    {
        $posts = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $postCount = 0;
        foreach ($posts as $post) {
            if (!$post instanceof CForumPost) {
                continue;
            }

            $post->setForum($targetForum);
            $this->entityManager->persist($post);
            ++$postCount;
        }

        return $postCount;
    }

    private function refreshForumLastPost(CForum $forum): void
    {
        $post = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.forum) = :forumId')
            ->setParameter('forumId', $forum->getIid(), Types::INTEGER)
            ->orderBy('p.postDate', 'DESC')
            ->addOrderBy('p.iid', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $forum->setForumLastPost($post instanceof CForumPost ? $post : null);
    }

    private function updateForumCounters(CForum $sourceForum, CForum $targetForum, int $postCount): void
    {
        $sourceForum->setForumThreads(max(0, (int) ($sourceForum->getForumThreads() ?? 0) - 1));
        $sourceForum->setForumPosts(max(0, (int) ($sourceForum->getForumPosts() ?? 0) - $postCount));
        $targetForum->setForumThreads((int) ($targetForum->getForumThreads() ?? 0) + 1);
        $targetForum->setForumPosts((int) ($targetForum->getForumPosts() ?? 0) + $postCount);
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

    private function assertThreadCreationAllowed(CForum $forum, bool $isTeacher): void
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

        if (1 !== (int) $forum->getAllowNewThreads()) {
            throw new AccessDeniedHttpException('New threads are not allowed in this forum.');
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

    private function assertEditableResource(?ResourceNode $resourceNode, string $action): void
    {
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException(\sprintf('You are not allowed to %s this forum resource.', $action));
        }
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
