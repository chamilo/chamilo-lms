<?php
/* For licensing terms, see /license.txt */

/**
 * AccessUrlEditUsersToUrl class definition
 * Contains several functions dealing with displaying,
 * editing,... of a Access_url_edit_users_to_url_functions.
 *
 * @version 1.0
 *
 * @author Toon Keppens <toon@vi-host.net>
 * @author Julio Montoya - Cleaning code
 * @author Ricardo Rodriguez - Separated the function and code
 */
class AccessUrlEditUsersToUrl
{
    /**
     * Search users by username, firstname or lastname, based on the given
     * search string.
     *
     * @param string Search string
     * @param int Deprecated param
     *
     * @return xajaxResponse Xajax response block
     * @assert () === false
     */
    public static function search_users($needle, $id)
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $xajax_response = new xajaxResponse();
        $return = '';
        if (!empty($needle)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);
            // search users where username or firstname or lastname begins likes $needle
            $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
            $sql = 'SELECT u.user_id, username, lastname, firstname FROM '.$tbl_user.' u '.
                   ' WHERE (username LIKE "'.$needle.'%" '.
                   ' OR firstname LIKE "'.$needle.'%" '.
                   ' OR lastname LIKE "'.$needle.'%") '.
                    $order_clause.
                   ' LIMIT 11';
            $rs = Database::query($sql);
            $i = 0;

            while ($user = Database::fetch_array($rs)) {
                $i++;
                if ($i <= 10) {
                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_url(\''.addslashes($user['user_id']).'\',\''.api_get_person_name(addslashes($user['firstname']), addslashes($user['lastname'])).' ('.addslashes($user['username']).')'.'\')">'.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')</a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
        }
        $xajax_response->addAssign(
            'ajax_list_users',
            'innerHTML',
            api_utf8_encode($return)
        );

        return $xajax_response;
    }
}
