<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class AddCourseToSession.
 */
class AddCourseToSession
{
    /**
     * Searches a course, given a search string and a type of search box.
     *
     * @param string $needle     Search string
     * @param string $type       Type of search box ('single' or anything else)
     * @param int    $id_session
     *
     * @return xajaxResponse XajaxResponse
     * @assert ('abc', 'single') !== null
     * @assert ('abc', 'multiple') !== null
     */
    public static function search_courses($needle, $type, $id_session)
    {
        $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        // Session value set in file add_courses_to_session.php
        $id_session = (int) $id_session;
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_title = null;
        $xajax_response = new xajaxResponse();
        $return = '';
        if (!empty($needle) && !empty($type)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);

            $cond_course_code = '';
            if (!empty($id_session)) {
                $id_session = (int) $id_session;
                // check course_code from session_rel_course table
                $sql = 'SELECT c_id FROM '.$tbl_session_rel_course.'
                        WHERE session_id = '.$id_session;
                $res = Database::query($sql);
                $course_codes = '';
                if (Database::num_rows($res) > 0) {
                    while ($row = Database::fetch_row($res)) {
                        $course_codes .= '\''.$row[0].'\',';
                    }
                    $course_codes = substr($course_codes, 0, (strlen($course_codes) - 1));
                    $cond_course_code = ' AND course.id NOT IN('.$course_codes.') ';
                }
            }

            if ('single' == $type) {
                // search users where username or firstname or lastname begins likes $needle
                $sql = 'SELECT
                            course.id,
                            course.visual_code,
                            course.title,
                            session_rel_course.session_id
                        FROM '.$tbl_course.' course
                        LEFT JOIN '.$tbl_session_rel_course.' session_rel_course
                            ON course.id = session_rel_course.c_id
                            AND session_rel_course.session_id = '.intval($id_session).'
                        WHERE
                            course.visual_code LIKE "'.$needle.'%" OR
                            course.title LIKE "'.$needle.'%"';
            } else {
                $sql = 'SELECT course.id, course.visual_code, course.title
                        FROM '.$tbl_course.' course
                        WHERE
                            course.visual_code LIKE "'.$needle.'%" '.$cond_course_code.'
                        ORDER BY course.code ';
            }

            if (api_is_multiple_url_enabled()) {
                $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $access_url_id = api_get_current_access_url_id();
                if (-1 != $access_url_id) {
                    if ('single' == $type) {
                        $sql = 'SELECT
                                    course.id,
                                    course.visual_code,
                                    course.title,
                                    session_rel_course.session_id
                                FROM '.$tbl_course.' course
                                LEFT JOIN '.$tbl_session_rel_course.' session_rel_course
                                    ON course.id = session_rel_course.c_id
                                    AND session_rel_course.session_id = '.intval($id_session).'
                                INNER JOIN '.$tbl_course_rel_access_url.' url_course
                                ON (url_course.c_id = course.id)
                                WHERE
                                    access_url_id = '.$access_url_id.' AND
                                    (course.visual_code LIKE "'.$needle.'%" OR
                                    course.title LIKE "'.$needle.'%" )';
                    } else {
                        $sql = 'SELECT course.id, course.visual_code, course.title
                                FROM '.$tbl_course.' course, '.$tbl_course_rel_access_url.' url_course
                                WHERE
                                    url_course.c_id = course.id AND
                                    access_url_id = '.$access_url_id.' AND
                                    course.visual_code LIKE "'.$needle.'%" '.$cond_course_code.'
                                ORDER BY course.code ';
                    }
                }
            }

            $rs = Database::query($sql);
            $course_list = [];
            if ('single' == $type) {
                while ($course = Database::fetch_array($rs)) {
                    $course_list[] = $course['id'];
                    $course_title = str_replace("'", "\'", $course_title);
                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_course_to_session(\''.$course['id'].'\',\''.$course_title.' ('.$course['visual_code'].')'.'\')">'.$course['title'].' ('.$course['visual_code'].')</a><br />';
                }
                $xajax_response->addAssign('ajax_list_courses_single', 'innerHTML', api_utf8_encode($return));
            } else {
                $return .= '<select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" style="width:340px;">';
                while ($course = Database::fetch_array($rs)) {
                    $course_list[] = $course['id'];
                    $course_title = str_replace("'", "\'", $course_title);
                    $return .= '<option value="'.$course['id'].'" title="'.htmlspecialchars($course['title'].' ('.$course['visual_code'].')', ENT_QUOTES).'">'.$course['title'].' ('.$course['visual_code'].')</option>';
                }
                $return .= '</select>';
                $xajax_response->addAssign('ajax_list_courses_multiple', 'innerHTML', api_utf8_encode($return));
            }
        }
        Session::write('course_list', $course_list);

        return $xajax_response;
    }
}
