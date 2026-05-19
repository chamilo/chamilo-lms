<?php

/* For licensing terms, see /license.txt */

/**
 * Redirection plugin.
 *
 * Redirects specific users to an administrator-defined URL after login.
 */
class RedirectionPlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.4',
            'Enrique Alcaraz, Julio Montoya',
            []
        );

        $this->isAdminPlugin = true;
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }

    /**
     * Inserts or replaces the redirection for one user.
     */
    public static function insert(int $userId, string $url)
    {
        $userId = (int) $userId;
        $url = trim($url);

        if (empty($userId) || empty($url)) {
            return false;
        }

        if (!self::isAllowedRedirectUrl($url)) {
            return false;
        }

        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            return false;
        }

        $table = Database::get_main_table('plugin_redirection');

        Database::delete(
            $table,
            ['user_id = ?' => [$userId]]
        );

        return Database::insert(
            $table,
            [
                'user_id' => $userId,
                'url' => $url,
            ]
        );
    }

    /**
     * Gets the current redirection for a given user.
     *
     * @return array|false
     */
    public static function getUrlFromUser(int $userId)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return false;
        }

        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            return false;
        }

        $table = Database::get_main_table('plugin_redirection');

        $result = Database::select(
            '*',
            $table,
            [
                'where' => [
                    'user_id = ?' => [$userId],
                ],
                'limit' => 1,
            ]
        );

        if (empty($result)) {
            return false;
        }

        return $result[0];
    }

    public static function getRedirectUrlForUser(int $userId): ?string
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return null;
        }

        $table = Database::get_main_table('plugin_redirection');

        $result = Database::select(
            'url',
            $table,
            [
                'where' => [
                    'user_id = ?' => [$userId],
                ],
                'limit' => 1,
            ]
        );

        if (empty($result) || empty($result[0]['url'])) {
            return null;
        }

        $url = trim((string) $result[0]['url']);

        if (!self::isAllowedRedirectUrl($url)) {
            return null;
        }

        return $url;
    }

    public static function deleteUserRedirection(int $userId): void
    {
        $table = Database::get_main_table('plugin_redirection');

        Database::delete(
            $table,
            ['user_id = ?' => [(int) $userId]]
        );
    }

    public static function delete(int $id): void
    {
        $table = Database::get_main_table('plugin_redirection');

        Database::delete(
            $table,
            ['id = ?' => [(int) $id]]
        );
    }

    public static function getAll(): array
    {
        $table = Database::get_main_table('plugin_redirection');

        return Database::select(
            '*',
            $table,
            [
                'order' => 'id DESC',
            ]
        );
    }

    public function install(): void
    {
        $table = Database::get_main_table('plugin_redirection');

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            user_id INT unsigned NOT NULL DEFAULT 0,
            url VARCHAR(2048) NOT NULL DEFAULT '',
            UNIQUE KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        Database::query($sql);
    }

    public function uninstall(): void
    {
        $table = Database::get_main_table('plugin_redirection');

        Database::query("DROP TABLE IF EXISTS $table");
    }

    public static function isAllowedRedirectUrl(string $url): bool
    {
        $url = trim($url);

        if ('' === $url) {
            return false;
        }

        if (str_contains($url, "\r") || str_contains($url, "\n")) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return false !== filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function redirectUser(int $userId): void
    {
        $record = self::getUrlFromUser($userId);

        if (empty($record) || empty($record['url'])) {
            return;
        }

        $url = (string) $record['url'];

        if (!self::isAllowedRedirectUrl($url)) {
            return;
        }

        header('Location: '.$url);
        exit;
    }
}
