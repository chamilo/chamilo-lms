<?php
/* For licensing terms, see /license.txt */

/**
 * Define the CardGame class as an extension of Plugin to
 * install/uninstall the plugin.
 *
 * @author Damien Renou
 *
 * @package chamilo.plugin.card_game
 */
class CardGame extends Plugin
{
    /**
     * CardGame constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Damien Renou'
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        // 'pan' is the ID of the current background image/panel
        $sql = "CREATE TABLE IF NOT EXISTS plugin_card_game(
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            pan int NOT NULL,
            access_date DATE NOT NULL,
            parts VARCHAR(500) NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);
    }

    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS plugin_card_game";
        Database::query($sql);
    }
}
