<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of the Accessurleditsessiontourl class.
 */
require_once 'xajax/xajax.inc.php';

/**
 * Accessurleditsessiontourl class
 * Contains several functions dealing with displaying,
 * editing,... of a Access_url_edit_session_to_url_functions.
 *
 * @version 1.0
 *
 * @author Toon Keppens <toon@vi-host.net>
 * @author Julio Montoya - Cleaning code
 * @author Ricardo Rodriguez - Separated the function and code
 */
class Accessurleditsessionstourl
{
    /**
     * Search sessions by name, based on a search string.
     *
     * @param string Search string
     * @param int Deprecated param
     *
     * @return string Xajax response block
     * @assert () === false
     */
    public function search_sessions($needle, $id)
    {
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $xajax_response = new xajaxResponse();
        $return = '';

        if (!empty($needle)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);
            // search sessiones where username or firstname or lastname begins likes $needle
            $sql = 'SELECT id, name FROM '.$tbl_session.' u
                    WHERE (name LIKE "'.$needle.'%")
                    ORDER BY name, id
                    LIMIT 11';
            $rs = Database::query($sql);
            $i = 0;
            while ($session = Database::fetch_array($rs)) {
                $i++;
                if ($i <= 10) {
                    $return .= '<a href="#" onclick="add_user_to_url(\''.addslashes($session['id']).'\',\''.addslashes($session['name']).' ('.addslashes($session['id']).')'.'\')">'.$session['name'].' </a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
        }
        $xajax_response->addAssign(
            'ajax_list_courses',
            'innerHTML',
            api_utf8_encode($return)
        );

        return $xajax_response;
    }
}
