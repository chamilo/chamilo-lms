<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

final class AiMediaFailoverService
{
    private const DEFAULT_TOOL = 'default';
    private const ACTIVE_PROVIDER_SESSION_PREFIX = 'ai_media_active_provider_';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AiProviderFactory $factory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Returns capabilities based on configuration + interfaces (not key validity).
     *
     * @return array<string,mixed>
     */
    public function getCapabilities(): array
    {
        $hasImage = $this->factory->hasProvidersForType('image');
        $hasVideo = $this->factory->hasProvidersForType('video');
        $hasDocProcess = $this->factory->hasProvidersForType('document_process');

        return [
            'success' => true,
            'has' => [
                'image' => $hasImage,
                'video' => $hasVideo,
                'document_process' => $hasDocProcess,
            ],
            'types' => [
                'image' => $this->factory->getProvidersForType('image'),
                'video' => $this->factory->getProvidersForType('video'),
                'document_process' => $this->factory->getProvidersForType('document_process'),
            ],
        ];
    }

    /**
     * Generate an image using preferred provider, with automatic failover.
     *
     * @param array<string,mixed> $options
     *
     * @return array{provider_used:string,result:array<string,mixed>}
     */
    public function generateImageWithFailover(
        string $prompt,
        string $tool,
        ?string $requestedProvider,
        array $options = []
    ): array {
        $tool = $this->normalizeTool($tool);
        $preferred = $this->resolvePreferredProvider('image', $tool, $requestedProvider);

        // Fast path: try active provider only (if no explicit user selection)
        if (null === $requestedProvider || '' === trim((string) $requestedProvider)) {
            $active = $this->getActiveProviderFromSession('image', $tool);
            if ('' !== $active) {
                try {
                    return $this->trySingleImageProvider($active, $prompt, $tool, $options, true);
                } catch (Throwable $e) {
                    $this->logger->warning('[AI Media] Active image provider failed, starting failover', [
                        'provider' => $active,
                        'tool' => $tool,
                        'error' => $e->getMessage(),
                    ]);

                    $this->clearActiveProviderInSession('image', $tool);
                    // fallthrough to full failover
                }
            }
        }

        $candidates = $this->buildCandidates('image', $preferred);

        $lastError = null;
        foreach ($candidates as $provider) {
            try {
                $out = $this->trySingleImageProvider($provider, $prompt, $tool, $options, false);

                // Persist active provider only on success.
                $this->setActiveProviderInSession('image', $tool, $out['provider_used']);

                return $out;
            } catch (Throwable $e) {
                $lastError = $e;

                $this->logger->warning('[AI Media] Image provider failed, trying next', [
                    'provider' => $provider,
                    'tool' => $tool,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->error('[AI Media] All image providers failed', [
            'preferred' => $preferred,
            'tool' => $tool,
            'error' => $lastError?->getMessage(),
        ]);

        throw new RuntimeException('All image providers failed.');
    }

    /**
     * Generate a video using preferred provider, with automatic failover.
     *
     * @param array<string,mixed> $options
     *
     * @return array{provider_used:string,result:array<string,mixed>}
     */
    public function generateVideoWithFailover(
        string $prompt,
        string $tool,
        ?string $requestedProvider,
        array $options = []
    ): array {
        $tool = $this->normalizeTool($tool);
        $preferred = $this->resolvePreferredProvider('video', $tool, $requestedProvider);

        // Fast path: try active provider only (if no explicit user selection)
        if (null === $requestedProvider || '' === trim((string) $requestedProvider)) {
            $active = $this->getActiveProviderFromSession('video', $tool);
            if ('' !== $active) {
                try {
                    return $this->trySingleVideoProvider($active, $prompt, $tool, $options, true);
                } catch (Throwable $e) {
                    $this->logger->warning('[AI Media] Active video provider failed, starting failover', [
                        'provider' => $active,
                        'tool' => $tool,
                        'error' => $e->getMessage(),
                    ]);

                    $this->clearActiveProviderInSession('video', $tool);
                }
            }
        }

        $candidates = $this->buildCandidates('video', $preferred);

        $lastError = null;
        foreach ($candidates as $provider) {
            try {
                $out = $this->trySingleVideoProvider($provider, $prompt, $tool, $options, false);

                $this->setActiveProviderInSession('video', $tool, $out['provider_used']);

                return $out;
            } catch (Throwable $e) {
                $lastError = $e;

                $this->logger->warning('[AI Media] Video provider failed, trying next', [
                    'provider' => $provider,
                    'tool' => $tool,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->error('[AI Media] All video providers failed', [
            'preferred' => $preferred,
            'tool' => $tool,
            'error' => $lastError?->getMessage(),
        ]);

        throw new RuntimeException('All video providers failed.');
    }

    /**
     * Poll a video job. This requires a provider that implements AiVideoJobProviderInterface.
     *
     * @return array{provider_used:string,result:array<string,mixed>}
     */
    public function getVideoJobWithFailover(
        string $jobId,
        string $tool,
        ?string $requestedProvider,
        int $maxBytes = 15728640
    ): array {
        $tool = $this->normalizeTool($tool);
        $preferred = $this->resolvePreferredProvider('video', $tool, $requestedProvider);
        $candidates = $this->buildCandidates('video', $preferred);

        $lastError = null;
        foreach ($candidates as $provider) {
            try {
                $obj = $this->factory->getProvider($provider, 'video');

                if (!$obj instanceof AiVideoJobProviderInterface) {
                    throw new RuntimeException('Provider does not support video job polling.');
                }

                $status = $obj->getVideoJobStatus($jobId);
                $normalizedStatus = $this->normalizeVideoJobStatus($status, $jobId);

                $result = [
                    'id' => $jobId,
                    'status' => $normalizedStatus['status'],
                    'is_base64' => false,
                    'content' => null,
                    'url' => null,
                    'content_type' => 'video/mp4',
                    'error' => $normalizedStatus['error'],
                ];

                // If terminal and success, try fetching content (base64 or URL).
                if ($this->isTerminalVideoStatus($result['status']) && $this->isSuccessVideoStatus($result['status'])) {
                    $content = $obj->getVideoJobContentAsBase64($jobId, $maxBytes);
                    $normalizedContent = $this->normalizeVideoJobContent($content);

                    $result['is_base64'] = (bool) ($normalizedContent['is_base64'] ?? false);
                    $result['content'] = $normalizedContent['content'] ?? null;
                    $result['url'] = $normalizedContent['url'] ?? null;
                    $result['content_type'] = (string) ($normalizedContent['content_type'] ?? 'video/mp4');
                    $result['error'] = $normalizedContent['error'] ?? $result['error'];
                }

                // Cache provider for next polls.
                $this->setActiveProviderInSession('video', $tool, $provider);

                return [
                    'provider_used' => $provider,
                    'result' => $result,
                ];
            } catch (Throwable $e) {
                $lastError = $e;

                $this->logger->warning('[AI Media] Video job provider failed, trying next', [
                    'provider' => $provider,
                    'tool' => $tool,
                    'jobId' => $jobId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->error('[AI Media] All video job providers failed', [
            'preferred' => $preferred,
            'tool' => $tool,
            'jobId' => $jobId,
            'error' => $lastError?->getMessage(),
        ]);

        throw new RuntimeException('All video job providers failed.');
    }

    private function trySingleImageProvider(
        string $provider,
        string $prompt,
        string $tool,
        array $options,
        bool $isActiveFastPath
    ): array {
        $obj = $this->factory->getProvider($provider, 'image');

        if (!$obj instanceof AiImageProviderInterface) {
            throw new RuntimeException('Provider does not implement AiImageProviderInterface.');
        }

        $raw = $obj->generateImage($prompt, $this->toolNameFor('image', $tool), $options);

        $result = $this->normalizeImageResult($raw);

        if ($this->isErrorLikeMediaResult($result)) {
            throw new RuntimeException('Provider returned an error-like image result.');
        }

        return [
            'provider_used' => $provider,
            'result' => $result,
        ];
    }

    private function trySingleVideoProvider(
        string $provider,
        string $prompt,
        string $tool,
        array $options,
        bool $isActiveFastPath
    ): array {
        $obj = $this->factory->getProvider($provider, 'video');

        if (!$obj instanceof AiVideoProviderInterface) {
            throw new RuntimeException('Provider does not implement AiVideoProviderInterface.');
        }

        $raw = $obj->generateVideo($prompt, $this->toolNameFor('video', $tool), $options);

        $result = $this->normalizeVideoResult($raw);

        if ($this->isErrorLikeMediaResult($result)) {
            throw new RuntimeException('Provider returned an error-like video result.');
        }

        return [
            'provider_used' => $provider,
            'result' => $result,
        ];
    }

    private function normalizeTool(string $tool): string
    {
        $tool = strtolower(trim($tool));
        return '' !== $tool ? $tool : self::DEFAULT_TOOL;
    }

    private function toolNameFor(string $type, string $tool): string
    {
        // Keep stable tool names for AiRequests toolName field.
        // Example: "document_image_generate" / "document_video_generate"
        return $tool.'_'.$type.'_generate';
    }

    private function resolvePreferredProvider(string $type, string $tool, ?string $requestedProvider): string
    {
        $requested = strtolower(trim((string) ($requestedProvider ?? '')));
        if ('' !== $requested) {
            return $requested;
        }

        $active = $this->getActiveProviderFromSession($type, $tool);
        if ('' !== $active) {
            return $active;
        }

        $list = $this->factory->getProvidersForType($type);
        $first = (string) ($list[0] ?? '');

        return strtolower(trim($first));
    }

    /**
     * @return string[]
     */
    private function buildCandidates(string $type, string $preferred): array
    {
        $out = [];
        $preferred = strtolower(trim($preferred));

        if ('' !== $preferred) {
            $out[] = $preferred;
        }

        foreach ($this->factory->getProvidersForType($type) as $p) {
            $p = strtolower(trim((string) $p));
            if ('' === $p) {
                continue;
            }
            if (!\in_array($p, $out, true)) {
                $out[] = $p;
            }
        }

        return $out;
    }

    private function buildActiveProviderSessionKey(string $type, string $tool): string
    {
        return self::ACTIVE_PROVIDER_SESSION_PREFIX.$type.'_'.$tool;
    }

    private function getActiveProviderFromSession(string $type, string $tool): string
    {
        try {
            $req = $this->requestStack->getCurrentRequest();
            if (null === $req || !$req->hasSession()) {
                return '';
            }

            return (string) $req->getSession()->get($this->buildActiveProviderSessionKey($type, $tool), '');
        } catch (Throwable) {
            return '';
        }
    }

    private function setActiveProviderInSession(string $type, string $tool, string $provider): void
    {
        try {
            $req = $this->requestStack->getCurrentRequest();
            if (null === $req || !$req->hasSession()) {
                return;
            }

            $req->getSession()->set($this->buildActiveProviderSessionKey($type, $tool), $provider);
        } catch (Throwable) {
            // ignore
        }
    }

    private function clearActiveProviderInSession(string $type, string $tool): void
    {
        try {
            $req = $this->requestStack->getCurrentRequest();
            if (null === $req || !$req->hasSession()) {
                return;
            }

            $req->getSession()->remove($this->buildActiveProviderSessionKey($type, $tool));
        } catch (Throwable) {
            // ignore
        }
    }

    /**
     * Normalize image responses to a stable shape for the UI.
     *
     * @return array<string,mixed>
     */
    private function normalizeImageResult(array|string|null $raw): array
    {
        if (null === $raw) {
            return ['error' => 'Empty provider response.'];
        }

        if (\is_string($raw)) {
            $s = trim($raw);

            if ($this->isErrorString($s)) {
                return ['error' => $this->stripErrorPrefix($s)];
            }

            if (preg_match('#^https?://#i', $s)) {
                return [
                    'url' => $s,
                    'is_base64' => false,
                    'content' => null,
                    'content_type' => 'image/png',
                    'revised_prompt' => null,
                ];
            }

            return [
                'content' => $s,
                'is_base64' => true,
                'url' => null,
                'content_type' => 'image/png',
                'revised_prompt' => null,
            ];
        }

        $content = isset($raw['content']) && \is_string($raw['content']) ? $raw['content'] : null;
        $url = isset($raw['url']) && \is_string($raw['url']) ? $raw['url'] : null;

        $isBase64 = (bool) ($raw['is_base64'] ?? (null !== $content && '' !== trim($content)));
        $contentType = isset($raw['content_type']) && \is_string($raw['content_type']) ? $raw['content_type'] : 'image/png';
        $revisedPrompt = isset($raw['revised_prompt']) && \is_string($raw['revised_prompt']) ? $raw['revised_prompt'] : null;

        // Allow providers to return an explicit error field.
        if (isset($raw['error']) && \is_string($raw['error']) && '' !== trim($raw['error'])) {
            return ['error' => trim($raw['error'])];
        }

        if ($isBase64 && (null === $content || '' === trim($content))) {
            return ['error' => 'Missing base64 content.'];
        }

        if (!$isBase64 && (null === $url || '' === trim($url))) {
            // It's allowed to be base64-only; but if provider explicitly says not base64, url must exist.
            return ['error' => 'Missing URL content.'];
        }

        return [
            'content' => $content,
            'url' => $url,
            'is_base64' => $isBase64,
            'content_type' => $contentType,
            'revised_prompt' => $revisedPrompt,
        ];
    }

    /**
     * Normalize video responses to a stable shape for the UI.
     *
     * @return array<string,mixed>
     */
    private function normalizeVideoResult(array|string|null $raw): array
    {
        if (null === $raw) {
            return ['error' => 'Empty provider response.'];
        }

        if (\is_string($raw)) {
            $s = trim($raw);

            if ($this->isErrorString($s)) {
                return ['error' => $this->stripErrorPrefix($s)];
            }

            if (preg_match('#^https?://#i', $s)) {
                return [
                    'id' => null,
                    'status' => 'completed',
                    'url' => $s,
                    'is_base64' => false,
                    'content' => null,
                    'content_type' => 'video/mp4',
                    'revised_prompt' => null,
                ];
            }

            return [
                'id' => null,
                'status' => 'completed',
                'content' => $s,
                'is_base64' => true,
                'url' => null,
                'content_type' => 'video/mp4',
                'revised_prompt' => null,
            ];
        }

        if (isset($raw['error']) && \is_string($raw['error']) && '' !== trim($raw['error'])) {
            return ['error' => trim($raw['error'])];
        }

        return [
            'id' => isset($raw['id']) && \is_string($raw['id']) ? $raw['id'] : null,
            'status' => isset($raw['status']) && \is_string($raw['status']) ? $raw['status'] : '',
            'content' => isset($raw['content']) && \is_string($raw['content']) ? $raw['content'] : null,
            'url' => isset($raw['url']) && \is_string($raw['url']) ? $raw['url'] : null,
            'is_base64' => (bool) ($raw['is_base64'] ?? false),
            'content_type' => isset($raw['content_type']) && \is_string($raw['content_type']) ? $raw['content_type'] : 'video/mp4',
            'revised_prompt' => isset($raw['revised_prompt']) && \is_string($raw['revised_prompt']) ? $raw['revised_prompt'] : null,
        ];
    }

    /**
     * @return array{status:string,error:?string}
     */
    private function normalizeVideoJobStatus(?array $raw, string $jobId): array
    {
        if (!\is_array($raw)) {
            return ['status' => '', 'error' => 'Empty job status response.'];
        }

        $status = isset($raw['status']) && \is_string($raw['status']) ? trim($raw['status']) : '';
        $error = isset($raw['error']) && \is_string($raw['error']) ? trim($raw['error']) : null;

        return [
            'status' => $status,
            'error' => '' !== (string) $error ? $error : null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function normalizeVideoJobContent(?array $raw): array
    {
        if (!\is_array($raw)) {
            return ['error' => 'Empty content response.'];
        }

        $isBase64 = (bool) ($raw['is_base64'] ?? false);
        $content = isset($raw['content']) && \is_string($raw['content']) ? $raw['content'] : null;
        $url = isset($raw['url']) && \is_string($raw['url']) ? $raw['url'] : null;
        $contentType = isset($raw['content_type']) && \is_string($raw['content_type']) ? $raw['content_type'] : 'video/mp4';
        $error = isset($raw['error']) && \is_string($raw['error']) ? trim($raw['error']) : null;

        return [
            'is_base64' => $isBase64,
            'content' => $content,
            'url' => $url,
            'content_type' => $contentType,
            'error' => '' !== (string) $error ? $error : null,
        ];
    }

    private function isErrorLikeMediaResult(array $result): bool
    {
        if (isset($result['error']) && \is_string($result['error']) && '' !== trim($result['error'])) {
            return true;
        }

        // Safety: treat a missing usable payload as error.
        $isBase64 = (bool) ($result['is_base64'] ?? false);
        $content = isset($result['content']) && \is_string($result['content']) ? trim($result['content']) : '';
        $url = isset($result['url']) && \is_string($result['url']) ? trim($result['url']) : '';

        if ($isBase64) {
            return '' === $content;
        }

        // Non-base64 requires URL OR job id (video).
        if ('' !== $url) {
            return false;
        }

        if (isset($result['id']) && \is_string($result['id']) && '' !== trim($result['id'])) {
            return false;
        }

        return true;
    }

    private function isErrorString(string $text): bool
    {
        $t = strtolower(trim($text));
        return str_starts_with($t, 'error:') || str_contains($t, 'invalid api key') || str_contains($t, 'incorrect api key');
    }

    private function stripErrorPrefix(string $text): string
    {
        $s = trim($text);
        if (str_starts_with(strtolower($s), 'error:')) {
            $s = trim(substr($s, 6));
        }
        return '' !== $s ? $s : 'Provider returned an error.';
    }

    private function isTerminalVideoStatus(string $status): bool
    {
        $s = strtolower(trim($status));
        return \in_array($s, ['completed', 'succeeded', 'done', 'failed', 'canceled', 'cancelled', 'error'], true);
    }

    private function isSuccessVideoStatus(string $status): bool
    {
        $s = strtolower(trim($status));
        return \in_array($s, ['completed', 'succeeded', 'done'], true);
    }
}
