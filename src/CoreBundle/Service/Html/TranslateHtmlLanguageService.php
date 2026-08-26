<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Html;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const LIBXML_HTML_NODEFDTD;
use const LIBXML_HTML_NOIMPLIED;
use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Parse / inventory / upsert Chamilo translatehtml language blocks
 * (<div|span class="mce-translatehtml" lang="…">…</div|span> and legacy span[lang]).
 *
 * Shared by the TinyMCE AI helper and MCP language-upsert tools so merge rules
 * stay identical: sibling language blocks, never "append before last </div>".
 */
final class TranslateHtmlLanguageService
{
    public const string MODE_UPSERT = 'upsert';
    public const string MODE_CREATE_ONLY = 'create_only';
    public const string MODE_REPLACE_ONLY = 'replace_only';

    public const string READ_MODE_FULL = 'full';
    public const string READ_MODE_INVENTORY = 'inventory';
    public const string READ_MODE_SOURCE = 'source';

    /**
     * @return list<string>
     */
    public function supportedReadModes(): array
    {
        return [
            self::READ_MODE_FULL,
            self::READ_MODE_INVENTORY,
            self::READ_MODE_SOURCE,
        ];
    }

    /**
     * @return list<string>
     */
    public function supportedWriteModes(): array
    {
        return [
            self::MODE_UPSERT,
            self::MODE_CREATE_ONLY,
            self::MODE_REPLACE_ONLY,
        ];
    }

    public function assertReadMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        if (!\in_array($mode, $this->supportedReadModes(), true)) {
            throw new InvalidArgumentException(\sprintf('Invalid read mode "%s". Use one of: %s.', $mode, implode(', ', $this->supportedReadModes())));
        }

        return $mode;
    }

    public function assertWriteMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        if (!\in_array($mode, $this->supportedWriteModes(), true)) {
            throw new InvalidArgumentException(\sprintf('Invalid mode "%s". Use one of: %s.', $mode, implode(', ', $this->supportedWriteModes())));
        }

        return $mode;
    }

    /**
     * Project an HTML field for MCP read modes (full / inventory / source).
     * Inventory and source omit the multi-language body under $bodyKey.
     *
     * $metaPrefix namespaces the metadata keys (has_markers, present_languages, …)
     * so a normalize method can project more than one HTML field on the same
     * entity without the two calls' metadata colliding. Leave it null for the
     * entity's primary field to keep the unprefixed keys other callers expect.
     *
     * @return array<string, mixed>
     */
    public function projectHtmlField(
        string $html,
        string $mode,
        string $sourceLanguage,
        string $bodyKey = 'content',
        ?string $metaPrefix = null,
    ): array {
        $mode = $this->assertReadMode($mode);
        $sourceLanguage = $this->normalizeLanguageCode($sourceLanguage);
        $inspection = $this->inspect($html, $sourceLanguage);
        $prefix = $metaPrefix ?? '';

        $base = [
            $prefix.'has_markers' => $inspection['hasMarkers'],
            $prefix.'present_languages' => $inspection['presentLanguages'],
            $prefix.'per_language' => $inspection['perLanguage'],
            $prefix.'content_sha256' => $inspection['contentSha256'],
            $prefix.'source_language' => $sourceLanguage,
        ];

        if (self::READ_MODE_INVENTORY === $mode) {
            $base[$prefix.'word_count'] = $this->countWords($html);

            return $base;
        }

        if (self::READ_MODE_SOURCE === $mode) {
            $base[$prefix.'source_html'] = $inspection['sourceHtml'];
            $base[$prefix.'word_count'] = $this->countWords($inspection['sourceHtml']);

            return $base;
        }

        $base[$bodyKey] = $html;
        $base[$prefix.'word_count'] = $this->countWords($html);

        return $base;
    }

    /**
     * Upsert one language, then run $sanitizeFullHtml on the merged document.
     *
     * @param callable(string): string $sanitizeFullHtml
     *
     * @return array{
     *     html: string,
     *     action: 'created'|'replaced',
     *     language: string,
     *     present_languages: list<string>,
     *     content_sha256: string,
     *     chars: int,
     *     words: int,
     *     has_markers: bool,
     *     per_language: array<string, array{chars: int, words: int}>
     * }
     */
    public function upsertLanguageSanitized(
        string $currentHtml,
        string $language,
        string $innerHtml,
        string $mode,
        string $sourceLanguageForWrap,
        ?string $ifMatchSha256,
        callable $sanitizeFullHtml,
    ): array {
        $result = $this->upsertLanguage(
            $currentHtml,
            $language,
            $innerHtml,
            $mode,
            $sourceLanguageForWrap,
            $ifMatchSha256,
        );

        $merged = $sanitizeFullHtml($result['html']);
        $after = $this->inspect($merged, $this->normalizeLanguageCode($sourceLanguageForWrap) ?: $result['language']);

        return [
            'html' => $merged,
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $after['presentLanguages'],
            'content_sha256' => $after['contentSha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $after['hasMarkers'],
            'per_language' => $after['perLanguage'],
        ];
    }

    public function normalizeLanguageCode(string $language): string
    {
        $language = str_replace('-', '_', trim($language));
        if ('' === $language) {
            return '';
        }

        $parts = explode('_', $language, 2);
        $base = strtolower($parts[0]);
        if (1 === \count($parts) || '' === trim($parts[1])) {
            return $base;
        }

        return $base.'_'.strtoupper($parts[1]);
    }

    public function languageCodesMatch(string $left, string $right): bool
    {
        $left = $this->normalizeLanguageCode($left);
        $right = $this->normalizeLanguageCode($right);

        if ('' === $left || '' === $right) {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        return explode('_', $left)[0] === explode('_', $right)[0];
    }

    public function contentSha256(string $html): string
    {
        return hash('sha256', $html);
    }

    /**
     * @return array{
     *     hasMarkers: bool,
     *     isFullDocument: bool,
     *     sourceHtml: string,
     *     presentLanguages: list<string>,
     *     perLanguage: array<string, array{chars: int, words: int}>,
     *     contentSha256: string
     * }
     */
    public function inspect(string $html, string $sourceLanguage = ''): array
    {
        $html = (string) $html;
        $sourceLanguage = $this->normalizeLanguageCode($sourceLanguage);
        $isFullDocument = 1 === preg_match('/<!doctype\s+html|<html\b/i', $html);
        $document = $this->loadHtmlDocument($html, $isFullDocument);
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(
            '//*[@lang and (contains(concat(" ", normalize-space(@class), " "), " mce-translatehtml ") or self::span)]'
        );

        $presentLanguages = [];
        $perLanguage = [];
        $sourceParts = [];

        if (false !== $nodes) {
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $language = $this->normalizeLanguageCode($node->getAttribute('lang'));
                if ('' === $language) {
                    continue;
                }

                $inner = $this->innerHtml($node);
                $presentLanguages[] = $language;

                if (!isset($perLanguage[$language])) {
                    $perLanguage[$language] = [
                        'chars' => 0,
                        'words' => 0,
                    ];
                }
                $perLanguage[$language]['chars'] += mb_strlen($inner);
                $perLanguage[$language]['words'] += $this->countWords($inner);

                if ('' !== $sourceLanguage && $this->languageCodesMatch($language, $sourceLanguage)) {
                    $sourceParts[] = $inner;
                }
            }
        }

        $hasMarkers = [] !== $presentLanguages;
        if ($hasMarkers) {
            if ('' !== $sourceLanguage) {
                $sourceHtml = implode("\n", array_filter($sourceParts, static fn (string $part): bool => '' !== trim($part)));
            } else {
                // No explicit source: first present language block(s) of the first language found.
                $firstLanguage = $presentLanguages[0] ?? '';
                $sourceParts = [];
                if (false !== $nodes && '' !== $firstLanguage) {
                    foreach ($nodes as $node) {
                        if (!$node instanceof DOMElement) {
                            continue;
                        }
                        if ($this->languageCodesMatch($this->normalizeLanguageCode($node->getAttribute('lang')), $firstLanguage)) {
                            $sourceParts[] = $this->innerHtml($node);
                        }
                    }
                }
                $sourceHtml = implode("\n", array_filter($sourceParts, static fn (string $part): bool => '' !== trim($part)));
            }
        } elseif ($isFullDocument) {
            $body = $document->getElementsByTagName('body')->item(0);
            $sourceHtml = $body instanceof DOMElement ? $this->innerHtml($body) : $html;
        } else {
            $sourceHtml = $html;
        }

        $uniqueLanguages = [];
        foreach ($presentLanguages as $language) {
            if (!$this->containsMatchingLanguage($uniqueLanguages, $language)) {
                $uniqueLanguages[] = $language;
            }
        }

        return [
            'hasMarkers' => $hasMarkers,
            'isFullDocument' => $isFullDocument,
            'sourceHtml' => trim($sourceHtml),
            'presentLanguages' => $uniqueLanguages,
            'perLanguage' => $perLanguage,
            'contentSha256' => $this->contentSha256($html),
        ];
    }

    /**
     * Merge new language blocks into existing HTML (editor AI helper path).
     * Existing languages are not replaced — only new ones are appended.
     *
     * @param array<string, string> $translations language => inner HTML
     */
    public function mergeTranslations(
        string $originalHtml,
        string $sourceLanguage,
        string $sourceHtml,
        bool $hasMarkers,
        bool $isFullDocument,
        array $translations,
    ): string {
        $blocks = '';
        foreach ($translations as $language => $translatedHtml) {
            $blocks .= $this->buildLanguageBlock((string) $language, $translatedHtml);
        }

        if ($hasMarkers) {
            return $this->appendBeforeBodyEnd($originalHtml, $blocks, $isFullDocument);
        }

        $sourceBlock = $this->buildLanguageBlock($sourceLanguage, $sourceHtml);
        $replacement = $sourceBlock.$blocks;

        if (!$isFullDocument) {
            return $replacement;
        }

        $count = 0;
        $updated = preg_replace_callback(
            '/(<body\b[^>]*>)(.*?)(<\/body>)/is',
            static fn (array $matches): string => $matches[1].$replacement.$matches[3],
            $originalHtml,
            1,
            $count,
        );

        return 1 === $count && \is_string($updated) ? $updated : $originalHtml.$replacement;
    }

    /**
     * Create or replace a single language block. Client sends INNER HTML only
     * (server wraps with the canonical mce-translatehtml marker).
     *
     * @return array{
     *     html: string,
     *     action: 'created'|'replaced',
     *     language: string,
     *     presentLanguages: list<string>,
     *     contentSha256: string,
     *     chars: int,
     *     words: int
     * }
     */
    public function upsertLanguage(
        string $currentHtml,
        string $language,
        string $innerHtml,
        string $mode = self::MODE_UPSERT,
        ?string $sourceLanguageForWrap = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $mode = strtolower(trim($mode));
        if (!\in_array($mode, $this->supportedWriteModes(), true)) {
            throw new InvalidArgumentException(\sprintf('Invalid mode "%s". Use one of: %s.', $mode, implode(', ', $this->supportedWriteModes())));
        }

        $language = $this->normalizeLanguageCode($language);
        if ('' === $language) {
            throw new InvalidArgumentException('The language is invalid.');
        }

        $currentHtml = (string) $currentHtml;
        if (null !== $ifMatchSha256 && '' !== $ifMatchSha256 && $ifMatchSha256 !== $this->contentSha256($currentHtml)) {
            throw new InvalidArgumentException('The content has changed since it was last read (ifMatchSha256 mismatch). Re-read and try again.');
        }

        $innerHtml = $this->normalizeInnerHtml($innerHtml, $language);
        if (!$this->hasVisibleContent($innerHtml)) {
            throw new InvalidArgumentException('The language content is empty.');
        }

        $sourceLanguageForWrap = $this->normalizeLanguageCode((string) $sourceLanguageForWrap);
        $inspection = $this->inspect($currentHtml, $sourceLanguageForWrap);

        $existed = $this->languageAlreadyPresent($inspection, $language, $sourceLanguageForWrap, $currentHtml);

        if (!$inspection['hasMarkers']) {
            $resultHtml = $this->upsertWithoutMarkers(
                $currentHtml,
                $language,
                $innerHtml,
                $mode,
                $sourceLanguageForWrap,
                $inspection['isFullDocument'],
            );
        } else {
            $resultHtml = $this->upsertWithMarkers(
                $currentHtml,
                $language,
                $innerHtml,
                $mode,
                $inspection['isFullDocument'],
            );
        }

        $after = $this->inspect($resultHtml, $sourceLanguageForWrap ?: $language);

        return [
            'html' => $resultHtml,
            'action' => $existed ? 'replaced' : 'created',
            'language' => $language,
            'presentLanguages' => $after['presentLanguages'],
            'contentSha256' => $after['contentSha256'],
            'chars' => mb_strlen($innerHtml),
            'words' => $this->countWords($innerHtml),
        ];
    }

    /**
     * @param array{
     *     hasMarkers: bool,
     *     presentLanguages: list<string>,
     *     ...
     * } $inspection
     */
    private function languageAlreadyPresent(
        array $inspection,
        string $language,
        string $sourceLanguageForWrap,
        string $currentHtml,
    ): bool {
        if ($inspection['hasMarkers']) {
            return $this->containsMatchingLanguage($inspection['presentLanguages'], $language);
        }

        // Unmarked non-empty content is treated as the wrap/source language (or sole language when wrap is omitted).
        if ('' === trim($currentHtml)) {
            return false;
        }

        if ('' === $sourceLanguageForWrap) {
            return true;
        }

        return $this->languageCodesMatch($language, $sourceLanguageForWrap);
    }

    public function buildLanguageBlock(string $language, string $html): string
    {
        $language = $this->normalizeLanguageCode($language);

        return \sprintf(
            '<div class="mce-translatehtml" lang="%s">%s</div>',
            htmlspecialchars($language, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $html,
        );
    }

    /**
     * @param list<string> $languages
     */
    public function containsMatchingLanguage(array $languages, string $targetLanguage): bool
    {
        foreach ($languages as $language) {
            if ($this->languageCodesMatch((string) $language, $targetLanguage)) {
                return true;
            }
        }

        return false;
    }

    public function countWords(string $html): int
    {
        $plainText = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = trim((string) preg_replace('/\s+/u', ' ', $plainText));

        if ('' === $plainText) {
            return 0;
        }

        $words = preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY);

        return \is_array($words) ? \count($words) : 0;
    }

    private function upsertWithoutMarkers(
        string $currentHtml,
        string $language,
        string $innerHtml,
        string $mode,
        string $sourceLanguageForWrap,
        bool $isFullDocument,
    ): string {
        $trimmed = trim($currentHtml);

        // Empty field → create sole language block.
        if ('' === $trimmed) {
            if (self::MODE_REPLACE_ONLY === $mode) {
                throw new InvalidArgumentException(\sprintf('No existing "%s" language block to replace (content is empty).', $language));
            }

            return $this->maybeWrapFullDocument($this->buildLanguageBlock($language, $innerHtml), $currentHtml, $isFullDocument);
        }

        // Content has no markers. If the caller is writing the wrap/source language
        // (or did not specify one), replace the whole field with a single block.
        if ('' === $sourceLanguageForWrap || $this->languageCodesMatch($language, $sourceLanguageForWrap)) {
            if (self::MODE_CREATE_ONLY === $mode) {
                throw new InvalidArgumentException(\sprintf('Language "%s" already exists as the unmarked content. Use mode=upsert or mode=replace_only.', $language));
            }

            return $this->maybeWrapFullDocument($this->buildLanguageBlock($language, $innerHtml), $currentHtml, $isFullDocument);
        }

        // Adding a different language: wrap existing as source, append target.
        if (self::MODE_REPLACE_ONLY === $mode) {
            throw new InvalidArgumentException(\sprintf('No existing "%s" language block to replace. Unmarked content will be wrapped as "%s" when you upsert.', $language, $sourceLanguageForWrap));
        }

        $merged = $this->buildLanguageBlock($sourceLanguageForWrap, $trimmed)
            .$this->buildLanguageBlock($language, $innerHtml);

        return $this->maybeWrapFullDocument($merged, $currentHtml, $isFullDocument);
    }

    private function upsertWithMarkers(
        string $currentHtml,
        string $language,
        string $innerHtml,
        string $mode,
        bool $isFullDocument,
    ): string {
        $document = $this->loadHtmlDocument($currentHtml, $isFullDocument);
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(
            '//*[@lang and (contains(concat(" ", normalize-space(@class), " "), " mce-translatehtml ") or self::span)]'
        );

        $found = null;
        if (false !== $nodes) {
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                if ($this->languageCodesMatch($node->getAttribute('lang'), $language)) {
                    $found = $node;

                    break;
                }
            }
        }

        if ($found instanceof DOMElement) {
            if (self::MODE_CREATE_ONLY === $mode) {
                throw new InvalidArgumentException(\sprintf('Language "%s" already exists. Use mode=upsert or mode=replace_only to overwrite it.', $language));
            }

            $this->replaceElementInnerHtml($found, $innerHtml);
            // Keep lang attribute normalized on the existing element.
            $found->setAttribute('lang', $language);
            if (!$found->hasAttribute('class') || !str_contains(' '.$found->getAttribute('class').' ', ' mce-translatehtml ')) {
                $class = trim($found->getAttribute('class').' mce-translatehtml');
                $found->setAttribute('class', $class);
            }

            return $this->saveHtmlDocument($document, $isFullDocument);
        }

        if (self::MODE_REPLACE_ONLY === $mode) {
            throw new InvalidArgumentException(\sprintf('No existing "%s" language block to replace.', $language));
        }

        $block = $this->buildLanguageBlock($language, $innerHtml);

        // Insert the new block as a sibling of the existing language marker(s),
        // inside whichever element already wraps them (e.g. a "tiny-content"
        // editor wrapper around a single-language field). Appending after the
        // raw HTML string instead (as appendBeforeBodyEnd does) would place the
        // new block outside that wrapper, splitting the languages across two
        // different DOM parents and breaking the language filter on display.
        $lastMarked = false !== $nodes && $nodes->length > 0 ? $nodes->item($nodes->length - 1) : null;
        $anchorParent = $lastMarked?->parentNode;

        if ($anchorParent instanceof DOMElement) {
            $this->appendFragmentTo($document, $anchorParent, $block);

            return $this->saveHtmlDocument($document, $isFullDocument);
        }

        return $this->appendBeforeBodyEnd($currentHtml, $block, $isFullDocument);
    }

    private function appendFragmentTo(DOMDocument $document, DOMElement $parent, string $html): void
    {
        $fragmentHtml = '<div id="__chamilo_fragment_root">'.$html.'</div>';
        $fragmentDocument = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $fragmentDocument->loadHTML(
            '<?xml encoding="UTF-8">'.$fragmentHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $fragmentDocument->getElementById('__chamilo_fragment_root');
        if (!$root instanceof DOMElement) {
            return;
        }

        foreach (iterator_to_array($root->childNodes) as $child) {
            $parent->appendChild($document->importNode($child, true));
        }
    }

    private function maybeWrapFullDocument(string $bodyHtml, string $originalHtml, bool $isFullDocument): string
    {
        if (!$isFullDocument) {
            return $bodyHtml;
        }

        $count = 0;
        $updated = preg_replace_callback(
            '/(<body\b[^>]*>)(.*?)(<\/body>)/is',
            static fn (array $matches): string => $matches[1].$bodyHtml.$matches[3],
            $originalHtml,
            1,
            $count,
        );

        return 1 === $count && \is_string($updated) ? $updated : $bodyHtml;
    }

    private function appendBeforeBodyEnd(string $html, string $blocks, bool $isFullDocument): string
    {
        if ($isFullDocument && false !== stripos($html, '</body>')) {
            return preg_replace('/<\/body>/i', $blocks.'</body>', $html, 1) ?? $html.$blocks;
        }

        return $html.$blocks;
    }

    /**
     * Accept pure inner HTML, or a single outer mce-translatehtml wrapper for this language.
     */
    private function normalizeInnerHtml(string $html, string $expectedLanguage): string
    {
        $html = trim($html);
        $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
        $html = preg_replace('/\s*```$/', '', $html) ?? $html;
        $html = trim($html);

        if ('' === $html) {
            return '';
        }

        if (1 === preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = trim((string) ($matches[1] ?? ''));
        }

        // Strip a single outer language wrapper if present.
        if (1 === preg_match(
            '/^\s*<(?P<tag>div|section|article|p|span)\b(?=[^>]*\blang\s*=\s*(["\'])(?P<lang>[a-z]{2}(?:[-_][a-z]{2})?)\2)(?=[^>]*\bclass\s*=\s*(["\'])[^"\']*\bmce-translatehtml\b[^"\']*\4)[^>]*>(?P<content>.*)<\/\k<tag>\s*>\s*$/is',
            $html,
            $wrap,
        )) {
            $wrappedLang = $this->normalizeLanguageCode((string) ($wrap['lang'] ?? ''));
            if ('' !== $wrappedLang && !$this->languageCodesMatch($wrappedLang, $expectedLanguage)) {
                throw new InvalidArgumentException(\sprintf('The provided content is wrapped for language "%s" but the upsert language is "%s".', $wrappedLang, $expectedLanguage));
            }
            $html = trim((string) ($wrap['content'] ?? ''));
        }

        // Nested translatehtml markers are not allowed inside a single-language payload.
        if (false !== stripos($html, 'mce-translatehtml')) {
            throw new InvalidArgumentException('The language content must not contain nested translatehtml markers. Send only the inner HTML for this language.');
        }

        return $html;
    }

    private function hasVisibleContent(string $html): bool
    {
        if ('' !== trim(strip_tags($html))) {
            return true;
        }

        return 1 === preg_match('/<(img|video|audio|iframe|embed|object|hr|br)\b/i', $html);
    }

    private function replaceElementInnerHtml(DOMElement $element, string $innerHtml): void
    {
        while ($element->hasChildNodes()) {
            $element->removeChild($element->firstChild);
        }

        $fragmentHtml = '<div id="__chamilo_fragment_root">'.$innerHtml.'</div>';
        $fragmentDocument = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $fragmentDocument->loadHTML(
            '<?xml encoding="UTF-8">'.$fragmentHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $fragmentDocument->getElementById('__chamilo_fragment_root');
        if (!$root instanceof DOMElement) {
            // Fallback: import nothing rather than corrupt the node.
            return;
        }

        $owner = $element->ownerDocument;
        if (null === $owner) {
            return;
        }

        foreach (iterator_to_array($root->childNodes) as $child) {
            $element->appendChild($owner->importNode($child, true));
        }
    }

    private function loadHtmlDocument(string $html, bool $isFullDocument): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        if ($isFullDocument) {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        } else {
            $document->loadHTML(
                '<?xml encoding="UTF-8"><div id="__chamilo_translate_root">'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    private function saveHtmlDocument(DOMDocument $document, bool $isFullDocument): string
    {
        if ($isFullDocument) {
            $html = $document->saveHTML() ?: '';

            // Drop the XML encoding preamble if present.
            return preg_replace('/^<\?xml[^>]*>\s*/i', '', $html) ?? $html;
        }

        $root = $document->getElementById('__chamilo_translate_root');
        if (!$root instanceof DOMElement) {
            // Some libxml builds ignore id on loadHTML fragments — fall back to first div.
            $root = $document->getElementsByTagName('div')->item(0);
        }
        if (!$root instanceof DOMElement) {
            return $document->saveHTML() ?: '';
        }

        return $this->innerHtml($root);
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $childNode) {
            $html .= $node->ownerDocument?->saveHTML($childNode) ?: '';
        }

        return $html;
    }
}
