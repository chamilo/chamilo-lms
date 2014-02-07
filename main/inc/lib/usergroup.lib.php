<?php
/* For licensing terms, see /license.txt */
/**
 * This class provides methods for the UserGroup management.
 * Include/require it in your code to use its features.
 * @package chamilo.library
 */
/**
 * Code
 */

/**
 * Class
 * @package chamilo.library
 */
class UserGroup extends Model
{
    public $columns = array(
        'id',
        'name',
        'description',
        'group_type',
        'picture',
        'url',
        'visibility',
        'updated_on',
        'created_on'
    );

    const SOCIAL_CLASS = '1';
    const NORMAL_CLASS = '0';
    public $groupType = 0;

    public function __construct()
    {
        $this->table = Database::get_main_table(TABLE_USERGROUP);
        $this->usergroup_rel_user_table = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $this->usergroup_rel_course_table = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $this->usergroup_rel_session_table = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
        $this->table_course = Database::get_main_table(TABLE_MAIN_COURSE);
    }

    public function get_count()
    {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }

    public function get_usergroup_by_course_with_data_count($course_id)
    {
        $row = Database::select('count(*) as count', $this->usergroup_rel_course_table, array('where' => array('course_id = ?' => $course_id)), 'first');
        return $row['count'];
    }

    public function get_id_by_name($name)
    {
        $row = Database::select('id', $this->table, array('where' => array('name = ?' => $name)), 'first');
        return $row['id'];
    }

    /**
     * Displays the title + grid
     */
    function display()
    {
        // action links
        echo '<div class="actions">';
        echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', '32').'</a>';

        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('new_class.png', get_lang('AddClasses'), '', '32').'</a>';

        echo Display::url(Display::return_icon('import_csv.png', get_lang('Import'), array(), ICON_SIZE_MEDIUM), 'usergroup_import.php');
        echo Display::url(Display::return_icon('export_csv.png', get_lang('Export'), array(), ICON_SIZE_MEDIUM), 'usergroup_export.php');

        echo '</div>';
        echo Display::grid_html('usergroups');
    }

    function display_teacher_view()
    {
        // action links
        echo Display::grid_html('usergroups');
    }

    /**
     * Gets a list of course ids by user group
     * @param   int     user group id
     * @return  array
     */
    public function get_courses_by_usergroup($id)
    {
        $results = Database::select('course_id', $this->usergroup_rel_course_table, array('where' => array('usergroup_id = ?' => $id)));
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['course_id'];
            }
        }
        return $array;
    }

    public function get_usergroup_in_course($options = array())
    {
        $sql = "SELECT u.* FROM {$this->usergroup_rel_course_table} usergroup
                INNER JOIN  {$this->table} u
                ON (u.id = usergroup.usergroup_id)
                INNER JOIN {$this->table_course} c
                ON (usergroup.course_id = c.id)
               ";
        $conditions = Database::parse_conditions($options);
        $sql .= $conditions;
        $result = Database::query($sql);
        $array = Database::store_result($result, 'ASSOC');
        return $array;
    }

    public function get_usergroup_not_in_course($options = array())
    {
        $course_id = null;
        if (isset($options['course_id'])) {
            $course_id = intval($options['course_id']);
            unset($options['course_id']);
        }

        if (empty($course_id)) {
            return false;
        }
        $sql = "SELECT DISTINCT u.id, name
                FROM {$this->table} u
                LEFT OUTER JOIN {$this->usergroup_rel_course_table} urc
                ON (u.id = urc.usergroup_id AND course_id = $course_id)
               ";
        $conditions = Database::parse_conditions($options);
        $sql .= $conditions;
        $result = Database::query($sql);
        $array = Database::store_result($result, 'ASSOC');
        return $array;
    }

    public function get_usergroup_by_course($course_id)
    {
        $options = array('where' => array('course_id = ?' => $course_id));
        $results = Database::select('usergroup_id', $this->usergroup_rel_course_table, $options);
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }
        return $array;
    }

    public function usergroup_was_added_in_course($usergroup_id, $course_id)
    {
        $results = Database::select('usergroup_id', $this->usergroup_rel_course_table, array('where' => array('course_id = ? AND usergroup_id = ?' => array($course_id, $usergroup_id))));
        if (empty($results)) {
            return false;
        }
        return true;
    }

    /**
     * Gets a list of session ids by user group
     * @param   int     user group id
     * @return  array
     */
    public function get_sessions_by_usergroup($id)
    {
        $results = Database::select('session_id', $this->usergroup_rel_session_table, array('where' => array('usergroup_id = ?' => $id)));
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['session_id'];
            }
        }
        return $array;
    }

    /**
     * Gets a list of user ids by user group
     * @param   int     user group id
     * @return  array   with a list of user ids
     */
    public function get_users_by_usergroup($id = null)
    {
        if (empty($id)) {
            $conditions = array();
        } else {
            $conditions = array('where' => array('usergroup_id = ?' => $id));
        }
        $results = Database::select('user_id', $this->usergroup_rel_user_table, $conditions, true);
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['user_id'];
            }
        }
        return $array;
    }

    /**
     * Gets the usergroup id list by user id
     * @param   int user id
     */
    public function get_usergroup_by_user($id)
    {
        $results = Database::select('usergroup_id', $this->usergroup_rel_user_table, array('where' => array('user_id = ?' => $id)));
        $array = array();
        if (!empty($results)) {
            foreach ($results as $row) {
                $array[] = $row['usergroup_id'];
            }
        }
        return $array;
    }

    /**
     * Subscribes sessions to a group  (also adding the members of the group in the session and course)
     * @param   int     usergroup id
     * @param   array   list of session ids
     */
    function subscribe_sessions_to_usergroup($usergroup_id, $list)
    {
        $current_list = self::get_sessions_by_usergroup($usergroup_id);
        $user_list = self::get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = array();
        if (!empty($list)) {
            foreach ($list as $session_id) {
                if (!in_array($session_id, $current_list)) {
                    $new_items[] = $session_id;
                }
            }
        }
        if (!empty($current_list)) {
            foreach ($current_list as $session_id) {
                if (!in_array($session_id, $list)) {
                    $delete_items[] = $session_id;
                }
            }
        }

        //Deleting items
        if (!empty($delete_items)) {
            foreach ($delete_items as $session_id) {
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    }
                }
                Database::delete($this->usergroup_rel_session_table, array('usergroup_id = ? AND session_id = ?' => array($usergroup_id, $session_id)));
            }
        }

        //Adding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $session_id) {
                $params = array('session_id' => $session_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_session_table, $params);

                if (!empty($user_list)) {
                    SessionManager::suscribe_users_to_session($session_id, $user_list, null, false);
                }
            }
        }
    }

    /**
     * Subscribes courses to a group (also adding the members of the group in the course)
     * @param   int     usergroup id
     * @param   array   list of course ids (integers)
     */
    function subscribe_courses_to_usergroup($usergroup_id, $list, $delete_groups = true)
    {
        $current_list = self::get_courses_by_usergroup($usergroup_id);
        $user_list = self::get_users_by_usergroup($usergroup_id);

        $delete_items = $new_items = array();
        if (!empty($list)) {
            foreach ($list as $id) {
                if (!in_array($id, $current_list)) {
                    $new_items[] = $id;
                }
            }
        }

        if (!empty($current_list)) {
            foreach ($current_list as $id) {
                if (!in_array($id, $list)) {
                    $delete_items[] = $id;
                }
            }
        }

        if ($delete_groups) {
            self::unsubscribe_courses_from_usergroup($usergroup_id, $delete_items);
        }

        //Addding new relationships
        if (!empty($new_items)) {
            foreach ($new_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }

                $params = array('course_id' => $course_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_course_table, $params);
            }
        }
    }

    function unsubscribe_courses_from_usergroup($usergroup_id, $delete_items)
    {
        //Deleting items
        if (!empty($delete_items)) {
            $user_list = self::get_users_by_usergroup($usergroup_id);
            foreach ($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);
                if (!empty($user_list)) {
                    foreach ($user_list as $user_id) {
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);
                    }
                }
                Database::delete($this->usergroup_rel_course_table, array('usergroup_id = ? AND course_id = ?' => array($usergroup_id, $course_id)));
            }
        }
    }

    /**
     * Subscribes users to a group
     * @param   int     usergroup id
     * @param   array   list of user ids
     */
    function subscribe_users_to_usergroup($usergroup_id, $list, $delete_users_not_present_in_list = true)
    {
        $current_list = self::get_users_by_usergroup($usergroup_id);
        $course_list = self::get_courses_by_usergroup($usergroup_id);
        $session_list = self::get_sessions_by_usergroup($usergroup_id);

        $delete_items = array();
        $new_items = array();

        if (!empty($list)) {
            foreach ($list as $user_id) {
                if (!in_array($user_id, $current_list)) {
                    $new_items[] = $user_id;
                }
            }
        }

        if (!empty($current_list)) {
            foreach ($current_list as $user_id) {
                if (!in_array($user_id, $list)) {
                    $delete_items[] = $user_id;
                }
            }
        }

        //Deleting items
        if (!empty($delete_items) && $delete_users_not_present_in_list) {
            foreach ($delete_items as $user_id) {
                //Removing courses
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);
                    }
                }
                //Removing sessions
                if (!empty($session_list)) {
                    foreach ($session_list as $session_id) {
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    }
                }
                Database::delete($this->usergroup_rel_user_table, array('usergroup_id = ? AND user_id = ?' => array($usergroup_id, $user_id)));
            }
        }

        //Addding new relationships
        if (!empty($new_items)) {
            //Adding sessions
            if (!empty($session_list)) {
                foreach ($session_list as $session_id) {
                    SessionManager::suscribe_users_to_session($session_id, $new_items, null, false);
                }
            }
            foreach ($new_items as $user_id) {
                //Adding courses
                if (!empty($course_list)) {
                    foreach ($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                $params = array('user_id' => $user_id, 'usergroup_id' => $usergroup_id);
                Database::insert($this->usergroup_rel_user_table, $params);
            }
        }
    }

    function usergroup_exists($name)
    {
        $sql = "SELECT * FROM $this->table WHERE name='".Database::escape_string($name)."'";
        $res = Database::query($sql);
        return Database::num_rows($res) != 0;
    }

    function save($values, $show_query = false) {
        $values['updated_on'] = $values['created_on'] = api_get_utc_datetime();
        $values['group_type'] = isset($values['group_type']) ? intval($values['group_type']) : $this->getGroupType();

        $groupId = parent::save($values, $show_query);

        if ($groupId) {
            $this->add_user_to_group(api_get_user_id(), $groupId, $values['visibility']);
            $picture = isset($_FILES['picture']) ? $_FILES['picture'] : null;
            $picture = $this->manageFileUpload($groupId, $picture);
            if ($picture) {
                $params = array(
                    'id' => $groupId,
                    'picture' => $picture
                );
                $this->update($params);
            }
        }

        return $groupId;
    }

    function manageFileUpload($groupId, $picture) {
        if (!empty($picture['name'])) {
            return $this->update_group_picture($groupId, $picture['name'], $picture['tmp_name']);
        }
        return false;
    }

    function update($values) {
        $values['updated_on'] = api_get_utc_datetime();
        $values['group_type'] = isset($values['group_type']) ? intval($values['group_type']) : $this->getGroupType();

        if (isset($values['id'])) {
            $picture = isset($_FILES['picture']) ? $_FILES['picture'] : null;
            if (!empty($picture)) {
                $picture = $this->manageFileUpload($values['id'], $picture);
                if ($picture) {
                    $values['picture'] = $picture;
                }
            }

            if (isset($values['delete_picture'])) {
                $values['picture'] = null;
            }
        }

        parent::update($values);

        if (isset($values['delete_picture'])) {
            $this->delete_group_picture($values['id']);
        }
    }

    /**
     * Creates new group pictures in various sizes of a user, or deletes user pfotos.
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php
     * @param	int	The group id
     * @param	string $file			The common file name for the newly created pfotos. It will be checked and modified for compatibility with the file system.
     * If full name is provided, path component is ignored.
     * If an empty name is provided, then old user photos are deleted only, @see UserManager::delete_user_picture() as the prefered way for deletion.
     * @param	string		$source_file	The full system name of the image from which user photos will be created.
     * @return	string/bool	Returns the resulting common file name of created images which usually should be stored in database.
     * When an image is removed the function returns an empty string. In case of internal error or negative validation it returns FALSE.
     */
    public function update_group_picture($group_id, $file = null, $source_file = null) {

        // Validation 1.
        if (empty($group_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where photos have to be placed.
        $path_info = self::get_group_picture_path_by_id($group_id, 'system', true);

        $path = $path_info['dir'];

        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        // The old photos (if any).
        $old_file = $path_info['file'];

        // Let us delete them.
        if (!empty($old_file)) {
            if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
                $prefix = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_';
                @rename($path.'small_'.$old_file, $path.$prefix.'small_'.$old_file);
                @rename($path.'medium_'.$old_file, $path.$prefix.'medium_'.$old_file);
                @rename($path.'big_'.$old_file, $path.$prefix.'big_'.$old_file);
                @rename($path.$old_file, $path.$prefix.$old_file);
            } else {
                @unlink($path.'small_'.$old_file);
                @unlink($path.'medium_'.$old_file);
                @unlink($path.'big_'.$old_file);
                @unlink($path.$old_file);
            }
        }

        // Exit if only deletion has been requested. Return an empty picture name.
        if ($delete) {
            return '';
        }

        // Validation 2.
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file = str_replace('\\', '/', $file);
        $filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        if (!in_array($extension, $allowed_types)) {
            return false;
        }

        // This is the common name for the new photos.
        if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && !empty($old_file)) {
            $old_extension = strtolower(substr(strrchr($old_file, '.'), 1));
            $filename = in_array($old_extension, $allowed_types) ? substr($old_file, 0, -strlen($old_extension)) : $old_file;
            $filename = (substr($filename, -1) == '.') ? $filename.$extension : $filename.'.'.$extension;
        } else {
            $filename = api_replace_dangerous_char($filename);
            if (PREFIX_IMAGE_FILENAME_WITH_UID) {
                $filename = uniqid('').'_'.$filename;
            }
            // We always prefix user photos with user ids, so on setting
            // api_get_setting('split_users_upload_directory') === 'true'
            // the correspondent directories to be found successfully.
            $filename = $group_id.'_'.$filename;
        }

        // Storing the new photos in 4 versions with various sizes.
        global $app;

        /*$image->resize(
        // get original size and set width (widen) or height (heighten).
        // width or height will be set maintaining aspect ratio.
            $image->getSize()->widen( 700 )
        );*/

        //Usign the Imagine service

        $image = $app['imagine']->open($source_file);

        $options = array(
            'quality' => 90,
        );

        //$image->resize(new Imagine\Image\Box(200, 200))->save($path.'big_'.$filename);
        $image->resize($image->getSize()->widen(200))->save($path.'big_'.$filename, $options);

        $image = $app['imagine']->open($source_file);
        $image->resize(new Imagine\Image\Box(85, 85))->save($path.'medium_'.$filename, $options);

        $image = $app['imagine']->open($source_file);
        $image->resize(new Imagine\Image\Box(22, 22))->save($path.'small_'.$filename);


        /*
        $small  = self::resize_picture($source_file, 22);
        $medium = self::resize_picture($source_file, 85);
        $normal = self::resize_picture($source_file, 200);

        $big = new Image($source_file); // This is the original picture.
        $ok = $small && $small->send_image($path.'small_'.$filename)
            && $medium && $medium->send_image($path.'medium_'.$filename)
            && $normal && $normal->send_image($path.'big_'.$filename)
            && $big && $big->send_image($path.$filename);
        return $ok ? $filename : false;*/
        return $filename;
    }


    /**
     * Gets the group picture URL or path from group ID (returns an array).
     * The return format is a complete path, enabling recovery of the directory
     * with dirname() or the file with basename(). This also works for the
     * functions dealing with the user's productions, as they are located in
     * the same directory.
     * @param	integer	User ID
     * @param	string	Type of path to return (can be 'none', 'system', 'rel', 'web')
     * @param	bool	Whether we want to have the directory name returned 'as if' there was a file or not (in the case we want to know which directory to create - otherwise no file means no split subdir)
     * @param	bool	If we want that the function returns the /main/img/unknown.jpg image set it at true
     * @return	array 	Array of 2 elements: 'dir' and 'file' which contain the dir and file as the name implies if image does not exist it will return the unknow image if anonymous parameter is true if not it returns an empty er's
     */
    public function get_group_picture_path_by_id($id, $type = 'none', $preview = false, $anonymous = false) {

        switch ($type) {
            case 'system': // Base: absolute system path.
                $base = api_get_path(SYS_DATA_PATH);
                break;
            case 'rel': // Base: semi-absolute web path (no server base).
                $base = api_get_path(REL_CODE_PATH);
                break;
            case 'web': // Base: absolute web path.
                $base = api_get_path(WEB_DATA_PATH);
                break;
            case 'none':
            default: // Base: empty, the result path below will be relative.
                $base = '';
        }

        if (empty($id) || empty($type)) {
            return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
        }

        $id = intval($id);
        $group_table = Database :: get_main_table(TABLE_USERGROUP);
        $sql = "SELECT picture FROM $group_table WHERE id = ".$id;
        $res = Database::query($sql);

        if (!Database::num_rows($res)) {
            return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
        }
        $user = Database::fetch_array($res);
        $picture_filename = trim($user['picture']);
        $dir = $base.'upload/groups/'.$id.'/';
        if (empty($picture_filename) && $anonymous) {
            return array(
                'dir' => $base.'img/',
                'file' => 'unknown.jpg'
            );
        }
        return array('dir' => $dir, 'file' => $picture_filename);
    }


    /**
     * Set a parent group
     * @param group_id
     * @param parent_group, if 0, we delete the parent_group association
     * @param relation_type
     * @return true or false
     **/
    public  function set_parent_group($group_id, $parent_group_id, $relation_type = 1){
        $table = Database :: get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $group_id = intval($group_id);
        $parent_group_id = intval($parent_group_id);
        if ($parent_group_id == 0) {
            $sql = "DELETE FROM $table WHERE subgroup_id = $group_id";
        } else {
            $sql = "SELECT group_id FROM $table WHERE subgroup_id = $group_id";
            $res = Database::query($sql);
            if (Database::num_rows($res)==0) {
                $sql = "INSERT INTO $table SET group_id = $parent_group_id, subgroup_id = $group_id, relation_type = $relation_type";
            } else {
                $sql = "UPDATE $table SET group_id = $parent_group_id, relation_type = $relation_type WHERE subgroup_id = $group_id";
            }
        }
        $res = Database::query($sql);
        return($res);
    }
    /**
     * Get the parent group
     * @param group_id
     * @param relation_type
     *
     * @return int parent_group_id or false
     **/
    public function get_parent_group($group_id, $relation_type = 1) {
        $table = Database :: get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $group_id = intval($group_id);

        $sql = "SELECT group_id FROM $table WHERE subgroup_id = $group_id";
        $res = Database::query($sql);
        if (Database::num_rows($res)==0) {
            return 0;
        } else {
            $arr = Database::fetch_assoc($res);
            return $arr['group_id'];
        }
    }

    public function get_subgroups($root, $level) {
        $t_group = Database::get_main_table(TABLE_USERGROUP);
        $t_rel_group = Database :: get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $select_part = "SELECT ";
        $cond_part='';
        for ($i=1; $i <= $level; $i++) {
            $g_number=$i;
            $rg_number=$i-1;
            if ( $i == $level) {
                $select_part .= "g$i.id as id_$i, g$i.name as name_$i ";
            } else {
                $select_part .="g$i.id as id_$i, g$i.name name_$i, ";
            }
            if ($i == 1) {
                $cond_part .= "FROM $t_group g1 JOIN $t_rel_group rg0 on g1.id = rg0.subgroup_id and rg0.usergroup_id = $root ";
            } else {
                $cond_part .= "LEFT JOIN $t_rel_group rg$rg_number on g$rg_number.id = rg$rg_number.group_id ";
                $cond_part .= "LEFT JOIN $t_group g$g_number on rg$rg_number.subgroup_id = g$g_number.id ";
            }
        }
        $sql = $select_part.' '. $cond_part;
        $res = Database::query($sql);
        $toreturn = array();

        while ($item = Database::fetch_assoc($res)) {
            foreach ($item as $key => $value ){
                if ($key == 'id_1') {
                    $toreturn[$value]['name'] = $item['name_1'];
                } else {
                    $temp =  explode('_',$key);
                    $index_key = $temp[1];
                    $string_key = $temp[0];
                    $previous_key = $string_key.'_'.$index_key-1;
                    if ( $string_key == 'id' && isset($item[$key]) ) {
                        $toreturn[$item[$previous_key]]['hrms'][$index_key]['name'] = $item['name_'.$index_id];
                    }
                }
            }
        }
        return $toreturn;
    }

    public function get_parent_groups($group_id) {
        $t_rel_group = Database :: get_main_table(TABLE_USERGROUP_REL_USERGROUP);
        $max_level = 10;
        $select_part = "SELECT ";
        $cond_part='';
        for ($i=1; $i <= $max_level; $i++) {
            $g_number=$i;
            $rg_number=$i-1;
            if ( $i == $max_level) {
                $select_part .= "rg$rg_number.group_id as id_$rg_number ";
            } else {
                $select_part .="rg$rg_number.group_id as id_$rg_number, ";
            }
            if ($i == 1) {
                $cond_part .= "FROM $t_rel_group rg0 LEFT JOIN $t_rel_group rg$i on rg$rg_number.group_id = rg$i.subgroup_id ";
            } else {
                $cond_part .= " LEFT JOIN $t_rel_group rg$i on rg$rg_number.group_id = rg$i.subgroup_id ";
            }
        }
        $sql = $select_part.' '. $cond_part . "WHERE rg0.subgroup_id='$group_id'";
        $res = Database::query($sql);
        $temp_arr = Database::fetch_array($res, 'NUM');
        $toreturn = array();
        if (is_array($temp_arr)) {
            foreach ($temp_arr as $elt) {
                if (isset($elt)) {
                    $toreturn[] = $elt;
                }
            }
        }
        return $toreturn;
    }

    /**
     * Gets the tags from a given group
     * @param int	group id
     * @param bool show group links or not
     *
     */
    public  function get_group_tags($group_id, $show_tag_links = true) {
        $tag					= Database :: get_main_table(TABLE_MAIN_TAG);
        $table_group_rel_tag	= Database :: get_main_table(TABLE_USERGROUP_REL_TAG);
        $group_id 				= intval($group_id);

        $sql = "SELECT tag
                FROM $tag t INNER JOIN $table_group_rel_tag gt ON (gt.tag_id= t.id)
                WHERE gt.usergroup_id = $group_id ";
        $res = Database::query($sql);
        $tags = array();
        if (Database::num_rows($res)>0) {
            while ($row = Database::fetch_array($res,'ASSOC')) {
                $tags[] = $row;
            }
        }

        if ($show_tag_links) {
            if (is_array($tags) && count($tags)>0) {
                foreach ($tags as $tag) {
                    $tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tag['tag'].'">'.$tag['tag'].'</a>';
                }
                if (is_array($tags) && count($tags)>0) {
                    $tags= implode(', ',$tag_tmp);
                }
            } else {
                $tags = '';
            }
        }
        return $tags;
    }

    /**
     * Gets the inner join from users and group table
     *
     * @return array   Database::store_result of the result
     *
     * @author Julio Montoya
     * */
    public  function get_groups_by_user($user_id = '', $relation_type = GROUP_USER_PERMISSION_READER, $with_image = false) {
        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $tbl_group				= $this->table;
        $user_id 				= intval($user_id);

        if ($relation_type == 0) {
            $where_relation_condition = '';
        } else {
            $relation_type 			= intval($relation_type);
            $where_relation_condition = "AND gu.relation_type = $relation_type ";
        }

        $sql = "SELECT
                    g.picture,
                    g.name,
                    g.description,
                    g.id ,
                    gu.relation_type
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.usergroup_id = g.id
				WHERE g.group_type = ".self::SOCIAL_CLASS." AND
				      gu.user_id = $user_id $where_relation_condition
                ORDER BY created_on desc ";

        $result = Database::query($sql);
        $array = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($with_image) {
                    $picture = self::get_picture_group($row['id'], $row['picture'],80);
                    $img = '<img src="'.$picture['file'].'" />';
                    $row['picture'] = $img;
                }
                $array[$row['id']] = $row;
            }
        }
        return $array;
    }

    /** Gets the inner join of users and group table
     * @param int  quantity of records
     * @param bool show groups with image or not
     * @return array  with group content
     * @author Julio Montoya
     * */
    public function get_groups_by_popularity($num = 6, $with_image = true) {
        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $tbl_group				= $this->table;
        if (empty($num)) {
            $num = 6;
        } else {
            $num = intval($num);
        }
        // only show admins and readers
        $where_relation_condition = " WHERE g.group_type = ".self::SOCIAL_CLASS." AND
                                      gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";
        $sql = "SELECT DISTINCT count(user_id) as count, g.picture, g.name, g.description, g.id
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.usergroup_id = g.id $where_relation_condition
				GROUP BY g.id
				ORDER BY count DESC LIMIT $num";

        $result=Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = self::get_picture_group($row['id'], $row['picture'],80);
                $img = '<img src="'.$picture['file'].'" />';
                $row['picture'] = $img;
            }
            if (empty($row['id'])) {
                continue;
            }
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /** Gets the last groups created
     * @param int  quantity of records
     * @param bool show groups with image or not
     * @return array  with group content
     * @author Julio Montoya
     * */
    public function get_groups_by_age($num = 6, $with_image = true) {

        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $tbl_group				= $this->table;

        if (empty($num)) {
            $num = 6;
        } else {
            $num = intval($num);
        }
        $where_relation_condition = " WHERE g.group_type = ".self::SOCIAL_CLASS." AND
                                      gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."', '".GROUP_USER_PERMISSION_HRM."') ";
        $sql = "SELECT DISTINCT
                  count(user_id) as count,
                  g.picture,
                  g.name,
                  g.description,
                  g.id
                FROM $tbl_group g
                INNER JOIN $table_group_rel_user gu ON gu.usergroup_id = g.id
                $where_relation_condition
                ORDER BY created_on desc LIMIT $num ";

        $result=Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $picture = self::get_picture_group($row['id'], $row['picture'],80);
                $img = '<img src="'.$picture['file'].'" />';
                $row['picture'] = $img;
            }
            if (empty($row['id'])) {
                continue;
            }
            $array[$row['id']] = $row;
        }
        return $array;
    }

    /**
     * Gets the group's members
     * @param int group id
     * @param bool show image or not of the group
     * @param array list of relation type use constants
     * @param int from value
     * @param int limit
     * @param array image configuration, i.e array('height'=>'20px', 'size'=> '20px')
     * @return array list of users in a group
     */
    public function get_users_by_group(
        $group_id,
        $with_image = false,
        $relation_type = array(),
        $from = null,
        $limit = null,
        $image_conf = array('size' => USER_IMAGE_SIZE_MEDIUM, 'height' => 80)
    ) {
        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $tbl_user				= Database::get_main_table(TABLE_MAIN_USER);
        $group_id 				= intval($group_id);

        if (empty($group_id)){
            return array();
        }

        $limit_text = '';
        if (isset($from) && isset($limit)) {
            $from     = intval($from);
            $limit    = intval($limit);
            $limit_text = "LIMIT $from, $limit";
        }

        if (count($relation_type) == 0) {
            $where_relation_condition = '';
        } else {
            $new_relation_type = array();
            foreach($relation_type as $rel) {
                $rel = intval($rel);
                $new_relation_type[] ="'$rel'";
            }
            $relation_type = implode(',', $new_relation_type);
            if (!empty($relation_type))
                $where_relation_condition = "AND gu.relation_type IN ($relation_type) ";
        }

        $sql = "SELECT picture_uri as image, u.user_id, u.firstname, u.lastname, relation_type
    		    FROM $tbl_user u INNER JOIN $table_group_rel_user gu
    			ON (gu.user_id = u.user_id)
    			WHERE gu.usergroup_id= $group_id $where_relation_condition
    			ORDER BY relation_type, firstname $limit_text";

        $result = Database::query($sql);
        $array  = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($with_image) {
                $userInfo = api_get_user_info($row['user_id']);
                $image_path   = UserManager::get_user_picture_path_by_id($row['user_id'], 'web', false, true);
                $picture      = UserManager::get_picture_user($row['user_id'], $image_path['file'], $image_conf['height'], $image_conf['size']);
                $row['image'] = '<img src="'.$picture['file'].'"  '.$picture['style'].'  />';
                $row['user_info'] = $userInfo;
            }
            $array[$row['user_id']] = $row;
        }
        return $array;
    }

    /**
     * Gets all the members of a group no matter the relationship for more specifications use get_users_by_group
     * @param int group id
     * @return array
     */
    public function get_all_users_by_group($group_id) {
        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $tbl_user				= Database::get_main_table(TABLE_MAIN_USER);
        $group_id 				= intval($group_id);

        if (empty($group_id)){
            return array();
        }
        $sql="SELECT u.user_id, u.firstname, u.lastname, relation_type
                FROM $tbl_user u
			    INNER JOIN $table_group_rel_user gu ON (gu.user_id = u.user_id)
			    WHERE gu.usergroup_id= $group_id
			    ORDER BY relation_type, firstname";

        $result=Database::query($sql);
        $array = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $array[$row['user_id']] = $row;
        }
        return $array;
    }

    /**
     * Deletes an url and session relationship
     * @author Julio Montoya
     * @param  char  course code
     * @param  int url id
     * @return boolean true if success
     * */
    public function delete_user_rel_group($user_id, $group_id) {
        $table = $this->usergroup_rel_user_table;
        $sql= "DELETE FROM $table WHERE user_id = ".intval($user_id)." AND usergroup_id = ".intval($group_id)."  ";
        $result = Database::query($sql);
        return $result;
    }

    /**
     * Add a user into a group
     * @author Julio Montoya
     * @param  user_id
     * @param  url_id
     * @return boolean true if success
     * */
    public function add_user_to_group($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER) {
        $table_url_rel_group = $this->usergroup_rel_user_table;
        if (!empty($user_id) && !empty($group_id)) {
            $role = self::get_user_group_role($user_id, $group_id);
            if ($role == 0) {
                $sql = "INSERT INTO $table_url_rel_group
           				SET user_id = ".intval($user_id).", usergroup_id = ".intval($group_id).", relation_type = ".intval($relation_type);
                $result = Database::query($sql);
            } elseif ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
                //if somebody already invited me I can be added
                self::update_user_role($user_id, $group_id, GROUP_USER_PERMISSION_READER);
            }
        }
        return $result;
    }

    /**
     * Gets the relationship between a group and a User
     * @author Julio Montoya
     * @param int user id
     * @param int group_id
     * @return int 0 if there are not relationship otherwise returns the user group
     * */
    public function get_user_group_role($user_id, $group_id) {
        $table_group_rel_user= $this->usergroup_rel_user_table;
        $return_value = 0;
        if (!empty($user_id) && !empty($group_id)) {
            $sql	= "SELECT relation_type FROM $table_group_rel_user WHERE usergroup_id = ".intval($group_id)." AND  user_id = ".intval($user_id)." ";
            $result = Database::query($sql);
            if (Database::num_rows($result)>0) {
                $row = Database::fetch_array($result,'ASSOC');
                $return_value = $row['relation_type'];
            }
        }
        return $return_value;
    }

    /**
     * Add a group of users into a group of URLs
     * @author Julio Montoya
     * @param  array of user_ids
     * @param  array of url_ids
     * */
    public function add_users_to_groups($user_list, $group_list, $relation_type = GROUP_USER_PERMISSION_READER) {
        $table_url_rel_group = $this->usergroup_rel_user_table;
        $result_array = array();
        $relation_type = intval($relation_type);

        if (is_array($user_list) && is_array($group_list)) {
            foreach ($group_list as $group_id) {
                foreach ($user_list as $user_id) {
                    $role = self::get_user_group_role($user_id,$group_id);
                    if ($role == 0) {
                        $sql = "INSERT INTO $table_url_rel_group
		               			SET user_id = ".intval($user_id).", usergroup_id = ".intval($group_id).", relation_type = ".intval($relation_type);

                        $result = Database::query($sql);
                        if ($result)
                            $result_array[$group_id][$user_id]=1;
                        else
                            $result_array[$group_id][$user_id]=0;
                    }
                }
            }
        }
        return 	$result_array;
    }

    /**
     * Deletes a group  and user relationship
     * @author Julio Montoya
     * @param int user id
     * @param int relation type (optional)
     * @return boolean true if success
     * */
    public function delete_users($group_id, $relation_type = '') {
        $table_	= $this->usergroup_rel_user_table;
        $condition_relation = "";
        if (!empty($relation_type)) {
            $relation_type = intval($relation_type);
            $condition_relation = " AND relation_type = '$relation_type'";
        }
        $sql	= "DELETE FROM $table_ WHERE usergroup_id = ".intval($group_id).$condition_relation;
        $result = Database::query($sql);
        return $result;
    }

    /**
     * Updates the group_rel_user table  with a given user and group ids
     * @author Julio Montoya
     * @param int  user id
     * @param int group id
     * @param int relation type
     * */
    public function update_user_role($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER) {
        $table_group_rel_user = $this->usergroup_rel_user_table;
        $group_id = intval($group_id);
        $user_id = intval($user_id);

        $sql = "UPDATE $table_group_rel_user
   				SET relation_type = ".intval($relation_type)." WHERE user_id = $user_id AND usergroup_id = $group_id" ;
        Database::query($sql);
    }


    public function get_group_admin_list($user_id, $group_id) {
        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $group_id = intval($group_id);
        $user_id = intval($user_id);

        $sql = "SELECT user_id FROM  $table_group_rel_user WHERE
   				relation_type = ".GROUP_USER_PERMISSION_ADMIN." AND user_id = $user_id AND group_id = $group_id" ;
        Database::query($sql);
    }


    public function get_all_group_tags($tag, $from=0, $number_of_items=10) {
        // database table definition

        $group_table 			= $this->table;
        $table_tag				= Database::get_main_table(TABLE_MAIN_TAG);
        $table_group_tag_values	= Database::get_main_table(TABLE_USERGROUP_REL_TAG);

        $field_id = 5;
        $tag = Database::escape_string($tag);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        // all the information of the field
        $sql = "SELECT g.id, g.name, g.description, g.picture
                FROM $table_tag t
                INNER JOIN $table_group_tag_values tv ON (tv.tag_id=t.id)
                INNER JOIN $group_table g ON(tv.usergroup_id =g.id)
				WHERE tag LIKE '$tag%' AND field_id= $field_id ORDER BY tag";

        $sql .= " LIMIT $from,$number_of_items";

        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result)> 0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
                $return[$row['id']] = $row;
            }
        }

        $keyword = $tag;
        $sql = "SELECT  g.id, g.name, g.description, g.url, g.picture FROM $group_table g";

        //@todo implement groups + multiple urls

        /*
        global $_configuration;
        if ($_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
            $access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
        }*/

        //@todo implement visibility

        if (isset ($keyword)) {
            $sql .= " WHERE (g.name LIKE '%".$keyword."%' OR g.description LIKE '%".$keyword."%'  OR  g.url LIKE '%".$keyword."%' )";
        }

        $direction = 'ASC';
        if (!in_array($direction, array('ASC','DESC'))) {
            $direction = 'ASC';
        }

        //$column = intval($column);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        //$sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        if (Database::num_rows($res)> 0) {
            while ($row = Database::fetch_array($res,'ASSOC')) {
                if (!in_array($row['id'], $return)) {
                    $return[$row['id']] = $row;
                }
            }
        }
        return $return;
    }

    public function delete_group_picture($group_id) {
        return self::update_group_picture($group_id);
    }

    public function is_group_admin($group_id, $user_id = 0) {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role	= $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN))) {
            return true;
        } else {
            return false;
        }
    }

    public function is_group_moderator($group_id, $user_id = 0) {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role	= $this->get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
            return true;
        } else {
            return false;
        }
    }

    public function is_group_member($group_id, $user_id = 0) {

        if (api_is_platform_admin()) {
           return true;
        }
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        $user_role	= self::get_user_group_role($user_id, $group_id);
        if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR, GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_HRM))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Shows the left column of the group page
     * @param int group id
     * @param int user id
     *
     */
    public function show_group_column_information($group_id, $user_id, $show = '') {
        global $relation_group_title, $my_group_role;
        $html = '';

        $group_info 	= $this->get($group_id);

        //my relation with the group is set here
        $my_group_role = self::get_user_group_role($user_id, $group_id);

        //@todo this must be move to default.css for dev use only
        $html .= '<style>
				#group_members { width:270px; height:300px; overflow-x:none; overflow-y: auto;}
				.group_member_item { width:100px; height:130px; float:left; margin:5px 5px 15px 5px; }
				.group_member_picture { display:block;
					margin:0;
					overflow:hidden; };
		</style>';

        //Loading group permission

        $links = '';
        switch ($my_group_role) {
            case GROUP_USER_PERMISSION_READER:
                // I'm just a reader
                $relation_group_title = get_lang('IAmAReader');
                $links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                $links .=  '<li><a href="groups.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.	Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_ADMIN:
                $relation_group_title = get_lang('IAmAnAdmin');
                $links .=  '<li><a href="group_edit.php?id='.$group_id.'">'.			Display::return_icon('group_edit.png', get_lang('EditGroup'), array('hspace'=>'6')).'<span class="'.($show=='group_edit'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('EditGroup').'</span></a></li>';
                $links .=  '<li><a href="group_waiting_list.php?id='.$group_id.'">'.	Display::return_icon('waiting_list.png', get_lang('WaitingList'), array('hspace'=>'6')).'<span class="'.($show=='waiting_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('WaitingList').'</span></a></li>';
                $links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                $links .=  '<li><a href="groups.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.	Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION:
//				$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('YouHaveBeenInvitedJoinNow'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouHaveBeenInvitedJoinNow').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
                $relation_group_title =  get_lang('WaitingForAdminResponse');
                break;
            case GROUP_USER_PERMISSION_MODERATOR:
                $relation_group_title = get_lang('IAmAModerator');
                //$links .=  '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
                //$links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
                //$links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
                if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
                    $links .=  '<li><a href="group_waiting_list.php?id='.$group_id.'">'.	Display::return_icon('waiting_list.png', get_lang('WaitingList'), array('hspace'=>'6')).'<span class="'.($show=='waiting_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('WaitingList').'</span></a></li>';
                }
                $links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                $links .=  '<li><a href="groups.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.	Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                break;
            case GROUP_USER_PERMISSION_HRM:
                $relation_group_title = get_lang('IAmAHRM');
                $links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="ajax" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
                $links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
                $links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
                $links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
                $links .=  '<li><a href="groups.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.	Display::return_icon('delete_data.gif', get_lang('LeaveGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
                break;
            default:
                //$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('JoinGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('JoinGroup').'</a></span></li>';
                break;
        }

        if (!empty($links)) {
            $html .= '<div class="well sidebar-nav"><ul class="nav nav-list">';
            if (!empty($group_info['description'])) {
                $html .= Display::tag('li', Security::remove_XSS($group_info['description'], STUDENT, true), array('class'=>'group_description'));
            }
            $html .= $links;
            $html .= '</ul></div>';
        }
        return $html;
    }

    function delete_topic($group_id, $topic_id) {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $topic_id = intval($topic_id);
        $group_id = intval($group_id);
        $sql = "UPDATE $table_message SET msg_status=3 WHERE group_id = $group_id AND (id = '$topic_id' OR parent_id = $topic_id) ";
        Database::query($sql);
    }

    public function get_groups_by_user_count($user_id = '', $relation_type = GROUP_USER_PERMISSION_READER, $with_image = false) {
        $table_group_rel_user	= $this->usergroup_rel_user_table;
        $tbl_group				= $this->table;
        $user_id 				= intval($user_id);

        if ($relation_type == 0) {
            $where_relation_condition = '';
        } else {
            $relation_type 			= intval($relation_type);
            $where_relation_condition = "AND gu.relation_type = $relation_type ";
        }

        $sql = "SELECT count(g.id) as count
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.usergroup_id = g.id WHERE gu.user_id = $user_id $where_relation_condition ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result, 'ASSOC');
            return $row['count'];
        }
        return 0;
    }

    /**
     * Gets the current group image
     * @param string group id
     * @param string picture group name
     * @param string height
     * @param string picture size it can be small_,  medium_  or  big_
     * @param string style css
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
    public function get_picture_group($id, $picture_file, $height, $size_picture = GROUP_IMAGE_SIZE_MEDIUM , $style = '') {
        $picture = array();
        $picture['style'] = $style;
        if ($picture_file == 'unknown.jpg') {
            $picture['file'] = api_get_path(WEB_IMG_PATH).$picture_file;
            return $picture;
        }

        switch ($size_picture) {
            case GROUP_IMAGE_SIZE_ORIGINAL :
                $size_picture = '';
                break;
            case GROUP_IMAGE_SIZE_BIG :
                $size_picture = 'big_';
                break;
            case GROUP_IMAGE_SIZE_MEDIUM :
                $size_picture = 'medium_';
                break;
            case GROUP_IMAGE_SIZE_SMALL :
                $size_picture = 'small_';
                break;
            default:
                $size_picture = 'medium_';
        }

        $image_array_sys = $this->get_group_picture_path_by_id($id, 'system', false, true);
        $image_array = $this->get_group_picture_path_by_id($id, 'web', false, true);
        $file = $image_array_sys['dir'].$size_picture.$picture_file;
        if (file_exists($file)) {
            $picture['file'] = $image_array['dir'].$size_picture.$picture_file;
            $picture['style'] = '';
            if ($height > 0) {
                $dimension = api_getimagesize($picture['file']);
                $margin = (($height - $dimension['width']) / 2);
                //@ todo the padding-top should not be here
                $picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension['width'].'px; height:'.$dimension['height'].';" ';
            }
        } else {
            $file = $image_array_sys['dir'].$picture_file;
            if (file_exists($file) && !is_dir($file)) {
                $picture['file'] = $image_array['dir'].$picture_file;
            } else {
                $picture['file'] = api_get_path(WEB_IMG_PATH).'unknown_group.png';
            }
        }
        return $picture;
    }

    public function getGroupStatusList() {
        $status = array();
        $status[GROUP_PERMISSION_OPEN] = get_lang('Open');
        $status[GROUP_PERMISSION_CLOSED] = get_lang('Closed');
        return $status;
    }

    public function setGroupType($type) {
        $this->groupType = (int)$type;
    }

    public function getGroupType() {
        return $this->groupType;
    }

    public function setForm($form, $type = 'add', $data = array()) {
        switch ($type) {
            case 'add':
                $header = get_lang('Add');
                break;
            case 'edit':
                $header = get_lang('Edit');
                break;
        }

        $form->addElement('header', $header);

        //Name
        $form->addElement('text', 'name', get_lang('Name'), array('class'=>'span5', 'maxlength'=>255));
        $form->applyFilter('name', 'html_filter');
        $form->applyFilter('name', 'trim');

        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('name', '', 'maxlength', 255);

        // Description
        $form->addElement('textarea', 'description', get_lang('Description'), array('class'=>'span5', 'cols'=>58, 'onKeyDown' => "maxCharForTextarea(this);", 'onKeyUp' => "maxCharForTextarea(this);"));
        $form->applyFilter('description', 'html_filter');
        $form->applyFilter('description', 'trim');

        if ($this->getGroupType() == self::NORMAL_CLASS) {
            $form->addElement('checkbox', 'group_type', null, get_lang('SocialGroup'), array('id' => "advanced_parameters"));

        }
        $display = "none";
        if ($type == 'edit' && $data['group_type'] == self::SOCIAL_CLASS) {
            $display = "block";
        }


        $form->addElement('html','<div id="options" style="display:'.$display.'">');


        // url
        $form->addElement('text', 'url', get_lang('URL'), array('class'=>'span5'));
        $form->applyFilter('url', 'html_filter');
        $form->applyFilter('url', 'trim');

        // Picture
        $allowed_picture_types = $this->getAllowedPictureExtensions();
        $form->addElement('file', 'picture', get_lang('AddPicture'));
        $form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
        if (isset($data['picture']) && strlen($data['picture']) > 0) {
            $picture = $this->get_picture_group($data['id'], $data['picture'], 80);
            $img = '<img src="'.$picture['file'].'" />';
            $form->addElement('label', null, $img);
            $form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));

        }
        $form->addElement('select', 'visibility', get_lang('GroupPermissions'), $this->getGroupStatusList());
        $form->addElement('html','</div>');


        $form->setRequiredNote('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>');

        $form->addElement('style_submit_button', 'submit', $header, 'class="add"');
    }

    public function getAllowedPictureExtensions() {
        return $allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
    }

    /**
     * Gets a list of all group
     * @param id of a group not to include (i.e. to exclude)
     * @return array : id => name
     **/
    public static function get_groups_list($without_this_one = NULL ) {
        $where='';
        if ( isset($without_this_one) && (intval($without_this_one) == $without_this_one) ) {
            $where = "WHERE id <> $without_this_one";
        }
        $table	= Database :: get_main_table(TABLE_USERGROUP);
        $sql = "SELECT id, name FROM $table $where order by name";
        $res = Database::query($sql);
        $list = array ();
        while ($item = Database::fetch_assoc($res)) {
            $list[$item['id']] = $item['name'];
        }
        return $list;
    }

}
