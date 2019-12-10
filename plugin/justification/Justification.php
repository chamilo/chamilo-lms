<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

class Justification extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return $this
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function getList()
    {
        $sql = 'SELECT * FROM justification_document ';
        $query = Database::query($sql);

        return Database::store_result($query, 'ASSOC');
    }

    /**
     * Install
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS justification_document (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            code TEXT NULL,
            name TEXT NULL,
            validity_duration INT,
            comment TEXT NULL,
            date_manual_on INT
        )";
        Database::query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS justification_document_rel_users (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            justification_document_id INT NOT NULL,
            file_path VARCHAR(255),
            user_id INT,
            date_validity DATE
        )";
        Database::query($sql);
    }

    public function uninstall()
    {
        $sql = 'DROP TABLE IF EXISTS justification_document';
        Database::query($sql);

        $sql = 'DROP TABLE IF EXISTS justification_document_rel_users';
        Database::query($sql);
    }
}
