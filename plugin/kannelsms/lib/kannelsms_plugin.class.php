<?php
/* For licensing terms, see /vendor/license.txt */

/**
 * Class KannelsmsPlugin
 * This script contains SMS type constants and basic plugin functions.
 *
 * @package chamilo.plugin.kannelsms.lib
 *
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 * @author Julio Montoya Refactor code
 */
class KannelsmsPlugin extends SmsPlugin
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $fields = [
            'tool_enable' => 'boolean',
            'hostAddress' => 'text',
            'username' => 'text',
            'password' => 'text',
            'from' => 'text',
        ];
        $smsTypeOptions = $this->getSmsTypeOptions();
        foreach ($smsTypeOptions as $smsTypeOption) {
            $fields[$smsTypeOption] = 'checkbox';
        }
        parent::__construct('0.1', 'Imanol Losada', $fields);
    }

    /**
     * create (a singleton function that ensures KannelsmsPlugin instance is
     * created only once. If it is already created, it returns the instance).
     *
     * @return object KannelsmsPlugin instance
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * install (uninstalls the plugin and removes all plugin's tables and/or rows).
     */
    public function uninstall()
    {
        $tSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql = "DELETE FROM $tSettings WHERE subkey = 'kannelsms'";
        Database::query($sql);
    }
}
