<?php

/**
 * Migrate the datatabase. Adds needed fields by Shibboleth to the User table.
 * Upgrade is checked at each user login so there is no need to manually run
 * an upgrade.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethUpgrade
{

    /**
     * Create additional fields required by the shibboleth plugin if those
     * are missing.
     */
    public static function update()
    {
        static $done = false;
        if ($done)
        {
            return false;
        }
        $done = true;
        self::create_shibb_unique_id_field_if_missing();
        self::create_shibb_persistent_id_field_if_missing();
    }

    /**
     * Creates the 'shibb_unique_id' field in the table 'user' of the main Chamilo database if it doesn't exist yet
     *
     * @author Nicolas Rod
     * @return void
     */
    public static function create_shibb_unique_id_field_if_missing()
    {
        $db_name = Database :: get_main_database();

        $sql = "SELECT * FROM `$db_name`.`user` LIMIT 1";
        $result = Database::query($sql);
        $row = mysql_fetch_assoc($result);

        $exists = array_key_exists('shibb_unique_id', $row);
        if ($exists)
        {
            return false;
        }

        //create the 'shibb_unique_id' field
        $sql = "ALTER TABLE `$db_name`.`user` ADD `shibb_unique_id` VARCHAR( 60 ) AFTER `auth_source`";
        $result_alter = Database::query($sql);

        /*
         *  Index cannot be a UNIQUE index as it may exist users which don't log in through Shibboleth
         *  and therefore don't have any value for 'shibb_unique_id'
         */
        $sql = "ALTER TABLE `$db_name`.`user` ADD INDEX ( `shibb_unique_id` )";
        $result_alter = Database::query($sql);
    }

    public static function create_shibb_persistent_id_field_if_missing()
    {        
        $db_name = Database :: get_main_database();

        $sql = "SELECT * FROM $db_name.user LIMIT 1";
        $result = Database::query($sql);
        $row = mysql_fetch_assoc($result);
        $exists = array_key_exists('shibb_persistent_id', $row);

        if ($exists)
        {
            return false;
        }

        $sql = "ALTER table $db_name.user ADD COLUMN shibb_persistent_id varchar(255) NULL DEFAULT NULL;";
        $result = api_sql_query($sql);
        return (bool) $result;
    }

}