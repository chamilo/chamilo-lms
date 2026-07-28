<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Ai;

use Chamilo\CoreBundle\AiProvider\AiChatCompletionClientInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\LegalRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_UNESCAPED_UNICODE;

final readonly class TermsAndConditionsTranslationService
{
    public const string CSRF_TOKEN_ID = 'terms_and_conditions_translation';

    private const string TOOL_NAME = 'terms_and_conditions_translation';
    private const int MAX_SECTION_HTML_LENGTH = 100000;
    private const int MAX_TOTAL_HTML_LENGTH = 250000;
    private const int MAX_OUTPUT_TOKENS = 12000;

    public function __construct(
        private SettingsManager $settingsManager,
        private LanguageRepository $languageRepository,
        private LegalRepository $legalRepository,
        private AiProviderFactory $aiProviderFactory,
        private AiChatCompletionClientInterface $aiClient,
        private AiRequestsRepository $aiRequestsRepository,
        private Security $security,
        private AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isTruthy($this->settingsManager->getSetting('ai_helpers.enable_ai_helpers', true))
            && $this->aiProviderFactory->hasProvidersForType('text');
    }

    /**
     * @return array<int, array{label: string, value: int, isocode: string, latestVersion: int|null}>
     */
    public function getActiveLanguageOptions(): array
    {
        /** @var Language[] $languages */
        $languages = $this->languageRepository->getAllAvailable()->getQuery()->getResult();
        $options = [];

        foreach ($languages as $language) {
            $languageId = (int) $language->getId();
            if ($languageId <= 0) {
                continue;
            }

            $label = trim((string) $language->getOriginalName());
            if ('' === $label) {
                $label = $language->getEnglishName();
            }

            $options[] = [
                'label' => $label,
                'value' => $languageId,
                'isocode' => $language->getIsocode(),
                'latestVersion' => $this->legalRepository->getLastVersionByLanguage($languageId),
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getProviderOptions(): array
    {
        $configured = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);
        if (\is_string($configured)) {
            $decoded = json_decode($configured, true);
            $configured = \is_array($decoded) ? $decoded : [];
        }
        if (!\is_array($configured)) {
            $configured = [];
        }

        $options = [];
        foreach ($this->aiProviderFactory->getProvidersForType('text') as $providerName) {
            $providerName = trim((string) $providerName);
            if ('' === $providerName) {
                continue;
            }

            $providerConfig = $configured[$providerName] ?? [];
            $model = '';
            if (\is_array($providerConfig)) {
                if (isset($providerConfig['text']) && \is_array($providerConfig['text'])) {
                    $model = trim((string) ($providerConfig['text']['model'] ?? ''));
                } else {
                    $model = trim((string) ($providerConfig['model'] ?? ''));
                }
            }

            $options[] = [
                'label' => '' !== $model ? $providerName.' ('.$model.')' : $providerName,
                'value' => $providerName,
            ];
        }

        return $options;
    }

    /**
     * @param array<int|string, mixed> $sections
     *
     * @return array<int, string>
     */
    public function translateSections(
        array $sections,
        Language $sourceLanguage,
        Language $targetLanguage,
        string $provider,
    ): array {
        $normalizedSections = $this->normalizeSections($sections);
        $availableProviders = array_column($this->getProviderOptions(), 'value');

        if ('' === $provider) {
            $provider = (string) ($availableProviders[0] ?? '');
        }
        if ('' === $provider || !\in_array($provider, $availableProviders, true)) {
            throw new RuntimeException('The selected AI text provider is not available.');
        }

        $sourceLabel = $this->getLanguageLabel($sourceLanguage);
        $targetLabel = $this->getLanguageLabel($targetLanguage);
        $translatedSections = $normalizedSections;
        $translatedCount = 0;

        foreach ($normalizedSections as $type => $html) {
            if ('' === trim($html)) {
                continue;
            }

            $translatedSections[$type] = $this->translateSection(
                sourceHtml: $html,
                sectionType: $type,
                sourceLanguage: $sourceLanguage->getIsocode(),
                sourceLabel: $sourceLabel,
                targetLanguage: $targetLanguage->getIsocode(),
                targetLabel: $targetLabel,
                provider: $provider,
            );
            ++$translatedCount;
        }

        if (0 === $translatedCount) {
            throw new RuntimeException('At least one terms and conditions section must contain text.');
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $auditPayload = json_encode(
                $normalizedSections,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
            );
            $contentLength = 0;
            foreach ($normalizedSections as $sectionHtml) {
                $contentLength += mb_strlen($sectionHtml);
            }

            $this->aiDisclosureHelper->logAudit(
                targetKey: 'platform:terms_translation:'.hash('sha256', (string) $auditPayload),
                userId: (int) $user->getId(),
                meta: [
                    'feature' => 'terms_and_conditions_translation',
                    'provider' => $provider,
                    'source_language' => $sourceLanguage->getIsocode(),
                    'target_language' => $targetLanguage->getIsocode(),
                    'translated_sections' => $translatedCount,
                    'content_length' => $contentLength,
                ],
            );
        }

        return $translatedSections;
    }

    /**
     * @param array<int|string, mixed> $sections
     *
     * @return array<int, string>
     */
    private function normalizeSections(array $sections): array
    {
        $normalized = [];
        $totalLength = 0;

        for ($type = 0; $type <= 15; ++$type) {
            $value = $sections[$type] ?? $sections[(string) $type] ?? '';
            if (!\is_string($value)) {
                throw new RuntimeException('One or more terms and conditions sections are invalid.');
            }

            $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
            $length = mb_strlen($value);
            if ($length > self::MAX_SECTION_HTML_LENGTH) {
                throw new RuntimeException('One terms and conditions section is too large to translate.');
            }

            $totalLength += $length;
            $normalized[$type] = $value;
        }

        if ($totalLength > self::MAX_TOTAL_HTML_LENGTH) {
            throw new RuntimeException('The terms and conditions content is too large to translate in one operation.');
        }

        return $normalized;
    }

    private function translateSection(
        string $sourceHtml,
        int $sectionType,
        string $sourceLanguage,
        string $sourceLabel,
        string $targetLanguage,
        string $targetLabel,
        string $provider,
    ): string {
        $marker = bin2hex(random_bytes(12));
        $requestedAfter = new DateTimeImmutable('-1 second');
        $user = $this->security->getUser();
        $userId = $user instanceof User ? (int) $user->getId() : 0;

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a professional legal translator. Translate the supplied terms and conditions HTML accurately using formal legal language appropriate for the target language. Return only the translated HTML fragment. Preserve all HTML tags, attributes, URLs, placeholders, entities, identifiers and data attributes. Do not add Markdown fences, explanations, comments, disclaimers, headings or language wrappers. Do not translate text inside code, pre, script or style elements.',
            ],
            [
                'role' => 'user',
                'content' => \sprintf(
                    "Request marker: %s\nSection type: %d\nTranslate from %s (%s) to %s (%s).\n\nHTML:\n%s",
                    $marker,
                    $sectionType,
                    $sourceLabel,
                    $sourceLanguage,
                    $targetLabel,
                    $targetLanguage,
                    $sourceHtml,
                ),
            ],
        ];

        try {
            $result = $this->aiClient->chatWithMeta($provider, $messages, [
                'tool' => self::TOOL_NAME,
                'language' => $targetLanguage,
                'temperature' => 0.1,
                'max_output_tokens' => self::MAX_OUTPUT_TOKENS,
                'max_tokens' => self::MAX_OUTPUT_TOKENS,
                'throw_on_error' => false,
            ]);

            $providerText = trim((string) $result->text);
            if ($this->looksLikeProviderFailureText($providerText)) {
                throw new RuntimeException('The AI provider could not generate the translation.');
            }

            $translatedHtml = $this->normalizeAiHtml($providerText);
            if ('' === trim($translatedHtml)) {
                throw new RuntimeException('The AI provider returned an empty translation.');
            }

            $this->assertProtectedValuesPreserved($sourceHtml, $translatedHtml);

            return $translatedHtml;
        } finally {
            if ($userId > 0) {
                $this->redactLatestAiRequest(
                    userId: $userId,
                    provider: $provider,
                    requestedAfter: $requestedAfter,
                    marker: $marker,
                    sourceHtml: $sourceHtml,
                    sectionType: $sectionType,
                    sourceLanguage: $sourceLanguage,
                    targetLanguage: $targetLanguage,
                );
            }
        }
    }

    private function redactLatestAiRequest(
        int $userId,
        string $provider,
        DateTimeImmutable $requestedAfter,
        string $marker,
        string $sourceHtml,
        int $sectionType,
        string $sourceLanguage,
        string $targetLanguage,
    ): void {
        try {
            foreach ([self::TOOL_NAME, 'chat', 'generateText'] as $toolName) {
                $request = $this->aiRequestsRepository->findLatestUnlinkedToolRequestSince(
                    $userId,
                    $toolName,
                    $provider,
                    $requestedAfter,
                );
                if (null === $request || !str_contains($request->getRequestText(), $marker)) {
                    continue;
                }

                $request->setRequestText(\sprintf(
                    'Terms and conditions translation; section=%d; source=%s; target=%s; content_sha256=%s; content_length=%d',
                    $sectionType,
                    $sourceLanguage,
                    $targetLanguage,
                    hash('sha256', $sourceHtml),
                    mb_strlen($sourceHtml),
                ));
                $this->aiRequestsRepository->save($request);

                return;
            }
        } catch (Throwable) {
            // Audit redaction must not replace the administrator-facing result.
        }
    }

    private function looksLikeProviderFailureText(string $text): bool
    {
        $normalized = strtolower(trim(strip_tags($text)));

        if ('' === $normalized) {
            return true;
        }

        $knownFailures = [
            'ai is temporarily unavailable',
            'i could not generate a response right now',
            'incorrect api key',
            'invalid api key',
            'invalid_api_key',
            'you can find your api key',
            'http 401',
            'http 403',
            'rate limit exceeded',
            'quota exceeded',
            'resource exhausted',
            'resource_exhausted',
            'the provider returned an error response',
        ];

        foreach ($knownFailures as $failure) {
            if (str_contains($normalized, $failure)) {
                return true;
            }
        }

        return 1 === preg_match(
            '/^(?:error(?: response)?\s*:\s*)?(?:unauthorized|forbidden)\s*[.!]?$/i',
            $normalized
        );
    }

    private function normalizeAiHtml(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
        $html = preg_replace('/\s*```$/', '', $html) ?? $html;
        $html = trim($html);

        if (1 === preg_match('/<\s*(?:script|iframe|object|embed|form|meta|base|link)\b/i', $html)
            || 1 === preg_match('/\son[a-z]+\s*=/i', $html)
            || 1 === preg_match('/(?:href|src|xlink:href)\s*=\s*["\']?\s*(?:javascript|vbscript|data:text\/html)/i', $html)
            || 1 === preg_match('/(?:expression\s*\(|url\s*\(\s*["\']?javascript:)/i', $html)
        ) {
            throw new RuntimeException('The AI provider returned unsafe HTML.');
        }

        if (1 === preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = trim((string) ($matches[1] ?? ''));
        }

        return $html;
    }

    private function assertProtectedValuesPreserved(string $sourceHtml, string $translatedHtml): void
    {
        if ($this->extractProtectedValues($sourceHtml) !== $this->extractProtectedValues($translatedHtml)) {
            throw new RuntimeException('The AI provider changed one or more protected links, identifiers or placeholders.');
        }
    }

    /**
     * @return list<string>
     */
    private function extractProtectedValues(string $html): array
    {
        $values = [];
        $patterns = [
            '/\b(?:href|src|xlink:href|id|name|data-[a-z0-9:_-]+)\s*=\s*(["\'])(.*?)\1/is',
            '/https?:\/\/[^\s<"\']+/i',
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            '/%\d*\$?[bcdeEfFgGosuxX]/',
            '/%[a-zA-Z0-9_.-]+%/',
            '/\{\{[^{}]+}}/',
            '/\[\[[^\[\]]+]]/',
        ];

        foreach ($patterns as $pattern) {
            if (false === preg_match_all($pattern, $html, $matches)) {
                continue;
            }

            $matchedValues = isset($matches[2]) && [] !== $matches[2] ? $matches[2] : $matches[0];
            foreach ($matchedValues as $value) {
                $values[] = trim((string) $value);
            }
        }

        sort($values);

        return $values;
    }

    private function getLanguageLabel(Language $language): string
    {
        $label = trim((string) $language->getOriginalName());

        return '' !== $label ? $label : $language->getEnglishName();
    }

    private function isTruthy(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
