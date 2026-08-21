<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionImport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<ExerciseQuestionImport>
 */
final readonly class ExerciseQuestionImportProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
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

        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to import exercises in this context.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $importType = $this->normalizeImportType((string) ($uriVariables['importType'] ?? 'aiken'));

        $response = new ExerciseQuestionImport();
        $response->importType = $importType;
        $response->title = $this->getImportTitle($importType);
        $response->canManage = true;
        $response->actionUrls = $this->getActionUrls($operation, $course, $session, $request);
        $response->sample = $this->getImportSample($importType);
        $response->learningPathContext = $this->isLearningPathImportContext($request);

        return $response;
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
    private function getActionUrls(Operation $operation, Course $course, ?Session $session, Request $request): array
    {
        $params = [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => (int) $this->cidReqHelper->getGroupId(),
            'origin' => (string) $request->query->get('origin', ''),
            'returnToLp' => (string) $request->query->get('returnToLp', ''),
            'lp_id' => $request->query->getInt('lp_id'),
            'learnpath_id' => $request->query->getInt('learnpath_id'),
            'lp_parent_id' => $request->query->getInt('lp_parent_id'),
            'parent' => $request->query->getInt('parent'),
        ];
        $queryString = http_build_query(array_filter(
            $params,
            static fn (mixed $value): bool => \is_int($value) ? $value > 0 : '' !== trim((string) $value)
        ));

        return [
            'excelTemplate' => '/api/exercise/import/excel/template.xlsx'.('' !== $queryString ? '?'.$queryString : ''),
        ];
    }

    private function isLearningPathImportContext(Request $request): bool
    {
        $origin = strtolower(trim((string) $request->query->get('origin', '')));
        $returnToLp = strtolower(trim((string) $request->query->get('returnToLp', '')));

        return 'learnpath' === $origin
            || \in_array($returnToLp, ['1', 'true', 'yes'], true)
            || $request->query->getInt('lp_id') > 0
            || $request->query->getInt('learnpath_id') > 0;
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
