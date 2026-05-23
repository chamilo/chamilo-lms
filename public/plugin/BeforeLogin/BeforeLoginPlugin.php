<?php

/* For licensing terms, see /license.txt */

/**
 * Shows a confirmation screen before anonymous users can see the login page.
 */
class BeforeLoginPlugin extends Plugin
{
    public const PLUGIN_NAME = 'BeforeLogin';

    private const SESSION_ACCEPTED = 'before_login_accepted';
    private const SESSION_TOKEN = 'before_login_token';
    private const TOKEN_FIELD = 'before_login_token';
    private const ACTION_FIELD = 'before_login_action';

    private ?string $lastError = null;

    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Julio Montoya, Chamilo contributors',
            [
                'option1' => 'wysiwyg',
                'option1_url' => 'text',
                'option2' => 'wysiwyg',
                'option2_url' => 'text',
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
        $info['supports_regions'] = true;
        $info['templates'] = ['template.tpl'];

        if (file_exists(__DIR__.'/custom.template.tpl')) {
            $info['templates'] = ['custom.template.tpl'];
        }

        $info['settings_form'] = $this->getSettingsForm();

        return $info;
    }

    public function renderRegion($region): string
    {
        if (!$this->isAllowedRegion((string) $region)) {
            return '';
        }

        return $this->renderGate('region');
    }

    public function renderGate(string $context = 'direct'): string
    {
        if (!$this->shouldDisplay()) {
            return '';
        }

        $this->handleSubmission();

        if ($this->isAccepted()) {
            return '';
        }

        return $this->renderPanel($context);
    }

    public function handleSubmission(): void
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
            return;
        }

        $action = (string) ($_POST[self::ACTION_FIELD] ?? '');

        if (!in_array($action, ['option1', 'option2'], true)) {
            return;
        }

        if (!$this->isValidToken((string) ($_POST[self::TOKEN_FIELD] ?? ''))) {
            $this->lastError = $this->get_lang('InvalidSecurityToken');

            return;
        }

        if ('option1' === $action) {
            if (empty($_POST['before_login_option1_confirm'])) {
                $this->lastError = $this->get_lang('PleaseConfirmBeforeContinue');

                return;
            }

            $_SESSION[self::SESSION_ACCEPTED] = 1;
            $this->redirectTo($this->getRedirectUrl('option1_url', false));
        }

        if ('option2' === $action) {
            if (empty($_POST['before_login_option2_confirm'])) {
                $this->lastError = $this->get_lang('PleaseConfirmBeforeContinue');

                return;
            }

            $this->redirectTo($this->getRedirectUrl('option2_url', false));
        }
    }

    public function renderStandalonePage(): string
    {
        if (!$this->isEnabled()) {
            api_not_allowed(true);
        }

        if (!api_is_anonymous()) {
            $this->redirectTo(api_get_path(WEB_PATH));
        }

        $content = $this->renderGate('direct');

        if ('' === trim($content)) {
            $content = $this->renderMessage($this->get_lang('BeforeLoginAlreadyAccepted'), 'info');
        }

        return $content;
    }

    public function isAccepted(): bool
    {
        return !empty($_SESSION[self::SESSION_ACCEPTED]);
    }

    private function shouldDisplay(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!api_is_anonymous()) {
            return false;
        }

        if ($this->isAccepted()) {
            return false;
        }

        if ('' === trim((string) $this->getSetting('option1'))) {
            return false;
        }

        return $this->isBeforeLoginPage();
    }

    private function isAllowedRegion(string $region): bool
    {
        return in_array($region, ['login_top', 'login_bottom', 'content_top', 'main_top'], true);
    }

    private function isBeforeLoginPage(): bool
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $phpSelf = (string) ($_SERVER['PHP_SELF'] ?? '');
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '');

        if (str_contains($requestPath, '/plugin-regions/login_top')
            || str_contains($requestPath, '/plugin-regions/login_bottom')
        ) {
            return true;
        }

        $routeContext = (string) ($_GET['_route'] ?? $_GET['route'] ?? '');

        if ('' !== $routeContext) {
            $routePath = (string) (parse_url($routeContext, PHP_URL_PATH) ?: $routeContext);

            if ('/' === $routePath
                || '' === $routePath
                || str_ends_with($routePath, '/index.php')
                || str_contains($routePath, '/login')
                || str_contains($routePath, '/main/auth/login')
            ) {
                return true;
            }
        }

        $paths = [$scriptName, $phpSelf, $requestPath];

        foreach ($paths as $path) {
            $path = str_replace('\\', '/', (string) $path);

            if ('' === $path || '/' === $path || str_ends_with($path, '/index.php')) {
                return true;
            }

            if (str_contains($path, '/main/auth/login')
                || str_contains($path, '/login')
                || str_contains($path, '/plugin/'.self::PLUGIN_NAME.'/index.php')
            ) {
                return true;
            }
        }

        return false;
    }


    private function renderPanel(string $context): string
    {
        $option1 = $this->cleanHtml((string) $this->getSetting('option1'));
        $option2 = $this->cleanHtml((string) $this->getSetting('option2'));

        $classes = 'direct' === $context
            ? 'mx-auto max-w-5xl'
            : 'fixed inset-0 z-[9999] overflow-y-auto bg-gray-90/75 p-4 md:p-8';

        $content = '<div class="'.$classes.'" data-before-login-gate="1">';
        $content .= '<div class="mx-auto max-w-5xl rounded-2xl border border-gray-20 bg-white p-6 shadow-xl">';
        $content .= '<div class="mb-6">';
        $content .= '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">'
            .$this->escape($this->get_lang('BeforeLogin')).'</p>';
        $content .= '<h1 class="mt-1 text-2xl font-bold text-gray-90">'
            .$this->escape($this->get_title()).'</h1>';
        $content .= '<p class="mt-2 text-sm text-gray-50">'
            .$this->escape($this->get_comment()).'</p>';
        $content .= '</div>';

        if (null !== $this->lastError) {
            $content .= $this->renderMessage($this->lastError, 'warning');
        }

        $content .= '<div class="grid gap-4 md:grid-cols-2">';
        $content .= $this->renderOptionCard('option1', $option1, $this->get_lang('ContinueToLogin'), true);

        if ('' !== trim(strip_tags($option2))) {
            $content .= $this->renderOptionCard('option2', $option2, $this->get_lang('ChooseAlternative'), false);
        }

        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }

    private function renderOptionCard(string $option, string $html, string $buttonLabel, bool $primary): string
    {
        $confirmName = 'option1' === $option
            ? 'before_login_option1_confirm'
            : 'before_login_option2_confirm';

        $buttonClass = $primary ? 'btn btn--primary' : 'btn btn--plain';
        $token = $this->getToken();

        $content = '<section class="rounded-xl border border-gray-20 bg-gray-10 p-4">';
        $content .= '<div class="prose max-w-none text-sm text-gray-80">'.$html.'</div>';
        $content .= '<form method="post" action="'.$this->escape($this->getFormActionUrl()).'" class="mt-4 space-y-4">';
        $content .= '<input type="hidden" name="'.self::ACTION_FIELD.'" value="'.$this->escape($option).'">';
        $content .= '<input type="hidden" name="'.self::TOKEN_FIELD.'" value="'.$this->escape($token).'">';
        $content .= '<label class="flex items-center gap-2 text-sm font-semibold text-gray-80">';
        $content .= '<input type="checkbox" name="'.$this->escape($confirmName).'" value="1" required>';
        $content .= '<span>'.$this->escape($this->get_lang('IConfirmThisChoice')).'</span>';
        $content .= '</label>';
        $content .= '<button type="submit" class="'.$this->escape($buttonClass).' inline-flex items-center gap-2">';
        $content .= '<span class="mdi mdi-check ch-tool-icon" aria-hidden="true"></span>';
        $content .= $this->escape($buttonLabel);
        $content .= '</button>';
        $content .= '</form>';
        $content .= '</section>';

        return $content;
    }

    private function renderMessage(string $message, string $type): string
    {
        $classes = 'warning' === $type
            ? 'mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800'
            : 'mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800';

        return '<div class="'.$classes.'">'.$this->escape($message).'</div>';
    }

    private function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_TOKEN])) {
            $_SESSION[self::SESSION_TOKEN] = bin2hex(random_bytes(16));
        }

        return (string) $_SESSION[self::SESSION_TOKEN];
    }

    private function isValidToken(string $token): bool
    {
        $expected = (string) ($_SESSION[self::SESSION_TOKEN] ?? '');

        return '' !== $expected && hash_equals($expected, $token);
    }

    private function getRedirectUrl(string $settingName, bool $fallbackToCurrent): string
    {
        $url = trim((string) $this->getSetting($settingName));
        $url = $this->sanitizeRedirectUrl($url);

        if ('' !== $url) {
            return $url;
        }

        return $fallbackToCurrent ? $this->getCurrentUrl() : api_get_path(WEB_PATH);
    }

    private function sanitizeRedirectUrl(string $url): string
    {
        if ('' === $url) {
            return '';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        if (false !== filter_var($url, FILTER_VALIDATE_URL)) {
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if (in_array($scheme, ['http', 'https'], true)) {
                return $url;
            }
        }

        return '';
    }

    private function redirectTo(string $url): void
    {
        $url = $this->sanitizeRedirectUrl($url) ?: api_get_path(WEB_PATH);

        if (!headers_sent()) {
            header('Location: '.$url);
            exit;
        }

        echo '<script>window.location.href = '.json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).';</script>';
        exit;
    }

    private function getFormActionUrl(): string
    {
        return api_get_path(WEB_PLUGIN_PATH).self::PLUGIN_NAME.'/index.php';
    }

    private function getCurrentUrl(): string
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

        if ('' === $requestUri) {
            return api_get_path(WEB_PATH);
        }

        return $requestUri;
    }

    private function getSetting(string $name): mixed
    {
        $value = $this->get($name);

        if (null !== $value) {
            return $value;
        }

        // Backward compatibility with the legacy lowercase plugin name.
        return api_get_plugin_setting('before_login', $name);
    }


    private function cleanHtml(string $html): string
    {
        $html = trim($html);

        if ('' === $html) {
            return '';
        }

        if (class_exists('Security') && method_exists('Security', 'remove_XSS')) {
            return Security::remove_XSS($html);
        }

        return strip_tags($html, '<p><br><ul><ol><li><strong><b><em><i><a><span><div>');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
