<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\AiProvider\AiCourseAnalyzerService;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Service\Mcp\McpTextAiService;
use JsonException;
use RuntimeException;
use Stringable;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class StudentSuccessCoach
{
    private const int MAX_AI_PAYLOAD_BYTES = 350_000;
    private const int MAX_OUTPUT_TOKENS = 6500;

    /**
     * This analysis becomes the reusable course context for every learner in
     * the same course/session scope.
     */
    private const string COURSE_ANALYSIS_PROMPT = <<<'PROMPT'
Analyze this course as reusable pedagogical context for a Student Success AI Coach.

Review the learning sequence, learning paths, standalone learning resources, exercises, assignments and surveys. Identify the intended learning progression, assessment strategy, likely difficulty points, prerequisite knowledge, engagement risks and opportunities for remediation.

Keep the analysis evidence-based and useful for later comparison with an individual learner's activity. Do not make assumptions about any particular learner.
PROMPT;

    /**
     * The model may only cite these fixed sources. The server resolves source
     * IDs back to these exact references so fabricated URLs never reach the UI.
     *
     * @var array<string, array{title:string,url:string}>
     */
    private const array EVIDENCE_SOURCES = [
        'CAST_UDL' => [
            'title' => 'CAST Universal Design for Learning Guidelines',
            'url' => 'https://udlguidelines.cast.org/',
        ],
        'DUNLOSKY_2013' => [
            'title' => 'Dunlosky et al. (2013), Improving Students’ Learning With Effective Learning Techniques',
            'url' => 'https://doi.org/10.1177/1529100612453266',
        ],
        'CEPEDA_2006' => [
            'title' => 'Cepeda et al. (2006), Distributed practice in verbal recall tasks',
            'url' => 'https://doi.org/10.1037/0033-2909.132.3.354',
        ],
        'ROEDIGER_KARPICKE_2006' => [
            'title' => 'Roediger & Karpicke (2006), Test-Enhanced Learning',
            'url' => 'https://doi.org/10.1111/j.1467-9280.2006.01693.x',
        ],
        'SWELLER_1988' => [
            'title' => 'Sweller (1988), Cognitive Load During Problem Solving',
            'url' => 'https://doi.org/10.1207/s15516709cog1202_4',
        ],
    ];

    public function __construct(
        private AiCourseAnalyzerService $courseAnalyzerService,
        private StudentSuccessAnalysisStorage $analysisStorage,
        private StudentSuccessPayloadBuilder $payloadBuilder,
        private McpTextAiService $textAiService,
        private MessageHelper $messageHelper,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    /**
     * @return array{generated:bool,provider:string,generatedAt:string|null}
     */
    public function ensureCourseAnalysis(
        Course $course,
        ?Session $session,
        User $teacher,
        ?string $requestedProvider = null,
    ): array {
        $stored = $this->analysisStorage->getCourseAnalysis($course, $session);
        if (\is_array($stored['analysis'] ?? null) && [] !== $stored['analysis']) {
            return [
                'generated' => false,
                'provider' => (string) ($stored['metadata']['provider'] ?? ''),
                'generatedAt' => isset($stored['generatedAt']) ? (string) $stored['generatedAt'] : null,
            ];
        }

        // Resolve the provider and enforce the configured quota before the
        // potentially expensive course-analysis request.
        $provider = $this->textAiService->resolveProvider($teacher, $requestedProvider);

        $result = $this->courseAnalyzerService->analyze(
            $course,
            $session,
            self::COURSE_ANALYSIS_PROMPT,
            $provider,
            true,
            true,
        );

        $structuredResponse = $result['structuredResponse'] ?? null;
        if (!\is_array($structuredResponse) || [] === $structuredResponse) {
            throw new RuntimeException('The course analyser did not return a reusable structured analysis.');
        }

        $this->analysisStorage->storeCourseAnalysis(
            $course,
            $session,
            $structuredResponse,
            [
                'source' => 'student_success_ai_coach',
                'provider' => $provider,
                'teacherPrompt' => self::COURSE_ANALYSIS_PROMPT,
                'payloadStats' => \is_array($result['payloadStats'] ?? null) ? $result['payloadStats'] : [],
                'responseMode' => (string) ($result['responseMode'] ?? 'full'),
                'responseRepaired' => (bool) ($result['responseRepaired'] ?? false),
            ],
        );

        $stored = $this->analysisStorage->getCourseAnalysis($course, $session);

        return [
            'generated' => true,
            'provider' => $provider,
            'generatedAt' => isset($stored['generatedAt']) ? (string) $stored['generatedAt'] : null,
        ];
    }

    /**
     * @return array{
     *     provider:string,
     *     courseAnalysisGenerated:bool,
     *     payloadCompacted:bool,
     *     analysis:array<string,mixed>,
     *     messageSent:bool,
     *     messageId:int|null
     * }
     */
    public function analyzeStudent(
        Course $course,
        ?Session $session,
        User $student,
        User $teacher,
        string $teacherPrompt = '',
        ?string $requestedProvider = null,
    ): array {
        $coursePreparation = $this->ensureCourseAnalysis(
            $course,
            $session,
            $teacher,
            $requestedProvider,
        );

        $payload = $this->payloadBuilder->build(
            $course,
            $session,
            $student,
            $teacherPrompt,
        );

        if (true !== ($payload['courseAnalysis']['available'] ?? false)) {
            throw new RuntimeException('The course analysis is not available.');
        }

        [$aiPayload, $payloadCompacted] = $this->compactPayloadForAi($payload);
        $userPrompt = $this->encodePayload($aiPayload);

        $rawAnalysis = $this->textAiService->requestJson(
            $teacher,
            $requestedProvider,
            $this->systemPrompt($course),
            $userPrompt,
            self::MAX_OUTPUT_TOKENS,
        );

        $provider = trim((string) ($rawAnalysis['_provider'] ?? $coursePreparation['provider']));
        $responseRepaired = (bool) ($rawAnalysis['_structured_output_repaired'] ?? false);
        unset($rawAnalysis['_provider'], $rawAnalysis['_structured_output_repaired']);

        $analysis = $this->normalizeAnalysis($rawAnalysis);
        if (!$this->hasUsefulAnalysis($analysis)) {
            throw new RuntimeException('The AI model returned an empty Student Success recommendation.');
        }

        $this->analysisStorage->storeStudentAnalysis(
            $student,
            $course,
            $session,
            $analysis,
            [
                'source' => 'student_success_ai_coach',
                'provider' => $provider,
                'teacherPrompt' => (string) ($payload['teacherPrompt'] ?? ''),
                'privacy' => \is_array($payload['privacy'] ?? null) ? $payload['privacy'] : [],
                'payloadCompacted' => $payloadCompacted,
                'responseRepaired' => $responseRepaired,
            ],
        );

        [$messageSent, $messageId] = $this->sendTeacherMessage(
            $teacher,
            $student,
            $course,
            $analysis,
        );

        return [
            'provider' => $provider,
            'courseAnalysisGenerated' => $coursePreparation['generated'],
            'payloadCompacted' => $payloadCompacted,
            'analysis' => $analysis,
            'messageSent' => $messageSent,
            'messageId' => $messageId,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStoredStudentAnalysis(
        User $student,
        Course $course,
        ?Session $session,
    ): ?array {
        return $this->analysisStorage->getStudentAnalysis($student, $course, $session);
    }

    private function systemPrompt(Course $course): string
    {
        $sourceLines = [];
        foreach (self::EVIDENCE_SOURCES as $id => $source) {
            $sourceLines[] = $id.' | '.$source['title'].' | '.$source['url'];
        }

        $courseLanguage = trim($course->getCourseLanguage());
        $courseLanguage = (string) preg_replace('/[^\p{L}\p{N}_-]+/u', ' ', $courseLanguage);
        $courseLanguage = trim(mb_substr($courseLanguage, 0, 80));
        if ('' === $courseLanguage) {
            $courseLanguage = 'en';
        }

        return <<<'PROMPT'
You are a teaching-methodology expert acting as a Student Success AI Coach for a teacher.

Read the reusable course analysis and the anonymous learner-activity report supplied in JSON. Recommend practical changes that could improve this learner's chances of success. Consider additional learning activities, pacing/rhythm, different learning methodologies, remediation, retrieval practice, spacing, scaffolding, cognitive load and accessibility when the evidence supports them.

Privacy rules:
- The learner is anonymous. Never try to identify the learner.
- If any personal data appears unexpectedly, ignore it and never repeat it in the output.
- Do not infer sensitive traits, diagnoses or protected characteristics.
- Base recommendations only on the supplied course/activity evidence.

Evidence rules:
- Do not invent citations, authors, papers, URLs or research findings.
- For each recommendation, sourceIds may contain ONLY IDs from the approved source catalog below when that source genuinely supports the recommendation.
- If none of the approved sources clearly applies, return an empty sourceIds array.

Return only valid JSON with this exact top-level structure:
{
  "summary": "Concise overall assessment",
  "priorityActions": ["action"],
  "recommendations": {
    "additionalActivities": [{"recommendation":"...","rationale":"...","sourceIds":["SOURCE_ID"]}],
    "rhythm": [{"recommendation":"...","rationale":"...","sourceIds":["SOURCE_ID"]}],
    "learningMethodologies": [{"recommendation":"...","rationale":"...","sourceIds":["SOURCE_ID"]}],
    "misc": [{"recommendation":"...","rationale":"...","sourceIds":["SOURCE_ID"]}]
  }
}

Keep the answer specific to observable activity. Prefer a small number of high-value recommendations over generic advice.
PROMPT
            ."\n\nCourse language: {$courseLanguage}."
            ."\nSource-language priority rules:"
            ."\n- Prioritize approved pedagogical sources that are practical for a teacher working in the course language."
            ."\n- Avoid adding a reference in another language merely to fill sourceIds."
            ."\n- If no approved source is practical in the course language, prefer an empty sourceIds array unless a source in another language is essential and materially supports a high-priority recommendation."
            ."\n- Never invent translated source titles, localized URLs or source IDs."
            ."\n\nApproved source catalog:\n"
            .implode("\n", $sourceLines);
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function normalizeAnalysis(array $raw): array
    {
        $recommendations = \is_array($raw['recommendations'] ?? null)
            ? $raw['recommendations']
            : [];

        return [
            'summary' => $this->cleanText($raw['summary'] ?? '', 5000),
            'priorityActions' => $this->normalizeStringList($raw['priorityActions'] ?? [], 8),
            'recommendations' => [
                'additionalActivities' => $this->normalizeRecommendationList(
                    $recommendations['additionalActivities'] ?? [],
                ),
                'rhythm' => $this->normalizeRecommendationList(
                    $recommendations['rhythm'] ?? [],
                ),
                'learningMethodologies' => $this->normalizeRecommendationList(
                    $recommendations['learningMethodologies'] ?? [],
                ),
                'misc' => $this->normalizeRecommendationList(
                    $recommendations['misc'] ?? [],
                ),
            ],
        ];
    }

    /**
     * @return list<array{recommendation:string,rationale:string,sources:list<array{id:string,title:string,url:string}>}>
     */
    private function normalizeRecommendationList(mixed $rawItems): array
    {
        if (!\is_array($rawItems)) {
            return [];
        }

        $items = [];
        foreach (\array_slice(array_values($rawItems), 0, 8) as $rawItem) {
            if (!\is_array($rawItem)) {
                if (\is_scalar($rawItem)) {
                    $text = $this->cleanText($rawItem, 3000);
                    if ('' !== $text) {
                        $items[] = [
                            'recommendation' => $text,
                            'rationale' => '',
                            'sources' => [],
                        ];
                    }
                }

                continue;
            }

            $recommendation = $this->cleanText(
                $rawItem['recommendation'] ?? $rawItem['text'] ?? '',
                3000,
            );
            $rationale = $this->cleanText($rawItem['rationale'] ?? '', 3500);
            if ('' === $recommendation && '' === $rationale) {
                continue;
            }

            $sourceIds = $rawItem['sourceIds'] ?? [];
            if (!\is_array($sourceIds)) {
                $sourceIds = [];
            }

            $sources = [];
            foreach (array_values(array_unique(array_map('strval', $sourceIds))) as $sourceId) {
                $sourceId = trim($sourceId);
                if (!isset(self::EVIDENCE_SOURCES[$sourceId])) {
                    continue;
                }

                $sources[] = [
                    'id' => $sourceId,
                    'title' => self::EVIDENCE_SOURCES[$sourceId]['title'],
                    'url' => self::EVIDENCE_SOURCES[$sourceId]['url'],
                ];
            }

            $items[] = [
                'recommendation' => $recommendation,
                'rationale' => $rationale,
                'sources' => $sources,
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $rawItems, int $limit): array
    {
        if (!\is_array($rawItems)) {
            return [];
        }

        $result = [];
        foreach (\array_slice(array_values($rawItems), 0, $limit) as $value) {
            $text = $this->cleanText($value, 2500);
            if ('' !== $text) {
                $result[] = $text;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function hasUsefulAnalysis(array $analysis): bool
    {
        if ('' !== trim((string) ($analysis['summary'] ?? ''))) {
            return true;
        }

        if (!empty($analysis['priorityActions'])) {
            return true;
        }

        $recommendations = $analysis['recommendations'] ?? [];
        if (!\is_array($recommendations)) {
            return false;
        }

        foreach ($recommendations as $items) {
            if (\is_array($items) && [] !== $items) {
                return true;
            }
        }

        return false;
    }

    private function cleanText(mixed $value, int $maxLength): string
    {
        if (!\is_scalar($value) && !$value instanceof Stringable) {
            return '';
        }

        $text = trim((string) $value);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return mb_substr($text, 0, $maxLength);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{0:array<string,mixed>,1:bool}
     */
    private function compactPayloadForAi(array $payload): array
    {
        $encoded = $this->encodePayload($payload);
        if (\strlen($encoded) <= self::MAX_AI_PAYLOAD_BYTES) {
            return [$payload, false];
        }

        $profiles = [
            ['listLimit' => 80, 'stringLimit' => 2500],
            ['listLimit' => 50, 'stringLimit' => 1600],
            ['listLimit' => 30, 'stringLimit' => 1000],
            ['listLimit' => 20, 'stringLimit' => 700],
            ['listLimit' => 12, 'stringLimit' => 500],
        ];

        $compacted = $payload;
        foreach ($profiles as $profile) {
            $compacted = $this->compactValue(
                $payload,
                $profile['listLimit'],
                $profile['stringLimit'],
            );
            if (!\is_array($compacted)) {
                $compacted = $payload;
            }

            $compacted['transmission'] = [
                'compacted' => true,
                'listLimit' => $profile['listLimit'],
                'stringLimit' => $profile['stringLimit'],
            ];

            if (\strlen($this->encodePayload($compacted)) <= self::MAX_AI_PAYLOAD_BYTES) {
                break;
            }
        }

        return [$compacted, true];
    }

    private function compactValue(mixed $value, int $listLimit, int $stringLimit): mixed
    {
        if (\is_string($value)) {
            return mb_substr($value, 0, $stringLimit);
        }

        if (!\is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $source = $isList ? \array_slice($value, 0, $listLimit) : $value;
        $result = [];

        foreach ($source as $key => $item) {
            $result[$key] = $this->compactValue($item, $listLimit, $stringLimit);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodePayload(array $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not encode the Student Success AI payload.', 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $analysis
     *
     * @return array{0:bool,1:int|null}
     */
    private function sendTeacherMessage(
        User $teacher,
        User $student,
        Course $course,
        array $analysis,
    ): array {
        $teacherId = (int) ($teacher->getId() ?? 0);
        if ($teacherId <= 0) {
            return [false, null];
        }

        $studentName = trim($student->getFirstname().' '.$student->getLastname());
        if ('' === $studentName) {
            $studentName = $student->getUsername();
        }

        $subject = \sprintf(
            'Student Success AI Coach: %s - %s',
            $studentName,
            (string) $course->getTitle(),
        );

        try {
            $messageId = $this->messageHelper->sendMessageSimple(
                $teacherId,
                $subject,
                $this->renderMessageHtml($analysis),
                $teacherId,
                false,
                false,
            );

            if (null !== $messageId && $this->aiDisclosureHelper->isDisclosureEnabled()) {
                $this->aiDisclosureHelper->markAiAssistedExtraField('message', $messageId, true);
            }

            return [null !== $messageId, $messageId];
        } catch (Throwable $exception) {
            error_log('[StudentSuccessAiCoach] Could not save teacher inbox message: '.$exception->getMessage());

            return [false, null];
        }
    }

    /**
     * @param array<string, mixed> $analysis
     */
    private function renderMessageHtml(array $analysis): string
    {
        $html = '<h2>Student Success AI Coach</h2>';
        $summary = trim((string) ($analysis['summary'] ?? ''));
        if ('' !== $summary) {
            $html .= '<p>'.htmlspecialchars($summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
        }

        $priorityActions = $analysis['priorityActions'] ?? [];
        if (\is_array($priorityActions) && [] !== $priorityActions) {
            $html .= '<h3>Priority actions</h3><ul>';
            foreach ($priorityActions as $action) {
                $html .= '<li>'.htmlspecialchars((string) $action, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
            }
            $html .= '</ul>';
        }

        $labels = [
            'additionalActivities' => 'Additional activities',
            'rhythm' => 'Rhythm',
            'learningMethodologies' => 'Learning methodologies',
            'misc' => 'Other recommendations',
        ];
        $recommendations = \is_array($analysis['recommendations'] ?? null)
            ? $analysis['recommendations']
            : [];

        foreach ($labels as $key => $label) {
            $items = $recommendations[$key] ?? [];
            if (!\is_array($items) || [] === $items) {
                continue;
            }

            $html .= '<h3>'.htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h3><ul>';
            foreach ($items as $item) {
                if (!\is_array($item)) {
                    continue;
                }

                $text = htmlspecialchars((string) ($item['recommendation'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $rationale = htmlspecialchars((string) ($item['rationale'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html .= '<li><strong>'.$text.'</strong>';
                if ('' !== $rationale) {
                    $html .= '<br>'.$rationale;
                }

                $sources = $item['sources'] ?? [];
                if (\is_array($sources) && [] !== $sources) {
                    $sourceLabels = [];
                    foreach ($sources as $source) {
                        if (!\is_array($source)) {
                            continue;
                        }
                        $title = htmlspecialchars(
                            (string) ($source['title'] ?? ''),
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8',
                        );
                        $url = htmlspecialchars(
                            (string) ($source['url'] ?? ''),
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8',
                        );
                        if ('' !== $title && '' !== $url) {
                            $sourceLabels[] = '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$title.'</a>';
                        } elseif ('' !== $title) {
                            $sourceLabels[] = $title;
                        }
                    }
                    if ([] !== $sourceLabels) {
                        $html .= '<br><small>Evidence: '.implode('; ', $sourceLabels).'</small>';
                    }
                }

                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '<p><small>This recommendation was generated with AI and should be reviewed by the teacher before acting on it.</small></p>';

        return $html;
    }
}
