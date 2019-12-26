<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Hook\HookResubscribe;
use Chamilo\CoreBundle\Hook\Interfaces\HookPluginInterface;

/**
 * Limit session resubscriptions.
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 */
class Resubscription extends Plugin implements HookPluginInterface
{
    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $options = [
            'calendar_year' => get_lang('Calendar year'),
            'natural_year' => get_lang('Natural year'),
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

        return $result ?: $result = new self();
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
        $hookObserver = HookResubscription::create();

        Container::instantiateHook(HookResubscribe::class)->attach($hookObserver);
    }

    /**
     * Uninstall the Resubscription hook.
     */
    public function uninstallHook()
    {
        $hookObserver = HookResubscription::create();

        Container::instantiateHook(HookResubscribe::class)->detach($hookObserver);
    }
}
