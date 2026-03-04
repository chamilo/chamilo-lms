<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PREG_OFFSET_CAPTURE;

/**
 * Handles disclosure of AI assistance without requiring DB schema changes.
 *
 * - Persists audit records in TrackEDefault (event_type = ai_disclosure_audit)
 * - Provides helper methods to add visible markers to content (plain text / HTML / Aiken / Glossary / structured payloads)
 */
final class AiDisclosureHelper
{
    public const EVENT_TYPE_AUDIT = 'ai_disclosure_audit';

    // We keep these as constants so they can be searched easily in DB.
    public const VALUE_TYPE_TARGET_KEY = 'target_key';

    public const DEFAULT_MARKER_PREFIX = '[AI-assisted] ';

    // Settings keys (best-effort): the real key might differ by portal.
    private const SETTING_KEYS = [
        'disclose_ai_assistance',
        'ai_helpers_disclose_ai_assistance',
        'ai_helpers.disclose_ai_assistance',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Best-effort check to see if disclosure is enabled.
     * Defaults to false if the setting cannot be read.
     */
    public function isDisclosureEnabled(): bool
    {
        foreach (self::SETTING_KEYS as $key) {
            $raw = $this->getSettingValue($key);
            if (null === $raw) {
                continue;
            }

            $val = strtolower(trim((string) $raw));
            if ('' === $val) {
                continue;
            }

            return \in_array($val, ['1', 'true', 'yes', 'on', 'enabled'], true);
        }

        return false;
    }

    /**
     * Store audit details in TrackEDefault (no schema changes).
     *
     * @param array<string,mixed> $meta Any metadata you want to keep for audits (provider, model, feature, mode, etc.)
     */
    public function logAudit(
        string $targetKey,
        int $userId,
        array $meta = [],
        int $courseId = 0,
        int $sessionId = 0,
        bool $flush = true,
    ): void {
        $targetKey = trim($targetKey);
        if ('' === $targetKey || $userId <= 0) {
            return;
        }

        // Best-effort: infer courseId from the target key (e.g. "course:123:learnpath:...").
        if ($courseId <= 0 && preg_match('/\bcourse:(\d+):/i', $targetKey, $m)) {
            $courseId = (int) $m[1];
        }

        $payload = [
            'target' => $targetKey,
            'meta' => $meta,
        ];

        try {
            $track = new TrackEDefault();
            $track
                ->setDefaultUserId($userId)
                ->setDefaultEventType(self::EVENT_TYPE_AUDIT)
                ->setDefaultValueType(self::VALUE_TYPE_TARGET_KEY)
                ->setDefaultValue((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
                ->setDefaultDate(new DateTime())
                ->setCId($courseId > 0 ? $courseId : null)
                ->setSessionId($sessionId > 0 ? $sessionId : null)
            ;

            $this->em->persist($track);
            if ($flush) {
                $this->em->flush();
            }
        } catch (Throwable) {
            // Never block main flow because of an audit log.
        }
    }

    /**
     * Adds a visible disclosure marker to plain text feedback.
     * Keep it plain so it works in legacy views and inbox messages.
     */
    public function prependDisclosureToPlainText(string $text): string
    {
        $text = ltrim($text);
        if ('' === $text) {
            return $text;
        }

        // Keep backward compatibility with your existing marker and JS matcher.
        $prefix = "🤖 AI-assisted\n\n";
        if (str_starts_with($text, '🤖 AI-assisted')) {
            return $text;
        }

        return $prefix.$text;
    }

    /**
     * Adds a small inline-styled disclosure tag to HTML content.
     *
     * This is intentionally inline-styled so it works in legacy views without relying on global CSS.
     */
    public function injectDisclosureTagIntoHtml(string $html): string
    {
        $html = (string) $html;
        if ('' === trim($html)) {
            return $html;
        }

        if (str_contains($html, 'data-ai-assistance="1"')) {
            return $html;
        }

        $tag = $this->buildDisclosureTagHtml();

        // If this looks like a full HTML doc, inject right after <body>.
        if (preg_match('#<body\b[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + \strlen($m[0][0]);

            return substr($html, 0, $pos)."\n".$tag."\n".substr($html, $pos);
        }

        // Otherwise just prepend.
        return $tag."\n".$html;
    }

    /**
     * Injects a visible disclosure prefix into Aiken text (first line of each question block).
     * This ensures the final imported question title carries the marker without UI/template changes.
     */
    public function injectDisclosurePrefixIntoAikenText(string $aikenText, string $prefix = self::DEFAULT_MARKER_PREFIX): string
    {
        $aikenText = (string) $aikenText;
        if ('' === trim($aikenText)) {
            return $aikenText;
        }

        // Split blocks by blank lines (Aiken standard).
        $blocks = preg_split('/\R{2,}/', trim($aikenText));
        if (!\is_array($blocks) || empty($blocks)) {
            return $aikenText;
        }

        $out = [];
        foreach ($blocks as $block) {
            $block = trim((string) $block);
            if ('' === $block) {
                continue;
            }

            $lines = preg_split('/\R/', $block);
            if (!\is_array($lines) || empty($lines)) {
                $out[] = $block;

                continue;
            }

            $lines = $this->prefixFirstMeaningfulLine($lines, $prefix);

            $out[] = implode("\n", $lines);
        }

        return implode("\n\n", $out)."\n";
    }

    /**
     * Injects a visible disclosure prefix into Glossary generator output.
     * Assumption: blocks separated by blank line; first line is the term title.
     */
    public function injectDisclosurePrefixIntoGlossaryTermsText(string $text, string $prefix = self::DEFAULT_MARKER_PREFIX): string
    {
        $text = (string) $text;
        if ('' === trim($text)) {
            return $text;
        }

        $blocks = preg_split('/\R{2,}/', trim($text));
        if (!\is_array($blocks) || empty($blocks)) {
            return $text;
        }

        $out = [];
        foreach ($blocks as $block) {
            $block = trim((string) $block);
            if ('' === $block) {
                continue;
            }

            $lines = preg_split('/\R/', $block);
            if (!\is_array($lines) || empty($lines)) {
                $out[] = $block;

                continue;
            }

            $lines = $this->prefixFirstMeaningfulLine($lines, $prefix);

            $out[] = implode("\n", $lines);
        }

        return implode("\n\n", $out)."\n";
    }

    /**
     * Decorate a structured payload (arrays) by inserting a marker prefix only into specific keys.
     * This is designed for LearnPath generation payloads.
     *
     * @param array<int,string> $keys keys that are considered "content" fields
     */
    public function decorateStructuredPayload(
        mixed $value,
        array $keys = ['lp_name', 'name', 'title', 'content', 'description', 'text'],
        string $prefix = self::DEFAULT_MARKER_PREFIX
    ): mixed {
        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                if (\is_string($k) && \in_array($k, $keys, true)) {
                    if (\is_string($v) && '' !== trim($v)) {
                        // Prefer HTML tag injection if it's HTML.
                        if ($this->looksLikeHtmlFragment($v)) {
                            $value[$k] = $this->injectDisclosureTagIntoHtml($v);
                        } else {
                            $value[$k] = $this->prefixIfMissing($v, $prefix);
                        }

                        continue;
                    }
                }

                $value[$k] = $this->decorateStructuredPayload($v, $keys, $prefix);
            }

            return $value;
        }

        return $value;
    }

    private function prefixFirstMeaningfulLine(array $lines, string $prefix): array
    {
        // Find the first non-empty line and prefix it.
        foreach ($lines as $i => $line) {
            $line = (string) $line;
            if ('' === trim($line)) {
                continue;
            }

            if (!str_starts_with($line, $prefix)) {
                $lines[$i] = $prefix.$line;
            }

            break;
        }

        return $lines;
    }

    private function prefixIfMissing(string $s, string $prefix): string
    {
        $s = (string) $s;
        if ('' === trim($s)) {
            return $s;
        }

        if (str_starts_with($s, $prefix)) {
            return $s;
        }

        // Avoid double-tagging if HTML tag already injected somewhere else.
        if (str_contains($s, 'data-ai-assistance="1"')) {
            return $s;
        }

        return $prefix.$s;
    }

    private function looksLikeHtmlFragment(string $s): bool
    {
        $s = trim($s);
        if ('' === $s) {
            return false;
        }

        if (str_contains($s, '<html') || str_contains($s, '<body') || str_contains($s, '</body>')) {
            return true;
        }

        return (bool) preg_match('#</?(p|div|span|h[1-6]|ul|ol|li|table|tr|td|img|a)\b#i', $s);
    }

    private function buildDisclosureTagHtml(): string
    {
        // Keep it compact and neutral. No provider/model details here.
        return '<div data-ai-assistance="1" style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border:1px solid #cbd5e1;border-radius:9999px;font-size:12px;line-height:16px;opacity:.85;margin:0 0 10px 0;">'
            .'<span aria-hidden="true">🤖</span>'
            .'<span>AI-assisted</span>'
            .'</div>';
    }

    private function getSettingValue(string $key): mixed
    {
        // Legacy global helper (works in most Chamilo contexts).
        try {
            if (\function_exists('api_get_setting')) {
                return api_get_setting($key);
            }

            if (\function_exists('api_get_configuration_value')) {
                return api_get_configuration_value($key);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
