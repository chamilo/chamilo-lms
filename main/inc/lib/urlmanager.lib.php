<?php
/* For licensing terms, see /license.txt */

/**
 * Class UrlManager
 * This library provides functions for the access_url management.
 * Include/require it in your code to use its functionality.
 *
 *	@package chamilo.library
 */
class UrlManager
{
    /**
    * Creates a new url access
    *
    * @author Julio Montoya <gugli100@gmail.com>,
    *
    * @param	string	$url The URL of the site
    * @param	string  $description The description of the site
    * @param	int		$active is active or not
    * @return boolean if success
    */
    public static function add($url, $description, $active)
    {
        $tms = time();
        $table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "INSERT INTO $table
                SET url 	= '".Database::escape_string($url)."',
                description = '".Database::escape_string($description)."',
                active 		= '".intval($active)."',
                created_by 	= '".api_get_user_id()."',
                tms = FROM_UNIXTIME(".$tms.")";
        $result = Database::query($sql);

        return $result;
    }

    /**
    * Updates an URL access
    * @author Julio Montoya <gugli100@gmail.com>,
    *
    * @param	int 	$url_id The url id
    * @param	string 	$url
    * @param	string  $description The description of the site
    * @param	int		$active is active or not
    * @return 	boolean if success
    */
    public static function update($url_id, $url, $description, $active)
    {
        $url_id = intval($url_id);
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "UPDATE $table
                SET url 	= '".Database::escape_string($url)."',
                description = '".Database::escape_string($description)."',
                active 		= '".intval($active)."',
                created_by 	= '".api_get_user_id()."',
                tms 		= '".api_get_utc_datetime()."'
                WHERE id = '$url_id'";

        $result = Database::query($sql);

        return $result;
    }

    /**
    * Deletes an url
    * @author Julio Montoya
    * @param int $id url id
     *
    * @return boolean true if success
    * */
    public static function delete($id)
    {
        $id = intval($id);
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $tableUser = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $tableCourse = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $tableSession = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $tableCourseCategory = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $tableGroup = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);

        $sql = "DELETE FROM $tableCourse WHERE access_url_id = ".$id;
        Database::query($sql);
        /*
        $sql = "DELETE FROM $tableCourseCategory WHERE access_url_id = ".$id;
        Database::query($sql);
        */
        $sql = "DELETE FROM $tableSession WHERE access_url_id = ".$id;
        Database::query($sql);
        $sql = "DELETE FROM $tableGroup WHERE access_url_id = ".$id;
        Database::query($sql);
        $sql = "DELETE FROM $tableUser WHERE access_url_id = ".$id;
        Database::query($sql);

        $sql= "DELETE FROM $table WHERE id = ".$id;
        Database::query($sql);

        return true;
    }

    /**
     * @param string $url
     *
     * @return int
     */
    public static function url_exist($url)
    {
        $table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT id FROM $table
                WHERE url = '".Database::escape_string($url)."' ";
        $res = Database::query($sql);
        $num = Database::num_rows($res);

        return $num;
    }

    /**
     * @param string $url
     *
     * @return int
     */
    public static function url_id_exist($url)
    {
        if (empty($url)) {
            return false;
        }
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT id FROM $table WHERE id = ".intval($url)."";
        $res = Database::query($sql);
        $num = Database::num_rows($res);

        return $num;
    }

    /**
     * This function get the quantity of URLs
     * @author Julio Montoya
     * @return int count of urls
     * */
    public static function url_count()
    {
        $table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT count(id) as count_result FROM $table_access_url";
        $res = Database::query($sql);
        $url = Database::fetch_array($res,'ASSOC');
        $result = $url['count_result'];

        return $result;
    }

    /**
     * Gets the id, url, description, and active status of ALL URLs
     * @author Julio Montoya
     * @return array
     * */
    public static function get_url_data()
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT id, url, description, active
                FROM $table
                ORDER BY id";
        $res = Database::query($sql);
        $urls = array ();
        while ($url = Database::fetch_array($res)) {
            $urls[] = $url;
        }

        return $urls;
    }

    /**
     * Gets the id, url, description, and active status of ALL URLs
     * @author Julio Montoya
     * @param int $url_id
     * @return array
     * */
    public static function get_url_data_from_id($url_id)
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT id, url, description, active
                FROM $table
                WHERE id = ".intval($url_id);
        $res = Database::query($sql);
        $row = Database::fetch_array($res);

        return $row;
    }

    /**
     * Gets the inner join of users and urls table
     * @author Julio Montoya
     * @param int  access url id
     * @param string $order_by
     * @return array   Database::store_result of the result
     **/
    public static function get_url_rel_user_data($access_url_id = null, $order_by = null)
    {
        $where = '';
        $table_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
        if (!empty($access_url_id)) {
            $where = "WHERE $table_url_rel_user.access_url_id = ".intval($access_url_id);
        }
        if (empty($order_by)) {
            $order_clause = api_sort_by_first_name(
            ) ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
        } else {
            $order_clause = $order_by;
        }
        $sql = "SELECT u.user_id, lastname, firstname, username, official_code, access_url_id
                FROM $tbl_user u
                INNER JOIN $table_url_rel_user
                ON $table_url_rel_user.user_id = u.user_id
                $where  $order_clause";
        $result = Database::query($sql);
        $users = Database::store_result($result);

        return $users;
    }

    /**
    * Gets the inner join of access_url and the course table
    *
    * @author Julio Montoya
    * @param int  access url id
    * @return array   Database::store_result of the result
    **/
    public static function get_url_rel_course_data($access_url_id = null)
    {
        $where = '';
        $table_url_rel_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);

        if (!empty($access_url_id)) {
            $where = " WHERE uc.access_url_id = ".intval($access_url_id);
        }

        $sql = "SELECT u.id, c_id, title, uc.access_url_id
                FROM $tbl_course u
                INNER JOIN $table_url_rel_course uc
                ON uc.c_id = u.id
                $where
                ORDER BY title, code";

        $result = Database::query($sql);
        $courses = Database::store_result($result);

        return $courses;
    }

    /**
     * Gets the number of rows with a specific course_code in access_url_rel_course table
     * @author Yoselyn Castillo
     * @param int $courseId
     *
     * @return int Database::num_rows($res);
     **/
    public static function getCountUrlRelCourse($courseId)
    {
        $courseId = intval($courseId);
        $tableUrlRelCourse = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql = "SELECT *
                FROM $tableUrlRelCourse
                WHERE c_id = '$courseId'";
        $res = Database::query($sql);

        return Database::num_rows($res);
    }

    /**
     * Gets the inner join of access_url and the session table
     * @author Julio Montoya
     * @param int  $access_url_id access url id
     *
     * @return array   Database::store_result of the result
     *
     **/
    public static function get_url_rel_session_data($access_url_id = null)
    {
        $where ='';
        $table_url_rel_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

        if (!empty($access_url_id)) {
            $where = "WHERE $table_url_rel_session.access_url_id = ".intval($access_url_id);
        }

        $sql = "SELECT id, name, access_url_id
                FROM $tbl_session u
                INNER JOIN $table_url_rel_session
                ON $table_url_rel_session.session_id = id
                $where
                ORDER BY name, id";

        $result = Database::query($sql);
        $sessions = Database::store_result($result);

        return $sessions;
    }

    /**
     * Gets the inner join of access_url and the usergroup table
     *
     * @author Julio Montoya
     * @param int  $access_url_id
     *
     * @return array   Database::store_result of the result
     **/
    public static function get_url_rel_usergroup_data($access_url_id = null)
    {
        $where = '';
        $table_url_rel_usergroup = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
        $table_user_group = Database::get_main_table(TABLE_USERGROUP);

        if (!empty($access_url_id)) {
            $where ="WHERE $table_url_rel_usergroup.access_url_id = ".intval($access_url_id);
        }

        $sql = "SELECT u.id, u.name, access_url_id
				FROM $table_user_group u
				INNER JOIN $table_url_rel_usergroup
				ON $table_url_rel_usergroup.usergroup_id = u.id
				$where
				ORDER BY name";

        $result = Database::query($sql);
        $courses = Database::store_result($result);

        return $courses;
    }

    /**
     * Gets the inner join of access_url and the usergroup table
     *
     * @author Julio Montoya
     * @param int  $access_url_id
     * @return array   Database::store_result of the result
     **/
    public static function getUrlRelCourseCategory($access_url_id = null)
    {
        $table_url_rel = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $where = " WHERE 1=1 ";
        if (!empty($access_url_id)) {
            $where .= " AND $table_url_rel.access_url_id = ".intval($access_url_id);
        }
        $where .= " AND (parent_id IS NULL) ";

        $sql = "SELECT u.id, name, access_url_id
                FROM $table u
                INNER JOIN $table_url_rel
                ON $table_url_rel.course_category_id = u.id
                $where
                ORDER BY name";

        $result = Database::query($sql);
        $courses = Database::store_result($result, 'ASSOC');

        return $courses;
    }

    /**
     * Sets the status of an URL 1 or 0
     * @author Julio Montoya
     * @param string lock || unlock
     * @param int url id
     * */
    public static function set_url_status($status, $url_id)
    {
        $url_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        if ($status == 'lock') {
            $status_db = '0';
        }
        if ($status == 'unlock') {
            $status_db = '1';
        }
        if (($status_db == '1' || $status_db == '0') && is_numeric($url_id)) {
            $sql = "UPDATE $url_table SET active='".intval($status_db)."'
                    WHERE id='".intval($url_id)."'";
            Database::query($sql);
        }
    }

    /**
    * Checks the relationship between an URL and a User (return the num_rows)
    * @author Julio Montoya
    * @param int user id
    * @param int url id
    * @return boolean true if success
    * */
    public static function relation_url_user_exist($user_id, $url_id)
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql= "SELECT user_id FROM $table
               WHERE access_url_id = ".intval($url_id)." AND user_id = ".intval($user_id)." ";
        $result = Database::query($sql);
        $num = Database::num_rows($result);

        return $num;
	}

    /**
    * Checks the relationship between an URL and a Course (return the num_rows)
    * @author Julio Montoya
    * @param int $courseId
    * @param int $urlId
    * @return boolean true if success
    * */
    public static function relation_url_course_exist($courseId, $urlId)
    {
        $table_url_rel_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql= "SELECT c_id FROM $table_url_rel_course
               WHERE
                    access_url_id = ".intval($urlId)." AND
                    c_id = '".intval($courseId)."'";
        $result = Database::query($sql);
        $num = Database::num_rows($result);

        return $num;
    }

    /**
     * Checks the relationship between an URL and a UserGr
     * oup (return the num_rows)
     * @author Julio Montoya
     * @param int $userGroupId
     * @param int $urlId
     * @return boolean true if success
     * */
    public static function relationUrlUsergroupExist($userGroupId, $urlId)
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
        $sql= "SELECT usergroup_id FROM $table
               WHERE 
                    access_url_id = ".intval($urlId)." AND
                    usergroup_id = ".intval($userGroupId);
        $result = Database::query($sql);
        $num = Database::num_rows($result);

        return $num;
    }

    /**
    * Checks the relationship between an URL and a Session (return the num_rows)
    * @author Julio Montoya
    * @param int user id
    * @param int url id
    * @return boolean true if success
    * */
    public static function relation_url_session_exist($session_id, $url_id)
    {
        $table_url_rel_session= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $session_id = intval($session_id);
        $url_id		= intval($url_id);
        $sql = "SELECT session_id FROM $table_url_rel_session
                WHERE
                    access_url_id = ".intval($url_id)." AND
                    session_id = ".Database::escape_string($session_id);
        $result = Database::query($sql);
        $num = Database::num_rows($result);

        return $num;
    }

    /**
     * Add a group of users into a group of URLs
     * @author Julio Montoya
     * @param  array of user_ids
     * @param  array of url_ids
     * @return array
     * */
    public static function add_users_to_urls($user_list, $url_list)
    {
        $table_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $result_array = array();

        if (is_array($user_list) && is_array($url_list)) {
            foreach ($url_list as $url_id) {
                foreach ($user_list as $user_id) {
                    $count = UrlManager::relation_url_user_exist($user_id,$url_id);
                    if ($count == 0) {
                        $sql = "INSERT INTO $table_url_rel_user
                                SET 
                                    user_id = ".intval($user_id).", 
                                    access_url_id = ".intval($url_id);
                        $result = Database::query($sql);
                        if ($result) {
                            $result_array[$url_id][$user_id] = 1;
                        } else {
                            $result_array[$url_id][$user_id] = 0;
                        }
                    }
                }
            }
        }

        return 	$result_array;
    }


    /**
     * Add a group of courses into a group of URLs
     * @author Julio Montoya
     * @param  array of course ids
     * @param  array of url_ids
     * @return array
     **/
    public static function add_courses_to_urls($course_list,$url_list)
    {
        $table_url_rel_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $result_array = array();

        if (is_array($course_list) && is_array($url_list)) {
            foreach ($url_list as $url_id) {
                foreach ($course_list as $course_code) {
                    $courseInfo = api_get_course_info($course_code);
                    $courseId = $courseInfo['real_id'];

                    $count = self::relation_url_course_exist($courseId, $url_id);
                    if ($count==0) {
                        $sql = "INSERT INTO $table_url_rel_course
                                SET c_id = '".$courseId."', access_url_id = ".intval($url_id);
                        $result = Database::query($sql);
                        if ($result) {
                            $result_array[$url_id][$course_code] = 1;
                        } else {
                            $result_array[$url_id][$course_code] = 0;
                        }
                    }
                }
            }
        }

        return $result_array;
    }

    /**
     * Add a group of user group into a group of URLs
     * @author Julio Montoya
     * @param  array $userGroupList of course ids
     * @param  array $urlList of url_ids
     * @return array
     **/
    public static function addUserGroupListToUrl($userGroupList, $urlList)
    {
        $resultArray = array();
        if (is_array($userGroupList) && is_array($urlList)) {
            foreach ($urlList as $urlId) {
                foreach ($userGroupList as $userGroupId) {
                    $count = self::relationUrlUsergroupExist($userGroupId, $urlId);
                    if ($count == 0) {
                        $result = self::addUserGroupToUrl($userGroupId, $urlId);
                        if ($result) {
                            $resultArray[$urlId][$userGroupId] = 1;
                        } else {
                            $resultArray[$urlId][$userGroupId] = 0;
                        }
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Add a group of user group into a group of URLs
     * @author Julio Montoya
     * @param  array of course ids
     * @param  array of url_ids
     * @return array
     **/
    public static function addCourseCategoryListToUrl($courseCategoryList, $urlList)
    {
        $resultArray = array();
        if (is_array($courseCategoryList) && is_array($urlList)) {
            foreach ($urlList as $urlId) {
                foreach ($courseCategoryList as $categoryCourseId) {
                    $count = self::relationUrlCourseCategoryExist($categoryCourseId, $urlId);
                    if ($count == 0) {
                        $result = self::addCourseCategoryToUrl($categoryCourseId, $urlId);
                        if ($result) {
                            $resultArray[$urlId][$categoryCourseId] = 1;
                        } else {
                            $resultArray[$urlId][$categoryCourseId] = 0;
                        }
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Checks the relationship between an URL and a UserGr
     * oup (return the num_rows)
     * @author Julio Montoya
     * @param int $categoryCourseId
     * @param int $urlId
     * @return boolean true if success
     * */
    public static function relationUrlCourseCategoryExist($categoryCourseId, $urlId)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $sql= "SELECT course_category_id FROM $table
               WHERE access_url_id = ".intval($urlId)." AND
                     course_category_id = ".intval($categoryCourseId);
        $result = Database::query($sql);
        $num = Database::num_rows($result);

        return $num;
    }

    /**
     * @param int $userGroupId
     * @param int $urlId
     * @return int
     */
    public static function addUserGroupToUrl($userGroupId, $urlId)
    {
        $urlRelUserGroupTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
        $sql = "INSERT INTO $urlRelUserGroupTable
                SET
                usergroup_id = '".intval($userGroupId)."',
                access_url_id = ".intval($urlId);
        Database::query($sql);

        return Database::insert_id();
    }

    /**
     * @param int $categoryId
     * @param int $urlId
     * @return int
     */
    public static function addCourseCategoryToUrl($categoryId, $urlId)
    {
        $exists = self::relationUrlCourseCategoryExist($categoryId, $urlId);
        if (empty($exists)) {
            $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);

            $sql = "INSERT INTO $table
                    SET
                    course_category_id = '".intval($categoryId)."',
                    access_url_id = ".intval($urlId);
            Database::query($sql);

            return Database::insert_id();
        }

        return 0;
    }

    /**
     * Add a group of sessions into a group of URLs
     * @author Julio Montoya
     * @param  array $session_list of session ids
     * @param  array $url_list of url_ids
     * @return array
     * */
    public static function add_sessions_to_urls($session_list, $url_list)
    {
        $table_url_rel_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $result_array = array();

        if (is_array($session_list) && is_array($url_list)) {
            foreach ($url_list as $url_id) {
                foreach ($session_list as $session_id) {
                    $count = UrlManager::relation_url_session_exist($session_id, $url_id);

                    if ($count == 0) {
                        $sql = "INSERT INTO $table_url_rel_session
		               			SET
		               			session_id = ".intval($session_id).",
		               			access_url_id = ".intval($url_id);
                        $result = Database::query($sql);
                        if ($result) {
                            $result_array[$url_id][$session_id] = 1;
                        } else {
                            $result_array[$url_id][$session_id] = 0;
                        }
                    }
                }
            }
        }

        return $result_array;
    }

    /**
     * Add a user into a url
     * @author Julio Montoya
     * @param  int $user_id
     * @param  int $url_id
     *
     * @return boolean true if success
     * */
    public static function add_user_to_url($user_id, $url_id = 1)
    {
        $table_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        if (empty($url_id)) {
            $url_id = 1;
        }
        $count  = UrlManager::relation_url_user_exist($user_id, $url_id);
        $result = true;
        if (empty($count)) {
            $sql = "INSERT INTO $table_url_rel_user (user_id, access_url_id)
                    VALUES ('".intval($user_id)."', '".intval($url_id)."') ";
            $result = Database::query($sql);
        }

        return $result;
    }

    /**
     * @param int $courseId
     * @param int $url_id
     *
     * @return resource
     */
    public static function add_course_to_url($courseId, $url_id = 1)
    {
        $table_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        if (empty($url_id)) {
            $url_id = 1;
        }
        $count = UrlManager::relation_url_course_exist($courseId, $url_id);
        if (empty($count)) {
            $sql = "INSERT INTO $table_url_rel_course
                    SET c_id = '".intval($courseId)."', access_url_id = ".intval($url_id);
            Database::query($sql);
        }

        return true;
    }

    /**
     * Inserts a session to a URL (access_url_rel_session table)
     * @param   int     Session ID
     * @param   int     URL ID
     *
     * @return  bool    True on success, false session already exists or insert failed
     */
    public static function add_session_to_url($session_id, $url_id = 1)
    {
        $table_url_rel_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        if (empty($url_id)) {
            $url_id = 1;
        }
        $result = false;
        $count = UrlManager::relation_url_session_exist($session_id, $url_id);
        $session_id	= intval($session_id);
        if (empty($count) && !empty($session_id)) {
            $url_id = intval($url_id);
            $sql = "INSERT INTO $table_url_rel_session
                    SET session_id = ".intval($session_id).", access_url_id = ".intval($url_id);
            $result = Database::query($sql);
        }

        return $result;
    }

    /**
    * Deletes an url and user relationship
    * @author Julio Montoya
    * @param int user id
    * @param int url id
     *
    * @return boolean true if success
    * */
    public static function delete_url_rel_user($user_id, $url_id)
    {
        $table_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $result = true;
        if (!empty($user_id) && !empty($url_id)) {
            $sql= "DELETE FROM $table_url_rel_user
                   WHERE user_id = ".intval($user_id)." AND access_url_id = ".intval($url_id);
            $result = Database::query($sql);
        }

        return $result;
    }

    /**
     * Deletes user from all portals
     * @author Julio Montoya
     * @param int user id
     *
     * @return boolean true if success
     * */
    public static function deleteUserFromAllUrls($userId)
    {
        $table_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $result = true;
        if (!empty($userId)) {
            $sql= "DELETE FROM $table_url_rel_user
                   WHERE user_id = ".intval($userId);
            Database::query($sql);
        }

        return $result;
    }

    /**
    * Deletes an url and course relationship
    * @author Julio Montoya
    * @param  int  $courseId
    * @param  int  $urlId
     *
    * @return boolean true if success
    * */
    public static function delete_url_rel_course($courseId, $urlId)
    {
        $table_url_rel_course= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql= "DELETE FROM $table_url_rel_course
               WHERE c_id = '".intval($courseId)."' AND access_url_id=".intval($urlId)."  ";
        $result = Database::query($sql);

        return $result;
    }

    /**
     * Deletes an url and $userGroup relationship
     * @author Julio Montoya
     * @param  int $userGroupId
     * @param  int $urlId
     *
     * @return boolean true if success
     * */
    public static function delete_url_rel_usergroup($userGroupId, $urlId)
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);
        $sql= "DELETE FROM $table
               WHERE usergroup_id = '".intval($userGroupId)."' AND
                     access_url_id = ".intval($urlId);
        $result = Database::query($sql);

        return $result;
    }

    /**
     * Deletes an url and $userGroup relationship
     * @author Julio Montoya
     * @param  int $userGroupId
     * @param  int $urlId
     *
     * @return boolean true if success
     * */
    public static function deleteUrlRelCourseCategory($userGroupId, $urlId)
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $sql= "DELETE FROM $table
               WHERE course_category_id = '".intval($userGroupId)."' AND
                     access_url_id=".intval($urlId)."  ";
        $result = Database::query($sql);

        return $result;
    }

    /**
    * Deletes an url and session relationship
    * @author Julio Montoya
    * @param  char  course code
    * @param  int url id
     *
    * @return boolean true if success
    * */
    public static function delete_url_rel_session($session_id, $url_id)
    {
        $table_url_rel_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $sql= "DELETE FROM $table_url_rel_session
               WHERE session_id = ".intval($session_id)." AND access_url_id=".intval($url_id)."  ";
        $result = Database::query($sql,'ASSOC');

        return $result;
    }

    /**
     * Updates the access_url_rel_user table  with a given user list
     * @author Julio Montoya
     * @param array $user_list
     * @param int $access_url_id
     * */
    public static function update_urls_rel_user($user_list, $access_url_id)
    {
        $table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql = "SELECT user_id 
                FROM $table_url_rel_user 
                WHERE access_url_id = ".intval($access_url_id);
        $result = Database::query($sql);
        $existing_users = array();

        //Getting all users
        while ($row = Database::fetch_array($result)) {
            $existing_users[] = $row['user_id'];
        }

        // Adding users
        $users_added = array();
        foreach ($user_list as $user_id_to_add) {
            if (!in_array($user_id_to_add, $existing_users)) {
                $result = UrlManager::add_user_to_url($user_id_to_add, $access_url_id);
                if ($result) {
                    $users_added[] = $user_id_to_add;
                }
            }
        }

        $users_deleted = array();
        // Deleting old users
        foreach ($existing_users as $user_id_to_delete) {
            if (!in_array($user_id_to_delete, $user_list)) {
                $result = UrlManager::delete_url_rel_user($user_id_to_delete, $access_url_id);
                if ($result) {
                    $users_deleted[] = $user_id_to_delete;
                }
            }
        }

        if (empty($users_added) && empty($users_deleted)) {
            return false;
        }

        return array('users_added' => $users_added, 'users_deleted' => $users_deleted);
    }

    /**
     * Updates the access_url_rel_course table  with a given user list
     * @author Julio Montoya
     * @param array $course_list
     * @param int access_url_id
     * */
    public static function update_urls_rel_course($course_list, $access_url_id)
    {
        $table_url_rel_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $sql = "SELECT c_id FROM $table_url_rel_course
                WHERE access_url_id = ".intval($access_url_id);
        $result = Database::query($sql);

        $existing_courses = array();
        while ($row = Database::fetch_array($result)){
            $existing_courses[] = $row['c_id'];
        }

        // Adding courses
        foreach ($course_list as $courseId) {
            UrlManager::add_course_to_url($courseId, $access_url_id);
            CourseManager::update_course_ranking($courseId, 0, $access_url_id);
        }

        // Deleting old courses
        foreach ($existing_courses as $courseId) {
            if (!in_array($courseId, $course_list)) {
                UrlManager::delete_url_rel_course($courseId, $access_url_id);
                CourseManager::update_course_ranking($courseId, 0, $access_url_id);
            }
        }
    }

    /**
     * Updates the access_url_rel_course table  with a given user list
     * @author Julio Montoya
     * @param array $userGroupList user list
     * @param int $urlId
     * */
    public static function update_urls_rel_usergroup($userGroupList, $urlId)
    {
        $table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);

        $sql = "SELECT usergroup_id FROM $table WHERE access_url_id = ".intval($urlId);
        $result = Database::query($sql);
        $existingItems = array();

        while ($row = Database::fetch_array($result)){
            $existingItems[] = $row['usergroup_id'];
        }

        // Adding
        foreach ($userGroupList as $userGroupId) {
            if (!in_array($userGroupId, $existingItems)) {
                UrlManager::addUserGroupToUrl($userGroupId, $urlId);
            }
        }

        // Deleting old items
        foreach ($existingItems as $userGroupId) {
            if (!in_array($userGroupId, $userGroupList)) {
                UrlManager::delete_url_rel_usergroup($userGroupId, $urlId);
            }
        }
    }

    /**
     * Updates the access_url_rel_course_category table with a given list
     * @author Julio Montoya
     * @param array $list course category list
     * @param int $urlId access_url_id
     **/
    public static function updateUrlRelCourseCategory($list, $urlId)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);

        $sql = "SELECT course_category_id FROM $table WHERE access_url_id = ".intval($urlId);
        $result = Database::query($sql);
        $existingItems = array();

        while ($row = Database::fetch_array($result)){
            $existingItems[] = $row['course_category_id'];
        }

        // Adding
        foreach ($list as $id) {
            UrlManager::addCourseCategoryToUrl($id, $urlId);
            $categoryInfo = CourseCategory::getCategoryById($id);
            $children = CourseCategory::getChildren($categoryInfo['code']);
            if (!empty($children)) {
                foreach ($children as $category) {
                    UrlManager::addCourseCategoryToUrl($category['id'], $urlId);
                }
            }
        }

        // Deleting old items
        foreach ($existingItems as $id) {
            if (!in_array($id, $list)) {
                UrlManager::deleteUrlRelCourseCategory($id, $urlId);
                $categoryInfo = CourseCategory::getCategoryById($id);

                $children = CourseCategory::getChildren($categoryInfo['code']);
                if (!empty($children)) {
                    foreach ($children as $category) {
                        UrlManager::deleteUrlRelCourseCategory($category['id'], $urlId);
                    }
                }
            }
        }
    }

    /**
     * Updates the access_url_rel_session table with a given user list
     * @author Julio Montoya
     * @param array $session_list
     * @param int $access_url_id
     * */
    public static function update_urls_rel_session($session_list, $access_url_id)
    {
        $table_url_rel_session	= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

        $sql = "SELECT session_id FROM $table_url_rel_session WHERE access_url_id=".intval($access_url_id);
        $result = Database::query($sql);
        $existing_sessions = array();

        while ($row = Database::fetch_array($result)){
            $existing_sessions[] = $row['session_id'];
        }

        // Adding users
        foreach ($session_list as $session) {
            if (!in_array($session, $existing_sessions)) {
                if (!empty($session) && !empty($access_url_id)) {
                    UrlManager::add_session_to_url($session, $access_url_id);
                }
            }
        }

        // Deleting old users
        foreach ($existing_sessions as $existing_session) {
            if (!in_array($existing_session, $session_list)) {
                if (!empty($existing_session) && !empty($access_url_id)) {
                    UrlManager::delete_url_rel_session($existing_session, $access_url_id);
                }
            }
        }
    }

    /**
     * @param int $user_id
     *
     * @return array
     */
    public static function get_access_url_from_user($user_id)
    {
        $table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $table_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT url, access_url_id FROM $table_url_rel_user url_rel_user INNER JOIN $table_url u
                ON (url_rel_user.access_url_id = u.id)
                WHERE user_id = ".intval($user_id);
        $result = Database::query($sql);
        $url_list = Database::store_result($result,'ASSOC');

        return $url_list;
    }

    /**
     * @param int $courseId
     * @return array
     */
    public static function get_access_url_from_course($courseId)
    {
        $table	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $table_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT url, access_url_id FROM $table c INNER JOIN $table_url u
                ON (c.access_url_id = u.id)
                WHERE c_id = ".intval($courseId);

        $result = Database::query($sql);
        $url_list = Database::store_result($result,'ASSOC');
        return $url_list;
    }

    /**
     * @param $session_id
     * @return array
     */
    public static function get_access_url_from_session($session_id)
    {
        $table_url_rel_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $table_url  = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT url, access_url_id FROM $table_url_rel_session url_rel_session INNER JOIN $table_url u
                ON (url_rel_session.access_url_id = u.id)
                WHERE session_id = ".intval($session_id);
        $result = Database::query($sql);
        $url_list = Database::store_result($result);

        return $url_list;
    }

    /**
     * @param string $url
     * @return bool|mixed|null
     */
    public static function get_url_id($url)
    {
        $table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT id FROM $table_access_url WHERE url = '".Database::escape_string($url)."'";
        $result = Database::query($sql);
        $access_url_id = Database::result($result, 0, 0);

        return $access_url_id;
    }

    /**
     *
     * @param string $needle
     * @return XajaxResponse
     */
    public static function searchCourseCategoryAjax($needle)
    {
        $response = new xajaxResponse();
        $return = '';

        if (!empty($needle)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);
            // search courses where username or firstname or lastname begins likes $needle
            $sql = 'SELECT id, name FROM '.Database::get_main_table(TABLE_MAIN_CATEGORY).' u
                    WHERE name LIKE "'.$needle.'%" AND (parent_id IS NULL or parent_id = 0)
                    ORDER BY name
                    LIMIT 11';
            $result = Database::query($sql);
            $i = 0;
            while ($data = Database::fetch_array($result)) {
                $i++;
                if ($i <= 10) {
                    $return .= '<a
                    href="javascript: void(0);"
                    onclick="javascript: add_user_to_url(\''.addslashes($data['id']).'\',\''.addslashes($data['name']).' \')">'.$data['name'].' </a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
        }
        $response->addAssign('ajax_list_courses', 'innerHTML', api_utf8_encode($return));

        return $response;
    }
}
