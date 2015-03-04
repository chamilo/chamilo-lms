<?php
/* For licensing terms, see /license.txt */

/**
 * Create a user in Drupal website when a user is registered in Chamilo LMS
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.createDrupalUser
 */
class CreateDrupalUser extends Plugin implements HookPluginInterface
{

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $parameters = array(
            'drupal_domain' => 'text'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return CreateDrupalUser
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        $this->installHook();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->uninstallHook();
    }

    /**
     * Install the Create User hook
     */
    public function installHook()
    {
        $hook = HookCreateDrupalUser::create();
        HookCreateUser::create()->attach($hook);
    }

    /**
     * Uninstall the Create User hook
     */
    public function uninstallHook()
    {
        $hook = HookCreateDrupalUser::create();
        HookCreateUser::create()->detach($hook);
    }

}
