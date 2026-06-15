<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ExerciseRuntimeReportExportService
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
    ) {}

    public function exportCsv(int $exerciseId, Request $request): StreamedResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId, $request);
        $rows = $this->buildExportRows($quiz, $request);
        $fileName = $this->buildFileName($quiz, 'csv');

        $response = new StreamedResponse(static function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if (!\is_resource($handle)) {
                return;
            }

            fputcsv($handle, self::getHeaders());
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName)
        );

        return $response;
    }

    public function exportXlsx(int $exerciseId, Request $request): BinaryFileResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId, $request);
        $rows = $this->buildExportRows($quiz, $request);
        $fileName = $this->buildFileName($quiz, 'xlsx');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Learner score');
        $sheet->fromArray(self::getHeaders(), null, 'A1');

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A'.$rowNumber);
            ++$rowNumber;
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filePath = tempnam(sys_get_temp_dir(), 'exercise-report-');
        if (false === $filePath) {
            throw new BadRequestHttpException('The export file could not be created.');
        }

        $xlsxPath = $filePath.'.xlsx';
        rename($filePath, $xlsxPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse(new File($xlsxPath));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @return array<int, string>
     */
    private static function getHeaders(): array
    {
        return [
            'First name',
            'Last name',
            'Username',
            'Group',
            'Duration',
            'Started at',
            'Completed at',
            'Score',
            'Max score',
            'Percentage',
            'IP',
            'Status',
            'Learning path',
        ];
    }

    private function getValidatedExercise(int $exerciseId, Request $request): CQuiz
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canExportReport()) {
            throw new AccessDeniedHttpException('You are not allowed to export this exercise report.');
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
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canExportReport()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    /**
     * @return array<int, array<int, int|float|string>>
     */
    private function buildExportRows(CQuiz $quiz, Request $request): array
    {
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $attempts = $this->getAttempts($quiz, $course, $session, $request);
        $rows = [];

        foreach ($attempts as $attempt) {
            $score = $attempt->getScore();
            $maxScore = $attempt->getMaxScore();
            $percentage = 0.0 < $maxScore ? round(($score * 100) / $maxScore, 2) : 0.0;

            $rows[] = [
                (string) $attempt->getUser()->getFirstname(),
                (string) $attempt->getUser()->getLastname(),
                (string) $attempt->getUser()->getUsername(),
                '-',
                $this->formatDuration($attempt->getExeDuration()),
                $this->formatDate($attempt->getStartDate()),
                $this->formatDate($attempt->getExeDate()),
                round($score, 2),
                round($maxScore, 2),
                $percentage,
                $attempt->getUserIp(),
                $this->getAttemptStatusLabel($attempt),
                $this->formatLearningPath($attempt),
            ];
        }

        return $rows;
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

    private function canExportReport(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getAttemptStatusLabel(TrackEExercise $attempt): string
    {
        if ('' !== trim($attempt->getQuestionsToCheck())) {
            return 'Pending correction';
        }

        if (self::STATUS_INCOMPLETE === (string) $attempt->getStatus()) {
            return 'Ongoing';
        }

        return 'Completed';
    }

    private function formatLearningPath(TrackEExercise $attempt): string
    {
        if (0 < $attempt->getOrigLpId()) {
            return '#'.$attempt->getOrigLpId();
        }

        return '-';
    }

    private function formatDuration(int $duration): string
    {
        $seconds = max(0, $duration);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if (0 < $hours) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    private function buildFileName(CQuiz $quiz, string $extension): string
    {
        $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '-', $quiz->getTitle()) ?: 'exercise-report';
        $safeTitle = trim($safeTitle, '-');
        if ('' === $safeTitle) {
            $safeTitle = 'exercise-report';
        }

        return strtolower($safeTitle).'-learner-score.'.$extension;
    }
}
