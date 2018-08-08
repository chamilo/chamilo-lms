<?php
/* For licensing terms, see /license.txt */

/**
 * Class DictionaryPlugin.
 */
class DictionaryPlugin extends Plugin
{
    /**
     * DictionaryPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Julio Montoya',
            [
                'enable_plugin_dictionary' => 'boolean',
            ]
        );
    }

    /**
     * @return DictionaryPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Installation process.
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_dictionary (
                id INT NOT NULL AUTO_INCREMENT,
                term VARCHAR(255) NOT NULL,
                definition LONGTEXT NOT NULL,
                PRIMARY KEY (id));
        ";
        Database::query($sql);
    }

    /**
     * Uninstall process.
     */
    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS plugin_dictionary";
        Database::query($sql);
    }
}
