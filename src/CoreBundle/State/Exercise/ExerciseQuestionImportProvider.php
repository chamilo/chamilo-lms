<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionImport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ExerciseQuestionImport>
 */
final readonly class ExerciseQuestionImportProvider implements ProviderInterface
{
    public const CSRF_TOKEN_ID = 'exercise_question_import';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to import exercises in this context.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $importType = $this->normalizeImportType((string) ($uriVariables['importType'] ?? 'aiken'));

        $response = new ExerciseQuestionImport();
        $response->importType = $importType;
        $response->title = $this->getImportTitle($importType);
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();
        $response->canManage = true;
        $response->actionUrls = $this->getActionUrls($course, $session, $request);
        $response->sample = $this->getImportSample($importType);

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

    private function normalizeImportType(string $importType): string
    {
        $importType = strtolower(trim($importType));
        if (\in_array($importType, ['aiken', 'excel', 'qti2'], true)) {
            return $importType;
        }

        throw new BadRequestHttpException('Unsupported import type.');
    }

    private function getImportTitle(string $importType): string
    {
        if ('aiken' === $importType) {
            return 'Import Aiken quiz';
        }

        if ('excel' === $importType) {
            return 'Import quiz from Excel';
        }

        if ('qti2' === $importType) {
            return 'Import exercises QTI2';
        }

        return 'Import questions';
    }

    /**
     * @return array<string, string>
     */
    private function getActionUrls(Course $course, ?Session $session, Request $request): array
    {
        $params = [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => $request->query->getInt('gid'),
        ];
        $queryString = http_build_query(array_filter($params, static fn (int $value): bool => 0 < $value));

        return [
            'excelTemplate' => '/api/exercise/import/excel/template.xlsx'.('' !== $queryString ? '?'.$queryString : ''),
        ];
    }

    private function getImportSample(string $importType): string
    {
        if ('excel' === $importType) {
            return $this->getExcelSample();
        }

        if ('qti2' === $importType) {
            return $this->getQti2Sample();
        }

        return $this->getAikenSample();
    }

    private function getAikenSample(): string
    {
        return <<<'TEXT'
This is the text for question 1
A. Answer 1
B. Answer 2
C. Answer 3
ANSWER: B

This is the text for question 2
A. Answer 1
B. Answer 2
C. Answer 3
D. Answer 4
ANSWER: D
ANSWER_EXPLANATION: this is an optional feedback comment that will appear next to the correct answer.
SCORE: 20
TEXT;
    }

    private function getQti2Sample(): string
    {
        return <<<'TEXT'
Upload an IMS/QTI 2 .zip package.

Supported import:
- IMS/QTI 2 package inside a ZIP file.
- Unique answer questions.
- Multiple answer questions.
- Fill in blanks questions.
- Free answer questions.

Unsupported QTI entries are skipped, matching the previous importer behavior.
TEXT;
    }

    private function getExcelSample(): string
    {
        return <<<'TEXT'
The Excel file must follow the Chamilo quiz template:

Column A: row label
Column B: text/title/content
Column C: score, option marker or question type

Example rows:
Quiz | Imported Excel quiz |
Question | What is PHP? |
Answer 1 | A programming language | x
Answer 2 | A database |
Score | | 10
FeedbackTrue | Correct. |
FeedbackFalse | Incorrect. |
Category | PHP basics |
QuestionType | | 1
TEXT;
    }
}
