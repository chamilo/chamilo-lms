<?php

declare(strict_types=1);

class ExtAuthChamiloLogoutButtonBehaviourPlugin extends Plugin
{
    private const DEFAULT_LOGOUT_URL = '/logout';

    protected function __construct()
    {
        $settings = [
            'behavior_enabled' => 'boolean',
            'apply_only_to_external_auth' => 'boolean',
            'logout_url' => 'text',
            'tooltip_text' => 'text',
            'show_alert' => 'boolean',
            'alert_text' => 'text',
        ];

        parent::__construct(
            '2.0',
            'Hubert Borderiou, Chamilo',
            $settings
        );
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }


    public function get(string $name): mixed
    {
        if ('behavior_enabled' === $name) {
            $value = parent::get('behavior_enabled');

            if (null !== $value && '' !== $value) {
                return $value;
            }

            return parent::get('enabled');
        }

        return parent::get($name);
    }

    public function get_info(): array
    {
        $info = parent::get_info();
        $info['supports_regions'] = false;

        return $info;
    }

    public function install(): void
    {
    }

    public function uninstall(): void
    {
    }

    public function getLogoutConfigurationForCurrentUser(): array
    {
        if (!$this->shouldApplyToCurrentUser()) {
            return [
                'active' => false,
                'logoutUrl' => self::DEFAULT_LOGOUT_URL,
                'tooltip' => '',
                'showAlert' => false,
                'alertText' => '',
                'disabled' => false,
            ];
        }

        $logoutUrl = $this->normalizeLogoutUrl((string) ($this->get('logout_url') ?? ''));
        $tooltip = $this->sanitizeText((string) ($this->get('tooltip_text') ?? ''));
        $alertText = $this->sanitizeText((string) ($this->get('alert_text') ?? ''));
        $showAlert = $this->isTruthy($this->get('show_alert'));
        $disabled = '#' === $logoutUrl;

        return [
            'active' => true,
            'logoutUrl' => $logoutUrl,
            'tooltip' => $tooltip,
            'showAlert' => $showAlert && '' !== $alertText,
            'alertText' => $alertText,
            'disabled' => $disabled,
        ];
    }

    private function shouldApplyToCurrentUser(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!$this->isTruthy($this->get('behavior_enabled'))) {
            return false;
        }

        if (function_exists('api_is_anonymous') && api_is_anonymous()) {
            return false;
        }

        if (!$this->isTruthy($this->get('apply_only_to_external_auth'))) {
            return true;
        }

        $sources = $this->getCurrentUserAuthSources();

        if ([] === $sources) {
            return false;
        }

        foreach ($sources as $source) {
            $source = strtolower(trim((string) $source));

            if ('' === $source) {
                continue;
            }

            if (!in_array($source, ['platform', 'local', 'chamilo'], true)) {
                return true;
            }
        }

        return false;
    }

    private function getCurrentUserAuthSources(): array
    {
        global $_user;

        $sources = [];

        try {
            if (function_exists('api_get_user_entity')) {
                $user = api_get_user_entity();

                if ($user && method_exists($user, 'getAuthSourcesAuthentications')) {
                    $accessUrl = null;

                    if (class_exists('Container') && method_exists('Container', 'getAccessUrlUtil')) {
                        $accessUrlUtil = Container::getAccessUrlUtil();
                        if ($accessUrlUtil && method_exists($accessUrlUtil, 'getCurrent')) {
                            $accessUrl = $accessUrlUtil->getCurrent();
                        }
                    }

                    if ($accessUrl) {
                        $sources = $user->getAuthSourcesAuthentications($accessUrl);
                    }
                }

                if ([] === $sources && $user && method_exists($user, 'getAuthSources')) {
                    foreach ($user->getAuthSources() as $authSource) {
                        if (method_exists($authSource, 'getAuthentication')) {
                            $sources[] = $authSource->getAuthentication();
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[ExtAuthChamiloLogoutButtonBehaviour] Unable to read user auth sources: '.$e->getMessage());
        }

        if ([] === $sources && isset($_user['auth_sources']) && is_array($_user['auth_sources'])) {
            $sources = $_user['auth_sources'];
        }

        return array_values(array_filter(array_map('strval', $sources)));
    }

    private function normalizeLogoutUrl(string $url): string
    {
        $url = trim($url);

        if ('' === $url) {
            return self::DEFAULT_LOGOUT_URL;
        }

        if ('#' === $url) {
            return '#';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return self::DEFAULT_LOGOUT_URL;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        if (in_array($scheme, ['http', 'https'], true) && '' !== $host) {
            return $url;
        }

        return self::DEFAULT_LOGOUT_URL;
    }

    private function sanitizeText(string $text): string
    {
        $text = trim(strip_tags($text));

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 500);
        }

        return substr($text, 0, 500);
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return 1 === $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}
