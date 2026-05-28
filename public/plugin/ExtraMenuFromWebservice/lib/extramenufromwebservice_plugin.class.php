<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Extra menu integration for Chamilo 2.
 *
 * The plugin no longer injects arbitrary HTML/CSS/JS returned by a remote
 * service. It exposes a small JSON endpoint consumed by a Vue component in
 * the Chamilo topbar. The remote service is expected to return API Platform
 * style JSON or a plain array of menu items.
 */
class ExtraMenuFromWebservicePlugin extends Plugin
{
    public const SESSION_TOKEN = 'extramenufromwebservice_plugin_token';
    public const SESSION_TOKEN_START = 'extramenufromwebservice_plugin_token_start';
    public const SESSION_MENU_CACHE_PREFIX = 'extramenufromwebservice_plugin_menu_';

    protected function __construct()
    {
        $settings = [
            'api_menu_url' => 'text',
            'authentication_url' => 'text',
            'authentication_email' => 'text',
            'authentication_password' => 'text',
            'api_bearer_token' => 'text',
            'normal_menu_url' => 'text',
            'mobile_menu_url' => 'text',
            'menu_request_mode' => [
                'type' => 'select',
                'options' => [
                    'api_platform_query' => 'API Platform query parameters',
                    'legacy_email_path' => 'Legacy email path',
                ],
            ],
            'session_timeout' => 'text',
            'cache_ttl' => 'text',
            'request_timeout' => 'text',
        ];

        parent::__construct(
            '0.2',
            'Chamilo',
            $settings
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    /**
     * Kept intentionally empty. The menu is rendered by Vue, not by injecting
     * raw HTML through plugin regions.
     */
    public function renderRegion($region)
    {
        return '';
    }

    public function getVueMenuResponse(): array
    {
        $title = $this->get_lang('MenuTitle');
        if (empty($title) || 'MenuTitle' === $title) {
            $title = 'Extra menu';
        }

        $response = [
            'enabled' => false,
            'title' => $title,
            'items' => [],
        ];

        if (!$this->isEnabled()) {
            return $response;
        }

        if (api_is_anonymous()) {
            return $response;
        }

        $userId = (int) api_get_user_id();
        if (0 >= $userId) {
            return $response;
        }

        $userInfo = api_get_user_info(
            $userId,
            false,
            false,
            false,
            false,
            false,
            true
        );

        $email = isset($userInfo['email']) ? trim((string) $userInfo['email']) : '';
        if ('' === $email) {
            return $response;
        }

        $isMobile = $this->isMobileRequest();
        $cacheKey = self::SESSION_MENU_CACHE_PREFIX.md5($userId.'|'.$email.'|'.($isMobile ? '1' : '0'));

        $cached = $this->readMenuCache($cacheKey);
        if (null !== $cached) {
            $response['enabled'] = !empty($cached);

            $response['items'] = $cached;

            return $response;
        }

        $items = $this->getMenuItems($email, $userId, $isMobile);

        $this->writeMenuCache($cacheKey, $items);
        $response['enabled'] = !empty($items);
        $response['items'] = $items;

        return $response;
    }

    public function getMenuItems(string $userEmail, int $userId, bool $isMobile = false): array
    {
        $menuUrl = $this->buildMenuUrl($userEmail, $userId, $isMobile);
        if ('' === $menuUrl) {
            return [];
        }

        $token = $this->getBearerToken();
        $headers = [
            'Accept: application/ld+json, application/json',
        ];

        if ('' !== $token) {
            $headers[] = 'Authorization: Bearer '.$token;
        }

        $request = $this->requestJson($menuUrl, 'GET', $headers);
        if (!$request['ok']) {
            error_log('[ExtraMenuFromWebservice] Menu request failed: '.$request['error']);

            return [];
        }

        return $this->extractMenuItems($request['data']);
    }

    public function getBearerToken(): string
    {
        $staticToken = trim((string) $this->get('api_bearer_token'));
        if ('' !== $staticToken) {
            return $staticToken;
        }

        $sessionTimeout = $this->getPositiveIntSetting('session_timeout', 86400);

        if (
            Session::has(self::SESSION_TOKEN) &&
            Session::has(self::SESSION_TOKEN_START)
        ) {
            $tokenStartTime = (int) Session::read(self::SESSION_TOKEN_START);

            if (!$this->tokenIsExpired($tokenStartTime, $sessionTimeout)) {
                return (string) Session::read(self::SESSION_TOKEN);
            }
        }

        $token = $this->requestAuthenticationToken();

        if ('' !== $token) {
            Session::write(self::SESSION_TOKEN, $token);
            Session::write(self::SESSION_TOKEN_START, time());
        }

        return $token;
    }

    public function requestAuthenticationToken(): string
    {
        $authenticationUrl = $this->normalizeHttpUrl((string) $this->get('authentication_url'));
        $authenticationEmail = trim((string) $this->get('authentication_email'));
        $authenticationPassword = (string) $this->get('authentication_password');

        if ('' === $authenticationUrl || '' === $authenticationEmail || '' === $authenticationPassword) {
            return '';
        }

        $payload = json_encode([
            'email' => $authenticationEmail,
            'password' => $authenticationPassword,
        ]);

        if (false === $payload) {
            return '';
        }

        $request = $this->requestJson(
            $authenticationUrl,
            'POST',
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            $payload
        );

        if (!$request['ok']) {
            error_log('[ExtraMenuFromWebservice] Authentication request failed: '.$request['error']);

            return '';
        }

        return $this->extractToken($request['data']);
    }

    public static function tokenIsExpired(int $tokenStartTime, int $pluginSessionTimeout): bool
    {
        return (time() - $tokenStartTime) > $pluginSessionTimeout;
    }

    private function buildMenuUrl(string $userEmail, int $userId, bool $isMobile): string
    {
        $menuUrl = trim((string) $this->get('api_menu_url'));

        if ('' === $menuUrl) {
            $menuUrl = $isMobile
                ? trim((string) $this->get('mobile_menu_url'))
                : trim((string) $this->get('normal_menu_url'));
        }

        $menuUrl = $this->normalizeHttpUrl($menuUrl);
        if ('' === $menuUrl) {
            return '';
        }

        $requestMode = (string) $this->get('menu_request_mode');

        if ('legacy_email_path' === $requestMode && '' === trim((string) $this->get('api_menu_url'))) {
            return rtrim($menuUrl, '/').'/'.rawurlencode($userEmail);
        }

        $params = [
            'email' => $userEmail,
            'userId' => $userId,
            'locale' => api_get_language_isocode(),
            'mobile' => $isMobile ? 1 : 0,
        ];

        $separator = str_contains($menuUrl, '?') ? '&' : '?';

        return $menuUrl.$separator.http_build_query($params);
    }

    private function requestJson(
        string $url,
        string $method = 'GET',
        array $headers = [],
        ?string $payload = null
    ): array {
        $response = [
            'ok' => false,
            'status' => 0,
            'data' => [],
            'error' => '',
        ];

        if (!function_exists('curl_init')) {
            $response['error'] = 'The PHP curl extension is not available.';

            return $response;
        }

        $timeout = $this->getPositiveIntSetting('request_timeout', 3);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if (null !== $payload) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        $rawResponse = curl_exec($curl);
        $curlError = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $response['status'] = $status;

        if (false === $rawResponse) {
            $response['error'] = $curlError ?: 'The remote service did not return a response.';

            return $response;
        }

        if (200 > $status || 300 <= $status) {
            $response['error'] = 'Unexpected HTTP status '.$status.'.';

            return $response;
        }

        $decoded = json_decode((string) $rawResponse, true);
        if (!is_array($decoded)) {
            $response['error'] = 'The remote service did not return valid JSON.';

            return $response;
        }

        $response['ok'] = true;
        $response['data'] = $decoded;

        return $response;
    }

    private function extractToken(array $payload): string
    {
        $candidates = [
            $payload['token'] ?? null,
            $payload['data']['token'] ?? null,
            $payload['data']['data']['token'] ?? null,
            $payload['hydra:member'][0]['token'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $token = trim((string) $candidate);

            if ('' !== $token) {
                return $token;
            }
        }

        return '';
    }

    private function extractMenuItems(array $payload): array
    {
        $candidateLists = [
            $payload['hydra:member'] ?? null,
            $payload['member'] ?? null,
            $payload['items'] ?? null,
            $payload['menu'] ?? null,
            $payload['data']['items'] ?? null,
            $payload['data']['menu'] ?? null,
            $payload['data']['data']['items'] ?? null,
            $payload['data']['data']['menu'] ?? null,
            $payload['data']['data'] ?? null,
        ];

        foreach ($candidateLists as $candidateList) {
            if (!is_array($candidateList)) {
                continue;
            }

            if (!$this->isList($candidateList)) {
                continue;
            }

            $items = $this->normalizeMenuItems($candidateList, 0);

            if (!empty($items)) {
                return $items;
            }
        }

        return [];
    }

    private function normalizeMenuItems(array $items, int $depth): array
    {
        if (2 < $depth) {
            return [];
        }

        $normalizedItems = [];
        $counter = 0;

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!$this->isMenuItemVisibleForCurrentUser($item)) {
                continue;
            }

            $normalized = $this->normalizeMenuItem($item, $depth);

            if (null === $normalized) {
                continue;
            }

            $normalizedItems[] = $normalized;
            $counter++;

            if (50 <= $counter) {
                break;
            }
        }

        return $normalizedItems;
    }

    private function normalizeMenuItem(array $item, int $depth): ?array
    {
        if (isset($item['enabled']) && false === (bool) $item['enabled']) {
            return null;
        }

        if (isset($item['active']) && false === (bool) $item['active']) {
            return null;
        }

        $title = $this->sanitizeText(
            $item['title']
                ?? $item['label']
                ?? $item['name']
                ?? ''
        );

        if ('' === $title) {
            return null;
        }

        $url = $this->sanitizeMenuUrl(
            (string) ($item['url'] ?? $item['href'] ?? $item['path'] ?? '#')
        );

        if ('' === $url) {
            return null;
        }

        $target = (string) ($item['target'] ?? '_self');
        $target = '_blank' === $target ? '_blank' : '_self';

        $icon = $this->sanitizeIcon((string) ($item['icon'] ?? 'menu-right'));

        $children = [];

        if (isset($item['children']) && is_array($item['children'])) {
            $children = $this->normalizeMenuItems($item['children'], $depth + 1);
        }

        return [
            'title' => $title,
            'url' => $url,
            'target' => $target,
            'icon' => $icon,
            'children' => $children,
        ];
    }

    private function isMenuItemVisibleForCurrentUser(array $item): bool
    {
        if (!isset($item['roles']) || !is_array($item['roles']) || empty($item['roles'])) {
            return true;
        }

        $allowedRoles = array_map(static function ($role): string {
            return strtoupper(trim((string) $role));
        }, $item['roles']);

        if (in_array('ROLE_USER', $allowedRoles, true)) {
            return true;
        }

        if (api_is_platform_admin() && in_array('ROLE_ADMIN', $allowedRoles, true)) {
            return true;
        }

        if (api_is_course_admin() && in_array('ROLE_TEACHER', $allowedRoles, true)) {
            return true;
        }

        if (!api_is_platform_admin() && in_array('ROLE_STUDENT', $allowedRoles, true)) {
            return true;
        }

        return false;
    }

    private function sanitizeMenuUrl(string $url): string
    {
        $url = trim($url);

        if ('#' === $url) {
            return '#';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    private function sanitizeIcon(string $icon): string
    {
        $icon = strtolower(trim($icon));
        $icon = preg_replace('/[^a-z0-9\-]/', '', $icon) ?: '';

        if ('' === $icon) {
            return 'mdi-menu-right';
        }

        if (!str_starts_with($icon, 'mdi-')) {
            $icon = 'mdi-'.$icon;
        }

        if (!preg_match('/^mdi-[a-z0-9\-]+$/', $icon)) {
            return 'mdi-menu-right';
        }

        return $icon;
    }

    private function sanitizeText(mixed $text): string
    {
        $text = trim(strip_tags((string) $text));

        return html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function normalizeHttpUrl(string $url): string
    {
        $url = trim($url);

        if ('' === $url) {
            return '';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $url;
    }

    private function readMenuCache(string $cacheKey): ?array
    {
        $cacheTtl = $this->getPositiveIntSetting('cache_ttl', 300);

        if (0 >= $cacheTtl || !Session::has($cacheKey)) {
            return null;
        }

        $cache = Session::read($cacheKey);

        if (!is_array($cache) || !isset($cache['time'], $cache['items']) || !is_array($cache['items'])) {
            return null;
        }

        if ((time() - (int) $cache['time']) > $cacheTtl) {
            return null;
        }

        return $cache['items'];
    }

    private function writeMenuCache(string $cacheKey, array $items): void
    {
        $cacheTtl = $this->getPositiveIntSetting('cache_ttl', 300);

        if (0 >= $cacheTtl) {
            return;
        }

        Session::write($cacheKey, [
            'time' => time(),
            'items' => $items,
        ]);
    }


    private function isMobileRequest(): bool
    {
        if (function_exists('api_is_browser_mobile')) {
            return (bool) api_is_browser_mobile();
        }

        $userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        if ('' === $userAgent) {
            return false;
        }

        $mobileMarkers = [
            'android',
            'blackberry',
            'iphone',
            'ipad',
            'ipod',
            'iemobile',
            'mobile',
            'opera mini',
            'webos',
            'windows phone',
        ];

        foreach ($mobileMarkers as $marker) {
            if (str_contains($userAgent, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function getPositiveIntSetting(string $name, int $default): int
    {
        $value = (int) $this->get($name);

        if (0 >= $value) {
            return $default;
        }

        return $value;
    }

    private function isList(array $value): bool
    {
        if ([] === $value) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
