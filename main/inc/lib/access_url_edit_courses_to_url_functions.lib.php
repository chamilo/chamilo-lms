<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of the Accessurleditcoursestourl class
 * @package chamilo.library
 */
/**
 * Init
 */
require_once ('xajax/xajax.inc.php');
/**
 * Access_url_edit_courses_to_url class
 * Contains several functions dealing with displaying,
 * editing,... of a Access_url_edit_courses_to_url_functions
 *
 * @version 1.0
 * @author Toon Keppens <toon@vi-host.net>
 * @author Julio Montoya - Cleaning code
 * @author Ricardo Rodriguez - Separated the function and code
 */
class Accessurleditcoursestourl
{
    /**
     * Search for a list of available courses by title or code, based on
     * a given string
     * @param string String to search for
     * @param int Deprecated param
     * @return string A formatted, xajax answer block
     * @assert () === false
     */
    function search_courses($needle, $id)
    {
        global $tbl_course;
        $xajax_response = new XajaxResponse();
        $return = '';

        if (!empty($needle)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);
            // search courses where username or firstname or lastname begins likes $needle
            $sql = 'SELECT code, title FROM '.$tbl_course.' u '.
                   ' WHERE (title LIKE "'.$needle.'%" '.
                   ' OR code LIKE "'.$needle.'%" '.
                   ' ) '.
                   ' ORDER BY title, code '.
                   ' LIMIT 11';
            $rs = Database::query($sql);
            $i=0;
            while ($course = Database :: fetch_array($rs)) {
                $i++;
                if ($i<=10) {
                     $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_url(\''.addslashes($course['code']).'\',\''.addslashes($course['title']).' ('.addslashes($course['code']).')'.'\')">'.$course['title'].' ('.$course['code'].')</a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
        }
        $xajax_response -> addAssign('ajax_list_courses','innerHTML',api_utf8_encode($return));
        return $xajax_response;
    }
}

