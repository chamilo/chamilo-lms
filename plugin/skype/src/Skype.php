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
            'variable',
            Database::get_main_table(TABLE_EXTRA_FIELD),
            array(
                'where'=> array(
                    'variable = ?' => array(
                        'skype'
                    )
                )
            )
        );

        if (empty($result)) {
            $extraField = new ExtraField('user');
            $extraField->save(array(
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => 'skype',
                'display_text' => 'Skype',
                'visible' => 1,
                'changeable' => 1
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
