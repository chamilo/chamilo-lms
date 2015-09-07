<?php
/* For licensing terms, see /license.txt */

/**
 * Class AddCourse
 */
class AddCourse
{
    /**
     * Defines the four needed keys to create a course based on several parameters.
     * @param string    The code you want for this course
     * @param string    Prefix added for ALL keys
     * @param string    Prefix added for databases only
     * @param string    Prefix added for paths only
     * @param bool      Add unique prefix
     * @param bool      Use code-independent keys
     * @return array    An array with the needed keys ['currentCourseCode'], ['currentCourseId'], ['currentCourseDbName'], ['currentCourseRepository']
     * @todo Eliminate the global variables.
     * @assert (null) === false
     */
    public static function define_course_keys($wanted_code, $prefix_for_all = '', $prefix_for_base_name = '', $prefix_for_path = '', $add_unique_prefix = false, $use_code_indepedent_keys = true)
    {
        global $prefixAntiNumber, $_configuration;
        $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $wanted_code = CourseManager::generate_course_code($wanted_code);
        $keys_course_code = $wanted_code;
        if (!$use_code_indepedent_keys) {
            $wanted_code = '';
        }

        if ($add_unique_prefix) {
            $unique_prefix = substr(md5(uniqid(rand())), 0, 10);
        } else {
            $unique_prefix = '';
        }

        $keys = array();
        $final_suffix = array('CourseId' => '', 'CourseDb' => '', 'CourseDir' => '');
        $limit_numb_try = 100;
        $keys_are_unique = false;
        $try_new_fsc_id = $try_new_fsc_db = $try_new_fsc_dir = 0;

        while (!$keys_are_unique) {

            $keys_course_id = $prefix_for_all . $unique_prefix . $wanted_code . $final_suffix['CourseId'];
            //$keys_course_db_name = $prefix_for_base_name . $unique_prefix . strtoupper($keys_course_id) . $final_suffix['CourseDb'];
            $keys_course_repository = $prefix_for_path . $unique_prefix . $wanted_code . $final_suffix['CourseDir'];
            $keys_are_unique = true;

            // Check whether they are unique.
            $query = "SELECT 1 FROM ".$course_table." WHERE code='".$keys_course_id."' LIMIT 0,1";
            $result = Database::query($query);

            if (Database::num_rows($result)) {
                $keys_are_unique = false;
                $try_new_fsc_id ++;
                $final_suffix['CourseId'] = substr(md5(uniqid(rand())), 0, 4);
            }
            if (file_exists(api_get_path(SYS_COURSE_PATH).$keys_course_repository)) {
                $keys_are_unique = false;
                $try_new_fsc_dir ++;
                $final_suffix['CourseDir'] = substr(md5(uniqid(rand())), 0, 4);
            }

            if (($try_new_fsc_id + $try_new_fsc_db + $try_new_fsc_dir) > $limit_numb_try) {
                return $keys;
            }
        }

        $keys['currentCourseCode'] = $keys_course_code;
        $keys['currentCourseId'] = $keys_course_id;
        $keys['currentCourseRepository'] = $keys_course_repository;

        return $keys;
    }

    /**
     * Initializes a file repository for a newly created course.
     * @param string Course repository
     * @param string Course code
     * @return int 0
     * @assert (null,null) === false
     */
    public static function prepare_course_repository($course_repository, $course_code)
    {
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();
        $htmlpage = "<!DOCTYPE html>\n<html lang=\"en\">\n  <head>\n    <meta charset=\"utf-8\">\n    <title>Not authorized</title>\n  </head>\n  <body>\n  </body>\n</html>";
        $cp = api_get_path(SYS_COURSE_PATH) . $course_repository;

        //Creating document folder
        mkdir($cp, $perm);
        mkdir($cp . '/document', $perm);
        $cpt = $cp . '/document/index.html';
        $fd = fopen($cpt, 'w');
        fwrite($fd, $htmlpage);
        fclose($fd);

        /*
        @chmod($cpt, $perm_file);
        @copy($cpt, $cp . '/document/index.html');
        mkdir($cp . '/document/images', $perm);
        @copy($cpt, $cp . '/document/images/index.html');
        mkdir($cp . '/document/images/gallery/', $perm);
        @copy($cpt, $cp . '/document/images/gallery/index.html');
        mkdir($cp . '/document/shared_folder/', $perm);
        @copy($cpt, $cp . '/document/shared_folder/index.html');
        mkdir($cp . '/document/audio', $perm);
        @copy($cpt, $cp . '/document/audio/index.html');
        mkdir($cp . '/document/flash', $perm);
        @copy($cpt, $cp . '/document/flash/index.html');
        mkdir($cp . '/document/video', $perm);
        @copy($cpt, $cp . '/document/video/index.html');    */

        //Creatind dropbox folder
        mkdir($cp . '/dropbox', $perm);
        $cpt = $cp . '/dropbox/index.html';
        $fd = fopen($cpt, 'w');
        fwrite($fd, $htmlpage);
        fclose($fd);
        @chmod($cpt, $perm_file);
        mkdir($cp . '/group', $perm);
        @copy($cpt, $cp . '/group/index.html');
        mkdir($cp . '/page', $perm);
        @copy($cpt, $cp . '/page/index.html');
        mkdir($cp . '/scorm', $perm);
        @copy($cpt, $cp . '/scorm/index.html');
        mkdir($cp . '/upload', $perm);
        @copy($cpt, $cp . '/upload/index.html');
        mkdir($cp . '/upload/forum', $perm);
        @copy($cpt, $cp . '/upload/forum/index.html');
        mkdir($cp . '/upload/forum/images', $perm);
        @copy($cpt, $cp . '/upload/forum/images/index.html');
        mkdir($cp . '/upload/test', $perm);
        @copy($cpt, $cp . '/upload/test/index.html');
        mkdir($cp . '/upload/blog', $perm);
        @copy($cpt, $cp . '/upload/blog/index.html');
        mkdir($cp . '/upload/learning_path', $perm);
        @copy($cpt, $cp . '/upload/learning_path/index.html');
        mkdir($cp . '/upload/learning_path/images', $perm);
        @copy($cpt, $cp . '/upload/learning_path/images/index.html');
        mkdir($cp . '/upload/calendar', $perm);
        @copy($cpt, $cp . '/upload/calendar/index.html');
        mkdir($cp . '/upload/calendar/images', $perm);
        @copy($cpt, $cp . '/upload/calendar/images/index.html');
        mkdir($cp . '/work', $perm);
        @copy($cpt, $cp . '/work/index.html');
        mkdir($cp . '/upload/announcements', $perm);
        @copy($cpt, $cp . '/upload/announcements/index.html');
        mkdir($cp . '/upload/announcements/images', $perm);
        @copy($cpt, $cp . '/upload/announcements/images/index.html');

        //Oral expression question type
        mkdir($cp . '/exercises', $perm);
        @copy($cpt, $cp . '/exercises/index.html');

        // Create .htaccess in the dropbox directory.
        $fp = fopen($cp . '/dropbox/.htaccess', 'w');
        fwrite(
            $fp,
            "AuthName AllowLocalAccess
                       AuthType Basic

                       order deny,allow
                       deny from all

                       php_flag zlib.output_compression off"
        );
        fclose($fp);

        // Build index.php of the course.
        /*$fd = fopen($cp . '/index.php', 'w');

        // str_replace() removes \r that cause squares to appear at the end of each line
        //@todo fix the harcoded include
        $string = str_replace(
            "\r",
            "",
            "<?" . "php
        \$cidReq = \"$course_code\";
        \$dbname = \"$course_code\";

        include(\"" . api_get_path(SYS_CODE_PATH) . "course_home/course_home.php\");
        ?>"
        );
        fwrite($fd, $string);
        @chmod($cp . '/index.php', $perm_file);*/
        return 0;
    }

    /**
     * Gets an array with all the course tables (deprecated?)
     * @return array
     * @assert (null) !== null
     */
    public static function get_course_tables()
    {
        $tables = array();

        $tables[] = 'tool';
        $tables[] = 'tool_intro';
        $tables[] = 'group_info';
        $tables[] = 'group_category';
        $tables[] = 'group_rel_user';
        $tables[] = 'group_rel_tutor';
        $tables[] = 'item_property';
        $tables[] = 'userinfo_content';
        $tables[] = 'userinfo_def';
        $tables[] = 'course_description';
        $tables[] = 'calendar_event';
        $tables[] = 'calendar_event_repeat';
        $tables[] = 'calendar_event_repeat_not';
        $tables[] = 'calendar_event_attachment';
        $tables[] = 'announcement';
        $tables[] = 'announcement_attachment';
        $tables[] = 'resource';
        $tables[] = 'student_publication';
        $tables[] = 'student_publication_assignment';
        $tables[] = 'document';
        $tables[] = 'forum_category';
        $tables[] = 'forum_forum';
        $tables[] = 'forum_thread';
        $tables[] = 'forum_post';
        $tables[] = 'forum_mailcue';
        $tables[] = 'forum_attachment';
        $tables[] = 'forum_notification';
        $tables[] = 'forum_thread_qualify';
        $tables[] = 'forum_thread_qualify_log';
        $tables[] = 'link';
        $tables[] = 'link_category';
        $tables[] = 'online_connected';
        $tables[] = 'online_link';
        $tables[] = 'chat_connected';
        $tables[] = 'quiz';
        $tables[] = 'quiz_rel_question';
        $tables[] = 'quiz_question';
        $tables[] = 'quiz_answer';
        $tables[] = 'quiz_question_option';
        $tables[] = 'quiz_question_category';
        $tables[] = 'quiz_question_rel_category';
        $tables[] = 'dropbox_post';
        $tables[] = 'dropbox_file';
        $tables[] = 'dropbox_person';
        $tables[] = 'dropbox_category';
        $tables[] = 'dropbox_feedback';
        $tables[] = 'lp';
        $tables[] = 'lp_item';
        $tables[] = 'lp_view';
        $tables[] = 'lp_item_view';
        $tables[] = 'lp_iv_interaction';
        $tables[] = 'lp_iv_objective';
        $tables[] = 'blog';
        $tables[] = 'blog_comment';
        $tables[] = 'blog_post';
        $tables[] = 'blog_rating';
        $tables[] = 'blog_rel_user';
        $tables[] = 'blog_task';
        $tables[] = 'blog_task_rel_user';
        $tables[] = 'blog_attachment';
        $tables[] = 'permission_group';
        $tables[] = 'permission_user';
        $tables[] = 'permission_task';
        $tables[] = 'role';
        $tables[] = 'role_group';
        $tables[] = 'role_permissions';
        $tables[] = 'role_user';
        $tables[] = 'survey';
        $tables[] = 'survey_question';
        $tables[] = 'survey_question_option';
        $tables[] = 'survey_invitation';
        $tables[] = 'survey_answer';
        $tables[] = 'survey_group';
        $tables[] = 'wiki';
        $tables[] = 'wiki_conf';
        $tables[] = 'wiki_discuss';
        $tables[] = 'wiki_mailcue';
        $tables[] = 'course_setting';
        $tables[] = 'glossary';
        $tables[] = 'notebook';
        $tables[] = 'attendance';
        $tables[] = 'attendance_sheet';
        $tables[] = 'attendance_calendar';
        $tables[] = 'attendance_result';
        $tables[] = 'attendance_sheet_log';
        $tables[] = 'thematic';
        $tables[] = 'thematic_plan';
        $tables[] = 'thematic_advance';

        return $tables;
    }

    /**
     * Executed only before create_course_tables()
     * @return void
     * @assert (null) === null
     */
    public static function drop_course_tables()
    {
        $list = self::get_course_tables();
        foreach ($list as $table) {
            $sql = "DROP TABLE IF EXISTS " . DB_COURSE_PREFIX . $table;
            Database::query($sql);
        }
    }


    /**
     * Returns a list of all files in the given course directory. The requested
     * directory will be checked against a "checker" directory to avoid access to
     * protected/unauthorized files
     * @param string Complete path to directory we want to list
     * @param array A list of files to which we want to add the files found
     * @param string Type of base directory from which we want to recover the files
     * @return array
     * @assert (null,null,null) === false
     * @assert ('abc',array(),'') === array()
     */
    public static function browse_folders($path, $files, $media)
    {
        if ($media == 'images') {
            $code_path = api_get_path(
                    SYS_CODE_PATH
                ) . 'default_course_document/images/';
        }
        if ($media == 'audio') {
            $code_path = api_get_path(
                    SYS_CODE_PATH
                ) . 'default_course_document/audio/';
        }
        if ($media == 'flash') {
            $code_path = api_get_path(
                    SYS_CODE_PATH
                ) . 'default_course_document/flash/';
        }
        if ($media == 'video') {
            $code_path = api_get_path(
                    SYS_CODE_PATH
                ) . 'default_course_document/video/';
        }
        if ($media == 'certificates') {
            $code_path = api_get_path(
                    SYS_CODE_PATH
                ) . 'default_course_document/certificates/';
        }
        if (is_dir($path)) {
            $handle = opendir($path);
            while (false !== ($file = readdir($handle))) {
                if (is_dir($path . $file) && strpos($file, '.') !== 0) {
                    $files[]['dir'] = str_replace(
                        $code_path,
                        '',
                        $path . $file . '/'
                    );
                    $files = self::browse_folders(
                        $path . $file . '/',
                        $files,
                        $media
                    );
                } elseif (is_file($path . $file) && strpos($file, '.') !== 0) {
                    $files[]['file'] = str_replace(
                        $code_path,
                        '',
                        $path . $file
                    );
                }
            }
        }
        return $files;
    }

    /**
     * Sorts pictures by type (used?)
     * @param array List of files (sthg like array(0=>array('png'=>1)))
     * @param string File type
     * @return array The received array without files not matching type
     * @assert (array(),null) === array()
     */
    public static function sort_pictures($files, $type)
    {
        $pictures = array();
        foreach ($files as $key => $value) {
            if (isset($value[$type]) && $value[$type] != '') {
                $pictures[][$type] = $value[$type];
            }
        }
        return $pictures;
    }

    /**
     * Function to convert a string from the language files to a string ready
     * to insert into the database (escapes single quotes)
     * @author Bart Mollet (bart.mollet@hogent.be)
     * @param string $string The string to convert
     * @return string The string converted to insert into the database
     * @assert ('a\'b') === 'ab'
     */
    public static function lang2db($string)
    {
        $string = str_replace("\\'", "'", $string);
        $string = Database::escape_string($string);
        return $string;
    }

    /**
     * Fills the course database with some required content and example content.
     * @param int Course (int) ID
     * @param string Course directory name (e.g. 'ABC')
     * @param string Language used for content (e.g. 'spanish')
     * @param bool Whether to fill the course with example content
     * @return bool False on error, true otherwise
     * @version 1.2
     * @assert (null, '', '', null) === false
     * @assert (1, 'ABC', null, null) === false
     * @assert (1, 'TEST', 'spanish', true) === true
     */
    public static function fill_db_course(
        $course_id,
        $course_repository,
        $language,
        $fill_with_exemplary_content = null

    ) {
        if (is_null($fill_with_exemplary_content)) {
            $fill_with_exemplary_content = api_get_setting(
                    'example_material_course_creation'
                ) != 'false';
        }
        global $_configuration;
        $course_id = intval($course_id);

        if (empty($course_id)) {
            return false;
        }

        $courseInfo = api_get_course_info_by_id($course_id);
        $now = api_get_utc_datetime(time());

        $tbl_course_homepage = Database::get_course_table(TABLE_TOOL_LIST);
        $TABLEINTROS = Database::get_course_table(TABLE_TOOL_INTRO);
        $TABLEGROUPCATEGORIES = Database::get_course_table(
            TABLE_GROUP_CATEGORY
        );
        $TABLEITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $TABLETOOLAGENDA = Database::get_course_table(TABLE_AGENDA);
        $TABLETOOLANNOUNCEMENTS = Database::get_course_table(
            TABLE_ANNOUNCEMENT
        );
        $TABLETOOLDOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
        $TABLETOOLLINK = Database::get_course_table(TABLE_LINK);
        $TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);
        $TABLEQUIZQUESTION = Database::get_course_table(
            TABLE_QUIZ_TEST_QUESTION
        );
        $TABLEQUIZQUESTIONLIST = Database::get_course_table(
            TABLE_QUIZ_QUESTION
        );
        $TABLEQUIZANSWERSLIST = Database::get_course_table(TABLE_QUIZ_ANSWER);
        $TABLESETTING = Database::get_course_table(TABLE_COURSE_SETTING);

        $TABLEFORUMCATEGORIES = Database::get_course_table(
            TABLE_FORUM_CATEGORY
        );
        $TABLEFORUMS = Database::get_course_table(TABLE_FORUM);
        $TABLEFORUMTHREADS = Database::get_course_table(TABLE_FORUM_THREAD);
        $TABLEFORUMPOSTS = Database::get_course_table(TABLE_FORUM_POST);
        $TABLEGRADEBOOK = Database::get_main_table(
            TABLE_MAIN_GRADEBOOK_CATEGORY
        );
        $TABLEGRADEBOOKLINK = Database::get_main_table(
            TABLE_MAIN_GRADEBOOK_LINK
        );
        $TABLEGRADEBOOKCERT = Database::get_main_table(
            TABLE_MAIN_GRADEBOOK_CERTIFICATE
        );

        include_once api_get_path(SYS_CODE_PATH) . 'lang/english/trad4all.inc.php';
        $file_to_include = api_get_path(SYS_CODE_PATH) . 'lang/' . $language . '/trad4all.inc.php';

        if (file_exists($file_to_include)) {
            include_once $file_to_include;
        }

        $visible_for_all = 1;
        $visible_for_course_admin = 0;
        $visible_for_platform_admin = 2;

        /*    Course tools  */

        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 1, '" . TOOL_COURSE_DESCRIPTION . "','course_description/','info.gif','" . self::string2binary(
                api_get_setting(
                    'course_create_active_tools',
                    'course_description'
                )
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 2, '" . TOOL_CALENDAR_EVENT . "','calendar/agenda.php','agenda.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'agenda')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage  (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 3, '" . TOOL_DOCUMENT . "','document/document.php','folder_document.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'documents')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 4, '" . TOOL_LEARNPATH . "','newscorm/lp_controller.php','scorms.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'learning_path')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
             VALUES ($course_id, 5, '" . TOOL_LINK . "','link/link.php','links.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'links')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage  (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES  ($course_id, 6, '" . TOOL_QUIZ . "','exercice/exercice.php','quiz.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'quiz')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 7, '" . TOOL_ANNOUNCEMENT . "','announcements/announcements.php','valves.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'announcements')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 8, '" . TOOL_FORUM . "','forum/index.php','forum.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'forums')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 9, '" . TOOL_DROPBOX . "','dropbox/index.php','dropbox.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'dropbox')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 10, '" . TOOL_USER . "','user/user.php','members.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'users')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 11, '" . TOOL_GROUP . "','group/group.php','group.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'groups')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 12, '" . TOOL_CHAT . "','chat/chat.php','chat.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'chat')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 13, '" . TOOL_STUDENTPUBLICATION . "','work/work.php','works.gif','" . self::string2binary(
                api_get_setting(
                    'course_create_active_tools',
                    'student_publications'
                )
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 14, '" . TOOL_SURVEY . "','survey/survey_list.php','survey.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'survey')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 15, '" . TOOL_WIKI . "','wiki/index.php','wiki.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'wiki')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 16, '" . TOOL_GRADEBOOK . "','gradebook/index.php','gradebook.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'gradebook')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 17, '" . TOOL_GLOSSARY . "','glossary/index.php','glossary.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'glossary')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 18, '" . TOOL_NOTEBOOK . "','notebook/index.php','notebook.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'notebook')
            ) . "','0','squaregrey.gif','NO','_self','interaction','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 19, '" . TOOL_ATTENDANCE . "','attendance/index.php','attendance.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'attendances')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 20, '" . TOOL_COURSE_PROGRESS . "','course_progress/index.php','course_progress.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'course_progress')
            ) . "','0','squaregrey.gif','NO','_self','authoring','0')"
        );

        if (api_get_setting('service_visio', 'active') == 'true') {
            $mycheck = api_get_setting('service_visio', 'visio_host');
            if (!empty($mycheck)) {
                Database::query(
                    "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
                     VALUES ($course_id, 21, '" . TOOL_VISIO_CONFERENCE . "','conference/index.php?type=conference','visio_meeting.gif','1','0','squaregrey.gif','NO','_self','interaction','0')"
                );
                Database::query(
                    "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
                     VALUES ($course_id, 22, '" . TOOL_VISIO_CLASSROOM . "','conference/index.php?type=classroom','visio.gif','1','0','squaregrey.gif','NO','_self','authoring','0')"
                );
            }
        }

        if (api_get_setting('search_enabled') == 'true') {
            Database::query(
                "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
                VALUES ($course_id, 23, '" . TOOL_SEARCH . "','search/','info.gif','" . self::string2binary(
                    api_get_setting(
                        'course_create_active_tools',
                        'enable_search'
                    )
                ) . "','0','search.gif','NO','_self','authoring','0')"
            );
        }

        $sql = "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
                VALUES ($course_id, 24,'" . TOOL_BLOGS . "','blog/blog_admin.php','blog_admin.gif','" . self::string2binary(
                api_get_setting('course_create_active_tools', 'blogs')
            ) . "','1','squaregrey.gif','NO','_self','admin','0')";
        Database::query($sql);

        /*  Course homepage tools for course admin only  */
        Database::query(
            "INSERT INTO $tbl_course_homepage  (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 25, '" . TOOL_TRACKING . "','tracking/courseLog.php','statistics.gif','$visible_for_course_admin','1','', 'NO','_self','admin','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 26, '" . TOOL_COURSE_SETTING . "','course_info/infocours.php','reference.gif','$visible_for_course_admin','1','', 'NO','_self','admin','0')"
        );
        Database::query(
            "INSERT INTO $tbl_course_homepage (c_id, id, name, link, image, visibility, admin, address, added_tool, target, category, session_id)
            VALUES ($course_id, 27, '" . TOOL_COURSE_MAINTENANCE . "','course_info/maintenance.php','backup.gif','$visible_for_course_admin','1','','NO','_self', 'admin','0')"
        );

        $defaultEmailExerciseAlert = 1;
        $alert = api_get_setting('email_alert_manager_on_new_quiz');
        if ($alert === 'true') {
            $defaultEmailExerciseAlert = 1;
        } else {
            $defaultEmailExerciseAlert = 0;
        }

        /* course_setting table (courseinfo tool)   */
        $settings = [
            'email_alert_manager_on_new_doc' => ['default' => 0, 'category' => 'work'],
            'email_alert_on_new_doc_dropbox' => ['default' => 0, 'category' => 'dropbox'],
            'allow_user_edit_agenda' => ['default' => 0, 'category' => 'agenda'],
            'allow_user_edit_announcement' => ['default' => 0, 'category' => 'announcement'],
            'email_alert_manager_on_new_quiz' => ['default' => $defaultEmailExerciseAlert, 'category' => 'quiz'],
            'allow_user_image_forum' => ['default' => 1, 'category' => 'forum'],
            'course_theme' => ['default' => '', 'category' => 'theme'],
            'allow_learning_path_theme' => ['default' => 1, 'category' => 'theme'],
            'allow_open_chat_window' => ['default' => 1, 'category' => 'chat'],
            'email_alert_to_teacher_on_new_user_in_course' => ['default' => 0, 'category' =>'registration'],
            'allow_user_view_user_list' => ['default' =>1, 'category' =>'user'],
            'display_info_advance_inside_homecourse' => ['default' => 1, 'category' =>'thematic_advance'],
            'email_alert_students_on_new_homework' => ['default' => 0, 'category' =>'work'],
            'enable_lp_auto_launch' => ['default' => 0, 'category' =>'learning_path'],
            'pdf_export_watermark_text' => ['default' =>'', 'category' =>'learning_path'],
            'allow_public_certificates' => ['default' => '', 'category' =>'certificates'],
            'documents_default_visibility' => ['default' =>'visible', 'category' =>'document']
        ];

        $counter = 1;
        foreach ($settings as $variable => $setting) {
            Database::query(
                "INSERT INTO $TABLESETTING (id, c_id, variable, value, category)
                 VALUES ($counter, $course_id, '".$variable."', '".$setting['default']."', '".$setting['category']."')"
            );
            $counter++;
        }

        /* Course homepage tools for platform admin only */

        /* Group tool */

        Database::query(
            "INSERT INTO $TABLEGROUPCATEGORIES  (c_id, id, title , description, max_student, self_reg_allowed, self_unreg_allowed, groups_per_user, display_order)
             VALUES ($course_id, '2', '" . self::lang2db(get_lang('DefaultGroupCategory')) . "', '', '8', '0', '0', '0', '0');"
        );

        /*    Example Material  */
        global $language_interface;
        $language_interface = !empty($language_interface) ? $language_interface : api_get_setting(
            'platformLanguage'
        );


        // Example material should be in the same language as the course is.
        $language_interface_original = $language_interface;
        $language_interface = $language;
        $now = api_get_utc_datetime();

        $files = [
            ['path' => '/shared_folder', 'title' => get_lang('UserFolders'), 'filetype' => 'folder', 'size' => 0],
            ['path' => '/chat_files', 'title' => get_lang('ChatFiles'), 'filetype' => 'folder', 'size' => 0],
        ];

        $counter = 1;
        foreach ($files as $file) {
            self::insertDocument($course_id, $counter, $file);
            $counter++;
        }

        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();

        $chat_path = $sys_course_path . $course_repository . '/document/chat_files';

        if (!is_dir($chat_path)) {
            @mkdir($chat_path, api_get_permissions_for_new_directories());
        }

        /*    Documents   */
        if ($fill_with_exemplary_content) {

            $files = [
                ['path' => '/images', 'title' => get_lang('Images'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/images/gallery', 'title' => get_lang('DefaultCourseImages'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/audio', 'title' => get_lang('Audio'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/flash', 'title' => get_lang('Flash'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/video', 'title' => get_lang('Video'), 'filetype' => 'folder', 'size' => 0],
                ['path' => '/certificates', 'title' => get_lang('Certificates'), 'filetype' => 'folder', 'size' => 0]
            ];

            foreach ($files as $file) {
                self::insertDocument($course_id, $counter, $file);
                $counter++;
            }

            // FILL THE COURSE DOCUMENT WITH DEFAULT COURSE PICTURES
            $folders_to_copy_from_default_course = array(
                'images',
                'audio',
                'flash',
                'video',
                'certificates',
            );

            $default_course_path = api_get_path(SYS_CODE_PATH) . 'default_course_document/';

            $default_document_array = array();
            foreach ($folders_to_copy_from_default_course as $folder) {
                $default_course_folder_path = $default_course_path . $folder . '/';
                $files = self::browse_folders(
                    $default_course_folder_path,
                    array(),
                    $folder
                );

                $sorted_array = self::sort_pictures($files, 'dir');
                $sorted_array = array_merge(
                    $sorted_array,
                    self::sort_pictures($files, 'file')
                );
                $default_document_array[$folder] = $sorted_array;
            }

            //echo '<pre>'; print_r($default_document_array);exit;

            //Light protection (adding index.html in every document folder)
            $htmlpage = "<!DOCTYPE html>\n<html lang=\"en\">\n <head>\n <meta charset=\"utf-8\">\n <title>Not authorized</title>\n  </head>\n  <body>\n  </body>\n</html>";

            $example_cert_id = 0;
            if (is_array($default_document_array) && count(
                    $default_document_array
                ) > 0
            ) {
                foreach ($default_document_array as $media_type => $array_media) {
                    $path_documents = "/$media_type/";

                    //hack until feature #5242 is implemented
                    if ($media_type == 'images') {
                        $media_type = 'images/gallery';
                        $images_folder = $sys_course_path . $course_repository . "/document/images/";

                        if (!is_dir($images_folder)) {
                            //Creating index.html
                            mkdir($images_folder, $perm);
                            $fd = fopen($images_folder . 'index.html', 'w');
                            fwrite($fd, $htmlpage);
                            @chmod($images_folder . 'index.html', $perm_file);
                        }
                    }

                    $course_documents_folder = $sys_course_path . $course_repository . "/document/$media_type/";
                    $default_course_path = api_get_path(SYS_CODE_PATH) . 'default_course_document' . $path_documents;

                    if (!is_dir($course_documents_folder)) {
                        // Creating index.html
                        mkdir($course_documents_folder, $perm);
                        $fd = fopen(
                            $course_documents_folder . 'index.html',
                            'w'
                        );
                        fwrite($fd, $htmlpage);
                        @chmod(
                            $course_documents_folder . 'index.html',
                            $perm_file
                        );
                    }

                    if (is_array($array_media) && count($array_media) > 0) {
                        foreach ($array_media as $key => $value) {
                            if (isset($value['dir']) && !empty($value['dir'])) {
                                if (!is_dir($course_documents_folder . $value['dir'])) {
                                    //Creating folder
                                    mkdir(
                                        $course_documents_folder . $value['dir'],
                                        $perm
                                    );

                                    //Creating index.html (for light protection)
                                    $index_html = $course_documents_folder . $value['dir'] . '/index.html';
                                    $fd = fopen($index_html, 'w');
                                    fwrite($fd, $htmlpage);
                                    @chmod($index_html, $perm_file);

                                    //Inserting folder in the DB
                                    $folder_path = substr(
                                        $value['dir'],
                                        0,
                                        strlen($value['dir']) - 1
                                    );
                                    $temp = explode('/', $folder_path);
                                    $title = $temp[count($temp) - 1];

                                    //hack until feature #5242 is implemented
                                    if ($title == 'gallery') {
                                        $title = get_lang(
                                            'DefaultCourseImages'
                                        );
                                    }

                                    if ($media_type == 'images/gallery') {
                                        $folder_path = 'gallery/' . $folder_path;
                                    }

                                    Database::query(
                                        "INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size)
                                        VALUES ($course_id,'$path_documents" . $folder_path . "','" . $title . "','folder','0')"
                                    );
                                    $image_id = Database:: insert_id();

                                    Database::query(
                                        "INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                                        VALUES ($course_id,'document',1,'$now','$now',$image_id,'DocumentAdded',1,NULL,NULL,0)"
                                    );
                                }
                            }

                            if (isset($value['file']) && !empty($value['file'])) {
                                if (!file_exists(
                                    $course_documents_folder . $value['file']
                                )
                                ) {
                                    //Copying file
                                    copy(
                                        $default_course_path . $value['file'],
                                        $course_documents_folder . $value['file']
                                    );
                                    chmod(
                                        $course_documents_folder . $value['file'],
                                        $perm_file
                                    );
                                    //echo $default_course_path.$value['file']; echo ' - '; echo $course_documents_folder.$value['file']; echo '<br />';
                                    $temp = explode('/', $value['file']);
                                    $file_size = filesize(
                                        $course_documents_folder . $value['file']
                                    );

                                    //hack until feature #5242 is implemented
                                    if ($media_type == 'images/gallery') {
                                        $value["file"] = 'gallery/' . $value["file"];
                                    }

                                    //Inserting file in the DB
                                    Database::query(
                                        "INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size)
                                        VALUES ($course_id,'$path_documents" . $value["file"] . "','" . $temp[count($temp) - 1] . "','file','$file_size')"
                                    );
                                    $image_id = Database:: insert_id();
                                    if ($image_id) {

                                        $sql = "UPDATE $TABLETOOLDOCUMENT SET id = iid WHERE iid = $image_id";
                                        Database::query($sql);

                                        if ($path_documents . $value['file'] == '/certificates/default.html') {
                                            $example_cert_id = $image_id;
                                        }
                                        Database::query(
                                            "INSERT INTO $TABLEITEMPROPERTY (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                                            VALUES ($course_id,'document',1,'$now','$now',$image_id,'DocumentAdded',1,NULL,NULL,1)"
                                        );
                                        $docId = Database:: insert_id();
                                        if ($docId) {
                                            $sql = "UPDATE $TABLEITEMPROPERTY SET id = iid WHERE iid = $docId";
                                            Database::query($sql);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $agenda = new Agenda();
            $agenda->setType('course');
            $agenda->set_course($courseInfo);
            $agenda->addEvent(
                $now,
                $now,
                0,
                get_lang('AgendaCreationTitle'),
                get_lang('AgendaCreationContenu')
            );

            /*  Links tool */

            $link = new Link();
            $link->setCourse($courseInfo);
            $links = [
                [
                    'c_id' => $course_id,
                    'url' => 'http://www.google.com',
                    'title' => 'Google',
                    'description' => get_lang('Google') ,
                    'category_id' => 0,
                    'on_homepage' => 0,
                    'target' => '_self',
                    'session_id' => 0
                ],
                [
                    'c_id' => $course_id,
                    'url' => 'http://www.wikipedia.org',
                    'title' => 'Wikipedia',
                    'description' => get_lang('Wikipedia') ,
                    'category_id' => 0,
                    'on_homepage' => 0,
                    'target' => '_self',
                    'session_id' => 0
                ]
            ];

            foreach($links as $params) {
                $link->save($params);
            }

            /* Announcement tool */
            AnnouncementManager::add_announcement(
                get_lang('AnnouncementExampleTitle'),
                get_lang('AnnouncementEx'),
                ['everyone' => 'everyone'],
                null,
                null,
                $now
            );

            $manager = Database::getManager();

            /* Introduction text */

            $intro_text = '<p style="text-align: center;">
                            <img src="' . api_get_path(REL_CODE_PATH) . 'img/mascot.png" alt="Mr. Chamilo" title="Mr. Chamilo" />
                            <h2>' . self::lang2db(get_lang('IntroductionText')) . '</h2>
                         </p>';

            $toolIntro = new Chamilo\CourseBundle\Entity\CToolIntro();
            $toolIntro
                ->setCId($course_id)
                ->setId(TOOL_COURSE_HOMEPAGE)
                ->setSessionId(0)
                ->setIntroText($intro_text);
            $manager->persist($toolIntro);

            $toolIntro = new Chamilo\CourseBundle\Entity\CToolIntro();
            $toolIntro
                ->setCId($course_id)
                ->setId(TOOL_STUDENTPUBLICATION)
                ->setSessionId(0)
                ->setIntroText(get_lang('IntroductionTwo'));
            $manager->persist($toolIntro);


            $toolIntro = new Chamilo\CourseBundle\Entity\CToolIntro();
            $toolIntro
                ->setCId($course_id)
                ->setId(TOOL_WIKI)
                ->setSessionId(0)
                ->setIntroText(get_lang('IntroductionWiki'));
            $manager->persist($toolIntro);

            $manager->flush();

            /*  Exercise tool */
            $exercise = new Exercise($course_id);
            $exercise->exercise = get_lang('ExerciceEx');
            $html = '<table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td width="110" valign="top" align="left">
                            <img src="' . api_get_path(WEB_CODE_PATH) . 'default_course_document/images/mr_dokeos/thinking.jpg">
                        </td>
                        <td valign="top" align="left">' . get_lang('Antique') . '</td></tr>
                    </table>';
            $exercise->type = 1;
            $exercise->setRandom(0);
            $exercise->active = 1;
            $exercise->results_disabled = 0;
            $exercise->description = $html;
            $exercise->save();

            $exercise_id = $exercise->id;

            $question = new MultipleAnswer();
            $question->question = get_lang('SocraticIrony');
            $question->description = get_lang('ManyAnswers');
            $question->weighting = 10;
            $question->position = 1;
            $question->course = $courseInfo;
            $question->save($exercise_id);
            $questionId = $question->id;

            $answer = new Answer($questionId, $courseInfo['real_id']);

            $answer->createAnswer(get_lang('Ridiculise'), 0, get_lang('NoPsychology'), -5, 1);
            $answer->createAnswer(get_lang('AdmitError'), 0, get_lang('NoSeduction'), -5, 2);
            $answer->createAnswer(get_lang('Force'), 1, get_lang('Indeed'), 5, 3);
            $answer->createAnswer(get_lang('Contradiction'), 1, get_lang('NotFalse'), 5, 4);

            $answer->save();

            /* Forum tool */

            require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

            $params = [
                'forum_category_title' => get_lang('ExampleForumCategory'),
                'forum_category_comment' => ''
            ];

            $forumCategoryId = store_forumcategory($params, $courseInfo, false);

            $params = [
                'forum_category' => $forumCategoryId,
                'forum_title' => get_lang('ExampleForum'),
                'forum_comment' => '',
                'default_view_type_group' => ['default_view_type' => 'flat'],
            ];

            $forumId = store_forum($params, $courseInfo, true);

            $forumInfo = get_forum_information($forumId, $courseInfo['real_id']);

            $params = [
                'post_title' => get_lang('ExampleThread'),
                'forum_id' => $forumId,
                'post_text' => get_lang('ExampleThreadContent'),
                'calification_notebook_title' => '',
                'numeric_calification' => '',
                'weight_calification' => '',
                'forum_category' => $forumCategoryId,
                'thread_peer_qualify' => 0,
            ];

            store_thread($forumInfo, $params, $courseInfo, false);

            /* Gradebook tool */
            $course_code = $courseInfo['code'];
            // father gradebook
            Database::query(
                "INSERT INTO $TABLEGRADEBOOK (name, description, user_id, course_code, parent_id, weight, visible, certif_min_score, session_id, document_id)
                VALUES ('$course_code','',1,'$course_code',0,100,0,75,NULL,$example_cert_id)"
            );
            $gbid = Database:: insert_id();
            Database::query(
                "INSERT INTO $TABLEGRADEBOOK (name, description, user_id, course_code, parent_id, weight, visible, certif_min_score, session_id, document_id)
                VALUES ('$course_code','',1,'$course_code',$gbid,100,1,75,NULL,$example_cert_id)"
            );
            $gbid = Database:: insert_id();
            Database::query(
                "INSERT INTO $TABLEGRADEBOOKLINK (type, ref_id, user_id, course_code, category_id, created_at, weight, visible, locked)
                VALUES (1,$exercise_id,1,'$course_code',$gbid,'$now',100,1,0)"
            );
        }

        //Installing plugins in course
        $app_plugin = new AppPlugin();
        $app_plugin->install_course_plugins($course_id);

        $language_interface = $language_interface_original;
        return true;
    }

    /**
     * @param int $course_id
     * @param int $counter
     * @param array $file
     */
    public static function insertDocument($course_id, $counter, $file)
    {
        $tableItem = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $tableDocument = Database::get_course_table(TABLE_DOCUMENT);

        $now = api_get_utc_datetime();

        $sql = "INSERT INTO $tableDocument (id, c_id, path,title,filetype,size)
                VALUES ($counter, $course_id, '".$file['path']."', '".$file['title']."', '".$file['filetype']."', '".$file['size']."')";
        Database::query($sql);
        $docId = Database:: insert_id();

        if ($docId) {
            $sql = "UPDATE $tableDocument SET id = iid WHERE iid = $docId";
            Database::query($sql);

            Database::query(
                "INSERT INTO $tableItem (id, c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility)
                VALUES ($counter, $course_id,'document',1,'$now', '$now', $docId, 'DocumentAdded', 1, NULL, NULL, 0)"
            );
            $id = Database:: insert_id();
            if ($id) {
                $sql = "UPDATE $tableItem SET id = iid WHERE iid = $id";
                Database::query($sql);
            }
        }
    }

    /**
     * string2binary converts the string "true" or "false" to the boolean true false (0 or 1)
     * This is used for the Chamilo Config Settings as these store true or false as string
     * and the api_get_setting('course_create_active_tools') should be 0 or 1 (used for
     * the visibility of the tool)
     * @param string $variable
     * @author Patrick Cool, patrick.cool@ugent.be
     * @assert ('true') === true
     * @assert ('false') === false
     */
    public static function string2binary($variable)
    {
        if ($variable == 'true') {
            return true;
        }
        if ($variable == 'false') {
            return false;
        }
    }

    /**
     * Function register_course to create a record in the course table of the main database
     * @param array Course details (see code for details)
     * @return int  Created course ID
     * @todo use an array called $params instead of lots of params
     * @assert (null) === false
     */
    public static function register_course($params)
    {
        global $error_msg, $firstExpirationDelay;

        $title = $params['title'];
        $code = $params['code'];
        $visual_code = $params['visual_code'];
        $directory = $params['directory'];
        $tutor_name = isset($params['tutor_name']) ? $params['tutor_name'] : null;
        //$description        = $params['description'];

        $category_code = isset($params['course_category']) ? $params['course_category'] : '';
        $course_language = isset($params['course_language']) && !empty($params['course_language']) ? $params['course_language'] : api_get_setting(
            'platformLanguage'
        );
        $user_id = empty($params['user_id']) ? api_get_user_id() : intval($params['user_id']);
        $department_name = isset($params['department_name']) ?
            $params['department_name'] : null;
        $department_url = isset($params['department_url']) ?
            $params['department_url'] : null;
        $disk_quota = isset($params['disk_quota']) ?
            $params['disk_quota'] : null;

        if (!isset($params['visibility'])) {
            $default_course_visibility = api_get_setting(
                'courses_default_creation_visibility'
            );
            if (isset($default_course_visibility)) {
                $visibility = $default_course_visibility;
            } else {
                $visibility = COURSE_VISIBILITY_OPEN_PLATFORM;
            }
        } else {
            $visibility = $params['visibility'];
        }

        $subscribe = isset($params['subscribe']) ? intval(
            $params['subscribe']
        ) : ($visibility == COURSE_VISIBILITY_OPEN_PLATFORM ? 1 : 0);
        $unsubscribe = isset($params['unsubscribe']) ? intval(
            $params['unsubscribe']
        ) : 0;
        $expiration_date = isset($params['expiration_date']) ? $params['expiration_date'] : null;
        $teachers = isset($params['teachers']) ? $params['teachers'] : null;
        $status = isset($params['status']) ? $params['status'] : null;

        $TABLECOURSE = Database:: get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database:: get_main_table(TABLE_MAIN_COURSE_USER);

        $ok_to_register_course = true;

        // Check whether all the needed parameters are present.
        if (empty($code)) {
            $error_msg[] = 'courseSysCode is missing';
            $ok_to_register_course = false;
        }
        if (empty($visual_code)) {
            $error_msg[] = 'courseScreenCode is missing';
            $ok_to_register_course = false;
        }
        if (empty($directory)) {
            $error_msg[] = 'courseRepository is missing';
            $ok_to_register_course = false;
        }

        if (empty($title)) {
            $error_msg[] = 'title is missing';
            $ok_to_register_course = false;
        }

        if (empty($expiration_date)) {
            $expiration_date = api_get_utc_datetime(
                time() + $firstExpirationDelay
            );
        } else {
            $expiration_date = api_get_utc_datetime($expiration_date);
        }

        if ($visibility < 0 || $visibility > 4) {
            $error_msg[] = 'visibility is invalid';
            $ok_to_register_course = false;
        }

        if (empty($disk_quota)) {
            $disk_quota = api_get_setting('default_document_quotum');
        }

        $time = api_get_utc_datetime();

        if (stripos($department_url, 'http://') === false && stripos(
                $department_url,
                'https://'
            ) === false
        ) {
            $department_url = 'http://' . $department_url;
        }
        //just in case
        if ($department_url == 'http://') {
            $department_url = '';
        }
        $course_id = 0;

        if ($ok_to_register_course) {

            // Here we must add 2 fields.
            $sql = "INSERT INTO " . $TABLECOURSE . " SET
                        code = '".Database:: escape_string($code)."',
                        directory = '".Database:: escape_string($directory)."',
                        course_language = '".Database:: escape_string($course_language)."',
                        title = '".Database:: escape_string($title)."',
                        description = '".self::lang2db(get_lang('CourseDescription'))."',
                        category_code = '".Database:: escape_string($category_code)."',
                        visibility      = '" . $visibility . "',
                        show_score      = '1',
                        disk_quota      = '" . intval($disk_quota) . "',
                        creation_date   = '$time',
                        expiration_date = '" . $expiration_date . "',
                        last_edit       = '$time',
                        last_visit      = NULL,
                        tutor_name = '" . Database:: escape_string($tutor_name) . "',
                        department_name = '" . Database:: escape_string($department_name) . "',
                        department_url = '" . Database:: escape_string($department_url) . "',
                        subscribe = '" . intval($subscribe) . "',
                        unsubscribe = '" . intval($unsubscribe) . "',
                        visual_code = '" . Database:: escape_string($visual_code) . "'";
            Database::query($sql);
            $course_id = Database::insert_id();

            if ($course_id) {
                $sort = api_max_sort_value('0', api_get_user_id());
                // Default true
                $addTeacher = isset($params['add_user_as_teacher']) ? $params['add_user_as_teacher'] : true;
                if ($addTeacher) {

                    $i_course_sort = CourseManager:: userCourseSort(
                        $user_id,
                        $code
                    );

                    if (!empty($user_id)) {
                        $sql = "INSERT INTO " . $TABLECOURSUSER . " SET
                                c_id     = '" . $course_id . "',
                                user_id         = '" . intval($user_id) . "',
                                status          = '1',
                                is_tutor        = '0',
                                sort            = '" . ($i_course_sort) . "',
                                user_course_cat = '0'";
                        Database::query($sql);
                    }
                }

                if (!empty($teachers)) {
                    if (!is_array($teachers)) {
                        $teachers = array($teachers);
                    }
                    foreach ($teachers as $key) {
                        //just in case
                        if ($key == $user_id) {
                            continue;
                        }
                        if (empty($key)) {
                            continue;
                        }
                        $sql = "INSERT INTO " . $TABLECOURSUSER . " SET
                            c_id     = '" . Database::escape_string($course_id) . "',
                            user_id         = '" . Database::escape_string($key) . "',
                            status          = '1',
                            is_tutor        = '0',
                            sort            = '" . ($sort + 1) . "',
                            user_course_cat = '0'";
                        Database::query($sql);
                    }
                }

                // Adding the course to an URL.
                if (api_is_multiple_url_enabled()) {
                    $url_id = 1;
                    if (api_get_current_access_url_id() != -1) {
                        $url_id = api_get_current_access_url_id();
                    }
                    UrlManager::add_course_to_url($course_id, $url_id);
                } else {
                    UrlManager::add_course_to_url($course_id, 1);
                }

                // Add event to the system log.
                $user_id = api_get_user_id();
                Event::addEvent(
                    LOG_COURSE_CREATE,
                    LOG_COURSE_CODE,
                    $code,
                    api_get_utc_datetime(),
                    $user_id,
                    $course_id
                );

                $send_mail_to_admin = api_get_setting(
                    'send_email_to_admin_when_create_course'
                );

                // @todo Improve code to send to all current portal administrators.
                if ($send_mail_to_admin == 'true') {
                    $siteName = api_get_setting('siteName');
                    $recipient_email = api_get_setting('emailAdministrator');
                    $recipient_name = api_get_person_name(
                        api_get_setting('administratorName'),
                        api_get_setting('administratorSurname')
                    );
                    $iname = api_get_setting('Institution');
                    $subject = get_lang(
                            'NewCourseCreatedIn'
                        ) . ' ' . $siteName . ' - ' . $iname;
                    $message = get_lang(
                            'Dear'
                        ) . ' ' . $recipient_name . ",\n\n" . get_lang(
                            'MessageOfNewCourseToAdmin'
                        ) . ' ' . $siteName . ' - ' . $iname . "\n";
                    $message .= get_lang('CourseName') . ' ' . $title . "\n";
                    $message .= get_lang(
                            'Category'
                        ) . ' ' . $category_code . "\n";
                    $message .= get_lang('Tutor') . ' ' . $tutor_name . "\n";
                    $message .= get_lang('Language') . ' ' . $course_language;

                    $userInfo = api_get_user_info($user_id);

                    $additionalParameters = array(
                        'smsType' => SmsPlugin::NEW_COURSE_BEEN_CREATED,
                        'userId' => $user_id,
                        'courseName' => $title,
                        'creatorUsername' => $userInfo['username']
                    );

                    api_mail_html(
                        $recipient_name,
                        $recipient_email,
                        $subject,
                        $message,
                        $siteName,
                        $recipient_email,
                        null,
                        null,
                        null,
                        $additionalParameters
                    );
                }
            }
        }

        return $course_id;
    }

    /**
     * Extract properties of the files from a ZIP package, write them to disk and
     * return them as an array.
     * @todo this function seems not to be used
     * @param string        Absolute path to the ZIP file
     * @param bool          Whether the ZIP file is compressed (not implemented). Defaults to TRUE.
     * @return array        List of files properties from the ZIP package
     * @deprecated seems not to be used
     * @assert (null) === false
     */
    public static function readPropertiesInArchive($archive, $is_compressed = true)
    {
        $uid = api_get_user_id();
        /*
        string tempnam (string dir, string prefix)
        tempnam() creates a unique temporary file in the dir directory. If the
        directory doesn't existm tempnam() will generate a filename in the system's
        temporary directory.
        Before PHP 4.0.6, the behaviour of tempnam() depended of the underlying OS.
        Under Windows, the "TMP" environment variable replaces the dir parameter;
        under Linux, the "TMPDIR" environment variable has priority, while for the
        OSes based on system V R4, the dir parameter will always be used if the
        directory which it represents exists. Consult your documentation for more
        details.
        tempnam() returns the temporary filename, or the string NULL upon failure.
        */
        $zip_file = new PclZip($archive);
        $tmp_dir_name = dirname($archive) . '/tmp' . $uid . uniqid($uid);
        if (mkdir(
            $tmp_dir_name,
            api_get_permissions_for_new_directories(),
            true
        )) {
            $unzipping_state = $zip_file->extract($tmp_dir_name);
        } else {
            die ('mkdir failed');
        }
        $path_to_archive_ini = dirname($tmp_dir_name) . '/archive.ini';
        //echo $path_to_archive_ini;
        $course_properties = parse_ini_file($path_to_archive_ini);
        rmdir($tmp_dir_name);
        return $course_properties;
    }

    /**
     * Generate a new id for c_tool table
     * @param int $courseId The course id
     * @return int the new id
     */
    public static function generateToolId($courseId)
    {
        $newIdResultData = Database::select(
            'id + 1 AS new_id',
            Database::get_course_table(TABLE_TOOL_LIST),
            [
                'where' => ['c_id = ?' => intval($courseId)],
                'order' => 'id',
                'limit' => 1
            ],
            'first'
        );

        if ($newIdResultData === false) {
            return 1;
        }

        return $newIdResultData['new_id'] > 0 ? $newIdResultData['new_id'] : 1;
    }

}
