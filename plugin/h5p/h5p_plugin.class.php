<?php
/* For licensing terms, see /license.txt */

class H5PPlugin extends Plugin
{
    public $table = 'plugin_mindmap';

    protected function __construct()
    {
        parent::__construct(
            '1.0', 'Damien Renou - Batisseurs Numériques',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return H5PPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the structure necessary for the plugin.
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_h5p(
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT,
            node_type VARCHAR(155),
            url_id INT,
            title VARCHAR(255) NOT NULL,
            descript VARCHAR(255) NOT NULL,
            creation_date VARCHAR(12) NOT NULL,
            terms_a VARCHAR(512) NOT NULL,
            terms_b VARCHAR(512) NOT NULL,
            terms_c VARCHAR(512) NOT NULL,
            terms_d VARCHAR(512) NOT NULL,
            terms_e VARCHAR(512) NOT NULL,
            terms_f VARCHAR(512) NOT NULL,
            opt_1 VARCHAR(512) NOT NULL,
            opt_2 VARCHAR(512) NOT NULL,
            opt_3 VARCHAR(512) NOT NULL,
            PRIMARY KEY (id));";
        Database::query($sql);
    }

    /**
     * Uninstall the plugin structure.
     */
    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS plugin_h5p";
        Database::query($sql);
    }
}
