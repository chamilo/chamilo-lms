<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Mcp;

use Chamilo\CoreBundle\AiProvider\AiChatCompletionClientInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Ai\AiRequestQuotaGuard;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

final readonly class McpTextAiService
{
    private const MAX_JSON_RESPONSE_LENGTH = 120_000;
    private const MAX_TEXT_RESPONSE_LENGTH = 2_000_000;
    private const MAX_REPAIR_CONTEXT_LENGTH = 30_000;

    public function __construct(
        private AiProviderFactory $aiProviderFactory,
        private AiChatCompletionClientInterface $chatCompletionClient,
        private AiRequestQuotaGuard $quotaGuard,
    ) {}

    public function resolveProvider(User $user, ?string $requestedProvider = null): string
    {
        $providerNames = $this->getProviderNames();
        if ([] === $providerNames) {
            throw new RuntimeException('No AI text provider is configured.');
        }

        $requestedProvider = null !== $requestedProvider ? trim($requestedProvider) : '';
        $providerName = '' === $requestedProvider ? $providerNames[0] : $requestedProvider;

        if (!\in_array($providerName, $providerNames, true)) {
            throw new InvalidArgumentException('The selected AI text provider is not available.');
        }

        $this->quotaGuard->assertCanRequest($user, $providerName, 'text');

        return $providerName;
    }

    /**
     * Request a JSON object and perform one controlled repair request when the
     * provider returns prose, Markdown fences or truncated/invalid JSON.
     *
     * @return array<string, mixed>
     */
    public function requestJson(
        User $user,
        ?string $requestedProvider,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens = 6000,
    ): array {
        $providerName = $this->resolveProvider($user, $requestedProvider);
        $raw = $this->requestRawJson(
            $providerName,
            $systemPrompt,
            $userPrompt,
            $maxTokens,
            0.1,
        );

        $repaired = false;

        try {
            $decoded = $this->decodeJson($raw);
        } catch (RuntimeException $firstException) {
            $repaired = true;
            $repairSystemPrompt = <<<'PROMPT'
Return only one valid JSON value. Repair the supplied response so it follows the original schema and instructions exactly. Preserve valid content, complete truncated arrays or strings when necessary, remove prose and Markdown, and do not add explanations outside the JSON.
PROMPT;
            $repairUserPrompt = "Original schema and instructions:\n"
                .mb_substr($systemPrompt, 0, self::MAX_REPAIR_CONTEXT_LENGTH)
                ."\n\nOriginal request:\n"
                .mb_substr($userPrompt, 0, self::MAX_REPAIR_CONTEXT_LENGTH)
                ."\n\nInvalid response to repair:\n"
                .mb_substr($raw, 0, self::MAX_REPAIR_CONTEXT_LENGTH);

            $repairedRaw = $this->requestRawJson(
                $providerName,
                $repairSystemPrompt,
                $repairUserPrompt,
                $maxTokens,
                0.0,
            );

            try {
                $decoded = $this->decodeJson($repairedRaw);
            } catch (RuntimeException $repairException) {
                throw new RuntimeException(
                    'The AI model returned invalid JSON after one repair attempt.',
                    0,
                    $repairException,
                );
            }
        }

        $decoded['_provider'] = $providerName;
        $decoded['_structured_output_repaired'] = $repaired;

        return $decoded;
    }

    /**
     * Request plain text or HTML and perform one controlled retry when the
     * provider returns an empty response, an error wrapper or only Markdown
     * fences.
     *
     * @return array{
     *     content: string,
     *     provider: string,
     *     repaired: bool
     * }
     */
    public function requestText(
        User $user,
        ?string $requestedProvider,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens = 6000,
    ): array {
        $providerName = $this->resolveProvider($user, $requestedProvider);
        $content = $this->requestRawText(
            $providerName,
            $systemPrompt,
            $userPrompt,
            $maxTokens,
            0.25,
        );

        $repaired = false;

        if (!$this->isUsableText($content)) {
            $repaired = true;
            $content = $this->requestRawText(
                $providerName,
                <<<'PROMPT'
Return only the requested final educational content. Do not include explanations, apologies, Markdown code fences or JSON. Complete the original request directly.
PROMPT,
                "Original instructions:\n"
                    .mb_substr($systemPrompt, 0, self::MAX_REPAIR_CONTEXT_LENGTH)
                    ."\n\nOriginal request:\n"
                    .mb_substr($userPrompt, 0, self::MAX_REPAIR_CONTEXT_LENGTH),
                $maxTokens,
                0.1,
            );
        }

        $content = $this->normalizeTextResponse($content);
        if (!$this->isUsableText($content)) {
            throw new RuntimeException(
                'The AI model did not return usable educational content after one retry.'
            );
        }

        return [
            'content' => $content,
            'provider' => $providerName,
            'repaired' => $repaired,
        ];
    }

    /**
     * @return list<string>
     */
    private function getProviderNames(): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $providerName): string => trim((string) $providerName),
            $this->aiProviderFactory->getProvidersForType('text'),
        )));
    }

    private function requestRawJson(
        string $providerName,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens,
        float $temperature,
    ): string {
        $raw = $this->chatCompletionClient->chat(
            $providerName,
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            [
                'temperature' => $temperature,
                'max_output_tokens' => $maxTokens,
                'max_tokens' => $maxTokens,
                'response_mime_type' => 'application/json',
                'throw_on_error' => true,
            ],
        );

        $raw = trim(mb_substr($raw, 0, self::MAX_JSON_RESPONSE_LENGTH));
        if ('' === $raw || str_starts_with($raw, 'Error:')) {
            throw new RuntimeException('The AI model returned an empty or invalid structured response.');
        }

        return $raw;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $raw = $this->normalizeRawJson($raw);

        $decoded = $this->tryDecode($raw);
        if (null !== $decoded) {
            return $decoded;
        }

        $length = strlen($raw);
        for ($offset = 0; $offset < $length; ++$offset) {
            $char = $raw[$offset];
            if ('{' !== $char && '[' !== $char) {
                continue;
            }

            $candidate = $this->extractBalancedJson($raw, $offset);
            if (null === $candidate) {
                continue;
            }

            $decoded = $this->tryDecode($candidate);
            if (null !== $decoded) {
                return $decoded;
            }
        }

        throw new RuntimeException('The AI model returned invalid JSON.');
    }

    private function normalizeRawJson(string $raw): string
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw)) ?? trim($raw);
        $raw = (string) preg_replace('/^```(?:json)?\s*/iu', '', $raw);
        $raw = (string) preg_replace('/\s*```$/u', '', $raw);

        return trim($raw);
    }

    private function requestRawText(
        string $providerName,
        string $systemPrompt,
        string $userPrompt,
        int $maxTokens,
        float $temperature,
    ): string {
        $raw = $this->chatCompletionClient->chat(
            $providerName,
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            [
                'temperature' => $temperature,
                'max_output_tokens' => $maxTokens,
                'max_tokens' => $maxTokens,
                'throw_on_error' => true,
            ],
        );

        return trim(mb_substr($raw, 0, self::MAX_TEXT_RESPONSE_LENGTH));
    }

    private function normalizeTextResponse(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', trim($content)) ?? trim($content);
        $content = (string) preg_replace('/^```(?:html|text|markdown)?\s*/iu', '', $content);
        $content = (string) preg_replace('/\s*```$/u', '', $content);

        return trim($content);
    }

    private function isUsableText(string $content): bool
    {
        $content = $this->normalizeTextResponse($content);
        if ('' === $content || str_starts_with($content, 'Error:')) {
            return false;
        }

        return mb_strlen(trim(strip_tags($content))) >= 40;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryDecode(string $candidate): ?array
    {
        try {
            $decoded = json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }

    private function extractBalancedJson(string $raw, int $start): ?string
    {
        $opening = $raw[$start] ?? '';
        if (!\in_array($opening, ['{', '['], true)) {
            return null;
        }

        $stack = [];
        $inString = false;
        $escaped = false;
        $length = strlen($raw);

        for ($index = $start; $index < $length; ++$index) {
            $char = $raw[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ('\\' === $char) {
                    $escaped = true;

                    continue;
                }

                if ('"' === $char) {
                    $inString = false;
                }

                continue;
            }

            if ('"' === $char) {
                $inString = true;

                continue;
            }

            if ('{' === $char || '[' === $char) {
                $stack[] = $char;

                continue;
            }

            if ('}' !== $char && ']' !== $char) {
                continue;
            }

            $expectedOpening = '}' === $char ? '{' : '[';
            $actualOpening = array_pop($stack);
            if ($actualOpening !== $expectedOpening) {
                return null;
            }

            if ([] === $stack) {
                return substr($raw, $start, $index - $start + 1);
            }
        }

        return null;
    }
}
