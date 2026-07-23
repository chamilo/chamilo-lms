<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

final readonly class ExerciseRuntimeAllAttemptsExportService
{
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';
    private const VISIBILITY_PUBLISHED = 2;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private ExerciseRuntimeAttemptPdfService $attemptPdfService,
    ) {}

    public function exportAllAttemptsZip(int $exerciseId, Request $request): BinaryFileResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId, $request);
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $attempts = $this->getAttempts($quiz, $course, $session, $request);
        $zipPath = $this->createZipFile();

        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new BadRequestHttpException('The attempts export archive could not be created.');
        }

        if ([] === $attempts) {
            $zip->addFromString('README.txt', 'No attempts found for the current filters.');
        }

        $usedNames = [];
        foreach ($attempts as $attempt) {
            $pdfFile = $this->attemptPdfService->buildAttemptPdfFile($exerciseId, (int) $attempt->getExeId(), $request);
            $fileName = $this->buildAttemptFileName($pdfFile['fileName'], $attempt, $usedNames);
            $zip->addFromString($fileName, $pdfFile['content']);
        }

        if (!$zip->close()) {
            throw new BadRequestHttpException('The attempts export archive could not be finalized.');
        }

        $response = new BinaryFileResponse(new File($zipPath));
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->buildArchiveFileName($quiz));
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function getValidatedExercise(int $exerciseId, Request $request): CQuiz
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canExportAttempts()) {
            throw new AccessDeniedHttpException('You are not allowed to export attempts for this exercise.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);

        return $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
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
            ->addSelect('links.visibility AS linkVisibility')
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

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (null === $row) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        $visibility = \is_array($row) ? (int) ($row['linkVisibility'] ?? 0) : 0;
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canExportAttempts()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    /**
     * @return array<int, TrackEExercise>
     */
    private function getAttempts(CQuiz $quiz, Course $course, ?Session $session, Request $request): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt', 'user')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $firstName = trim((string) $request->query->get('firstName', ''));
        if ('' !== $firstName) {
            $queryBuilder
                ->andWhere('LOWER(user.firstname) LIKE :firstName')
                ->setParameter('firstName', '%'.mb_strtolower($firstName).'%', Types::STRING)
            ;
        }

        $lastName = trim((string) $request->query->get('lastName', ''));
        if ('' !== $lastName) {
            $queryBuilder
                ->andWhere('LOWER(user.lastname) LIKE :lastName')
                ->setParameter('lastName', '%'.mb_strtolower($lastName).'%', Types::STRING)
            ;
        }

        $status = trim((string) $request->query->get('status', ''));
        if (self::STATUS_PENDING_CORRECTION === $status) {
            $queryBuilder->andWhere("attempt.questionsToCheck <> ''");
        } elseif (self::STATUS_INCOMPLETE === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->setParameter('status', self::STATUS_INCOMPLETE, Types::STRING)
            ;
        } elseif (self::STATUS_COMPLETED === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->andWhere("attempt.questionsToCheck = ''")
                ->setParameter('status', self::STATUS_COMPLETED, Types::STRING)
            ;
        }

        $attempts = [];
        foreach ($queryBuilder->getQuery()->getResult() as $attempt) {
            if ($attempt instanceof TrackEExercise) {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
    }

    private function canExportAttempts(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function createZipFile(): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'exercise-attempts-');
        if (false === $filePath) {
            throw new BadRequestHttpException('The attempts export archive could not be created.');
        }

        $zipPath = $filePath.'.zip';
        if (!rename($filePath, $zipPath)) {
            throw new BadRequestHttpException('The attempts export archive could not be prepared.');
        }

        return $zipPath;
    }

    /**
     * @param array<string, bool> $usedNames
     */
    private function buildAttemptFileName(string $baseFileName, TrackEExercise $attempt, array &$usedNames): string
    {
        $user = $attempt->getUser();
        $learnerName = $this->safeFileSegment(trim($user->getLastname().'-'.$user->getFirstname()));
        if ('' === $learnerName) {
            $learnerName = $this->safeFileSegment($user->getUsername());
        }

        $baseName = $this->safeFileSegment(pathinfo($baseFileName, PATHINFO_FILENAME));
        if ('' === $baseName) {
            $baseName = 'attempt-'.(int) $attempt->getExeId();
        }

        $fileName = $learnerName.'-'.$baseName.'.pdf';
        $fileName = strtolower(trim($fileName, '-'));
        if (!isset($usedNames[$fileName])) {
            $usedNames[$fileName] = true;

            return $fileName;
        }

        $counter = 2;
        do {
            $candidate = preg_replace('/\.pdf$/', '-'.$counter.'.pdf', $fileName) ?: $fileName;
            ++$counter;
        } while (isset($usedNames[$candidate]));

        $usedNames[$candidate] = true;

        return $candidate;
    }

    private function buildArchiveFileName(CQuiz $quiz): string
    {
        $safeTitle = $this->safeFileSegment($quiz->getTitle());
        if ('' === $safeTitle) {
            $safeTitle = 'exercise';
        }

        return strtolower($safeTitle).'-attempts.zip';
    }

    private function safeFileSegment(string $value): string
    {
        $segment = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?: '';
        $segment = trim($segment, '-');

        return substr($segment, 0, 80);
    }
}
