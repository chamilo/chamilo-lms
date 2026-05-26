<?php

/* For licensing terms, see /license.txt. */

class PENSPlugin extends Plugin
{
    protected $strings = [];
    private array $errorMessages = [];

    public const TABLE_PENS = 'plugin_pens';

    public function __construct()
    {
        parent::__construct('1.1', 'Guillaume Viguier-Just, Yannick Warnier');

        $this->loadPluginStrings();
    }

    public function get_name(): string
    {
        return 'Pens';
    }

    /**
     * @return PENSPlugin
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

    private function installDatabase(): void
    {
        $pensTable = Database::get_main_table(self::TABLE_PENS);

        $sql = "CREATE TABLE IF NOT EXISTS $pensTable (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    pens_version VARCHAR(255) NOT NULL,
                    package_type VARCHAR(255) NOT NULL,
                    package_type_version VARCHAR(255) NOT NULL,
                    package_format VARCHAR(255) NOT NULL,
                    package_id VARCHAR(255) NOT NULL,
                    client VARCHAR(255) NOT NULL,
                    vendor_data TEXT DEFAULT NULL,
                    package_name VARCHAR(255) NOT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY package_id (package_id)
                )";

        Database::query($sql);
    }

    private function uninstallDatabase(): void
    {
        $pensTable = Database::get_main_table(self::TABLE_PENS);
        Database::query("DROP TABLE IF EXISTS $pensTable");
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    public function get_author(): string
    {
        return 'Guillaume Viguier-Just, Yannick Warnier';
    }

    public function get_version(): string
    {
        return '1.1';
    }

    public function get_info(): array
    {
        $result = parent::get_info();
        $result['title'] = $this->get_lang('plugin_title');
        $result['comment'] = $this->get_lang('plugin_comment');
        $result['is_course_plugin'] = false;
        $result['is_mail_plugin'] = false;

        return $result;
    }

    private function loadPluginStrings(): void
    {
        $basePath = dirname(__DIR__).'/lang/';
        $englishFile = $basePath.'english.php';

        $this->strings = [];

        if (is_readable($englishFile)) {
            $strings = [];
            require $englishFile;
            if (isset($strings) && is_array($strings)) {
                $this->strings = $strings;
            }
        }

        $interfaceLanguage = api_get_language_isocode();
        if (empty($interfaceLanguage) || 'english' === $interfaceLanguage) {
            return;
        }

        $languageFile = $basePath.$interfaceLanguage.'.php';
        if (!is_readable($languageFile)) {
            return;
        }

        $strings = [];
        require $languageFile;

        if (isset($strings) && is_array($strings)) {
            $this->strings = array_merge($this->strings, $strings);
        }
    }
}
