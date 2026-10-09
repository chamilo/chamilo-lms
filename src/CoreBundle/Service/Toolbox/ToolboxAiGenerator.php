<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Toolbox;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\Mcp\McpTextAiService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use RuntimeException;

final readonly class ToolboxAiGenerator
{
    private const int MAX_HTML_LENGTH = 40000;
    private const int MAX_CSS_LENGTH = 40000;
    private const int MAX_JAVASCRIPT_LENGTH = 70000;
    private const int MAX_OUTPUT_TOKENS = 10000;
    private const array PROVIDER_DEFAULT_TEXT_TIMEOUTS = [
        'grok' => 180.0,
    ];

    public function __construct(
        private McpTextAiService $textAiService,
        private SettingsManager $settingsManager,
        private ToolboxJavascriptStructureValidator $javascriptStructureValidator,
    ) {}

    /**
     * @param array{html?: string, css?: string, javascript?: string}|null $previousSource
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     changeSummary: string,
     *     html: string,
     *     css: string,
     *     javascript: string,
     *     provider: string
     * }
     */
    public function generate(
        User $user,
        string $title,
        string $prompt,
        ?string $requestedProvider = null,
        ?array $previousSource = null,
    ): array {
        $title = trim(strip_tags($title));
        $prompt = trim($prompt);

        if ('' === $title) {
            throw new InvalidArgumentException('The application title is required.');
        }
        if ('' === $prompt) {
            throw new InvalidArgumentException('Describe the educational application you want to create.');
        }
        if (mb_strlen($prompt) > 12000) {
            throw new InvalidArgumentException('The Toolbox prompt is too long.');
        }

        $systemPrompt = <<<'PROMPT'
You are an educational applications developer. Create a small, self-contained educational application that will run inside Chamilo as a SCORM 1.2 SCO.

Return ONLY one JSON object with exactly these string fields:
{
  "title": "Short application title",
  "description": "One short teacher-facing description",
  "change_summary": "One short sentence describing what this version contains",
  "html": "Body markup only. Do not include html/head/body/style/script tags.",
  "css": "CSS only.",
  "javascript": "JavaScript only."
}

Runtime rules:
- Use only vanilla HTML, CSS and JavaScript. Do not depend on external libraries, URLs, APIs, images, fonts, CDNs or services.
- The page already provides a SCORM 1.2 API as window.API. Do not search parent/top/opener frames for an API.
- Call window.API.LMSInitialize("") before using SCORM.
- Save meaningful progress with cmi.core.lesson_location and cmi.suspend_data.
- Save a score from 0 to 100 with cmi.core.score.raw when the activity has a score, and set cmi.core.score.min to 0 and cmi.core.score.max to 100.
- Set cmi.core.lesson_status to "incomplete" while work remains and to "completed" when the learner finishes the activity.
- Call LMSCommit("") after meaningful progress. Call LMSFinish("") only when the activity is finished or intentionally closed.
- Resume from cmi.core.lesson_location / cmi.suspend_data when they contain previous state.
- Do not use fetch, XMLHttpRequest, WebSocket, WebTransport, WebRTC/RTCPeerConnection, EventSource, sendBeacon, camera/microphone APIs, cookies, localStorage, sessionStorage, indexedDB, window.open, parent, top, opener, eval, Function constructors or dynamic imports.
- Use only window.API / SCORM cmi.suspend_data for persistence. Normal JavaScript functions, arrow functions and event callbacks are allowed; only the Function constructor is forbidden.
- Use Canvas/CSS for graphics instead of SVG. Before returning, verify that forbidden browser/network API names do not appear in the generated source, including comments.
- Do not create forms, links or controls that navigate away from the activity. Use buttons for interaction.
- Put all behavior in the JavaScript field. Do not use inline HTML event handlers such as onclick/onload.
- Keep the interface responsive and keyboard-friendly.
- Use concise educational text and clear visual feedback.
- Before returning, verify that the JavaScript has balanced parentheses, brackets, braces, strings, comments and template literals.
- Never include Markdown fences or explanations outside the JSON.
PROMPT;

        $userPrompt = "Application title: {$title}\n\nTeacher request:\n{$prompt}";

        if (null !== $previousSource) {
            $userPrompt .= <<<'TEXT'

This is an update of an existing application. Preserve working behavior unless the teacher explicitly asks to change it. Improve the existing source rather than starting from an unrelated design.
TEXT;
            $userPrompt .= "\n\nPrevious HTML:\n".mb_substr((string) ($previousSource['html'] ?? ''), 0, 20000);
            $userPrompt .= "\n\nPrevious CSS:\n".mb_substr((string) ($previousSource['css'] ?? ''), 0, 20000);
            $userPrompt .= "\n\nPrevious JavaScript:\n".mb_substr((string) ($previousSource['javascript'] ?? ''), 0, 30000);
        }

        $result = $this->requestJson(
            $user,
            $requestedProvider,
            $systemPrompt,
            $userPrompt,
        );

        $generated = $this->normalizeGeneratedResult($result, $title, $requestedProvider);
        $violation = $this->findSourceViolation(
            $generated['html'],
            $generated['css'],
            $generated['javascript'],
        );

        if (null !== $violation) {
            $repairProvider = '' !== $generated['provider'] ? $generated['provider'] : $requestedProvider;
            $repairResult = $this->requestJson(
                $user,
                $repairProvider,
                $this->validationRepairSystemPrompt(),
                $this->buildValidationRepairPrompt($title, $prompt, $generated, $violation),
            );
            $generated = $this->normalizeGeneratedResult($repairResult, $title, $repairProvider);
            $violation = $this->findSourceViolation(
                $generated['html'],
                $generated['css'],
                $generated['javascript'],
            );

            if (null !== $violation) {
                throw new RuntimeException('The AI-generated application still fails Toolbox validation after one automatic repair: '.$violation);
            }
        }

        return $generated;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(
        User $user,
        ?string $provider,
        string $systemPrompt,
        string $userPrompt,
    ): array {
        try {
            return $this->textAiService->requestJson(
                $user,
                $provider,
                $systemPrompt,
                $userPrompt,
                self::MAX_OUTPUT_TOKENS,
            );
        } catch (RuntimeException $exception) {
            throw new RuntimeException($this->buildGenerationErrorMessage($provider, $exception->getMessage()), 0, $exception);
        }
    }

    private function buildGenerationErrorMessage(?string $provider, string $message): string
    {
        $message = trim($message);
        $normalized = strtolower($message);
        if (str_contains($normalized, 'daily ai token limit')
            || str_contains($normalized, 'monthly ai token limit')
        ) {
            return $message;
        }

        $providerName = trim((string) $provider);
        $context = $this->getTextProviderContext($providerName);
        $identity = '' !== $providerName ? '"'.$providerName.'"' : 'the configured provider';
        if ('' !== $context['model']) {
            $identity .= ' (model "'.$context['model'].'")';
        }

        if (str_contains($normalized, 'timeout') || str_contains($normalized, 'timed out')) {
            $timeout = $context['timeout'];
            $timeoutDetail = $timeout > 0.0 ? ' Provider text timeout: '.$this->formatTimeout($timeout).' seconds.' : '';

            return 'AI Toolbox generation timed out using text provider '.$identity.'.'.$timeoutDetail
                .' Review the provider and web-server timeouts, or simplify the requested application.';
        }

        if (str_contains($normalized, 'rate limit') || str_contains($normalized, 'too many requests')) {
            return 'AI Toolbox generation was rate-limited by text provider '.$identity.'. Please retry later or adjust the provider limits.';
        }

        if (str_contains($normalized, 'authentication failed')) {
            return 'AI Toolbox could not authenticate with text provider '.$identity.'. Verify the provider API credentials.';
        }

        if (str_contains($normalized, 'token/context limit')) {
            return 'AI Toolbox generation exceeded the token/context limit of text provider '.$identity
                .'. Reduce the prompt or existing application size, or select a model with a larger context window.';
        }

        if (str_contains($normalized, 'output limit')
            || str_contains($normalized, 'truncated')
            || str_contains($normalized, 'response limit')
            || str_contains($normalized, 'invalid json')
            || str_contains($normalized, 'incomplete text response')
            || str_contains($normalized, 'empty structured response')
        ) {
            return 'AI Toolbox generation did not receive a complete structured response from text provider '.$identity
                .'. This request allows up to '.self::MAX_OUTPUT_TOKENS
                .' output tokens. Reduce the requested application size or verify the model output-token limit.';
        }

        return 'AI Toolbox generation failed using text provider '.$identity
            .'. Check the provider configuration and server logs for details.';
    }

    /**
     * @return array{model: string, timeout: float}
     */
    private function getTextProviderContext(string $provider): array
    {
        if ('' === $provider) {
            return ['model' => '', 'timeout' => 0.0];
        }

        $configured = $this->settingsManager->getSetting('ai_helpers.ai_providers', true);
        if (\is_string($configured)) {
            $decoded = json_decode($configured, true);
            $configured = \is_array($decoded) ? $decoded : [];
        }
        if (!\is_array($configured)) {
            return ['model' => '', 'timeout' => 0.0];
        }

        $providerConfig = $configured[$provider] ?? [];
        if (!\is_array($providerConfig)) {
            return ['model' => '', 'timeout' => 0.0];
        }

        $textConfig = $providerConfig['text'] ?? [];
        if (!\is_array($textConfig)) {
            return [
                'model' => trim((string) ($providerConfig['model'] ?? '')),
                'timeout' => 0.0,
            ];
        }

        $timeout = is_numeric($textConfig['timeout'] ?? null)
            ? max(0.0, (float) $textConfig['timeout'])
            : (self::PROVIDER_DEFAULT_TEXT_TIMEOUTS[strtolower($provider)] ?? 0.0);

        return [
            'model' => trim((string) ($textConfig['model'] ?? $providerConfig['model'] ?? '')),
            'timeout' => $timeout,
        ];
    }

    private function formatTimeout(float $timeout): string
    {
        return 0.0 === fmod($timeout, 1.0) ? (string) (int) $timeout : rtrim(rtrim((string) $timeout, '0'), '.');
    }

    private function plainText(mixed $value, int $maxLength): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $maxLength);
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     changeSummary: string,
     *     html: string,
     *     css: string,
     *     javascript: string,
     *     provider: string
     * }
     */
    private function normalizeGeneratedResult(array $result, string $fallbackTitle, ?string $requestedProvider): array
    {
        $generatedTitle = $this->plainText($result['title'] ?? $fallbackTitle, 255);
        $description = $this->plainText($result['description'] ?? '', 1000);
        $changeSummary = $this->plainText($result['change_summary'] ?? '', 1000);
        $html = trim((string) ($result['html'] ?? ''));
        $css = trim((string) ($result['css'] ?? ''));
        $javascript = trim((string) ($result['javascript'] ?? ''));

        if ('' === $html || '' === $javascript) {
            throw new RuntimeException('The AI model did not return a complete Toolbox application.');
        }

        return [
            'title' => '' !== $generatedTitle ? $generatedTitle : $fallbackTitle,
            'description' => $description,
            'changeSummary' => $changeSummary,
            'html' => $html,
            'css' => $css,
            'javascript' => $javascript,
            'provider' => trim((string) ($result['_provider'] ?? $requestedProvider ?? '')),
        ];
    }

    private function validationRepairSystemPrompt(): string
    {
        return <<<'PROMPT'
You repair a generated educational HTML/CSS/JavaScript application so it can run correctly and safely inside Chamilo Toolbox as SCORM 1.2.

Return ONLY one JSON object with exactly these string fields:
{
  "title": "Short application title",
  "description": "One short teacher-facing description",
  "change_summary": "One short sentence describing the repaired version",
  "html": "Body markup only. Do not include html/head/body/style/script tags.",
  "css": "CSS only.",
  "javascript": "JavaScript only."
}

Preserve the educational behavior and visual design as much as possible, but fix the reported Toolbox validation failure.
Use only vanilla HTML/CSS/JavaScript and no external resources.
Use window.API for SCORM 1.2 persistence and cmi.suspend_data / cmi.core.lesson_location for resumable state.
Do not use fetch, XMLHttpRequest, WebSocket, WebTransport, WebRTC/RTCPeerConnection, EventSource, sendBeacon, camera/microphone APIs, cookies, localStorage, sessionStorage, indexedDB, navigation APIs, window.open, parent, top, opener, eval, the Function constructor, or dynamic imports.
Normal JavaScript function declarations, anonymous function callbacks and arrow functions are allowed. Only the Function constructor is forbidden.
Ensure the JavaScript has balanced parentheses, brackets, braces, strings, comments and template literals.
Do not use SVG; use Canvas or CSS when graphics are needed.
Do not include forbidden API names in comments or explanatory strings.
Never include Markdown fences or prose outside the JSON.
PROMPT;
    }

    /**
     * @param array{
     *     title: string,
     *     description: string,
     *     changeSummary: string,
     *     html: string,
     *     css: string,
     *     javascript: string,
     *     provider: string
     * } $generated
     */
    private function buildValidationRepairPrompt(
        string $requestedTitle,
        string $teacherPrompt,
        array $generated,
        string $violation,
    ): string {
        return "Requested application: {$requestedTitle}\n"
            ."Teacher request:\n{$teacherPrompt}\n\n"
            ."Toolbox validation failure:\n{$violation}\n\n"
            ."Repair this generated candidate without changing the learning objective:\n\n"
            ."TITLE:\n".mb_substr($generated['title'], 0, 255)."\n\n"
            ."DESCRIPTION:\n".mb_substr($generated['description'], 0, 1000)."\n\n"
            ."HTML:\n".mb_substr($generated['html'], 0, self::MAX_HTML_LENGTH)."\n\n"
            ."CSS:\n".mb_substr($generated['css'], 0, self::MAX_CSS_LENGTH)."\n\n"
            ."JAVASCRIPT:\n".mb_substr($generated['javascript'], 0, self::MAX_JAVASCRIPT_LENGTH);
    }

    private function findSourceViolation(string $html, string $css, string $javascript): ?string
    {
        if (mb_strlen($html) > self::MAX_HTML_LENGTH
            || mb_strlen($css) > self::MAX_CSS_LENGTH
            || mb_strlen($javascript) > self::MAX_JAVASCRIPT_LENGTH
        ) {
            return 'The generated Toolbox application is too large.';
        }

        $javascriptStructureViolation = $this->javascriptStructureValidator->findViolation($javascript);
        if (null !== $javascriptStructureViolation) {
            return $javascriptStructureViolation;
        }

        $blockedHtml = [
            '/<\s*(html|head|body|meta|script|style|iframe|frame|object|embed|base|link|form|a|svg|math)\b/i' => 'HTML contains a blocked document, executable, navigation or SVG element.',
            '/\b(?:src|href|action)\s*=\s*["\']\s*(?:https?:|\/\/|javascript:)/i' => 'HTML contains an external or JavaScript URL.',
            '/<\s*meta\b[^>]*http-equiv\s*=\s*["\']?refresh/i' => 'HTML contains a meta refresh.',
            '/\bon[a-z]+\s*=\s*["\']/i' => 'HTML contains an inline event handler.',
            '/\bsrcdoc\s*=/i' => 'HTML contains srcdoc.',
        ];
        foreach ($blockedHtml as $pattern => $message) {
            if (1 === preg_match($pattern, $html)) {
                return $message;
            }
        }

        if (1 === preg_match('/url\s*\(\s*["\']?\s*(?:https?:|\/\/|javascript:)/i', $css)) {
            return 'CSS contains an external URL.';
        }
        if (str_contains(strtolower($css), '</style')) {
            return 'CSS contains an invalid style terminator.';
        }

        $blockedJavascript = [
            '/\bfetch\s*\(/i' => 'JavaScript uses fetch().',
            '/\bXMLHttpRequest\b/i' => 'JavaScript uses XMLHttpRequest.',
            '/\bWebSocket\b/i' => 'JavaScript uses WebSocket.',
            '/\bWebTransport\b/i' => 'JavaScript uses WebTransport.',
            '/\bRTCPeerConnection\b/i' => 'JavaScript uses RTCPeerConnection.',
            '/\bRTCDataChannel\b/i' => 'JavaScript uses RTCDataChannel.',
            '/\bgetUserMedia\b/i' => 'JavaScript uses getUserMedia.',
            '/\bmediaDevices\b/i' => 'JavaScript uses mediaDevices.',
            '/\bEventSource\b/i' => 'JavaScript uses EventSource.',
            '/\bsendBeacon\b/i' => 'JavaScript uses sendBeacon.',
            '/\bdocument\s*\.\s*cookie\b/i' => 'JavaScript uses document.cookie.',
            '/\blocalStorage\b/i' => 'JavaScript uses localStorage; use SCORM cmi.suspend_data instead.',
            '/\bsessionStorage\b/i' => 'JavaScript uses sessionStorage; use SCORM cmi.suspend_data instead.',
            '/\bindexedDB\b/i' => 'JavaScript uses indexedDB; use SCORM cmi.suspend_data instead.',
            '/\bwindow\s*\.\s*(?:parent|top|opener|open)\b/i' => 'JavaScript accesses a blocked window capability.',
            '/\b(?:parent|top|opener)\s*\./i' => 'JavaScript accesses a parent/top/opener frame.',
            '/\beval\s*\(/i' => 'JavaScript uses eval().',
            '/\b(?:new\s+)?Function\s*\(/' => 'JavaScript uses the Function constructor.',
            '/\bimport\s*\(/i' => 'JavaScript uses dynamic import().',
            '/\b(?:window|document)\s*\.\s*location\b/i' => 'JavaScript accesses window/document location.',
            '/\blocation\s*\.\s*(?:href|assign|replace|reload)\b/i' => 'JavaScript uses a navigation capability.',
            '/\bhttps?:\/\//i' => 'JavaScript contains an external HTTP(S) URL.',
            '/<\/script/i' => 'JavaScript contains a script terminator.',
        ];
        foreach ($blockedJavascript as $pattern => $message) {
            if (1 === preg_match($pattern, $javascript)) {
                return $message;
            }
        }

        return null;
    }
}
