<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumThreadGrading;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadQualify;
use Chamilo\CourseBundle\Entity\CForumThreadQualifyLog;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<ForumThreadGrading>
 */
final class ForumThreadGradingProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    private const LINK_FORUM_THREAD = 5;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumThreadRepository $threadRepository,
        private readonly GradeBookCategoryRepository $gradebookCategoryRepository,
        private readonly Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumThreadGrading
    {
        $request = $this->requestStack->getCurrentRequest();
        $threadId = $this->resolveThreadId($uriVariables, $request);
        if (null === $request) {
            return ForumThreadGrading::fromArray($threadId, []);
        }

        $thread = $this->threadRepository->find($threadId);
        if (!$thread instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        $forum = $thread->getForum();
        if (!$forum instanceof CForum) {
            throw new NotFoundHttpException('Forum not found.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $canManage = $this->canManageForumsInCurrentView($this->security, $request);
        $canPeerGrade = $this->canPeerGradeThread($thread, $forum, $course, $session, $currentUser);
        if (!$canManage && !$canPeerGrade) {
            throw new AccessDeniedHttpException('You are not allowed to grade this forum thread.');
        }

        $link = $this->findGradebookLink($course, $thread);

        return ForumThreadGrading::fromArray($threadId, [
            'enabled' => $link instanceof GradebookLink || $thread->getThreadQualifyMax() > 0,
            'categoryId' => $link?->getCategory()->getId(),
            'title' => $thread->getThreadTitleQualify() ?: $thread->getTitle(),
            'maxScore' => $thread->getThreadQualifyMax(),
            'weight' => $thread->getThreadWeight(),
            'peerQualify' => $thread->isThreadPeerQualify(),
            'canManage' => $canManage,
            'canPeerGrade' => $canPeerGrade,
            'categories' => $canManage ? $this->getGradebookCategories((int) $course->getId(), $session?->getId()) : [],
            'students' => $this->getThreadStudents($thread, $course, $session, $canManage, $currentUser),
        ]);
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function resolveThreadId(array $uriVariables, ?Request $request): int
    {
        $threadId = $uriVariables['threadId'] ?? $request?->attributes->get('threadId');

        if (is_numeric($threadId)) {
            return (int) $threadId;
        }

        return 0;
    }

    private function canPeerGradeThread(
        CForumThread $thread,
        CForum $forum,
        Course $course,
        ?Session $session,
        User $currentUser,
    ): bool {
        if (!$thread->isThreadPeerQualify() || $thread->getThreadQualifyMax() <= 0) {
            return false;
        }

        if (!$this->isStudentSubscribed($currentUser, $course, $session)) {
            return false;
        }

        if (!$this->isForumResourceVisible($forum, $course, $session)) {
            return false;
        }

        if (!$this->isForumResourceVisible($thread, $course, $session)) {
            return false;
        }

        return true;
    }

    private function findGradebookLink(Course $course, CForumThread $thread): ?GradebookLink
    {
        return $this->entityManager->getRepository(GradebookLink::class)->findOneBy([
            'course' => $course,
            'type' => self::LINK_FORUM_THREAD,
            'refId' => (int) $thread->getIid(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getGradebookCategories(int $courseId, ?int $sessionId): array
    {
        $this->gradebookCategoryRepository->createDefaultCategory($courseId, $sessionId);

        $categories = [];
        foreach ($this->gradebookCategoryRepository->getCategoriesForCourse($courseId, $sessionId) as $category) {
            if (!$category instanceof GradebookCategory) {
                continue;
            }

            $categories[] = [
                'id' => $category->getId(),
                'title' => $category->getParent() instanceof GradebookCategory
                    ? $category->getParent()->getTitle().' / '.$category->getTitle()
                    : $category->getTitle(),
            ];
        }

        return $categories;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getThreadStudents(
        CForumThread $thread,
        Course $course,
        ?Session $session,
        bool $canManage,
        User $currentUser,
    ): array {
        $users = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT u')
            ->from(CForumPost::class, 'p')
            ->innerJoin('p.user', 'u')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $students = [];
        foreach ($users as $user) {
            if (!$user instanceof User || !$this->isStudentSubscribed($user, $course, $session)) {
                continue;
            }

            if (!$canManage && $user->getId() === $currentUser->getId()) {
                continue;
            }

            $qualification = $this->findLatestQualification($thread, $user, $canManage ? null : $currentUser);
            $score = $qualification instanceof CForumThreadQualify ? $qualification->getQualify() : null;
            $students[] = [
                'userId' => $user->getId(),
                'fullName' => $user->getFullName(),
                'username' => $user->getUsername(),
                'score' => $score,
                'qualified' => null !== $score,
                'history' => $canManage ? $this->getScoreHistory($thread, $user) : [],
            ];
        }

        return $students;
    }

    private function findLatestQualification(
        CForumThread $thread,
        User $user,
        ?User $qualifyUser,
    ): ?CForumThreadQualify {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('q')
            ->from(CForumThreadQualify::class, 'q')
            ->andWhere('IDENTITY(q.thread) = :threadId')
            ->andWhere('IDENTITY(q.user) = :userId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('userId', $user->getId(), Types::INTEGER)
            ->orderBy('q.qualifyTime', 'DESC')
            ->setMaxResults(1)
        ;

        if ($qualifyUser instanceof User) {
            $queryBuilder
                ->andWhere('IDENTITY(q.qualifyUser) = :qualifyUserId')
                ->setParameter('qualifyUserId', $qualifyUser->getId(), Types::INTEGER)
            ;
        }

        $qualification = $queryBuilder->getQuery()->getOneOrNullResult();

        return $qualification instanceof CForumThreadQualify ? $qualification : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getScoreHistory(CForumThread $thread, User $user): array
    {
        $logs = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(CForumThreadQualifyLog::class, 'l')
            ->andWhere('l.threadId = :threadId')
            ->andWhere('l.userId = :userId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('userId', $user->getId(), Types::INTEGER)
            ->orderBy('l.qualifyTime', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult()
        ;

        $history = [];
        foreach ($logs as $log) {
            if (!$log instanceof CForumThreadQualifyLog) {
                continue;
            }

            $qualifyUser = null !== $log->getQualifyUserId()
                ? $this->entityManager->getRepository(User::class)->find($log->getQualifyUserId())
                : null;

            $history[] = [
                'score' => $log->getQualify(),
                'qualifyUserId' => $log->getQualifyUserId(),
                'qualifyUserName' => $qualifyUser instanceof User ? $qualifyUser->getFullName() : '',
                'date' => $log->getQualifyTime() instanceof DateTimeInterface
                    ? $log->getQualifyTime()->format(DateTimeInterface::ATOM)
                    : null,
            ];
        }

        return $history;
    }

    private function isStudentSubscribed(User $user, Course $course, ?Session $session): bool
    {
        if ($session instanceof Session) {
            return null !== $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy([
                'user' => $user,
                'course' => $course,
                'session' => $session,
                'status' => Session::STUDENT,
            ]);
        }

        return null !== $this->entityManager->getRepository(CourseRelUser::class)->findOneBy([
            'user' => $user,
            'course' => $course,
            'status' => CourseRelUser::STUDENT,
        ]);
    }
}
