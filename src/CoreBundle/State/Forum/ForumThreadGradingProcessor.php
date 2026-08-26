<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CForumThreadQualify;
use Chamilo\CourseBundle\Entity\CForumThreadQualifyLog;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<mixed, JsonResponse>
 */
final class ForumThreadGradingProcessor implements ProcessorInterface
{
    use ForumActionStateHelperTrait;
    use ForumGradebookGuardTrait;
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumThreadRepository $threadRepository,
        private readonly Security $security,
        private readonly GradebookLinkManager $gradebookLinkManager,
        private readonly CidReqHelper $cidReqHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $request = $this->getCurrentRequest();
        $operationName = (string) $operation->getName();

        return match ($operationName) {
            'update_forum_thread_grading' => $this->updateThreadGrading($request, $uriVariables),
            'save_forum_thread_score' => $this->saveThreadScore($request, $uriVariables),
            default => throw new BadRequestHttpException('Unsupported forum grading operation.'),
        };
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function updateThreadGrading(Request $request, array $uriVariables): JsonResponse
    {
        $this->assertTeacher($this->security);
        $payload = $this->getJsonData($request);

        $thread = $this->getThread($uriVariables, $request);
        $this->assertEditableForumResource($thread->getResourceNode(), $this->security);

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertForumThreadNotLockedByGradebook($this->gradebookLinkManager, $course, $session, $thread);
        $enabled = $this->getBoolean($payload, 'enabled');

        if (!$enabled) {
            $thread
                ->setThreadTitleQualify('')
                ->setThreadQualifyMax(0)
                ->setThreadWeight(0)
                ->setThreadPeerQualify(false)
            ;
            $this->gradebookLinkManager->removeLinks(
                $course,
                $session,
                GradebookLinkResourceResolver::LINK_FORUM_THREAD,
                (int) $thread->getIid(),
            );

            $this->entityManager->persist($thread);
            $this->entityManager->flush();

            $this->registerForumEventLog('disable-thread-grading', 'thread', (string) $thread->getIid());

            return new JsonResponse([
                'threadId' => $thread->getIid(),
                'enabled' => false,
                'message' => 'Thread grading disabled.',
            ]);
        }

        $categoryId = $this->getRequiredInt($payload, 'categoryId');
        $this->gradebookLinkManager->requireCategory($course, $session, $categoryId, true);

        $maxScore = $this->getPositiveFloat($payload, 'maxScore');
        $weight = $this->getPositiveFloat($payload, 'weight');
        $title = $this->getOptionalText($payload, 'title', 250);
        if ('' === $title) {
            $title = $thread->getTitle();
        }

        $thread
            ->setThreadTitleQualify($title)
            ->setThreadQualifyMax($maxScore)
            ->setThreadWeight($weight)
            ->setThreadPeerQualify($this->getBoolean($payload, 'peerQualify'))
        ;

        $link = $this->gradebookLinkManager->upsertLink(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_FORUM_THREAD,
            (int) $thread->getIid(),
            $categoryId,
            $weight,
        );

        $this->entityManager->persist($thread);
        $this->entityManager->flush();

        $this->registerForumEventLog('update-thread-grading', 'thread', (string) $thread->getIid());

        return new JsonResponse([
            'threadId' => $thread->getIid(),
            'enabled' => true,
            'categoryId' => $link->getCategory()->getId(),
            'title' => $thread->getThreadTitleQualify(),
            'maxScore' => $thread->getThreadQualifyMax(),
            'weight' => $thread->getThreadWeight(),
            'peerQualify' => $thread->isThreadPeerQualify(),
            'message' => 'Thread grading updated.',
        ]);
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function saveThreadScore(Request $request, array $uriVariables): JsonResponse
    {
        $payload = $this->getJsonData($request);

        $thread = $this->getThread($uriVariables, $request);
        if ($thread->getThreadQualifyMax() <= 0) {
            throw new BadRequestHttpException('Thread grading is not enabled.');
        }

        $userId = $this->getRequiredInt($payload, 'userId');
        $score = $this->getScore($payload, $thread->getThreadQualifyMax());
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertSessionBelongsToCourse($session, $course);
        $qualifyUser = $this->security->getUser();
        if (!$qualifyUser instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        $this->assertForumThreadNotLockedByGradebook($this->gradebookLinkManager, $course, $session, $thread);

        $canManage = $this->isAllowedToEditHelper->check(coach: true);
        if ($canManage) {
            $this->assertEditableForumResource($thread->getResourceNode(), $this->security);
        } elseif (!$this->canScoreThreadAsPeer($thread, $course, $session, $user, $qualifyUser)) {
            throw new AccessDeniedHttpException('You are not allowed to grade this forum thread.');
        }

        $qualification = $this->findThreadQualification(
            $thread,
            $user,
            $thread->isThreadPeerQualify() ? $qualifyUser : null,
        );
        $now = new DateTime('now', new DateTimeZone('UTC'));

        if (!$qualification instanceof CForumThreadQualify) {
            $qualification = new CForumThreadQualify();
            $qualification
                ->setCId((int) $course->getId())
                ->setThread($thread)
                ->setUser($user)
                ->setQualifyUser($qualifyUser)
            ;
        } else {
            $this->storeScoreHistory($qualification, $session);
        }

        $qualification
            ->setQualify($score)
            ->setQualifyTime($now)
        ;

        $this->entityManager->persist($qualification);
        $this->entityManager->flush();

        $this->registerForumEventLog('save-thread-score', 'thread', (string) $thread->getIid());

        return new JsonResponse([
            'threadId' => $thread->getIid(),
            'userId' => $user->getId(),
            'score' => $qualification->getQualify(),
            'message' => 'Thread score saved.',
        ]);
    }

    private function canScoreThreadAsPeer(
        CForumThread $thread,
        Course $course,
        ?Session $session,
        User $targetUser,
        User $qualifyUser,
    ): bool {
        if (!$thread->isThreadPeerQualify()) {
            return false;
        }

        if ($targetUser->getId() === $qualifyUser->getId()) {
            return false;
        }

        $forum = $thread->getForum();
        if (!$forum instanceof CForum) {
            return false;
        }

        if (!$this->isStudentSubscribed($targetUser, $course, $session)
            || !$this->isStudentSubscribed($qualifyUser, $course, $session)
        ) {
            return false;
        }

        return $this->hasUserPostedInThread($thread, $targetUser);
    }

    private function hasUserPostedInThread(CForumThread $thread, User $user): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('IDENTITY(p.user) = :userId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('userId', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count > 0;
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

    private function findThreadQualification(
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

    private function storeScoreHistory(CForumThreadQualify $qualification, ?Session $session): void
    {
        $thread = $qualification->getThread();
        $user = $qualification->getUser();
        $qualifyUser = $qualification->getQualifyUser();

        $log = (new CForumThreadQualifyLog())
            ->setCId($qualification->getCId())
            ->setThreadId((int) $thread->getIid())
            ->setUserId((int) $user->getId())
            ->setQualify($qualification->getQualify())
            ->setQualifyUserId((int) $qualifyUser->getId())
            ->setQualifyTime($qualification->getQualifyTime() ?? new DateTime('now', new DateTimeZone('UTC')))
        ;

        if ($session instanceof Session) {
            $log->setSessionId((int) $session->getId());
        }

        $this->entityManager->persist($log);
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function getThread(array $uriVariables, Request $request): CForumThread
    {
        $threadId = $uriVariables['threadId'] ?? $request->attributes->get('threadId');
        if (!is_numeric($threadId) || (int) $threadId <= 0) {
            throw new BadRequestHttpException('Invalid thread id.');
        }

        $thread = $this->threadRepository->find((int) $threadId);
        if (!$thread instanceof CForumThread) {
            throw new NotFoundHttpException('Forum thread not found.');
        }

        return $thread;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getPositiveFloat(array $payload, string $key): float
    {
        $value = (float) ($payload[$key] ?? 0);
        if ($value <= 0) {
            throw new BadRequestHttpException('Invalid '.$key.'.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getScore(array $payload, float $maxScore): float
    {
        $score = (float) ($payload['score'] ?? -1);
        if ($score < 0 || $score > $maxScore) {
            throw new BadRequestHttpException('Grade cannot exceed max score.');
        }

        return $score;
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
