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
}
