<?php

/* For licensing terms, see /license.txt */

/**
 * The Tour class allows a guided tour of the interface.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class Tour extends Plugin
{
    protected function __construct()
    {
        $parameters = [
            'show_tour' => 'boolean',
            'theme' => 'text',
        ];

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * @return Tour
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install()
    {
        $this->installDatabase();
    }

    public function uninstall()
    {
        $this->uninstallDatabase();
    }

    /**
     * Check whether the tour should still be displayed to the user.
     */
    public function checkTourForUser(string $pageName, int $userId): bool
    {
        if ('' === trim($pageName) || $userId <= 0) {
            return false;
        }

        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $result = Database::select(
            'COUNT(id) AS qty',
            $pluginTourLogTable,
            [
                'where' => [
                    'page_class = ? AND user_id = ?' => [$pageName, $userId],
                ],
            ],
            'first'
        );

        if (false !== $result && !empty($result['qty'])) {
            return 0 === (int) $result['qty'];
        }

        return true;
    }

    /**
     * Save a completed tour only once per user/page.
     */
    public function saveCompletedTour(string $pageName, int $userId): bool
    {
        if ('' === trim($pageName) || $userId <= 0) {
            return false;
        }

        if (!$this->checkTourForUser($pageName, $userId)) {
            return true;
        }

        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $result = Database::insert(
            $pluginTourLogTable,
            [
                'page_class' => $pageName,
                'user_id' => $userId,
                'visualization_datetime' => api_get_utc_datetime(),
            ]
        );

        return false !== $result;
    }

    /**
     * Get all configured tours.
     */
    public function getTourConfig(): array
    {
        $pluginPath = api_get_path(SYS_PLUGIN_PATH).'Tour/';
        $configFile = $pluginPath.'config/tour.json';

        if (!is_file($configFile) || !is_readable($configFile)) {
            return [];
        }

        $jsonContent = file_get_contents($configFile);
        if (false === $jsonContent || '' === trim($jsonContent)) {
            return [];
        }

        $data = json_decode($jsonContent, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Return one configured tour by its logical page name.
     */
    public function getTourByName(string $pageName): ?array
    {
        $config = $this->getTourConfig();

        foreach ($config as $pageContent) {
            if (!is_array($pageContent)) {
                continue;
            }

            if (($pageContent['name'] ?? '') === $pageName) {
                return $pageContent;
            }
        }

        return null;
    }

    /**
     * Return one configured tour by its page selector.
     * Kept for backward compatibility.
     */
    public function getTourByPageClass(string $pageClass): ?array
    {
        $config = $this->getTourConfig();

        foreach ($config as $pageContent) {
            if (!is_array($pageContent)) {
                continue;
            }

            if (($pageContent['pageClass'] ?? '') === $pageClass) {
                return $pageContent;
            }
        }

        return null;
    }

    private function installDatabase(): void
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "CREATE TABLE IF NOT EXISTS $pluginTourLogTable (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            page_class VARCHAR(255) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            visualization_datetime DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX idx_tour_log_page_user (page_class, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        Database::query($sql);
    }

    private function uninstallDatabase(): void
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "DROP TABLE IF EXISTS $pluginTourLogTable";

        Database::query($sql);
    }
}
