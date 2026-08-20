<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationImport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookResultLog;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<GradebookEvaluationImport, GradebookEvaluationImport>
 */
final readonly class GradebookEvaluationImportProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_evaluation_import';

    private const MAX_FILE_SIZE = 5242880;

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookEvaluationImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken((string) $request->request->get('submittedCsrfToken', ''));
        $resolved = $this->contextResolver->resolve($request, true);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $evaluation = $this->getEvaluationInGradebook(
            $request->request->getInt('evaluationId'),
            $rootCategory,
            $resolved['course'],
            $resolved['session'],
        );
        if (1 === (int) $evaluation->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('This evaluation is locked.');
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A valid CSV file is required.');
        }
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new BadRequestHttpException('The CSV file is too large.');
        }
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!\in_array($extension, ['csv', 'txt'], true)) {
            throw new BadRequestHttpException('Only CSV files are supported.');
        }

        $overwrite = $request->request->getBoolean('overwrite');
        $ignoreErrors = $request->request->getBoolean('ignoreErrors');
        $rows = $this->readCsv($file, $ignoreErrors);
        $students = $this->contextResolver->getStudents($resolved['course'], $resolved['session']);
        $studentsByUsername = [];
        foreach ($students as $student) {
            $studentsByUsername[mb_strtolower($student->getUsername())] = $student;
        }

        $existingResults = $this->entityManager->getRepository(GradebookResult::class)->findBy([
            'evaluation' => $evaluation,
        ]);
        $resultsByUserId = [];
        foreach ($existingResults as $result) {
            if (!$result instanceof GradebookResult || null === $result->getUser()->getId()) {
                continue;
            }
            $resultsByUserId[(int) $result->getUser()->getId()] = $result;
        }

        $plan = [];
        $unchangedCount = 0;
        $skippedCount = 0;
        $maxScore = (float) $evaluation->getMax();
        $numberDecimals = $this->getNumberDecimals();
        $seenUsernames = [];

        foreach ($rows as $row) {
            $username = trim((string) ($row['data']['username'] ?? ''));
            $normalizedUsername = mb_strtolower($username);
            if (isset($seenUsernames[$normalizedUsername])) {
                throw new BadRequestHttpException('The learner '.$username.' appears more than once in the CSV file.');
            }
            $seenUsernames[$normalizedUsername] = true;

            $student = $studentsByUsername[$normalizedUsername] ?? null;
            if (!$student instanceof User) {
                throw new BadRequestHttpException('The learner on CSV row '.$row['line'].' is outside the current course context.');
            }

            if (!$ignoreErrors) {
                $this->validateIdentity($row['data'], $student, $row['line']);
            }

            $rawScore = trim((string) ($row['data']['score'] ?? ''));
            if ('' === $rawScore || !is_numeric($rawScore)) {
                throw new BadRequestHttpException('The score on CSV row '.$row['line'].' must be numeric.');
            }

            $score = round((float) $rawScore, $numberDecimals);
            if ($score < 0 || $score > $maxScore) {
                throw new BadRequestHttpException('The score on CSV row '.$row['line'].' is outside the evaluation score range.');
            }

            $studentId = (int) $student->getId();
            $existing = $resultsByUserId[$studentId] ?? null;
            if ($existing instanceof GradebookResult && null !== $existing->getScore()
                && abs((float) $existing->getScore() - $score) < 0.0000001
            ) {
                ++$unchangedCount;

                continue;
            }
            if ($existing instanceof GradebookResult && !$overwrite) {
                throw new BadRequestHttpException('CSV row '.$row['line'].' would overwrite an existing score. Enable overwrite to continue.');
            }

            $createdAt = $existing instanceof GradebookResult
                ? $existing->getCreatedAt()
                : $this->parseImportedDate((string) ($row['data']['date'] ?? ''), $row['line']);

            $plan[] = [
                'student' => $student,
                'score' => $score,
                'createdAt' => $createdAt,
                'existing' => $existing,
            ];
        }

        $addedCount = 0;
        $overwrittenCount = 0;
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            foreach ($plan as $change) {
                $result = $change['existing'];
                if ($result instanceof GradebookResult) {
                    $this->logResult($result, $evaluation, $change['student']);
                    $result->setScore($change['score']);
                    ++$overwrittenCount;

                    continue;
                }

                $result = new GradebookResult();
                $result
                    ->setEvaluation($evaluation)
                    ->setUser($change['student'])
                    ->setScore($change['score'])
                    ->setCreatedAt($change['createdAt'])
                ;
                $this->entityManager->persist($result);
                ++$addedCount;
            }

            $this->entityManager->flush();
            $this->updateEvaluationStats($evaluation);
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }

        $response = new GradebookEvaluationImport();
        $response->success = true;
        $response->addedCount = $addedCount;
        $response->overwrittenCount = $overwrittenCount;
        $response->unchangedCount = $unchangedCount;
        $response->skippedCount = $skippedCount;
        $response->message = 'The Gradebook results were imported.';

        return $response;
    }

    /**
     * @return list<array{line: int, data: array<string, string>}>
     */
    private function readCsv(UploadedFile $file, bool $ignoreErrors): array
    {
        $handle = fopen($file->getPathname(), 'rb');
        if (false === $handle) {
            throw new BadRequestHttpException('The CSV file could not be read.');
        }

        try {
            $header = fgetcsv($handle, 0, ';');
            if (false === $header || [] === $header) {
                throw new BadRequestHttpException('The CSV file is empty or invalid.');
            }

            $header = array_map(
                static fn (mixed $value): string => strtolower(trim((string) $value, " \t\n\r\0\x0B\xEF\xBB\xBF")),
                $header,
            );
            if (!\in_array('username', $header, true) || !\in_array('score', $header, true)) {
                throw new BadRequestHttpException('The CSV header must contain username and score columns.');
            }
            if (!$ignoreErrors) {
                foreach (['official_code', 'lastname', 'firstname'] as $requiredColumn) {
                    if (!\in_array($requiredColumn, $header, true)) {
                        throw new BadRequestHttpException('The CSV header must contain '.$requiredColumn.' unless Ignore errors is enabled.');
                    }
                }
            }

            $rows = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, ';')) !== false) {
                ++$line;
                if ($this->isEmptyCsvRow($values)) {
                    continue;
                }
                if (\count($values) > \count($header)) {
                    throw new BadRequestHttpException('CSV row '.$line.' contains more values than the header.');
                }

                $values = array_pad($values, \count($header), '');
                $data = [];
                foreach ($header as $index => $key) {
                    if ('' !== $key) {
                        $data[$key] = trim((string) ($values[$index] ?? ''));
                    }
                }
                $rows[] = ['line' => $line, 'data' => $data];
            }
        } finally {
            fclose($handle);
        }

        if ([] === $rows) {
            throw new BadRequestHttpException('The CSV file does not contain any result rows.');
        }

        return $rows;
    }

    /**
     * @param array<string, string> $row
     */
    private function validateIdentity(array $row, User $student, int $line): void
    {
        if ((string) ($row['lastname'] ?? '') !== (string) $student->getLastname()
            || (string) ($row['firstname'] ?? '') !== (string) $student->getFirstname()
            || (string) ($row['official_code'] ?? '') !== (string) ($student->getOfficialCode() ?? '')
        ) {
            throw new BadRequestHttpException('Learner identity data does not match on CSV row '.$line.'.');
        }
    }

    private function getEvaluationInGradebook(
        int $evaluationId,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookEvaluation {
        if ($evaluationId <= 0) {
            throw new BadRequestHttpException('A valid evaluation id is required.');
        }

        $evaluation = $this->entityManager->getRepository(GradebookEvaluation::class)->find($evaluationId);
        if (!$evaluation instanceof GradebookEvaluation) {
            throw new NotFoundHttpException('The requested evaluation was not found.');
        }
        if ((int) $evaluation->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested evaluation belongs to another course.');
        }

        $this->contextResolver->getCategoryInGradebook(
            (int) $evaluation->getCategory()->getId(),
            $rootCategory,
            $course,
            $session,
        );

        return $evaluation;
    }

    private function parseImportedDate(string $value, int $line): DateTime
    {
        if ('' === trim($value)) {
            return new DateTime('now', new DateTimeZone('UTC'));
        }

        $formats = ['!Y-m-d H:i:s', '!Y-m-d H:i', DateTimeInterface::ATOM];
        $timezone = $this->getUserTimezone();
        foreach ($formats as $format) {
            $candidate = DateTimeImmutable::createFromFormat($format, trim($value), $timezone);
            $errors = DateTimeImmutable::getLastErrors();
            if (false !== $candidate && (false === $errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']))) {
                return DateTime::createFromImmutable($candidate->setTimezone(new DateTimeZone('UTC')));
            }
        }

        throw new BadRequestHttpException('The date on CSV row '.$line.' is invalid.');
    }

    private function getUserTimezone(): DateTimeZone
    {
        $user = $this->security->getUser();
        $timezoneId = date_default_timezone_get();
        if ($user instanceof User && '' !== trim($user->getTimezone())) {
            $timezoneId = $user->getTimezone();
        }

        try {
            return new DateTimeZone($timezoneId);
        } catch (Throwable) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    /**
     * @param list<string|null> $row
     */
    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if ('' !== trim((string) $value)) {
                return false;
            }
        }

        return true;
    }

    private function logResult(GradebookResult $result, GradebookEvaluation $evaluation, User $student): void
    {
        $log = new GradebookResultLog();
        $log
            ->setResult($result)
            ->setEvaluation($evaluation)
            ->setUser($student)
            ->setCreatedAt(new DateTime('now', new DateTimeZone('UTC')))
        ;
        if (null !== $result->getScore()) {
            $log->setScore((float) $result->getScore());
        }
        $this->entityManager->persist($log);
    }

    private function updateEvaluationStats(GradebookEvaluation $evaluation): void
    {
        if (!$this->contextResolver->isSettingEnabled('gradebook.allow_gradebook_stats')) {
            return;
        }

        $results = $this->entityManager->getRepository(GradebookResult::class)->findBy(['evaluation' => $evaluation]);
        $scoreList = [];
        $sum = 0.0;
        $best = 0.0;
        $count = 0;
        foreach ($results as $result) {
            if (!$result instanceof GradebookResult || null === $result->getUser()->getId()) {
                continue;
            }
            $score = $result->getScore();
            $scoreList[(string) $result->getUser()->getId()] = $score;
            $numericScore = null !== $score ? (float) $score : 0.0;
            $sum += $numericScore;
            $best = max($best, $numericScore);
            ++$count;
        }

        $evaluation
            ->setBestScore($best)
            ->setAverageScore($count > 0 ? $sum / $count : 0.0)
            ->setUserScoreList($scoreList)
        ;
    }

    private function getNumberDecimals(): int
    {
        $value = $this->settingsManager->getSetting('gradebook.gradebook_number_decimals');
        if (null === $value || '' === $value) {
            $value = $this->settingsManager->getSetting('gradebook_number_decimals');
        }

        return max(0, min(6, (int) ($value ?? 2)));
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('The security token is invalid or expired.');
        }
    }
}
