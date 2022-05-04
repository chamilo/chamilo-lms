<?php
/* For licensing terms, see /license.txt */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Class MigrationMoodlePlugin.
 */
class MigrationMoodlePlugin extends Plugin implements HookPluginInterface
{
    public const SETTING_USER_FILTER = 'user_filter';
    public const SETTING_URL_ID = 'url_id';
    public const SETTING_MOODLE_PATH = 'moodle_path';

    public $isAdminPlugin = true;

    /**
     * MigrationMoodlePlugin constructor.
     */
    protected function __construct()
    {
        $version = '0.0.1';
        $author = 'Angel Fernando Quiroz Campos';
        $settings = [
            'active' => 'boolean',
            'db_host' => 'text',
            'db_user' => 'text',
            'db_password' => 'text',
            'db_name' => 'text',
            self::SETTING_USER_FILTER => 'text',
            self::SETTING_URL_ID => 'text',
            self::SETTING_MOODLE_PATH => 'text',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * @return MigrationMoodlePlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return Connection
     */
    public function getConnection()
    {
        $params = [
            'host' => $this->get('db_host'),
            'user' => $this->get('db_user'),
            'password' => $this->get('db_password'),
            'dbname' => $this->get('db_name'),
            'driver' => 'pdo_mysql',
        ];

        $connection = DriverManager::getConnection($params, new Configuration());

        return $connection;
    }

    /**
     * Perform actions after configure the plugin.
     *
     * Add user extra field.
     *
     * @throws Exception
     *
     * @return MigrationMoodlePlugin
     */
    public function performActionsAfterConfigure()
    {
        if ('true' === $this->get('active')) {
            $this->installHook();
        } else {
            $this->uninstallHook();
        }

        return $this;
    }

    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin.
     *
     * @throws Exception
     *
     * @return void
     */
    public function installHook()
    {
        $hookObserver = MigrationMoodleCheckLoginCredentialsHook::create();

        CheckLoginCredentialsHook::create()->attach($hookObserver);
    }

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin.
     *
     * @throws Exception
     *
     * @return void
     */
    public function uninstallHook()
    {
        $hookObserver = MigrationMoodleCheckLoginCredentialsHook::create();

        CheckLoginCredentialsHook::create()->detach($hookObserver);
    }

    /**
     * @return string
     */
    public function getUserFilterSetting()
    {
        $userFilter = $this->get(self::SETTING_USER_FILTER);

        return trim($userFilter);
    }

    /**
     * @return int
     */
    public function getAccessUrlId()
    {
        $urlId = (int) $this->get(self::SETTING_URL_ID);

        return $urlId ?: 1;
    }

    /**
     * @return string
     */
    public function getMoodledataPath()
    {
        $path = $this->get(self::SETTING_MOODLE_PATH);

        return rtrim($path, ' /');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isTaskDone($name)
    {
        $result = Database::select(
            'COUNT(1) c',
            'plugin_migrationmoodle_task',
            [
                'where' => [
                    'name = ?' => Database::escape_string($name.'_task'),
                    'or name = ?' => Database::escape_string($name.'_script'),
                ],
            ],
            'first'
        );

        return $result['c'] > 0;
    }
}
