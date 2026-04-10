<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

class teachdoc_hub extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '2.0.0',
            'Damien Renou',
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_oel_tools_teachdoc(
            id INT NOT NULL AUTO_INCREMENT,
            id_user INT,
            title VARCHAR(255) NOT NULL,
            id_parent INT,
            order_lst INT,
            type_node INT,
            type_base INT,
            colors VARCHAR(25) NOT NULL,
            quizztheme VARCHAR(25) NOT NULL,
            id_url INT,
            lp_id INT,
            behavior TINYINT NOT NULL DEFAULT '0',
            leveldoc TINYINT NOT NULL DEFAULT '0',
            local_folder VARCHAR(60) NOT NULL,
            date_create VARCHAR(12) NOT NULL,
            base_html LONGTEXT NOT NULL,
            base_css LONGTEXT NOT NULL,
            gpscomps LONGTEXT NOT NULL,
            gpsstyle LONGTEXT NOT NULL,
            recent_save TINYINT NOT NULL DEFAULT '0',
            options VARCHAR(1080) NOT NULL,
            PRIMARY KEY (id));";
        Database::query($sql);

        $sqlToken = 'CREATE TABLE IF NOT EXISTS plugin_oel_tools_token(
            id INT NOT NULL AUTO_INCREMENT,
            id_user INT,
            token VARCHAR(50),
            PRIMARY KEY (id));';
        Database::query($sqlToken);
    }

    public function uninstall(): void
    {
        // $sql = "DROP TABLE IF EXISTS plugin_oel_tools_teachdoc";
        // Database::query($sql);
    }

    public function get_name(): string
    {
        return 'CStudio';
    }
}
