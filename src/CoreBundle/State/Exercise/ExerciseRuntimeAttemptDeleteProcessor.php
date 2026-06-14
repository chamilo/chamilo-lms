<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeAttemptDelete;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Deletes a learner attempt from the migrated exercise report.
 *
 * @implements ProcessorInterface<ExerciseRuntimeAttemptDelete, ExerciseRuntimeAttemptDelete>
 */
final readonly class ExerciseRuntimeAttemptDeleteProcessor implements ProcessorInterface
{
    private const LINK_TYPE_EXERCISE = 1;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeAttemptDelete
    {
        if (!$data instanceof ExerciseRuntimeAttemptDelete) {
            throw new BadRequestHttpException('Invalid delete request.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to delete this exercise attempt.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $attemptId = isset($uriVariables['attemptId']) ? (int) $uriVariables['attemptId'] : (int) ($data->attemptId ?? 0);
        if (0 >= $exerciseId || 0 >= $attemptId) {
            throw new BadRequestHttpException('A valid exercise and attempt are required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        if ($this->isGradebookLocked((int) $quiz->getIid(), $course)) {
            throw new BadRequestHttpException('This exercise is locked by gradebook.');
        }
        if (!$this->canDeleteResults()) {
            throw new AccessDeniedHttpException('Deleting exercise results is not allowed.');
        }

        $attempt = $this->getAttempt($attemptId, $quiz, $course, $session);

        $this->entityManager->remove($attempt);
        $this->entityManager->flush();

        $response = new ExerciseRuntimeAttemptDelete();
        $response->exerciseId = $exerciseId;
        $response->attemptId = $attemptId;
        $response->success = true;
        $response->message = 'Attempt deleted';

        return $response;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        if (null === $queryBuilder->getQuery()->getOneOrNullResult()) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        return $quiz;
    }


    private function canDeleteResults(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access');
    }

    private function isGradebookLocked(int $exerciseId, Course $course): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            return false;
        }

        $lockedLink = $this->entityManager->createQueryBuilder()
            ->select('link.id')
            ->from(GradebookLink::class, 'link')
            ->andWhere('link.locked = :locked')
            ->andWhere('link.refId = :exerciseId')
            ->andWhere('link.type = :linkType')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->setParameter('locked', 1, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('linkType', self::LINK_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $lockedLink;
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function getAttempt(int $attemptId, CQuiz $quiz, Course $course, ?Session $session): TrackEExercise
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('attempt.exeId = :attemptId')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('attemptId', $attemptId, Types::INTEGER)
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $attempt = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$attempt instanceof TrackEExercise) {
            throw new NotFoundHttpException('The requested attempt was not found.');
        }

        return $attempt;
    }
}
