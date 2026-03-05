<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\TrackEDefault;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Security;
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
    public const EXTRA_FIELD_VARIABLE_AI_ASSISTED = 'ai_assisted';

    // We keep these as constants so they can be searched easily in DB.
    public const VALUE_TYPE_TARGET_KEY = 'target_key';

    public const DEFAULT_MARKER_PREFIX = '[AI-assisted] ';

    // Settings keys (best-effort): the real key might differ by portal.
    private const SETTING_KEYS = [
        'disclose_ai_assistance',
        'ai_helpers_disclose_ai_assistance',
        'ai_helpers.disclose_ai_assistance',
    ];

    private ?bool $enabledCache = null;

    /** @var array<string,bool> */
    private array $aiAssistedCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Best-effort check to see if disclosure is enabled.
     * Defaults to false if the setting cannot be read.
     */
    public function isDisclosureEnabled(): bool
    {
        if (null !== $this->enabledCache) {
            return $this->enabledCache;
        }

        foreach (self::SETTING_KEYS as $key) {
            $raw = $this->getSettingValue($key);
            if (null === $raw) {
                continue;
            }

            $val = strtolower(trim((string) $raw));
            if ('' === $val) {
                continue;
            }

            $this->enabledCache = \in_array($val, ['1', 'true', 'yes', 'on', 'enabled'], true);

            return $this->enabledCache;
        }

        $this->enabledCache = false;

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
     * Marks an item as AI-assisted using ExtraFields (without modifying stored content).
     *
     * Best-effort:
     * - Creates the extra field if missing (per handler type).
     * - Upserts the value in TABLE_EXTRA_FIELD_VALUES.
     * - Never blocks the main flow.
     */
    public function markAiAssistedExtraField(string $type, int $itemId, bool $enabled = true): void
    {
        $type = trim($type);
        if ('' === $type || $itemId <= 0) {
            return;
        }

        try {
            if (!class_exists(\ExtraField::class) || !class_exists(\ExtraFieldValue::class)) {
                return;
            }

            $ef = new \ExtraField($type);
            $fieldInfo = $ef->get_handler_field_info_by_field_variable(self::EXTRA_FIELD_VARIABLE_AI_ASSISTED);

            if (!$fieldInfo || empty($fieldInfo['id'])) {
                $fieldId = $ef->save([
                    'display_text' => 'AI-assisted',
                    'variable' => self::EXTRA_FIELD_VARIABLE_AI_ASSISTED,
                    'value_type' => \ExtraField::FIELD_TYPE_CHECKBOX,
                    'visible_to_self' => 1,
                    'visible_to_others' => 1,
                    'changeable' => 1,
                    'filter' => 0,
                    'field_order' => 0,
                    'default_value' => 0,
                ]);

                if (!$fieldId) {
                    return;
                }

                $fieldInfo = $ef->get((int) $fieldId);
                if (!$fieldInfo || empty($fieldInfo['id'])) {
                    return;
                }
            }

            $fieldId = (int) $fieldInfo['id'];
            if ($fieldId <= 0) {
                return;
            }

            $efv = new \ExtraFieldValue($type);
            $efv->save([
                'field_id' => $fieldId,
                'item_id' => $itemId,
                'field_value' => $enabled ? 1 : 0,
                'comment' => '',
            ]);

            // Keep in-memory cache coherent for this request.
            $k = $type.':'.$itemId;
            $this->aiAssistedCache[$k] = $enabled;
        } catch (\Throwable) {
            return;
        }
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

    public function isAiAssistedExtraField(string $type, int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        $type = trim($type);
        if ('' === $type) {
            return false;
        }

        $k = $type.':'.$itemId;
        if (array_key_exists($k, $this->aiAssistedCache)) {
            return $this->aiAssistedCache[$k];
        }

        try {
            $efv = new \ExtraFieldValue($type);
            $row = $efv->get_values_by_handler_and_field_variable($itemId, self::EXTRA_FIELD_VARIABLE_AI_ASSISTED);

            $isAi = $row && (string) ($row['field_value'] ?? '') === '1';
            $this->aiAssistedCache[$k] = (bool) $isAi;

            return $this->aiAssistedCache[$k];
        } catch (\Throwable) {
            $this->aiAssistedCache[$k] = false;

            return false;
        }
    }

    public function shouldShowAiBadge(string $type, int $itemId): bool
    {
        return $this->isDisclosureEnabled() && $this->isAiAssistedExtraField($type, $itemId);
    }

    public static function renderAiBadgeHtml(string $position = 'suffix'): string
    {
        $tooltip = get_lang('Co-generated with AI');
        $margin = ('prefix' === $position) ? 'margin-right:8px;' : 'margin-left:8px;';

        return '<span'
            .' title="'.Security::remove_XSS($tooltip).'"'
            .' aria-label="'.Security::remove_XSS($tooltip).'"'
            .' role="note"'
            .' style="display:inline-flex;align-items:center;gap:6px;padding:2px 10px;'.$margin
            .'border:1px solid #cbd5e1;border-radius:9999px;background:#f8fafc;color:#334155;'
            .'font-size:12px;line-height:16px;white-space:nowrap;vertical-align:middle;cursor:help"'
            .'>'
            .'<span aria-hidden="true" style="font-size:12px;line-height:12px">🤖</span>'
            .'<span style="font-weight:600;letter-spacing:.2px">AI</span>'
            .'</span>';
    }

    public function decorateTitle(string $title, string $type, int $itemId, bool $prepend = false): string
    {
        if (!$this->shouldShowAiBadge($type, $itemId)) {
            return $title;
        }

        $badge = self::renderAiBadgeHtml($prepend ? 'prefix' : 'suffix');

        return $prepend ? ($badge.' '.$title) : ($title.$badge);
    }

    public function decorateTitleText(string $title, string $type, int $itemId): string
    {
        $title = (string) $title;
        if ('' === trim($title)) {
            return $title;
        }

        if (!$this->isDisclosureEnabled()) {
            return $title;
        }

        if (!$this->isAiAssistedExtraField($type, $itemId)) {
            return $title;
        }

        $prefix = self::DEFAULT_MARKER_PREFIX; // "[AI-assisted] "
        if (str_starts_with($title, $prefix)) {
            return $title;
        }

        return $prefix.$title;
    }
}
