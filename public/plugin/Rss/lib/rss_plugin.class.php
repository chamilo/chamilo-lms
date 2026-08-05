<?php

/* For licensing terms, see /license.txt */

/**
 * RSS content plugin.
 */
class RssPlugin extends Plugin
{
    public const PLUGIN_NAME = 'Rss';
    public const DEFAULT_MAX_ITEMS = 5;
    private const TIMEOUT_SECONDS = 5;

    protected function __construct()
    {
        parent::__construct(
            '1.2',
            'Laurent Opprecht, Julio Montoya',
            [
                'block_title' => 'text',
                'rss' => 'text',
            ]
        );
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['supports_regions'] = false;

        return $info;
    }

    public function get_block_title(): string
    {
        return trim((string) $this->get('block_title'));
    }

    public function get_rss(): string
    {
        return trim((string) $this->get('rss'));
    }

    public function getFeedPageUrl(): string
    {
        return api_get_path(WEB_PATH).'plugin/'.self::PLUGIN_NAME.'/index.php';
    }

    public function renderRegion($region): string
    {
        return '';
    }

    public function renderBlock(bool $isRegionBlock = true): string
    {
        $url = $this->get_rss();
        $regionAttribute = $isRegionBlock ? ' data-rss-region-block="1"' : '';

        $content = '<section class="rounded-2xl border border-gray-20 bg-white p-4 shadow-sm"'.$regionAttribute.'>';
        $content .= $this->renderHeader();

        if ('' === $url) {
            $content .= $this->renderMessage($this->get_lang('no_rss'), 'warning');
            $content .= '</section>';

            return $content;
        }

        if (!$this->isValidFeedUrl($url)) {
            $content .= $this->renderMessage($this->get_lang('invalid_rss_url'), 'warning');
            $content .= '</section>';

            return $content;
        }

        try {
            $items = $this->getFeedItems($url);
        } catch (Throwable $exception) {
            error_log('[RssPlugin] '.$exception->getMessage());
            $content .= $this->renderMessage($this->get_lang('no_valid_rss'), 'warning');
            $content .= '</section>';

            return $content;
        }

        if ([] === $items) {
            $content .= $this->renderMessage($this->get_lang('no_items'), 'warning');
            $content .= '</section>';

            return $content;
        }

        $content .= '<div class="space-y-3">';

        foreach ($items as $item) {
            $content .= $this->renderItem($item);
        }

        $content .= '</div>';
        $content .= '</section>';

        return $content;
    }

    public function renderFullPage(): string
    {
        $content = '<div class="space-y-6">';
        $content .= $this->renderBlock(false);
        $content .= '</div>';

        return $content;
    }

    public function isValidFeedUrl(string $url): bool
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parts['host'] ?? '')));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if ('' === $host || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        return $this->isAllowedHost($host);
    }

    private function renderHeader(): string
    {
        $title = $this->get_block_title();
        $title = '' !== $title ? $title : $this->get_title();

        return '
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">'
                    .$this->escape($this->get_lang('Feed')).'
                </p>
                <h2 class="mt-1 text-xl font-bold text-gray-90">'
                    .$this->escape($title).'
                </h2>
            </div>';
    }

    private function renderMessage(string $message, string $type): string
    {
        $classes = 'warning' === $type
            ? 'bg-yellow-50 text-yellow-800 border-yellow-200'
            : 'bg-blue-50 text-blue-800 border-blue-200';

        return '<div class="rounded-lg border p-3 text-sm '.$classes.'">'.$this->escape($message).'</div>';
    }

    private function renderItem(array $item): string
    {
        $title = $this->escape($item['title']);
        $link = $this->sanitizeExternalUrl($item['link']);
        $description = $this->cleanDescription($item['description']);
        $date = $this->escape($item['date']);

        $titleHtml = $title;

        if ('' !== $link) {
            $titleHtml = '
                <a class="font-semibold text-blue-700 hover:underline" href="'.$this->escape($link).'" target="_blank" rel="noopener noreferrer nofollow">
                    '.$title.'
                </a>';
        }

        $dateHtml = '' !== $date
            ? '<p class="mt-1 text-xs text-gray-50">'.$date.'</p>'
            : '';

        return '
            <article class="rounded-xl border border-gray-20 bg-gray-10 p-4">
                <h3 class="text-base font-semibold text-gray-90">'.$titleHtml.'</h3>
                '.$dateHtml.'
                <div class="mt-2 text-sm text-gray-70">'.$description.'</div>
            </article>';
    }

    private function getFeedItems(string $url): array
    {
        $feedContent = $this->fetchFeedContent($url);

        if ('' === $feedContent) {
            return [];
        }

        $readerClass = $this->getReaderClass();

        if (null === $readerClass || !method_exists($readerClass, 'importString')) {
            throw new RuntimeException('RSS reader is not available.');
        }

        $channel = $readerClass::importString($feedContent);
        $items = [];
        $count = 0;

        foreach ($channel as $item) {
            if ($count >= self::DEFAULT_MAX_ITEMS) {
                break;
            }

            $items[] = [
                'title' => (string) $item->getTitle(),
                'link' => (string) $item->getLink(),
                'description' => (string) $item->getDescription(),
                'date' => $this->formatDate($item->getDateModified() ?: $item->getDateCreated()),
            ];

            $count++;
        }

        return $items;
    }

    private function getReaderClass(): ?string
    {
        if (class_exists('Laminas\Feed\Reader\Reader')) {
            return 'Laminas\Feed\Reader\Reader';
        }

        if (class_exists('Zend\Feed\Reader\Reader')) {
            return 'Zend\Feed\Reader\Reader';
        }

        return null;
    }

    private function fetchFeedContent(string $url): string
    {
        if (function_exists('curl_init')) {
            return $this->fetchFeedContentWithCurl($url);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'follow_location' => 0,
                'ignore_errors' => true,
                'header' => "User-Agent: Chamilo RSS plugin\r\n",
            ],
            'https' => [
                'timeout' => self::TIMEOUT_SECONDS,
                'follow_location' => 0,
                'ignore_errors' => true,
                'header' => "User-Agent: Chamilo RSS plugin\r\n",
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if (false === $content) {
            return '';
        }

        return substr((string) $content, 0, 1024 * 1024);
    }

    private function fetchFeedContentWithCurl(string $url): string
    {
        $handle = curl_init($url);

        if (false === $handle) {
            return '';
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_USERAGENT => 'Chamilo RSS plugin',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_MAXFILESIZE => 1024 * 1024,
        ]);

        $content = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if (false === $content || 200 > $statusCode || 399 < $statusCode) {
            return '';
        }

        return substr((string) $content, 0, 1024 * 1024);
    }

    private function isAllowedHost(string $host): bool
    {
        $host = trim($host, "[] \t\n\r\0\x0B.");

        if ('' === $host || 'localhost' === $host || str_ends_with($host, '.local')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($host);
        }

        $addresses = gethostbynamel($host);

        if (false === $addresses || [] === $addresses) {
            return false;
        }

        foreach ($addresses as $address) {
            if (!$this->isPublicIp($address)) {
                return false;
            }
        }

        return true;
    }

    private function isPublicIp(string $address): bool
    {
        return false !== filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function sanitizeExternalUrl(string $url): string
    {
        $url = trim($url);

        if ('' === $url) {
            return '';
        }

        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    private function cleanDescription(string $description): string
    {
        $description = trim($description);

        if ('' === $description) {
            return '';
        }

        if (class_exists('Security') && method_exists('Security', 'remove_XSS')) {
            return Security::remove_XSS($description);
        }

        return strip_tags($description, '<p><br><ul><ol><li><strong><b><em><i><a>');
    }

    private function formatDate($date): string
    {
        if ($date instanceof DateTimeInterface) {
            return api_format_date($date->getTimestamp(), DATE_FORMAT_SHORT);
        }

        return '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
