<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Security;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PREG_SPLIT_NO_EMPTY;

final class WikiPageRenderer
{
    public function normalizeReflink(?string $raw): string
    {
        if (null === $raw || '' === trim($raw)) {
            return 'index';
        }

        $normalized = $this->normalizeToken($raw);
        $homeLabel = \function_exists('get_lang') ? (string) get_lang('Home') : 'Home';
        $aliases = array_filter([
            'index',
            $this->normalizeToken($homeLabel),
        ]);

        return \in_array($normalized, $aliases, true) ? 'index' : $normalized;
    }

    public function displayTitle(string $reflink, ?string $storedTitle = null): string
    {
        if ('index' === $reflink) {
            return \function_exists('get_lang') ? (string) get_lang('Home') : 'Home';
        }

        if (null !== $storedTitle && '' !== trim($storedTitle)) {
            return $this->sanitizeTitle($storedTitle);
        }

        return str_replace('_', ' ', $reflink);
    }

    public function sanitizeContent(string $content, bool $strictFiltering): string
    {
        if (class_exists(Security::class)) {
            if ($strictFiltering && \defined('COURSEMANAGERLOWSECURITY')) {
                return (string) Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
            }

            return (string) Security::remove_XSS($content);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function sanitizeTitle(string $title): string
    {
        if (class_exists(Security::class)) {
            return trim(html_entity_decode(strip_tags((string) Security::remove_XSS($title)), ENT_QUOTES, 'UTF-8'));
        }

        return trim(strip_tags($title));
    }

    /**
     * @param array<int, string> $existingReflinks
     * @param array<string, int> $context
     */
    public function renderInternalLinks(
        string $content,
        array $existingReflinks,
        int $nodeId,
        array $context,
    ): string {
        $existing = array_fill_keys($existingReflinks, true);

        $rendered = preg_replace_callback(
            '/\[\[([^\[\]]+)\]\]/u',
            function (array $matches) use ($existing, $nodeId, $context): string {
                $value = trim((string) ($matches[1] ?? ''));
                if ('' === $value) {
                    return '';
                }

                $parts = explode('|', $value, 2);
                $rawLink = trim(strip_tags($parts[0]));
                $reflink = $this->normalizeReflink($rawLink);
                $label = isset($parts[1]) ? trim(strip_tags($parts[1])) : trim(strip_tags($value));

                if ('index' === $reflink) {
                    $label = $this->displayTitle('index');
                }

                if ('' === $label) {
                    $label = $this->displayTitle($reflink);
                }

                $query = [
                    'cid' => $context['cid'],
                    'title' => $reflink,
                ];

                if (($context['sid'] ?? 0) > 0) {
                    $query['sid'] = $context['sid'];
                }

                if (($context['gid'] ?? 0) > 0) {
                    $query['gid'] = $context['gid'];
                }

                $url = '/resources/wiki/'.$nodeId.'/?'.http_build_query($query);
                $exists = isset($existing[$reflink]);
                $class = $exists
                    ? 'wiki-internal-link text-primary hover:underline'
                    : 'wiki-internal-link text-red-500 hover:underline';

                return \sprintf(
                    '<a class="%s" data-wiki-exists="%s" data-wiki-reflink="%s" href="%s">%s</a>',
                    $class,
                    $exists ? '1' : '0',
                    htmlspecialchars($reflink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                );
            },
            $content,
        );

        return null === $rendered ? $content : $rendered;
    }

    public function normalizeStoredProgress(int|string|null $progress): int
    {
        $value = max(0, (int) $progress);

        if ($value <= 10) {
            return min(100, $value * 10);
        }

        return min(100, $value);
    }

    public function wordCount(string $content): int
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        if (null === $text || '' === $text) {
            return 0;
        }

        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return \is_array($tokens) ? \count($tokens) : 0;
    }

    /**
     * @return array<int, string>
     */
    public function extractInternalReflinks(string $content): array
    {
        preg_match_all('/\[\[([^\[\]]+)\]\]/u', $content, $matches);
        $values = $matches[1] ?? [];
        $reflinks = [];

        foreach ($values as $value) {
            $parts = explode('|', (string) $value, 2);
            $reflink = $this->normalizeReflink((string) $parts[0]);

            if ('' !== $reflink) {
                $reflinks[$reflink] = $reflink;
            }
        }

        return array_values($reflinks);
    }

    public function serializeInternalReflinks(string $content): string
    {
        $reflinks = $this->extractInternalReflinks($content);

        if ([] === $reflinks) {
            return '';
        }

        return implode('', array_map(
            static fn (string $reflink): string => $reflink.' ',
            $reflinks,
        ));
    }

    private function normalizeToken(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = strip_tags(trim($value));
        $value = \function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        $value = str_replace(' ', '_', $value);
        $normalized = preg_replace('/_+/', '_', $value);

        return null === $normalized ? $value : $normalized;
    }
}
