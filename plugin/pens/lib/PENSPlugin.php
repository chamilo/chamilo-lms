<?php
/* For licensing terms, see /license.txt */

/**
 * Class PENSPlugin
 * This class is used to add an advanced subscription allowing the admin to
 * create user queues requesting a subscribe to a session
 * @package chamilo.plugin.pens
 */
class PENSPlugin extends Plugin implements HookPluginInterface
{
    protected $strings;
    private $errorMessages;
    const TABLE_PENS = 'plugin_pens';


    /**
     * Constructor
     */
    public function __construct()
    {
        $parameters = array(
        );

        parent::__construct($this->get_version(), $this->get_author(), $parameters);

        $this->errorMessages = array();
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return AdvancedSubscriptionPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     * @return void
     */
    public function install()
    {
        $this->installDatabase();
        $this->installHook();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $setting = api_get_setting('status', 'pens');
        if ($setting === 'installed') {
            $this->uninstallHook();
            // Note: Keeping area field data is intended so it will not be removed
            $this->uninstallDatabase();
        }
    }

    /**
     * Create the database tables for the plugin
     * @return void
     */
    private function installDatabase()
    {
        $pensTable = Database::get_main_table(PENSPlugin::TABLE_PENS);

        $sql = "CREATE TABLE $pensTable (
	        id int unsigned NOT NULL auto_increment,
	        pens_version varchar(255) NOT NULL,
	        package_type varchar(255) NOT NULL,
	        package_type_version varchar(255) NOT NULL,
        	package_format varchar(255) NOT NULL,
	        package_id varchar (255) NOT NULL,
	        client varchar(255) NOT NULL,
	        vendor_data text,
	        package_name varchar(255) NOT NULL,
	        created_at datetime NOT NULL,
	        updated_at datetime NULL,
	        PRIMARY KEY (id),
	        UNIQUE KEY package_id (package_id)
	        )";
        Database::query($sql);
    }

    /**
     * Drop the database tables for the plugin
     * @return void
     */
    private function uninstallDatabase()
    {
        /* Drop plugin tables */
        $pensTable = Database::get_main_table(PENSPlugin::TABLE_PENS);

        $sql = "DROP TABLE IF EXISTS $pensTable; ";
        Database::query($sql);

        /* Delete settings */
        $settingsTable = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        Database::query("DELETE FROM $settingsTable WHERE subkey = 'plugin_pens'");
    }

    /**
     * Get the error messages list
     * @return array The message list
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * This method will call the Hook management insertHook to add Hook observer from this plugin
     * @return int
     */
    public function installHook()
    {
    }

    /**
     * This method will call the Hook management deleteHook to disable Hook observer from this plugin
     * @return int
     */
    public function uninstallHook()
    {
    }

    /**
     * Copied and fixed from plugin.class.php
     * Returns the "system" name of the plugin in lowercase letters
     * @return string
     */
    public function get_name()
    {
        return 'PENS';
    }

    /**
     * Get author(s)
     * @return string
     */
    public function get_author()
    {
        return 'Guillaume Viguier-Just, Yannick Warnier';
    }

    /**
     * Returns the plugin version
     * @return string
     */
    public function get_version()
    {
        return '1.1';
    }
    /**
     * Get generic plugin info
     * @return array
     */
    public function get_info()
    {
        $result = array();
        $result['title'] = $this->get_name();
        $result['comment'] = 'Provides support for the PENS course exchange standard. Read the readme.txt file in the plugin/pens/ folder for a complete installation.';
        $result['version'] = $this->get_version();
        $result['author'] = $this->get_author();
        $result['plugin_class'] = get_class($this);
        $result['is_course_plugin'] = false;
        $result['is_mail_plugin'] = false;
        return $result;
    }
}
