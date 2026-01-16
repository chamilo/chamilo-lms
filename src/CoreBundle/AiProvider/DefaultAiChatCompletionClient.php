<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Psr\Log\LoggerInterface;
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
        try {
            $providerInstance = $this->factory->getProvider($provider, 'text');

            if (!\is_object($providerInstance)) {
                $this->logger->warning('[AiTutorChat] Invalid provider instance', [
                    'provider' => $provider,
                    'type' => \gettype($providerInstance),
                ]);

                return new AiChatCompletionResult('AI is temporarily unavailable. Please try again later.', null);
            }

            // If provider has a native chatWithMeta(), use it
            if (method_exists($providerInstance, 'chatWithMeta')) {
                $r = $providerInstance->chatWithMeta($messages, $options);
                if ($r instanceof AiChatCompletionResult) {
                    return $r;
                }
            }

            // Default: fallback to chat() and no conversation id
            $text = '';

            if (method_exists($providerInstance, 'chat')) {
                $text = (string) $providerInstance->chat($messages, $options);
            } elseif (method_exists($providerInstance, 'generateText')) {
                $prompt = $this->messagesToPrompt($messages);
                $text = (string) $providerInstance->generateText($prompt, $options);
            } elseif (method_exists($providerInstance, 'generate')) {
                $prompt = $this->messagesToPrompt($messages);
                $text = (string) $providerInstance->generate($prompt, $options);
            }

            $text = trim($text);
            if ('' === $text) {
                $text = 'I could not generate a response right now. Please try again.';
            }

            return new AiChatCompletionResult($text, null);
        } catch (Throwable $e) {
            $this->logger->error('[AiTutorChat] Chat provider call failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return new AiChatCompletionResult('AI is temporarily unavailable. Please try again later.', null);
        }
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

        // If provider returned empty/unknown, fallback message
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
        // Prefer explicit keys only (avoid picking generic "id" which might be message id).
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
     * Convert messages into a compact prompt for non-chat providers.
     *
     * @param array<int, array{role:string,content:string}> $messages
     */
    private function messagesToPrompt(array $messages): string
    {
        $lines = [];

        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = trim((string) ($m['content'] ?? ''));

            if ('' === $content) {
                continue;
            }

            $lines[] = strtoupper($role).': '.$content;
        }

        return implode("\n", $lines);
    }
}
