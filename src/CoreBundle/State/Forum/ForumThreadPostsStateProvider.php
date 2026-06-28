<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadPosts;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeInterface;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
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
    use ForumCourseSettingHelperTrait;
    use ForumExtraFieldHelperTrait;
    use ForumGradebookGuardTrait;
    use ForumStateHelperTrait;

    /**
     * @var array<int, string>
     */
    private array $posterAvatarUrlByUserId = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumThreadRepository $threadRepository,
        private readonly CForumAttachmentRepository $attachmentRepository,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly CourseAccessResolver $courseAccessResolver,
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
        $group = $this->getGroup($this->entityManager, $request);
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

        $showPosterAvatar = $this->arePosterImagesAllowed($course);
        $lockedByGradebook = $this->isForumThreadLockedByGradebook(
            $this->entityManager,
            $this->settingsManager,
            $this->security,
            $course,
            $thread,
        );

        return [
            'forum' => $this->serializeForum($forum),
            'thread' => $this->serializeThread(
                $thread,
                $course,
                $session,
                $canManage,
                $canSubscribe && $this->isSubscribedToThread($course, $user, (int) $thread->getIid()),
                $canSubscribe,
                $lockedByGradebook,
                $group,
            ),
            'canReply' => $this->canReply($forum, $thread),
            'canManageThread' => $canManage,
            'posts' => array_map(
                fn (CForumPost $post): array => $this->serializePost(
                    $post,
                    $forum,
                    $thread,
                    $canManage,
                    $showPosterAvatar,
                    $lockedByGradebook,
                    $course,
                    $session,
                    $group,
                ),
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
        return $this->isCourseSettingEnabled($this->entityManager, $course, 'hide_forum_notifications');
    }

    private function arePosterImagesAllowed(Course $course): bool
    {
        $value = $this->getCourseSettingValue($this->entityManager, $course, 'allow_user_image_forum');
        if (null === $value || '' === trim((string) $value)) {
            return true;
        }

        return $this->isTruthyForumCourseSettingValue($value);
    }

    private function shouldHideForumPostRevisionLanguage(): bool
    {
        return $this->isTruthySetting(
            $this->settingsManager->getSetting('forum.hide_forum_post_revision_language', true),
        );
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
        bool $lockedByGradebook,
        ?CGroup $group,
    ): array {
        $visible = $this->isForumResourceVisible($thread, $course, $session);
        $canMutate = $canManage && !$lockedByGradebook;
        $poster = $thread->getUser();
        $posterRole = $this->getPosterRole($poster, $course, $session, $group);
        $threadDate = $thread->getThreadDate();

        return [
            'iid' => $thread->getIid(),
            'title' => $thread->getTitle(),
            'locked' => $thread->getLocked(),
            'threadDate' => $this->formatDate($threadDate),
            'createdAt' => $this->formatDate($threadDate),
            'date' => $this->formatDate($threadDate),
            'threadDateIso' => $this->formatDateIso($threadDate),
            'createdAtIso' => $this->formatDateIso($threadDate),
            'threadDateTimestamp' => $threadDate?->getTimestamp(),
            'threadRelativeTime' => $this->formatRelativeTimeLabel($threadDate),
            'threadSticky' => $thread->getThreadSticky(),
            'threadVisible' => $visible,
            'threadReplies' => $thread->getThreadReplies(),
            'posterUserId' => $poster instanceof User ? (int) $poster->getId() : 0,
            'posterFullName' => $thread->getPosterFullName(),
            'posterRole' => $posterRole,
            'posterRoleLabel' => $this->getPosterRoleLabel($posterRole),
            'posterIsTeacher' => 'teacher' === $posterRole,
            'subscribed' => $subscribed,
            'canSubscribe' => $canSubscribe,
            'lockedByGradebook' => $lockedByGradebook,
            'gradebookLockedMessage' => $lockedByGradebook ? $this->getForumThreadGradebookLockedMessage() : '',
            'canEdit' => $canMutate,
            'canDelete' => $canMutate,
            'canToggleLock' => $canMutate,
            'canToggleSticky' => $canMutate,
            'canToggleVisibility' => $canMutate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePost(
        CForumPost $post,
        CForum $forum,
        CForumThread $thread,
        bool $canManage,
        bool $showPosterAvatar,
        bool $lockedByGradebook,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $canEdit = !$lockedByGradebook && $this->canEditPost($post, $forum, $thread, $canManage);
        $canModerate = $canManage && !$lockedByGradebook;
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
        $revisionLanguage = $this->shouldHideForumPostRevisionLanguage() ? '' : $this->getPostRevisionLanguage($post);
        $poster = $post->getUser();
        $posterRole = $this->getPosterRole($poster, $course, $session, $group);
        $posterAvatarUrl = $showPosterAvatar ? $this->getPosterAvatarUrl($poster) : '';
        $postDate = $post->getPostDate();

        return [
            'iid' => $post->getIid(),
            'title' => $post->getTitle(),
            'postText' => $post->getPostText(),
            'postDate' => $this->formatDate($postDate),
            'createdAt' => $this->formatDate($postDate),
            'date' => $this->formatDate($postDate),
            'sentAt' => $this->formatDate($postDate),
            'postDateIso' => $this->formatDateIso($postDate),
            'createdAtIso' => $this->formatDateIso($postDate),
            'sentAtIso' => $this->formatDateIso($postDate),
            'postDateTimestamp' => $postDate?->getTimestamp(),
            'createdAtTimestamp' => $postDate?->getTimestamp(),
            'postRelativeTime' => $this->formatRelativeTimeLabel($postDate),
            'postParentId' => $post->getPostParent()?->getIid(),
            'visible' => $post->getVisible(),
            'status' => $status,
            'statusLabel' => $this->getPostStatusLabel($status),
            'posterUserId' => $poster instanceof User ? (int) $poster->getId() : 0,
            'posterFullName' => $post->getPosterFullName(),
            'posterAvatarUrl' => $posterAvatarUrl,
            'showPosterAvatar' => $showPosterAvatar && '' !== $posterAvatarUrl,
            'posterRole' => $posterRole,
            'posterRoleLabel' => $this->getPosterRoleLabel($posterRole),
            'posterIsTeacher' => 'teacher' === $posterRole,
            'canEdit' => $canEdit,
            'canDelete' => $canEdit,
            'canApprove' => $canModerate && CForumPost::STATUS_VALIDATED !== $status,
            'canReject' => $canModerate && CForumPost::STATUS_REJECTED !== $status,
            'canToggleVisibility' => $canModerate,
            'canReplyToPost' => $this->canReply($forum, $thread),
            'canQuote' => $this->canReply($forum, $thread),
            'canMove' => $canModerate && !$this->isFirstPost($post, $thread),
            'revisionRequested' => $revisionRequested,
            'revisionLanguage' => $revisionLanguage,
            'canAskRevision' => !$lockedByGradebook && $isAuthor && $this->areForumPostRevisionsEnabled(),
            'canGiveRevision' => !$isAuthor && $revisionRequested && $this->canReply($forum, $thread),
            'canReport' => $this->isReportAvailableForCurrentRequest(),
            'attachments' => $attachments,
        ];
    }

    private function areForumPostRevisionsEnabled(): bool
    {
        return $this->isTruthySetting(
            $this->settingsManager->getSetting('forum.allow_forum_post_revisions', true),
        );
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
    private function formatDateIso(?DateTimeInterface $date): string
    {
        if (!$date instanceof DateTimeInterface) {
            return '';
        }

        return $date->format(DateTimeInterface::ATOM);
    }

    private function formatRelativeTimeLabel(?DateTimeInterface $date): string
    {
        if (!$date instanceof DateTimeInterface) {
            return '';
        }

        $diffInSeconds = $date->getTimestamp() - time();
        $units = [
            ['unit' => 'year', 'seconds' => 31536000, 'en' => ['year', 'years'], 'es' => ['año', 'años']],
            ['unit' => 'month', 'seconds' => 2592000, 'en' => ['month', 'months'], 'es' => ['mes', 'meses']],
            ['unit' => 'week', 'seconds' => 604800, 'en' => ['week', 'weeks'], 'es' => ['semana', 'semanas']],
            ['unit' => 'day', 'seconds' => 86400, 'en' => ['day', 'days'], 'es' => ['día', 'días']],
            ['unit' => 'hour', 'seconds' => 3600, 'en' => ['hour', 'hours'], 'es' => ['hora', 'horas']],
            ['unit' => 'minute', 'seconds' => 60, 'en' => ['minute', 'minutes'], 'es' => ['minuto', 'minutos']],
            ['unit' => 'second', 'seconds' => 1, 'en' => ['second', 'seconds'], 'es' => ['segundo', 'segundos']],
        ];

        $absoluteDiff = abs($diffInSeconds);
        $locale = strtolower((string) ($this->requestStack->getCurrentRequest()?->getLocale() ?? ''));
        if (45 > $absoluteDiff) {
            return str_starts_with($locale, 'es') ? 'ahora' : 'just now';
        }

        $selected = $units[count($units) - 1];
        foreach ($units as $unit) {
            if ($absoluteDiff >= $unit['seconds']) {
                $selected = $unit;
                break;
            }
        }

        $amount = (int) round($diffInSeconds / $selected['seconds']);
        if (0 === $amount) {
            $amount = 0 > $diffInSeconds ? -1 : 1;
        }

        $absoluteAmount = abs($amount);
        if (str_starts_with($locale, 'es')) {
            $unit = $selected['es'][1 === $absoluteAmount ? 0 : 1];

            return 0 > $amount ? 'hace '.$absoluteAmount.' '.$unit : 'en '.$absoluteAmount.' '.$unit;
        }

        $unit = $selected['en'][1 === $absoluteAmount ? 0 : 1];

        return 0 > $amount ? $absoluteAmount.' '.$unit.' ago' : 'in '.$absoluteAmount.' '.$unit;
    }

    private function getPosterAvatarUrl(?User $user): string
    {
        if (!$user instanceof User) {
            return '';
        }

        $userId = (int) $user->getId();
        if (isset($this->posterAvatarUrlByUserId[$userId])) {
            return $this->posterAvatarUrlByUserId[$userId];
        }

        $illustrationUrl = trim((string) ($user->illustrationUrl ?? ''));
        if ('' === $illustrationUrl) {
            $illustrationUrl = trim((string) $this->illustrationRepository->getIllustrationUrl($user));
        }

        if ('' !== $illustrationUrl) {
            $this->posterAvatarUrlByUserId[$userId] = $this->normalizeAvatarUrl($illustrationUrl);

            return $this->posterAvatarUrlByUserId[$userId];
        }

        $this->posterAvatarUrlByUserId[$userId] = '';

        return '';
    }

    private function normalizeAvatarUrl(string $avatarUrl): string
    {
        if (str_contains($avatarUrl, 'w=')) {
            return $avatarUrl;
        }

        return $avatarUrl.(str_contains($avatarUrl, '?') ? '&' : '?').'w=64';
    }


    private function getPosterRole(?User $user, Course $course, ?Session $session, ?CGroup $group): string
    {
        if (!$user instanceof User) {
            return '';
        }

        if ($this->isPosterTeacher($user, $course, $session, $group)) {
            return 'teacher';
        }

        return 'student';
    }

    private function isPosterTeacher(User $user, Course $course, ?Session $session, ?CGroup $group): bool
    {
        if ($group instanceof CGroup) {
            $groupRoles = $this->courseAccessResolver->resolveGroupRoles($user, $course, $group);
            if (\in_array(ResourceNodeVoter::ROLE_CURRENT_COURSE_GROUP_TEACHER, $groupRoles, true)) {
                return true;
            }
        }

        $courseRoles = $this->courseAccessResolver->resolveCourseRoles($user, $course, $session);

        return \in_array(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER, $courseRoles, true)
            || \in_array(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER, $courseRoles, true);
    }

    private function getPosterRoleLabel(string $role): string
    {
        return match ($role) {
            'teacher' => 'Teacher',
            'student' => 'Student',
            default => '',
        };
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
        return $this->getForumExtraFieldValue(
            $this->extraFieldValuesRepository,
            $itemType,
            $itemId,
            $variable,
        );
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
