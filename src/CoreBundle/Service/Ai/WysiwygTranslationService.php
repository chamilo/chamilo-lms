<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Ai;

use Chamilo\CoreBundle\AiProvider\AiChatCompletionClientInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Repository\AiRequestsRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Throwable;

final readonly class WysiwygTranslationService
{
    private const string TOOL_NAME = 'wysiwyg_translation';
    private const int MAX_HTML_LENGTH = 100000;
    private const int MAX_OUTPUT_TOKENS = 12000;

    public function __construct(
        private SettingsManager $settingsManager,
        private LanguageRepository $languageRepository,
        private AiProviderFactory $aiProviderFactory,
        private AiChatCompletionClientInterface $aiClient,
        private AiRequestsRepository $aiRequestsRepository,
        private Security $security,
        private AiDisclosureHelper $aiDisclosureHelper,
        private TranslateHtmlLanguageService $translateHtmlLanguageService,
    ) {}

    public function isEnabled(): bool
    {
        return $this->isTruthy($this->settingsManager->getSetting('editor.translate_html', true))
            && $this->isTruthy($this->settingsManager->getSetting('ai_helpers.enable_ai_helpers', true))
            && $this->aiProviderFactory->hasProvidersForType('text');
    }

    public function isAllLanguagesAllowed(): bool
    {
        return $this->isTruthy(
            $this->settingsManager->getSetting('ai_helpers.wysiwyg_translation_all_languages', true)
        );
    }

    public function getSourceLanguage(?Course $course): string
    {
        $sourceLanguage = $course?->getCourseLanguage() ?: $this->languageRepository->getPlatformDefaultIso();

        return $this->translateHtmlLanguageService->normalizeLanguageCode($sourceLanguage ?: 'en_US');
    }

    /**
     * @return array<string, string> [isocode => label]
     */
    public function getActiveLanguages(): array
    {
        $languages = $this->languageRepository->getAllAvailableToArray(true, false);
        $normalized = [];

        foreach ($languages as $isocode => $label) {
            $code = $this->translateHtmlLanguageService->normalizeLanguageCode((string) $isocode);
            if ('' === $code) {
                continue;
            }

            $normalized[$code] = trim((string) $label) ?: $code;
        }

        return $normalized;
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
     * @param list<string>          $requestedLanguages
     * @param array<string, string> $activeLanguages
     *
     * @return array{html: string, added: list<string>, skipped: list<string>}
     */
    public function translate(
        string $html,
        string $sourceLanguage,
        array $requestedLanguages,
        array $activeLanguages,
        string $provider,
        int $courseId = 0,
        int $sessionId = 0,
    ): array {
        $html = trim($html);
        if ('' === $html) {
            throw new RuntimeException('The editor content is empty.');
        }
        if (mb_strlen($html) > self::MAX_HTML_LENGTH) {
            throw new RuntimeException('The editor content is too large to translate in one request.');
        }

        $sourceLanguage = $this->translateHtmlLanguageService->normalizeLanguageCode($sourceLanguage);
        if ('' === $sourceLanguage) {
            throw new RuntimeException('The source language is invalid.');
        }

        $availableProviders = array_column($this->getProviderOptions(), 'value');
        if ('' === $provider) {
            $provider = (string) ($availableProviders[0] ?? '');
        }
        if ('' === $provider || !\in_array($provider, $availableProviders, true)) {
            throw new RuntimeException('The selected AI text provider is not available.');
        }

        $markerInfo = $this->translateHtmlLanguageService->inspect($html, $sourceLanguage);
        $presentLanguages = $markerInfo['presentLanguages'];
        $sourceHtml = $markerInfo['sourceHtml'];

        if ('' === trim($sourceHtml)) {
            if ($markerInfo['hasMarkers']) {
                throw new RuntimeException('No translate_html block matches the current source language.');
            }

            throw new RuntimeException('The editor content is empty.');
        }

        $targets = [];
        $skipped = [];
        foreach ($requestedLanguages as $requestedLanguage) {
            $targetLanguage = $this->translateHtmlLanguageService->normalizeLanguageCode((string) $requestedLanguage);
            if ('' === $targetLanguage || !isset($activeLanguages[$targetLanguage])) {
                throw new RuntimeException('One or more requested languages are not active on this platform.');
            }
            if ($this->translateHtmlLanguageService->languageCodesMatch($targetLanguage, $sourceLanguage)) {
                $skipped[] = $targetLanguage;

                continue;
            }
            if ($this->translateHtmlLanguageService->containsMatchingLanguage($presentLanguages, $targetLanguage)) {
                $skipped[] = $targetLanguage;

                continue;
            }
            if (!\in_array($targetLanguage, $targets, true)) {
                $targets[] = $targetLanguage;
            }
        }

        if ([] === $targets) {
            return [
                'html' => $html,
                'added' => [],
                'skipped' => array_values(array_unique($skipped)),
            ];
        }

        $sourceLabel = $activeLanguages[$sourceLanguage] ?? $sourceLanguage;
        $translations = [];
        foreach ($targets as $targetLanguage) {
            $targetLabel = $activeLanguages[$targetLanguage] ?? $targetLanguage;
            $translations[$targetLanguage] = $this->translateFragment(
                $sourceHtml,
                $sourceLanguage,
                $sourceLabel,
                $targetLanguage,
                $targetLabel,
                $provider,
            );
        }

        $resultHtml = $this->translateHtmlLanguageService->mergeTranslations(
            $html,
            $sourceLanguage,
            $sourceHtml,
            $markerInfo['hasMarkers'],
            $markerInfo['isFullDocument'],
            $translations,
        );

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$courseId.':wysiwyg_translation:'.hash('sha256', $sourceHtml),
                userId: (int) $user->getId(),
                meta: [
                    'feature' => 'wysiwyg_translation',
                    'provider' => $provider,
                    'source_language' => $sourceLanguage,
                    'target_languages' => array_keys($translations),
                    'content_length' => mb_strlen($sourceHtml),
                ],
                courseId: $courseId,
                sessionId: $sessionId,
            );
        }

        return [
            'html' => $resultHtml,
            'added' => array_keys($translations),
            'skipped' => array_values(array_unique($skipped)),
        ];
    }

    private function translateFragment(
        string $sourceHtml,
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
                'content' => 'Translate HTML content accurately. Return only the translated HTML fragment. Preserve all HTML tags, attributes, URLs, placeholders, entities, code, IDs and data attributes. Do not add Markdown fences, explanations, headings, language wrappers or comments. Do not translate text inside code, pre, script or style elements.',
            ],
            [
                'role' => 'user',
                'content' => \sprintf(
                    "Request marker: %s\nTranslate from %s (%s) to %s (%s).\n\nHTML:\n%s",
                    $marker,
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
                'throw_on_error' => true,
            ]);

            $translatedHtml = $this->normalizeAiHtml((string) $result->text);
            if ('' === trim($translatedHtml)) {
                throw new RuntimeException('The AI provider returned an empty translation.');
            }

            return $translatedHtml;
        } finally {
            if ($userId > 0) {
                $this->redactLatestAiRequest(
                    $userId,
                    $provider,
                    $requestedAfter,
                    $marker,
                    $sourceHtml,
                    $sourceLanguage,
                    $targetLanguage,
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
                    'WYSIWYG translation; source=%s; target=%s; content_sha256=%s; content_length=%d',
                    $sourceLanguage,
                    $targetLanguage,
                    hash('sha256', $sourceHtml),
                    mb_strlen($sourceHtml),
                ));
                $this->aiRequestsRepository->save($request);

                return;
            }
        } catch (Throwable) {
            // Audit redaction must not break the editor workflow.
        }
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
            || false !== stripos($html, 'mce-translatehtml')
        ) {
            throw new RuntimeException('The AI provider returned unsafe HTML.');
        }

        if (1 === preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = trim((string) ($matches[1] ?? ''));
        }

        return $html;
    }

    private function isTruthy(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
