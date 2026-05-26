<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class DefaultAiChatCompletionClient implements AiChatCompletionClientInterface
{
    public function __construct(
        private readonly AiProviderFactory $factory,
        private readonly LoggerInterface $logger
    ) {}

    public function chat(string $provider, array $messages, array $options = []): string
    {
        $r = $this->chatWithMeta($provider, $messages, $options);

        $text = trim($r->text);
        if ('' === $text) {
            return 'I could not generate a response right now. Please try again.';
        }

        return $text;
    }

    public function chatWithMeta(string $provider, array $messages, array $options = []): AiChatCompletionResult
    {
        $throwOnError = (bool) ($options['throw_on_error'] ?? false);
        unset($options['throw_on_error']);

        try {
            $providerInstance = $this->factory->getProvider($provider, 'text');

            if (!\is_object($providerInstance)) {
                $this->logger->warning('[AiTutorChat] Invalid provider instance', [
                    'provider' => $provider,
                    'type' => \gettype($providerInstance),
                ]);

                if ($throwOnError) {
                    throw new RuntimeException('Invalid provider instance.');
                }

                return new AiChatCompletionResult('AI is temporarily unavailable. Please try again later.', null);
            }

            // 1) Preferred: chatWithMeta()
            if (method_exists($providerInstance, 'chatWithMeta')) {
                $raw = $providerInstance->chatWithMeta($messages, $options);
                $res = $this->extractResult($provider, $raw);

                if ($throwOnError) {
                    $this->assertUsableOrThrow($provider, $res);
                }

                return $res;
            }

            // 2) Next: chat()
            if (method_exists($providerInstance, 'chat')) {
                $raw = $providerInstance->chat($messages, $options);
                $res = $this->extractResult($provider, $raw);

                if ($throwOnError) {
                    $this->assertUsableOrThrow($provider, $res);
                }

                return $res;
            }

            // 3) Universal fallback: generateText(prompt)
            if (method_exists($providerInstance, 'generateText')) {
                $prompt = $this->messagesToPrompt($messages);
                $raw = $providerInstance->generateText($prompt, $options);
                $res = $this->extractResult($provider, $raw);

                if ($throwOnError) {
                    $this->assertUsableOrThrow($provider, $res);
                }

                return $res;
            }

            // 4) Legacy fallback: generate(prompt)
            if (method_exists($providerInstance, 'generate')) {
                $prompt = $this->messagesToPrompt($messages);
                $raw = $providerInstance->generate($prompt, $options);
                $res = $this->extractResult($provider, $raw);

                if ($throwOnError) {
                    $this->assertUsableOrThrow($provider, $res);
                }

                return $res;
            }

            $this->logger->warning('[AiTutorChat] Provider does not support chat or text generation', [
                'provider' => $provider,
                'class' => $providerInstance::class,
            ]);

            if ($throwOnError) {
                throw new RuntimeException('Provider does not support chat or text generation.');
            }

            return new AiChatCompletionResult('AI is temporarily unavailable. Please try again later.', null);
        } catch (Throwable $e) {
            $this->logger->error('[AiTutorChat] Chat provider call failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            if ($throwOnError) {
                throw $e;
            }

            return new AiChatCompletionResult('AI is temporarily unavailable. Please try again later.', null);
        }
    }

    private function assertUsableOrThrow(string $provider, AiChatCompletionResult $r): void
    {
        $text = trim((string) $r->text);

        if ($this->looksLikeErrorResponseText($text)) {
            $this->logger->warning('[AiTutorChat] Error-like response text detected', [
                'provider' => $provider,
                'preview' => mb_substr($text, 0, 180),
            ]);

            throw new RuntimeException('Provider returned an error-like response text.');
        }
    }

    private function looksLikeErrorResponseText(string $text): bool
    {
        $t = strtolower(trim($text));

        if ('' === $t) {
            return true;
        }

        // Generic fallback
        if (str_contains($t, 'ai is temporarily unavailable')) {
            return true;
        }

        // Typical auth/config errors that some providers return as plain text
        $needles = [
            'incorrect api key',
            'invalid api key',
            'you can find your api key',
            'error response:',
            'invalid_api_key',
            'unauthorized',
            'forbidden',
            'http 401',
            'http 403',
            'rate limit',
            'quota',
        ];

        foreach ($needles as $n) {
            if (str_contains($t, $n)) {
                return true;
            }
        }

        return false;
    }

    private function extractResult(string $provider, mixed $raw): AiChatCompletionResult
    {
        // Already normalized
        if ($raw instanceof AiChatCompletionResult) {
            return $this->normalizeEmpty($raw);
        }

        $text = '';
        $conversationId = null;

        // Common array shapes: ['text' => '...', 'conversation_id' => '...']
        if (\is_array($raw)) {
            $text = (string) ($raw['text'] ?? $raw['content'] ?? $raw['message'] ?? $raw['output_text'] ?? '');
            $conversationId = $this->extractConversationIdFromArray($raw);

            return $this->normalizeEmpty(new AiChatCompletionResult(trim($text), $conversationId));
        }

        // Objects: try getters / public properties
        if (\is_object($raw)) {
            if (method_exists($raw, 'getText')) {
                $text = (string) $raw->getText();
            } elseif (property_exists($raw, 'text')) {
                /** @phpstan-ignore-next-line */
                $text = (string) $raw->text;
            } elseif (method_exists($raw, '__toString')) {
                $text = (string) $raw;
            }

            if (method_exists($raw, 'getConversationId')) {
                $conversationId = $raw->getConversationId();
            } elseif (property_exists($raw, 'conversationId')) {
                /** @phpstan-ignore-next-line */
                $conversationId = $raw->conversationId;
            } elseif (property_exists($raw, 'conversation_id')) {
                /** @phpstan-ignore-next-line */
                $conversationId = $raw->conversation_id;
            } elseif (property_exists($raw, 'previous_id')) {
                /** @phpstan-ignore-next-line */
                $conversationId = $raw->previous_id;
            }

            $conversationId = $this->normalizeConvId($conversationId);

            return $this->normalizeEmpty(new AiChatCompletionResult(trim((string) $text), $conversationId));
        }

        // Strings
        $text = trim((string) $raw);

        if ('' === $text) {
            $this->logger->warning('[AiTutorChat] Provider returned empty response', [
                'provider' => $provider,
                'rawType' => \gettype($raw),
            ]);

            $text = 'I could not generate a response right now. Please try again.';
        }

        return new AiChatCompletionResult($text, null);
    }

    private function normalizeEmpty(AiChatCompletionResult $r): AiChatCompletionResult
    {
        $text = trim($r->text);
        if ('' === $text) {
            return new AiChatCompletionResult(
                'I could not generate a response right now. Please try again.',
                $this->normalizeConvId($r->conversationId)
            );
        }

        return new AiChatCompletionResult($text, $this->normalizeConvId($r->conversationId));
    }

    /**
     * @param array<string,mixed> $raw
     */
    private function extractConversationIdFromArray(array $raw): ?string
    {
        $cand = $raw['conversation_id'] ?? $raw['conversationId'] ?? $raw['previous_id'] ?? $raw['previousId'] ?? null;

        return $this->normalizeConvId($cand);
    }

    private function normalizeConvId(mixed $v): ?string
    {
        if (null === $v) {
            return null;
        }

        $s = trim((string) $v);

        return '' !== $s ? $s : null;
    }

    /**
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function messagesToPrompt(array $messages): string
    {
        $lines = [];

        foreach ($messages as $m) {
            $role = (string) ($m['role'] ?? 'user');
            $content = trim((string) ($m['content'] ?? ''));

            if ('' === $content) {
                continue;
            }

            $lines[] = strtoupper($role).': '.$content;
        }

        return implode("\n", $lines);
    }
}
