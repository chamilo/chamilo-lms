<?php
/* For licensing terms, see /license.txt */

/**
 * Config the plugin
 * @author Enrique Alcaraz Lopez
 * @package chamilo.plugin.redirection
 */
class RedirectionPlugin extends Plugin
{
    public $isAdminPlugin = true;

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $version = '1.0';
        $author = 'Enrique Alcaraz, Julio Montoya';
        parent::__construct($version, $author, ['enabled' => 'boolean']);
        $this->isAdminPlugin = true;
    }

    /**
     * @return RedirectionPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * @param int $userId
     * @param string $url
     * @return false|string
     */
    public static function insert($userId, $url)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return false;
        }

        $sql = "DELETE FROM plugin_redirection WHERE user_id = $userId";
        Database::query($sql);

        $userInfo = api_get_user_info($userId);

        if (empty($userInfo)) {
            return false;
        }

        return Database::insert(
            'plugin_redirection',
            [
                'user_id' => $userId,
                'url' => $url,
            ]
        );
    }

    /**
     * @param $userId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getUrlFromUser($userId)
    {
        $userId = (int) $userId;
        $userInfo = api_get_user_info($userId);
        if (empty($userInfo)) {
            return false;
        }
        $sql = "SELECT * FROM plugin_redirection WHERE user_id = $userId LIMIT 1";
        $result = Database::query($sql);
        return Database::fetch_array($result, 'ASSOC');
    }

    /**
     * @param int $id
     */
    public static function delete($id)
    {
        $table = Database::get_main_table('plugin_redirection');
        Database::delete(
            $table,
            array('id = ?' => array($id))
        );
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        $table = Database::get_main_table('plugin_redirection');

        return Database::select('*', $table);
    }

    public static function install()
    {
        $table = Database::get_main_table('plugin_redirection');

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            user_id INT unsigned NOT NULL DEFAULT 0,
            url VARCHAR(255) NOT NULL DEFAULT ''
        )";

        Database::query($sql);
    }

    public static function uninstall()
    {
        $table = Database::get_main_table('plugin_redirection');
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);
    }
}
