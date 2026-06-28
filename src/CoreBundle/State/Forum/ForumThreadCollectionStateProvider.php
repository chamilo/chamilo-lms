<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadsByForum;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumNotification;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeInterface;
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
    use ForumCourseSettingHelperTrait;
    use ForumGradebookGuardTrait;
    use ForumStateHelperTrait;

    /**
     * @var array<int, string>
     */
    private array $posterAvatarUrlByUserId = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumRepository $forumRepository,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly CourseAccessResolver $courseAccessResolver,
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
        $group = $this->getGroup($this->entityManager, $request);
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
        $showPosterAvatar = $this->arePosterImagesAllowed($course);
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
                $this->isForumThreadLockedByGradebook($this->entityManager, $this->settingsManager, $this->security, $course, $thread),
                $showPosterAvatar,
                $course,
                $session,
                $group,
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
        bool $lockedByGradebook,
        bool $showPosterAvatar,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $canMutate = $canManage && !$lockedByGradebook;
        $lastPost = $thread->getThreadLastPost();
        $lastPostUser = $lastPost instanceof CForumPost ? $lastPost->getUser() : null;
        $poster = $thread->getUser();
        $posterRole = $this->getPosterRole($poster, $course, $session, $group);
        $lastPosterRole = $this->getPosterRole($lastPostUser, $course, $session, $group);
        $posterAvatarUrl = $showPosterAvatar ? $this->getPosterAvatarUrl($poster) : '';
        $lastPosterAvatarUrl = $showPosterAvatar ? $this->getPosterAvatarUrl($lastPostUser) : '';
        $threadDate = $thread->getThreadDate();
        $lastPostDate = $lastPost instanceof CForumPost ? $lastPost->getPostDate() : null;

        return [
            '@id' => '/api/forum_threads/'.$thread->getIid(),
            '@type' => 'ForumThread',
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
            'threadReplies' => $thread->getThreadReplies(),
            'threadViews' => $thread->getThreadViews(),
            'threadSticky' => $thread->getThreadSticky(),
            'threadVisible' => $visible,
            'threadTitleQualify' => $thread->getThreadTitleQualify(),
            'threadQualifyMax' => $thread->getThreadQualifyMax(),
            'threadWeight' => $thread->getThreadWeight(),
            'threadPeerQualify' => $thread->isThreadPeerQualify(),
            'gradebookEnabled' => $thread->getThreadQualifyMax() > 0,
            'lockedByGradebook' => $lockedByGradebook,
            'gradebookLockedMessage' => $lockedByGradebook ? $this->getForumThreadGradebookLockedMessage() : '',
            'posterUserId' => $poster instanceof User ? (int) $poster->getId() : 0,
            'posterFullName' => $thread->getPosterFullName(),
            'posterAvatarUrl' => $posterAvatarUrl,
            'showPosterAvatar' => $showPosterAvatar && '' !== $posterAvatarUrl,
            'posterRole' => $posterRole,
            'posterRoleLabel' => $this->getPosterRoleLabel($posterRole),
            'posterIsTeacher' => 'teacher' === $posterRole,
            'lastPostId' => $lastPost instanceof CForumPost ? $lastPost->getIid() : null,
            'lastPostTitle' => $lastPost instanceof CForumPost ? $lastPost->getTitle() : '',
            'lastPostText' => $lastPost instanceof CForumPost ? $lastPost->getPostText() : '',
            'lastPostDate' => $this->formatDate($lastPostDate),
            'lastPostCreatedAt' => $this->formatDate($lastPostDate),
            'lastPostDateIso' => $this->formatDateIso($lastPostDate),
            'lastPostCreatedAtIso' => $this->formatDateIso($lastPostDate),
            'lastPostDateTimestamp' => $lastPostDate?->getTimestamp(),
            'lastPostRelativeTime' => $this->formatRelativeTimeLabel($lastPostDate),
            'lastPosterUserId' => $lastPostUser instanceof User ? (int) $lastPostUser->getId() : 0,
            'lastPosterFullName' => $lastPost instanceof CForumPost ? $lastPost->getPosterFullName() : '',
            'lastPosterAvatarUrl' => $lastPosterAvatarUrl,
            'showLastPosterAvatar' => $showPosterAvatar && '' !== $lastPosterAvatarUrl,
            'lastPosterRole' => $lastPosterRole,
            'lastPosterRoleLabel' => $this->getPosterRoleLabel($lastPosterRole),
            'lastPosterIsTeacher' => 'teacher' === $lastPosterRole,
            'pendingPostCount' => $pendingPostCount,
            'subscribed' => $subscribed,
            'canSubscribe' => $canSubscribe,
            'canEdit' => $canMutate,
            'canDelete' => $canMutate,
            'canToggleLock' => $canMutate,
            'canToggleSticky' => $canMutate,
            'canToggleVisibility' => $canMutate,
        ];
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
