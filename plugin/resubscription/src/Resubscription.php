<?php
/* For licensing terms, see /license.txt */

/**
 * Limit session resubscriptions.
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 *
 * @package chamilo.plugin.resubscription
 */
class Resubscription extends Plugin implements HookPluginInterface
{
    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $options = [
            'calendar_year' => get_lang('CalendarYear'),
            'natural_year' => get_lang('NaturalYear'),
        ];
        $parameters = [
            'resubscription_limit' => [
                'type' => 'select',
                'options' => $options,
            ],
        ];
        parent::__construct('0.1', 'Imanol Losada Oriol', $parameters);
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return Resubscription
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin.
     */
    public function install()
    {
        $this->installHook();
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        $this->uninstallHook();
    }

    /**
     * Install the Resubscription hook.
     */
    public function installHook()
    {
        $hook = HookResubscription::create();
        HookResubscribe::create()->attach($hook);
    }

    /**
     * Uninstall the Resubscription hook.
     */
    public function uninstallHook()
    {
        $hook = HookResubscription::create();
        HookResubscribe::create()->detach($hook);
    }
}
