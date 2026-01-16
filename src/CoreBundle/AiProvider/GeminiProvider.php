<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Exception;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeminiProvider implements AiProviderInterface, AiDocumentProviderInterface
{
    private string $apiKey;

    // Text
    private string $textModel;
    private string $textUrlTemplate;
    private float $textTemperature;
    private int $textMaxOutputTokens;

    // Document (fallbacks to text if missing)
    private string $documentModel;
    private string $documentUrlTemplate;
    private float $documentTemperature;
    private int $documentMaxOutputTokens;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SettingsManager $settingsManager,
        private readonly AiRequestsRepository $aiRequestsRepository,
        private readonly Security $security
    ) {
        $config = $this->readProvidersConfig();

        if (!isset($config['gemini']) || !\is_array($config['gemini'])) {
            throw new RuntimeException('Gemini configuration is missing.');
        }

        $providerConfig = $config['gemini'];

        $this->apiKey = (string) ($providerConfig['api_key'] ?? '');
        if ('' === $this->apiKey) {
            throw new RuntimeException('Gemini API key is missing.');
        }

        $textCfg = $providerConfig['text'] ?? null;
        if (!\is_array($textCfg)) {
            throw new RuntimeException('Gemini configuration for text processing is missing.');
        }

        $this->textModel = (string) ($textCfg['model'] ?? 'gemini-2.5-flash');
        $this->textUrlTemplate = (string) ($textCfg['url'] ?? 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent');
        $this->textTemperature = (float) ($textCfg['temperature'] ?? 0.7);
        $this->textMaxOutputTokens = (int) ($textCfg['max_output_tokens'] ?? 1000);

        $docCfg = $providerConfig['document'] ?? null;

        $this->documentModel = \is_array($docCfg) ? (string) ($docCfg['model'] ?? $this->textModel) : $this->textModel;
        $this->documentUrlTemplate = \is_array($docCfg) ? (string) ($docCfg['url'] ?? $this->textUrlTemplate) : $this->textUrlTemplate;
        $this->documentTemperature = \is_array($docCfg) ? (float) ($docCfg['temperature'] ?? $this->textTemperature) : $this->textTemperature;
        $this->documentMaxOutputTokens = \is_array($docCfg) ? (int) ($docCfg['max_output_tokens'] ?? $this->textMaxOutputTokens) : $this->textMaxOutputTokens;
    }

    public function generateQuestions(string $topic, int $numQuestions, string $questionType, string $language): ?string
    {
        $prompt = \sprintf(
            'Generate %d "%s" questions in Aiken format in the %s language about "%s".
            Ensure each question follows this format:

            1. The question text.
            A. Option A
            B. Option B
            C. Option C
            D. Option D
            ANSWER: (Correct answer letter)

            The output should be plain text without additional symbols or markdown.',
            $numQuestions,
            $questionType,
            $language,
            $topic
        );

        return $this->requestGemini(
            $this->buildUrl($this->textUrlTemplate, $this->textModel),
            $this->textTemperature,
            $this->textMaxOutputTokens,
            $prompt,
            'quiz'
        );
    }

    public function generateLearnPath(string $topic, int $chaptersCount, string $language, int $wordsCount, bool $addTests, int $numQuestions): ?array
    {
        $tocPrompt = \sprintf(
            'Generate a structured table of contents for a course in "%s" with %d chapters on "%s".
            Return a numbered list, each chapter on a new line. No conclusion.',
            $language,
            $chaptersCount,
            $topic
        );

        $lpStructure = $this->requestGemini(
            $this->buildUrl($this->textUrlTemplate, $this->textModel),
            $this->textTemperature,
            $this->textMaxOutputTokens,
            $tocPrompt,
            'learnpath'
        );

        if (!$lpStructure) {
            return ['success' => false, 'message' => 'Failed to generate course structure.'];
        }

        $lpItems = [];
        $chapters = explode("\n", trim($lpStructure));
        foreach ($chapters as $chapterTitle) {
            $chapterTitle = trim($chapterTitle);
            if ('' === $chapterTitle) {
                continue;
            }

            $chapterPrompt = \sprintf(
                'Create a learning chapter in HTML for "%s" in "%s" with %d words.
                Title: "%s". Assume the reader already knows the context.',
                $topic,
                $language,
                $wordsCount,
                $chapterTitle
            );

            $chapterContent = $this->requestGemini(
                $this->buildUrl($this->textUrlTemplate, $this->textModel),
                $this->textTemperature,
                $this->textMaxOutputTokens,
                $chapterPrompt,
                'learnpath'
            );

            if (!$chapterContent) {
                continue;
            }

            $lpItems[] = [
                'title' => $chapterTitle,
                'content' => "<html><head><title>{$chapterTitle}</title></head><body>{$chapterContent}</body></html>",
            ];
        }

        $quizItems = [];
        if ($addTests) {
            foreach ($lpItems as $chapter) {
                $quizPrompt = \sprintf(
                    'Generate %d multiple-choice questions in Aiken format in %s about "%s".
            Ensure each question follows this format:

            1. The question text.
            A. Option A
            B. Option B
            C. Option C
            D. Option D
            ANSWER: (Correct answer letter)

            Each question must have exactly 4 options and one answer line.
            Return only valid questions without extra text.',
                    $numQuestions,
                    $language,
                    $chapter['title']
                );

                $quizContent = $this->requestGemini(
                    $this->buildUrl($this->textUrlTemplate, $this->textModel),
                    $this->textTemperature,
                    $this->textMaxOutputTokens,
                    $quizPrompt,
                    'learnpath'
                );

                if (!$quizContent) {
                    continue;
                }

                $valid = $this->filterValidAikenQuestions($quizContent);
                if (!empty($valid)) {
                    $quizItems[] = [
                        'title' => 'Quiz: '.$chapter['title'],
                        'content' => implode("\n\n", $valid),
                    ];
                }
            }
        }

        return [
            'success' => true,
            'topic' => $topic,
            'lp_items' => $lpItems,
            'quiz_items' => $quizItems,
        ];
    }

    public function gradeOpenAnswer(string $prompt, string $toolName): ?string
    {
        return $this->requestGemini(
            $this->buildUrl($this->textUrlTemplate, $this->textModel),
            $this->textTemperature,
            $this->textMaxOutputTokens,
            $prompt,
            $toolName
        );
    }

    public function generateDocument(string $prompt, string $toolName, ?array $options = []): ?string
    {
        $format = isset($options['format']) ? (string) $options['format'] : '';
        if ('' !== $format) {
            $prompt .= "\n\nOutput format: {$format}.";
        }

        return $this->requestGemini(
            $this->buildUrl($this->documentUrlTemplate, $this->documentModel),
            $this->documentTemperature,
            $this->documentMaxOutputTokens,
            $prompt,
            $toolName
        );
    }

    private function requestGemini(string $url, float $temperature, int $maxOutputTokens, string $prompt, string $toolName): ?string
    {
        $userId = $this->getUserId();
        if (!$userId) {
            throw new RuntimeException('User not authenticated.');
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxOutputTokens,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if (
                200 !== $statusCode
                || !isset($data['candidates'][0]['content']['parts'][0]['text'])
            ) {
                error_log('[AI][Gemini] Invalid response (status='.$statusCode.').');

                return null;
            }

            $generatedContent = (string) $data['candidates'][0]['content']['parts'][0]['text'];

            // Gemini usually returns usageMetadata, not usage.prompt_tokens
            $usageMeta = $data['usageMetadata'] ?? [];
            $promptTokens = (int) ($usageMeta['promptTokenCount'] ?? 0);
            $completionTokens = (int) ($usageMeta['candidatesTokenCount'] ?? 0);
            $totalTokens = (int) ($usageMeta['totalTokenCount'] ?? ($promptTokens + $completionTokens));

            $aiRequest = new AiRequests();
            $aiRequest
                ->setUserId($userId)
                ->setToolName($toolName)
                ->setRequestText($prompt)
                ->setPromptTokens($promptTokens)
                ->setCompletionTokens($completionTokens)
                ->setTotalTokens($totalTokens)
                ->setAiProvider('gemini')
            ;

            $this->aiRequestsRepository->save($aiRequest);

            return $generatedContent;
        } catch (Exception $e) {
            error_log('[AI][Gemini] Exception: '.$e->getMessage());

            return null;
        }
    }

    private function filterValidAikenQuestions(string $quizContent): array
    {
        $questions = preg_split('/\n{2,}/', trim($quizContent)) ?: [];

        $validQuestions = [];
        foreach ($questions as $questionBlock) {
            $lines = explode("\n", trim($questionBlock));

            if (\count($lines) < 6) {
                continue;
            }

            $options = \array_slice($lines, 1, 4);
            $validOptions = array_filter($options, static fn ($line) => (bool) preg_match('/^[A-D]\. .+/', $line));

            $answerLine = (string) end($lines);
            if (4 === \count($validOptions) && preg_match('/^ANSWER: [A-D]$/', $answerLine)) {
                $validQuestions[] = implode("\n", $lines);
            }
        }

        return $validQuestions;
    }

    private function buildUrl(string $template, string $model): string
    {
        // If template expects %s, inject model; else keep as-is
        return str_contains($template, '%s') ? \sprintf($template, $model) : $template;
    }

    private function getUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof UserInterface ? $user->getId() : null;
    }

    private function readProvidersConfig(): array
    {
        $configJson = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);

        if (\is_string($configJson)) {
            return json_decode($configJson, true) ?? [];
        }

        if (\is_array($configJson)) {
            return $configJson;
        }

        return [];
    }
}
