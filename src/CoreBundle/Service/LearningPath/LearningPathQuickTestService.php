<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\AiProvider\AiChatCompletionClientInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

final readonly class LearningPathQuickTestService
{
    private const QUESTION_COUNT = 2;
    private const MAX_CONTENT_LENGTH = 20000;
    private const MAX_GENERATED_LENGTH = 12000;
    private const OUTPUT_TOKEN_BUDGET = 800;

    public function __construct(
        private AiFeatureAccessHelper $aiFeatureAccessHelper,
        private AiProviderFactory $aiProviderFactory,
        private AiChatCompletionClientInterface $aiChatCompletionClient,
        private CDocumentRepository $documentRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function isAvailable(Course $course): bool
    {
        return $this->aiFeatureAccessHelper->isFeatureEnabledForCourse(
            'exercise_generator',
            (int) $course->getId(),
        ) && $this->aiProviderFactory->hasProvidersForType('text');
    }

    /**
     * @return array{exerciseId: int, provider: string, title: string}
     */
    public function createExercise(
        Course $course,
        CDocument $document,
        string $sourceTitle,
        string $requestedProvider = '',
    ): array {
        if (!$this->isAvailable($course)) {
            throw new AccessDeniedHttpException('AI exercise generation is disabled in this course.');
        }

        $courseId = (int) $course->getId();
        if ((int) api_get_course_int_id() !== $courseId) {
            throw new BadRequestHttpException('The active course context does not match the learning path.');
        }

        $content = $this->readDocumentContent($document);
        $plainText = $this->normalizeDocumentText($content);
        if ('' === $plainText) {
            throw new BadRequestHttpException('The document has no text content to process.');
        }

        $providerName = $this->resolveProviderName($requestedProvider);
        $documentTitle = $this->plainText($sourceTitle);
        if ('' === $documentTitle) {
            $documentTitle = $this->plainText((string) $document->getTitle());
        }
        $language = trim((string) $course->getCourseLanguage()) ?: 'en';
        $requestMarker = 'LP_QUICK_TEST_REQUEST:'.hash(
            'sha256',
            $plainText.'|'.$documentTitle.'|'.microtime(true),
        );

        $user = $this->security->getUser();
        $userId = $user instanceof User ? (int) $user->getId() : 0;
        $lastRequestId = $this->getLastAiRequestId($userId);

        try {
            $generated = $this->requestAiken(
                $providerName,
                $documentTitle,
                $plainText,
                $language,
                $requestMarker,
            );
            $normalizedAiken = $this->tryNormalizeAiken($generated);

            if (null === $normalizedAiken) {
                $repaired = $this->requestAiken(
                    $providerName,
                    $documentTitle,
                    $plainText,
                    $language,
                    $requestMarker,
                    $generated,
                );
                $normalizedAiken = $this->tryNormalizeAiken($repaired);
            }
        } catch (Throwable $exception) {
            error_log('[AI][lp_quick_test] Question generation failed: '.$exception->getMessage());

            throw new HttpException(502, 'The AI model could not generate the quick test.');
        } finally {
            $this->redactGeneratedRequestLogs(
                $userId,
                $lastRequestId,
                $providerName,
                $requestMarker,
                $documentTitle,
                $plainText,
            );
        }

        if (null === $normalizedAiken) {
            throw new HttpException(502, 'The AI model did not return two valid Aiken questions.');
        }

        $exerciseTitle = mb_substr(
            'Quick test: '.('' !== $documentTitle ? $documentTitle : 'Document'),
            0,
            255,
        );
        $exerciseId = $this->importAiken($normalizedAiken, $exerciseTitle, $providerName);

        return [
            'exerciseId' => $exerciseId,
            'provider' => $providerName,
            'title' => $exerciseTitle,
        ];
    }

    private function resolveProviderName(string $requestedProvider): string
    {
        $providerNames = array_values(array_filter(array_map(
            static fn (mixed $providerName): string => trim((string) $providerName),
            $this->aiProviderFactory->getProvidersForType('text'),
        )));

        if ([] === $providerNames) {
            throw new HttpException(503, 'No AI text provider is configured.');
        }

        $requestedProvider = trim($requestedProvider);
        if ('' === $requestedProvider) {
            if (1 !== \count($providerNames)) {
                throw new BadRequestHttpException('Select an AI provider.');
            }

            return $providerNames[0];
        }

        if (!\in_array($requestedProvider, $providerNames, true)) {
            throw new BadRequestHttpException('The selected AI provider is not configured for text generation.');
        }

        return $requestedProvider;
    }

    private function requestAiken(
        string $providerName,
        string $documentTitle,
        string $plainText,
        string $language,
        string $requestMarker,
        ?string $previousOutput = null,
    ): string {
        $systemPrompt = <<<'PROMPT'
Return only valid Aiken plain text. Generate exactly two independent multiple-choice questions, each with four non-empty options labelled A. through D. and exactly one line in the form ANSWER: X. Do not use Markdown, code fences, numbering, explanations, headings, or introductory text. Never refer to the source document.
PROMPT;

        $userPrompt = $this->buildGenerationPrompt(
            $documentTitle,
            $plainText,
            $language,
            $requestMarker,
            $previousOutput,
        );
        $result = $this->aiChatCompletionClient->chatWithMeta(
            $providerName,
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            [
                'temperature' => 0.2,
                'max_output_tokens' => self::OUTPUT_TOKEN_BUDGET,
                'max_tokens' => self::OUTPUT_TOKEN_BUDGET,
                'throw_on_error' => true,
            ],
        );

        $generated = trim(mb_substr((string) $result->text, 0, self::MAX_GENERATED_LENGTH));
        if ('' === $generated || str_starts_with($generated, 'Error:')) {
            throw new HttpException(502, 'The AI model returned an invalid quick test.');
        }

        return $generated;
    }

    private function buildGenerationPrompt(
        string $documentTitle,
        string $plainText,
        string $language,
        string $requestMarker,
        ?string $previousOutput,
    ): string {
        $prompt = $requestMarker."\n"
            .'Language: '.$language."\n"
            .'Document title: '.('' !== $documentTitle ? $documentTitle : 'Untitled document')."\n\n"
            ."Create exactly two questions using only this saved document content:\n"
            .$plainText;

        if (null !== $previousOutput) {
            $prompt .= "\n\nThe previous output was invalid or incomplete. Regenerate the full test from the source content. "
                ."Do not continue the previous output.\nPrevious invalid output:\n"
                .mb_substr($previousOutput, 0, 4000);
        }

        return $prompt;
    }

    private function getLastAiRequestId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $lastId = $this->entityManager->createQueryBuilder()
            ->select('MAX(aiRequest.id)')
            ->from(AiRequests::class, 'aiRequest')
            ->andWhere('aiRequest.userId = :userId')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $lastId;
    }

    private function redactGeneratedRequestLogs(
        int $userId,
        int $lastRequestId,
        string $providerName,
        string $requestMarker,
        string $documentTitle,
        string $plainText,
    ): void {
        if ($userId <= 0) {
            return;
        }

        try {
            /** @var AiRequests[] $requests */
            $requests = $this->entityManager->createQueryBuilder()
                ->select('aiRequest')
                ->from(AiRequests::class, 'aiRequest')
                ->andWhere('aiRequest.id > :lastRequestId')
                ->andWhere('aiRequest.userId = :userId')
                ->andWhere('aiRequest.aiProvider = :providerName')
                ->andWhere('aiRequest.requestText LIKE :requestMarker')
                ->setParameter('lastRequestId', $lastRequestId, Types::INTEGER)
                ->setParameter('userId', $userId, Types::INTEGER)
                ->setParameter('providerName', $providerName, Types::STRING)
                ->setParameter('requestMarker', '%'.$requestMarker.'%', Types::STRING)
                ->getQuery()
                ->getResult()
            ;

            $safeTitle = mb_substr($this->oneLine($documentTitle), 0, 200);
            $safeAudit = \sprintf(
                'LP quick test from saved document; title=%s; content_sha256=%s; content_length=%d',
                '' !== $safeTitle ? $safeTitle : 'Untitled document',
                hash('sha256', $plainText),
                mb_strlen($plainText),
            );

            foreach ($requests as $request) {
                $request->setRequestText($safeAudit);
                $this->entityManager->persist($request);
            }
            if ([] !== $requests) {
                $this->entityManager->flush();
            }
        } catch (Throwable) {
            // Audit redaction must never replace the main user-facing result.
        }
    }

    private function readDocumentContent(CDocument $document): string
    {
        try {
            $content = (string) $this->documentRepository->getResourceFileContent($document);
        } catch (Throwable) {
            $content = '';
        }

        if ('' !== trim($content)) {
            return $content;
        }

        return (string) ($document->getResourceNode()?->getContent() ?? '');
    }

    private function normalizeDocumentText(string $html): string
    {
        $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $text = (string) preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        return mb_substr($text, 0, self::MAX_CONTENT_LENGTH);
    }

    /**
     * @psalm-suppress UndefinedFunction
     */
    private function tryNormalizeAiken(string $generated): ?string
    {
        $this->loadAikenImporter();

        $generated = $this->normalizeAikenSyntax($generated);
        $exerciseInfo = [
            'name' => 'Quick test',
            'total_weight' => 20,
            'question' => [],
        ];
        setExerciseInfoFromAikenText($generated, $exerciseInfo);

        $validQuestions = [];
        foreach ((array) ($exerciseInfo['question'] ?? []) as $question) {
            if (!\is_array($question)) {
                continue;
            }

            $title = $this->oneLine((string) ($question['title'] ?? ''));
            $answers = array_values((array) ($question['answer'] ?? []));
            $correctAnswers = array_values(array_unique(array_map(
                static fn (mixed $index): int => (int) $index,
                (array) ($question['correct_answers'] ?? []),
            )));

            if ('' === $title || \count($answers) < 2 || 1 !== \count($correctAnswers)) {
                continue;
            }

            $correctIndex = $correctAnswers[0];
            if (!isset($answers[$correctIndex]) || \count($answers) > 26) {
                continue;
            }

            $normalizedAnswers = [];
            foreach ($answers as $answer) {
                $value = $this->oneLine((string) (\is_array($answer) ? ($answer['value'] ?? '') : $answer));
                if ('' === $value) {
                    $normalizedAnswers = [];

                    break;
                }
                $normalizedAnswers[] = $value;
            }
            if ([] === $normalizedAnswers) {
                continue;
            }

            $validQuestions[] = [
                'title' => $title,
                'answers' => $normalizedAnswers,
                'correctIndex' => $correctIndex,
                'feedback' => $this->oneLine((string) ($question['feedback'] ?? '')),
            ];
        }

        if (self::QUESTION_COUNT !== \count($validQuestions)) {
            return null;
        }

        $blocks = [];
        foreach ($validQuestions as $question) {
            $lines = [$question['title']];
            foreach ($question['answers'] as $index => $answer) {
                $lines[] = \chr(65 + $index).'. '.$answer;
            }
            $lines[] = 'ANSWER: '.\chr(65 + $question['correctIndex']);
            if ('' !== $question['feedback']) {
                $lines[] = 'ANSWER_EXPLANATION: '.$question['feedback'];
            }
            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    private function normalizeAikenSyntax(string $generated): string
    {
        $generated = str_replace(["\r\n", "\r"], "\n", trim($generated));
        $generated = (string) preg_replace('/```(?:aiken|text)?\s*/iu', '', $generated);
        $generated = str_replace('```', '', $generated);

        $normalizedLines = [];
        foreach (explode("\n", $generated) as $line) {
            $line = trim($line);
            if ('' === $line) {
                $normalizedLines[] = '';

                continue;
            }

            if (preg_match('/^(?:[-*]\s*)?([A-Z])\s*[\)\:\-\.]\s*(.+?)\s*$/iu', $line, $matches)) {
                $normalizedLines[] = strtoupper($matches[1]).'. '.trim($matches[2], " *\t");

                continue;
            }

            if (preg_match(
                '/^\**\s*(?:ANSWER|RESPUESTA|R[ÉE]PONSE|CORRECT(?:\s+ANSWER)?|RESPUESTA\s+CORRECTA)\s*:\s*([A-Z])\s*[.\s]*\**$/iu',
                $line,
                $matches,
            )) {
                $normalizedLines[] = 'ANSWER: '.strtoupper($matches[1]);

                continue;
            }

            $normalizedLines[] = trim($line, " *\t");
        }

        return trim(implode("\n", $normalizedLines));
    }

    /**
     * @psalm-suppress UndefinedFunction
     */
    private function importAiken(string $aiken, string $title, string $providerName): int
    {
        $this->loadAikenImporter();

        $exerciseId = aiken_import_exercise(null, [
            'exercise_title' => $title,
            'nro_questions' => self::QUESTION_COUNT,
            'aiken_format' => $aiken,
            'ai_generated' => '1',
            'ai_provider_used' => $providerName,
            'ai_feature' => 'learning_path_quick_test',
        ]);

        if (!\is_int($exerciseId) && !ctype_digit((string) $exerciseId)) {
            throw new HttpException(500, 'The generated quick test could not be imported.');
        }

        $exerciseId = (int) $exerciseId;
        if ($exerciseId <= 0) {
            throw new HttpException(500, 'The generated quick test could not be imported.');
        }

        return $exerciseId;
    }

    private function loadAikenImporter(): void
    {
        $basePath = api_get_path(SYS_CODE_PATH).'exercise/export/aiken/';

        require_once $basePath.'aiken_import.inc.php';

        require_once $basePath.'aiken_classes.php';
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));
    }

    private function oneLine(string $value): string
    {
        $value = $this->plainText($value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
