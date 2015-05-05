<?php
/* For licensing terms, see /license.txt */

/**
 * Create Skype user field
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 * @package chamilo.plugin.skype
 */
class Skype extends Plugin implements HookPluginInterface
{

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct('0.1', 'Imanol Losada Oriol');
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return Skype
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
        $result = Database::select(
            'field_variable',
            Database::get_main_table(TABLE_MAIN_USER_FIELD),
            array(
                'where'=> array(
                    'field_variable = ?' => array(
                        'skype'
                    )
                )
            )
        );
        if (empty($result)) {
            require_once api_get_path(LIBRARY_PATH).'extra_field.lib.php';
            $extraField = new Extrafield('user');
            $extraField->save(array(
                'field_type' => UserManager::USER_FIELD_TYPE_TEXT,
                'field_variable' => 'skype',
                'field_display_text' => 'Skype',
                'field_visible' => 1,
                'field_changeable' => 1
            ));
        }
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
     * Install the Skype hook
     */
    public function installHook()
    {
        $hook = HookObserverSkype::create();
        HookEventSkype::create()->attach($hook);
    }

    /**
     * Uninstall the Skype hook
     */
    public function uninstallHook()
    {
        $hook = HookObserverSkype::create();
        HookEventSkype::create()->detach($hook);
    }

}
