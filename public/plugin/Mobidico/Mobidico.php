<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class Mobidico extends Plugin
{
    public $isCoursePlugin = true;

    public $addCourseTool = true;

    public $course_settings = [];

    protected function __construct()
    {
        parent::__construct(
            '0.2',
            'Julio Montoya',
            [
                'mobidico_url' => 'text',
                'api_key' => 'text',
                'request_timeout' => 'text',
                'verify_ssl' => 'boolean',
            ]
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install(): void
    {
        $this->syncCourseTools();
    }

    public function uninstall(): void
    {
        $this->removeInvalidCourseToolShortcuts();
        $this->uninstall_course_fields_in_all_courses();
    }

    public function performActionsAfterConfigure()
    {
        if ($this->isEnabled()) {
            $this->syncCourseTools();
        }

        return $this;
    }

    public function syncCourseTools(): void
    {
        try {
            /*
             * Rebuild the Mobidico course-tool rows instead of only adding missing rows.
             * Some older plugin iterations could leave a c_tool row without a valid
             * resource link, which makes it invisible in the Chamilo 2 course home.
             * Reinstalling the plugin course fields through the base Plugin API keeps
             * the same pattern used by other legacy course plugins.
             */
            $this->removeInvalidCourseToolShortcuts();
            $this->uninstall_course_fields_in_all_courses();
            $this->install_course_fields_in_all_courses(true);
        } catch (Throwable $exception) {
            error_log('[Mobidico] Course tool synchronization failed: '.$exception->getMessage());
        }
    }

    public function getLaunchUrl(int $userId): ?string
    {
        $baseUrl = $this->getBaseUrl();
        $apiKey = $this->getApiKey();

        if ('' === $baseUrl || '' === $apiKey || 0 >= $userId) {
            return null;
        }

        $session = $this->requestRemoteSession($baseUrl, $apiKey, $userId);

        if (null === $session) {
            return null;
        }

        return $baseUrl.'/app/index.html?session='.rawurlencode($session);
    }

    public function getBaseUrl(): string
    {
        $url = trim((string) $this->get('mobidico_url'));
        $url = rtrim($url, "/ \t\n\r\0\x0B");

        if ('' === $url) {
            return '';
        }

        $parts = parse_url($url);

        if (
            false === $parts
            || empty($parts['scheme'])
            || empty($parts['host'])
            || !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
        ) {
            return '';
        }

        return $url;
    }

    public function getApiKey(): string
    {
        return trim((string) $this->get('api_key'));
    }

    public function getRequestTimeout(): float
    {
        $timeout = (float) $this->get('request_timeout');

        if (0.0 >= $timeout) {
            return 5.0;
        }

        return min($timeout, 30.0);
    }

    public function shouldVerifySsl(): bool
    {
        $value = $this->get('verify_ssl');

        if (null === $value || '' === $value) {
            return true;
        }

        return in_array($value, ['1', 1, true, 'true', 'yes', 'on'], true);
    }

    private function removeInvalidCourseToolShortcuts(): void
    {
        try {
            $connection = Database::getManager()->getConnection();

            /*
             * Older development builds wrongly created CShortcut rows pointing to the
             * Mobidico CTool resource node. Those rows render as /r/course_tool/links/{id}/link
             * and fail because course-tool resources are not CLink resources.
             *
             * Delete by relation, not only by title, because some rows can have a
             * translated or suffixed title while still pointing to the Mobidico CTool.
             */
            $deletedRows = $connection->executeStatement(
                <<<'SQL'
DELETE s
FROM c_shortcut s
LEFT JOIN c_tool ct ON ct.resource_node_id = s.shortcut_node_id
LEFT JOIN tool t ON t.id = ct.tool_id
WHERE LOWER(TRIM(s.title)) LIKE 'mobidico%'
   OR LOWER(TRIM(ct.title)) LIKE 'mobidico%'
   OR LOWER(TRIM(t.title)) = 'mobidico'
SQL
            );

            if ($deletedRows > 0) {
                error_log('[Mobidico] Removed '.$deletedRows.' invalid course-tool shortcut(s).');
            }
        } catch (Throwable $exception) {
            error_log('[Mobidico] Invalid shortcut cleanup failed: '.$exception->getMessage());
        }
    }

    private function requestRemoteSession(string $baseUrl, string $apiKey, int $userId): ?string
    {
        $authenticateUrl = $baseUrl.'/app/desktop/php/authenticate.php';

        try {
            $client = HttpClient::create([
                'timeout' => $this->getRequestTimeout(),
                'verify_peer' => $this->shouldVerifySsl(),
                'verify_host' => $this->shouldVerifySsl(),
            ]);

            $response = $client->request('POST', $authenticateUrl, [
                'body' => [
                    'chamiloid' => $userId,
                    'API_KEY' => $apiKey,
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                error_log('[Mobidico] Authentication failed with HTTP status '.$response->getStatusCode());

                return null;
            }

            $payload = json_decode($response->getContent(false), true);

            if (!is_array($payload)) {
                error_log('[Mobidico] Authentication response is not valid JSON.');

                return null;
            }

            if ('OK' !== ($payload['status'] ?? null)) {
                error_log('[Mobidico] Authentication response status is not OK.');

                return null;
            }

            $session = trim((string) ($payload['session'] ?? ''));

            if ('' === $session) {
                error_log('[Mobidico] Authentication response does not contain a session token.');

                return null;
            }

            return $session;
        } catch (ExceptionInterface $exception) {
            error_log('[Mobidico] Authentication request failed: '.$exception->getMessage());

            return null;
        } catch (Throwable $exception) {
            error_log('[Mobidico] Unexpected authentication error: '.$exception->getMessage());

            return null;
        }
    }
}
