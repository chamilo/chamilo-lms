<?php
/* For licensing terms, see /license.txt */

/**
 * Create Skype user field
 *
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 * @package chamilo.plugin.skype
 */
class Skype extends Plugin
{

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct(
            '0.2',
            'Imanol Losada Oriol',
            [$this->get_lang('ReadTheReadmeFile') => 'html']
        );
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
}
