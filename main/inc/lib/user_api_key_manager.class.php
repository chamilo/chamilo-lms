<?php

/**
 * Manage user api keys
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class UserApiKeyManager
{

    /**
     * The name of the default service.
     * 
     * @return string 
     */
    public static function default_service()
    {
        return 'chamilo';
    }

    /**
     *  
     */
    public static function end_of_time()
    {
        $time = 2147483647; //mysql int max value
    }

    public static function get_by_id($id)
    {
        $table = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $table WHERE id=$id";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $result = Database::fetch_array($res, 'ASSOC');
        return $result;
    }

    /**
     *
     * @param int $duration in seconds
     * @param int $user_id
     * @param string $api_service
     * @param string $api_end_point
     * @return AccessToken 
     */
    public static function create_temp_token($api_service = null, $duration = 60, $user_id = null, $api_end_point = null)
    {
        $time = time();
        $validity_start_date = $time;
        $validity_end_date = $time + $duration;
        return self::create_token($user_id, $api_key = null, $api_service, $api_end_point, $validity_start_date, $validity_end_date);
    }

    /**
     *
     * @param int $user_id
     * @param string $api_key
     * @param string $api_service
     * @param string $api_end_point
     * @param int $validity_start_date
     * @param int $validity_end_date
     * @param string $description
     * @return AccessToken 
     */
    public static function create_token($user_id = null, $api_key = null, $api_service = null, $api_end_point = null, $validity_start_date = null, $validity_end_date = null, $description)
    {
        $time = time();
        $user_id = $user_id ? $user_id : Chamilo::user()->user_id();
        $api_key = $api_key ? $api_key : uniqid('', true);
        $api_service = $api_service ? $api_service : self::default_service();
        $api_end_point = $api_end_point ? $api_end_point : '';
        $validity_start_date = $validity_start_date ? $validity_start_date : $time;
        $validity_end_date = $validity_end_date ? $validity_end_date : self::end_of_time();
        $created_date = $time;

        $user_id = (int) $user_id;
        $api_key = Database::escape_string($api_key);
        $api_service = Database::escape_string($api_service);
        $api_end_point = Database::escape_string($api_end_point);
        $validity_start_date = date('Y-m-d H:i:s', $validity_start_date);
        $validity_end_date = date('Y-m-d H:i:s', $validity_end_date);
        $created_date = date('Y-m-d H:i:s', $created_date);

        $values = array();
        $values['user_id'] = $user_id;
        $values['api_key'] = $api_key;
        $values['api_service'] = $api_service;
        $values['api_end_point'] = $api_end_point;
        $values['validity_start_date'] = $validity_start_date;
        $values['validity_end_date'] = $validity_end_date;
        $values['created_date'] = $created_date;

        $table = Database::get_main_table(TABLE_MAIN_USER_API_KEY);

        $id = Database::insert($table, $values);
        return AccessToken::create($id, $user_id, $api_key);
    }

}