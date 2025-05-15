<?php

/* For licensing terms, see /license.txt */

/**
 * Limit session resubscriptions.
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 */
class Resubscription extends Plugin
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
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
    }

    public function get_name()
    {
        return 'Resubscription';
    }
}
