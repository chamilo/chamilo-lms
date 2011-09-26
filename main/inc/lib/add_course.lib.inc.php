<?php
/* For licensing terms, see /license.txt */
/**
 * This is the course creation library for Chamilo.
 * Include/require it in your code for using its functionality.
 *
 * @package chamilo.library
 * @todo Clean up horrible structure, script is unwieldy, for example easier way to deal with
 * different tool visibility settings: ALL_TOOLS_INVISIBLE, ALL_TOOLS_VISIBLE, CORE_TOOLS_VISIBLE...
 */
/**
 * Code
 */
require_once api_get_path(LIBRARY_PATH).'database.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

/* FUNCTIONS */

/**
* Top-level function to create a course. Calls other functions to take care of
* the various parts of the course creation.
* @param string     Course code requested (might be altered to match possible values)
* @param string     Course title
* @param string     Tutor name
* @param int        Course category code
* @param string     Course language
* @param int        Course admin ID
* @param string     DB prefix
* @param int        Expiration delay in unix timestamp
* @param bool/null  A boolean flag to enable filling the created course with exemplary content. If the flag is NULL, then the platform setting 'example_material_course_creation' is taken into account.
* @return mixed     Course code if course was successfully created, false otherwise
*/
function create_course($wanted_code, $title, $tutor_name, $category_code, $course_language, $course_admin_id, $db_prefix, $first_expiration_delay, $fill_with_exemplary_content = null) {

    $keys = define_course_keys($wanted_code, '', $db_prefix);

    if (count($keys)) {
        $visual_code = $keys['currentCourseCode'];
        $code = $keys['currentCourseId'];
        $db_name = $keys['currentCourseDbName'];
        $directory = $keys['currentCourseRepository'];
        $expiration_date = time() + $first_expiration_delay;

        prepare_course_repository($directory, $code);
        update_Db_course($db_name);
        fill_course_repository($directory, $fill_with_exemplary_content);
        fill_Db_course($db_name, $directory, $course_language, $fill_with_exemplary_content);
        register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, $course_admin_id, $expiration_date);

        return $code;
    }

    return false;
}

// TODO: Such a function might be useful in other places too. It might be moved in the CourseManager class.
// Also, the function might be upgraded for avoiding code duplications.
function generate_course_code($course_title, $encoding = null) {
    if (empty($encoding)) {
        $encoding = api_get_system_encoding();
    }
    return substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(api_transliterate($course_title, 'X', $encoding))), 0, 20);
}

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
 */
function define_course_keys($wanted_code, $prefix_for_all = '', $prefix_for_base_name = '', $prefix_for_path = '', $add_unique_prefix = false, $use_code_indepedent_keys = true) {

    global $prefixAntiNumber, $_configuration;
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);

    $wanted_code = generate_course_code($wanted_code);
    $keys_course_code = $wanted_code;
    if (!$use_code_indepedent_keys) {
        $wanted_code = '';
    }

    if ($add_unique_prefix) {
        $unique_prefix = substr(md5(uniqid(rand())), 0, 10);
    } else {
        $unique_prefix = '';
    }

    $keys = array ();
    $final_suffix = array('CourseId' => '', 'CourseDb' => '', 'CourseDir' => '');
    $limit_numb_try = 100;
    $keys_are_unique = false;
    $try_new_fsc_id = $try_new_fsc_db = $try_new_fsc_dir = 0;

    while (!$keys_are_unique) {

        $keys_course_id = $prefix_for_all . $unique_prefix . $wanted_code . $final_suffix['CourseId'];
        $keys_course_db_name = $prefix_for_base_name . $unique_prefix . strtoupper($keys_course_id) . $final_suffix['CourseDb'];
        $keys_course_repository = $prefix_for_path . $unique_prefix . $wanted_code . $final_suffix['CourseDir'];
        $keys_are_unique = true;

        // Check whether they are unique.
        $query = "SELECT 1 FROM ".$course_table." WHERE code='".$keys_course_id."' LIMIT 0,1";
        $result = Database::query($query);

        if ($keys_course_id == DEFAULT_COURSE || Database::num_rows($result)) {
            $keys_are_unique = false;
            $try_new_fsc_id ++;
            $final_suffix['CourseId'] = substr(md5(uniqid(rand())), 0, 4);
        }

        if ($_configuration['single_database']) {
            $query = "SHOW TABLES FROM ".$_configuration['main_database']." LIKE '".$_configuration['table_prefix'].$keys_course_db_name.$_configuration['db_glue']."%'";
            $result = Database::query($query);
        } else {
            $query = "SHOW DATABASES LIKE '$keys_course_db_name'";
            $result = Database::query($query);
        }

        if (Database::num_rows($result)) {
            $keys_are_unique = false;
            $try_new_fsc_db ++;
            $final_suffix['CourseDb'] = substr('_'.md5(uniqid(rand())), 0, 4);
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

    // Db name can't begin with a number.
    if (stripos('abcdefghijklmnopqrstuvwxyz', $keys_course_db_name[0]) === false) {
        $keys_course_db_name = $prefixAntiNumber . $keys_course_db_name;
    }

    $keys['currentCourseCode'] = $keys_course_code;
    $keys['currentCourseId'] = $keys_course_id;
    $keys['currentCourseDbName'] = $keys_course_db_name;
    $keys['currentCourseRepository'] = $keys_course_repository;

    return $keys;
}

/**
 * Initializes a file repository for a newly created course.
 */
function prepare_course_repository($course_repository, $course_code) {

    $perm = api_get_permissions_for_new_directories();
    $perm_file = api_get_permissions_for_new_files();

    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository, $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/images', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/images/gallery/', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/shared_folder/', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/audio', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/flash', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/video', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/document/video/flv', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/dropbox', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/group', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/page', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/scorm', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/temp', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/forum', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/forum/images', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/test', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/blog', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/learning_path', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/learning_path/images', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/calendar', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/calendar/images', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/work', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/announcements', $perm);
    mkdir(api_get_path(SYS_COURSE_PATH).$course_repository . '/upload/announcements/images', $perm);

    // Create .htaccess in the dropbox directory.
    $fp = fopen(api_get_path(SYS_COURSE_PATH).$course_repository . '/dropbox/.htaccess', 'w');
    fwrite($fp, "AuthName AllowLocalAccess
                   AuthType Basic

                   order deny,allow
                   deny from all

                   php_flag zlib.output_compression off");
    fclose($fp);

    // Build index.php of the course.
    $fd = fopen(api_get_path(SYS_COURSE_PATH).$course_repository . '/index.php', 'w');

    // str_replace() removes \r that cause squares to appear at the end of each line
    $string = str_replace("\r", "", "<?" . "php
    \$cidReq = \"$course_code\";
    \$dbname = \"$course_code\";

    include(\"".api_get_path(SYS_CODE_PATH)."course_home/course_home.php\");
    ?>");
    fwrite($fd, $string);
    @chmod(api_get_path(SYS_COURSE_PATH).$course_repository . '/index.php',$perm_file);
    $fd = fopen(api_get_path(SYS_COURSE_PATH).$course_repository . '/group/index.html', 'w');
    $string = '<html></html>';
    fwrite($fd, $string);
    fclose($fd);
    return 0;
};

/**
 * Creates all the necessary tables for a new course.
 */
function update_Db_course($course_db_name = null) {
    global $_configuration;
    
    $charset_clause = ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci';
    
    $use_one_db = true;
    
    if ($use_one_db) {
    	$course_db_name = DB_COURSE_PREFIX;
    } else {
	    if (!$_configuration['single_database']) {
	        Database::query("CREATE DATABASE IF NOT EXISTS " . $course_db_name . "" . $charset_clause);
	    }	
	    $course_db_name = $_configuration['table_prefix'].$course_db_name.$_configuration['db_glue'];
    }

    //@todo define the backticks inside those table names directly (instead of adding them afterwards)
    $tbl_course_homepage        = $course_db_name . 'tool';
    $TABLEINTROS                = $course_db_name . 'tool_intro';

    // Group tool
    $TABLEGROUPS                = $course_db_name . 'group_info';
    $TABLEGROUPCATEGORIES       = $course_db_name . 'group_category';
    $TABLEGROUPUSER             = $course_db_name . 'group_rel_user';
    $TABLEGROUPTUTOR            = $course_db_name . 'group_rel_tutor';

    $TABLEITEMPROPERTY          = $course_db_name . 'item_property';

    $TABLETOOLUSERINFOCONTENT   = $course_db_name . 'userinfo_content';
    $TABLETOOLUSERINFODEF       = $course_db_name . 'userinfo_def';

    $TABLETOOLCOURSEDESC        = $course_db_name . 'course_description';
    $TABLETOOLAGENDA            = $course_db_name . 'calendar_event';
    $TABLETOOLAGENDAREPEAT      = $course_db_name . 'calendar_event_repeat';
    $TABLETOOLAGENDAREPEATNOT   = $course_db_name . 'calendar_event_repeat_not';
    $TABLETOOLAGENDAATTACHMENT  = $course_db_name . 'calendar_event_attachment';

    // Announcements
    $TABLETOOLANNOUNCEMENTS             = $course_db_name . 'announcement';
    $TABLETOOLANNOUNCEMENTSATTACHMENT   = $course_db_name . 'announcement_attachment';

    // Resourcelinker
    $TABLEADDEDRESOURCES        = $course_db_name . 'resource';

    // Student Publication
    $TABLETOOLWORKS             = $course_db_name . 'student_publication';
    $TABLETOOLWORKSASS          = $course_db_name . 'student_publication_assignment';

    // Document
    $TABLETOOLDOCUMENT          = $course_db_name . 'document';

    // Forum
    $TABLETOOLFORUMCATEGORY     = $course_db_name . 'forum_category';
    $TABLETOOLFORUM             = $course_db_name . 'forum_forum';
    $TABLETOOLFORUMTHREAD       = $course_db_name . 'forum_thread';
    $TABLETOOLFORUMPOST         = $course_db_name . 'forum_post';
    $TABLETOOLFORUMMAILCUE      = $course_db_name . 'forum_mailcue';
    $TABLETOOLFORUMATTACHMENT   = $course_db_name . 'forum_attachment';
    $TABLETOOLFORUMNOTIFICATION = $course_db_name . 'forum_notification';
    $TABLETOOLFORUMQUALIFY      = $course_db_name . 'forum_thread_qualify';
    $TABLETOOLFORUMQUALIFYLOG   = $course_db_name . 'forum_thread_qualify_log';

    // Link
    $TABLETOOLLINK              = $course_db_name . 'link';
    $TABLETOOLLINKCATEGORIES    = $course_db_name . 'link_category';

    $TABLETOOLONLINECONNECTED   = $course_db_name . 'online_connected';
    $TABLETOOLONLINELINK        = $course_db_name . 'online_link';

    // Chat
    $TABLETOOLCHATCONNECTED     = $course_db_name . 'chat_connected';

    // Quiz (a.k.a. exercises)
    $TABLEQUIZ                  = $course_db_name . 'quiz';
    $TABLEQUIZQUESTION          = $course_db_name . 'quiz_rel_question';
    $TABLEQUIZQUESTIONLIST      = $course_db_name . 'quiz_question';
    $TABLEQUIZANSWERSLIST       = $course_db_name . 'quiz_answer';
    $TABLEQUIZQUESTIONOPTION    = $course_db_name . 'quiz_question_option';

    // Dropbox
    $TABLETOOLDROPBOXPOST       = $course_db_name . 'dropbox_post';
    $TABLETOOLDROPBOXFILE       = $course_db_name . 'dropbox_file';
    $TABLETOOLDROPBOXPERSON     = $course_db_name . 'dropbox_person';
    $TABLETOOLDROPBOXCATEGORY   = $course_db_name . 'dropbox_category';
    $TABLETOOLDROPBOXFEEDBACK   = $course_db_name . 'dropbox_feedback';

    // New Learning path
    $TABLELP                    = $course_db_name . 'lp';
    $TABLELPITEM                = $course_db_name . 'lp_item';
    $TABLELPVIEW                = $course_db_name . 'lp_view';
    $TABLELPITEMVIEW            = $course_db_name . 'lp_item_view';
    $TABLELPIVINTERACTION       = $course_db_name . 'lp_iv_interaction';
    $TABLELPIVOBJECTIVE         = $course_db_name . 'lp_iv_objective';

    // Blogs
    $tbl_blogs                  = $course_db_name . 'blog';
    $tbl_blogs_comments         = $course_db_name . 'blog_comment';
    $tbl_blogs_posts            = $course_db_name . 'blog_post';
    $tbl_blogs_rating           = $course_db_name . 'blog_rating';
    $tbl_blogs_rel_user         = $course_db_name . 'blog_rel_user';
    $tbl_blogs_tasks            = $course_db_name . 'blog_task';
    $tbl_blogs_tasks_rel_user   = $course_db_name . 'blog_task_rel_user';
    $tbl_blogs_attachment       = $course_db_name . 'blog_attachment';

    //Blogs permissions
    $tbl_permission_group       = $course_db_name . 'permission_group';
    $tbl_permission_user        = $course_db_name . 'permission_user';
    $tbl_permission_task        = $course_db_name . 'permission_task';

    //Blog roles
    $tbl_role                   = $course_db_name . 'role';
    $tbl_role_group             = $course_db_name . 'role_group';
    $tbl_role_permissions       = $course_db_name . 'role_permissions';
    $tbl_role_user              = $course_db_name . 'role_user';

    //Survey variables for course homepage;
    $TABLESURVEY                = $course_db_name . 'survey';
    $TABLESURVEYQUESTION        = $course_db_name . 'survey_question';
    $TABLESURVEYQUESTIONOPTION  = $course_db_name . 'survey_question_option';
    $TABLESURVEYINVITATION      = $course_db_name . 'survey_invitation';
    $TABLESURVEYANSWER          = $course_db_name . 'survey_answer';
    $TABLESURVEYGROUP           = $course_db_name . 'survey_group';

    // Wiki
    $TABLETOOLWIKI              = $course_db_name . 'wiki';
    $TABLEWIKICONF              = $course_db_name . 'wiki_conf';
    $TABLEWIKIDISCUSS           = $course_db_name . 'wiki_discuss';
    $TABLEWIKIMAILCUE           = $course_db_name . 'wiki_mailcue';

    // audiorecorder
    $TABLEAUDIORECORDER         = $course_db_name . 'audiorecorder';

    // Course settings
    $TABLESETTING               = $course_db_name . 'course_setting';

    // Glossary
    $TBL_GLOSSARY               = $course_db_name . 'glossary';

    // Notebook
    $TBL_NOTEBOOK               = $course_db_name . 'notebook';

    // Attendance
    $TBL_ATTENDANCE             = $course_db_name . 'attendance';
    $TBL_ATTENDANCE_SHEET       = $course_db_name . 'attendance_sheet';
    $TBL_ATTENDANCE_CALENDAR    = $course_db_name . 'attendance_calendar';
    $TBL_ATTENDANCE_RESULT      = $course_db_name . 'attendance_result';
    $TBL_ATTENDANCE_SHEET_LOG   = $course_db_name . 'attendance_sheet_log';

    // Thematic
    $TBL_THEMATIC               = $course_db_name . 'thematic';
    $TBL_THEMATIC_PLAN          = $course_db_name . 'thematic_plan';
    $TBL_THEMATIC_ADVANCE       = $course_db_name . 'thematic_advance';
    
    $add_to_all_tables = ' c_id INT NOT NULL, ';

    /*  Announcement tool	*/   

    $sql = "
        CREATE TABLE `".$TABLETOOLANNOUNCEMENTS . "` (
        $add_to_all_tables
        id mediumint unsigned NOT NULL auto_increment,
        title text,
        content mediumtext,
        end_date date default NULL,
        display_order mediumint NOT NULL default 0,
        email_sent tinyint default 0,
        session_id smallint default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLANNOUNCEMENTS . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Announcement Attachment
    $sql = "CREATE TABLE  `".$TABLETOOLANNOUNCEMENTSATTACHMENT."` (
			$add_to_all_tables
            id int NOT NULL auto_increment,
            path varchar(255) NOT NULL,
            comment text,
            size int NOT NULL default 0,
            announcement_id int NOT NULL,
            filename varchar(255) NOT NULL,
            PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    /*
            Resources
    */

    $sql = "
        CREATE TABLE `".$TABLEADDEDRESOURCES . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        source_type varchar(50) default NULL,
        source_id int unsigned default NULL,
        resource_type varchar(50) default NULL,
        resource_id int unsigned default NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLUSERINFOCONTENT . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        user_id int unsigned NOT NULL,
        definition_id int unsigned NOT NULL,
        editor_ip varchar(39) default NULL,
        edition_time datetime default NULL,
        content text NOT NULL,
        PRIMARY KEY (c_id, id),
        KEY user_id (user_id)
        )" . $charset_clause;
    Database::query($sql);

    // Unused table. Temporarily ignored for tests.
    // Reused because of user/userInfo and user/userInfoLib scripts
    $sql = "
        CREATE TABLE `".$TABLETOOLUSERINFODEF . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(80) NOT NULL default '',
        comment text,
        line_count tinyint unsigned NOT NULL default 5,
        rank tinyint unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    /*            Forum tool	*/

    // Forum Category
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMCATEGORY . "` (
		 $add_to_all_tables
         cat_id int NOT NULL auto_increment,
         cat_title varchar(255) NOT NULL default '',
         cat_comment text,
         cat_order int NOT NULL default 0,
         locked int NOT NULL default 0,
         session_id smallint unsigned NOT NULL default 0,
         PRIMARY KEY (c_id, cat_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMCATEGORY . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Forum
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUM . "` (
        $add_to_all_tables
         forum_id int NOT NULL auto_increment,
         forum_title varchar(255) NOT NULL default '',
         forum_comment text,
         forum_threads int default 0,
         forum_posts int default 0,
         forum_last_post int default 0,
         forum_category int default NULL,
         allow_anonymous int default NULL,
         allow_edit int default NULL,
         approval_direct_post varchar(20) default NULL,
         allow_attachments int default NULL,
         allow_new_threads int default NULL,
         default_view varchar(20) default NULL,
         forum_of_group varchar(20) default NULL,
         forum_group_public_private varchar(20) default 'public',
         forum_order int default NULL,
         locked int NOT NULL default 0,
         session_id int NOT NULL default 0,
         forum_image varchar(255) NOT NULL default '',
         start_time datetime NOT NULL default '0000-00-00 00:00:00',
         end_time datetime NOT NULL default '0000-00-00 00:00:00',
         PRIMARY KEY (c_id, forum_id)
        )" . $charset_clause;
    Database::query($sql);

    // Forum Threads
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMTHREAD . "` (
         $add_to_all_tables
         thread_id int NOT NULL auto_increment,
         thread_title varchar(255) default NULL,
         forum_id int default NULL,
         thread_replies int default 0,
         thread_poster_id int default NULL,
         thread_poster_name varchar(100) default '',
         thread_views int default 0,
         thread_last_post int default NULL,
         thread_date datetime default '0000-00-00 00:00:00',
         thread_sticky tinyint unsigned default 0,
         locked int NOT NULL default 0,
         session_id int unsigned default NULL,
         thread_title_qualify varchar(255) default '',
         thread_qualify_max float(6,2) UNSIGNED NOT NULL default 0,
         thread_close_date datetime default '0000-00-00 00:00:00',
         thread_weight float(6,2) UNSIGNED NOT NULL default 0,
         PRIMARY KEY (c_id, thread_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMTHREAD . "` ADD INDEX idx_forum_thread_forum_id (forum_id)";
    Database::query($sql);

    // Forum Posts
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMPOST . "` (
         $add_to_all_tables
         post_id int NOT NULL auto_increment,
         post_title varchar(250) default NULL,
         post_text text,
         thread_id int default 0,
         forum_id int default 0,
         poster_id int default 0,
         poster_name varchar(100) default '',
         post_date datetime default '0000-00-00 00:00:00',
         post_notification tinyint default 0,
         post_parent_id int default 0,
         visible tinyint default 1,
         PRIMARY KEY (c_id, post_id),
         KEY poster_id (poster_id),
         KEY forum_id (forum_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMPOST . "` ADD INDEX idx_forum_post_thread_id (thread_id)";
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMPOST . "` ADD INDEX idx_forum_post_visible (visible)";
    Database::query($sql);

    // Forum Mailcue
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMMAILCUE . "` (
         $add_to_all_tables
         thread_id int default NULL,
         user_id int default NULL,
         post_id int default NULL
        )" . $charset_clause;
    Database::query($sql);

    // Forum Attachment
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMATTACHMENT."` (
    		  $add_to_all_tables
              id int NOT NULL auto_increment,
              path varchar(255) NOT NULL,
              comment text,
              size int NOT NULL default 0,
              post_id int NOT NULL,
              filename varchar(255) NOT NULL,
              PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    // Forum notification
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMNOTIFICATION."` (
    		  $add_to_all_tables
              user_id int,
              forum_id int,
              thread_id int,
              post_id int,
              KEY user_id (user_id),
              KEY forum_id (forum_id)
            )" . $charset_clause;
    Database::query($sql);

    // Forum thread qualify :Add table forum_thread_qualify
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMQUALIFY."` (
    		$add_to_all_tables
            id int unsigned AUTO_INCREMENT,
            user_id int unsigned NOT NULL,
            thread_id int NOT NULL,
            qualify float(6,2) NOT NULL default 0,
            qualify_user_id int  default NULL,
            qualify_time datetime default '0000-00-00 00:00:00',
            session_id int  default NULL,
            PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMQUALIFY . "` ADD INDEX (user_id, thread_id)";
    Database::query($sql);

    //Forum thread qualify: Add table forum_thread_qualify_historical
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMQUALIFYLOG."` (
    		$add_to_all_tables
            id int unsigned AUTO_INCREMENT,
            user_id int unsigned NOT NULL,
            thread_id int NOT NULL,
            qualify float(6,2) NOT NULL default 0,
            qualify_user_id int default NULL,
            qualify_time datetime default '0000-00-00 00:00:00',
            session_id int default NULL,
            PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMQUALIFYLOG. "` ADD INDEX (user_id, thread_id)";
    Database::query($sql);

    /*        
     * Exercise tool    
    */

    // Exercise tool - Tests/exercises
    $sql = "
        CREATE TABLE `".$TABLEQUIZ . "` (
        $add_to_all_tables        
        id mediumint unsigned NOT NULL auto_increment,
        title varchar(255) NOT NULL,
        description text default NULL,
        sound varchar(255) default NULL,
        type tinyint unsigned NOT NULL default 1,
        random smallint(6) NOT NULL default 0,
        random_answers tinyint unsigned NOT NULL default 0,
        active tinyint NOT NULL default 0,
        results_disabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
        access_condition TEXT DEFAULT NULL,
        max_attempt int NOT NULL default 0,
        start_time datetime NOT NULL default '0000-00-00 00:00:00',
        end_time datetime NOT NULL default '0000-00-00 00:00:00',
        feedback_type int NOT NULL default 0,
        expired_time int NOT NULL default '0',
        session_id smallint default 0,
        propagate_neg INT NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLEQUIZ . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Exercise tool - questions
    $sql = "
        CREATE TABLE `".$TABLEQUIZQUESTIONLIST . "` (
        $add_to_all_tables
        id mediumint unsigned NOT NULL auto_increment,
        question TEXT NOT NULL,
        description text default NULL,
        ponderation float(6,2) NOT NULL default 0,
        position mediumint unsigned NOT NULL default 1,
        type    tinyint unsigned NOT NULL default 2,
        picture varchar(50) default NULL,        
        level   int unsigned NOT NULL default 0,
        extra   varchar(255) default NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLEQUIZQUESTIONLIST . "` ADD INDEX (position)";
    Database::query($sql);

    // Exercise tool - answers
    $sql = "
        CREATE TABLE `".$TABLEQUIZANSWERSLIST . "` (
        $add_to_all_tables
        id mediumint unsigned NOT NULL,
        question_id mediumint unsigned NOT NULL,
        answer text NOT NULL,
        correct mediumint unsigned default NULL,
        comment text default NULL,
        ponderation float(6,2) NOT NULL default 0,
        position mediumint unsigned NOT NULL default 1,
        hotspot_coordinates text,
        hotspot_type enum('square','circle','poly','delineation','oar') default NULL,
        destination text NOT NULL,
        id_auto int NOT NULL AUTO_INCREMENT,   
        PRIMARY KEY (c_id, id, question_id),
        UNIQUE KEY id_auto (id_auto)
        )" . $charset_clause;
    Database::query($sql);
    
    
    
    // Exercise tool - answer options
    $sql = "
        CREATE TABLE `".$TABLEQUIZQUESTIONOPTION . "` (
        $add_to_all_tables
        id          int NOT NULL auto_increment,
        question_id int NOT NULL,                
        name        varchar(255),
        position    int unsigned NOT NULL,        
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);
    

    // Exercise tool - Test/question relations
    $sql = "
        CREATE TABLE `".$TABLEQUIZQUESTION . "` (
        $add_to_all_tables
        question_id mediumint unsigned NOT NULL,
        exercice_id mediumint unsigned NOT NULL,
        question_order mediumint unsigned NOT NULL default 1,
        PRIMARY KEY (c_id, question_id,exercice_id)
        )" . $charset_clause;
    Database::query($sql);

    /*        Course description	*/

    $sql = "
        CREATE TABLE `".$TABLETOOLCOURSEDESC . "` (
        $add_to_all_tables
        id TINYINT UNSIGNED NOT NULL auto_increment,
        title VARCHAR(255),
        content TEXT,
        session_id smallint default 0,
        description_type tinyint unsigned NOT NULL default 0,
        progress INT NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLCOURSEDESC . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    /*  Course homepage tool list    */

    $sql = "
        CREATE TABLE `" . $tbl_course_homepage . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        name varchar(255) NOT NULL,
        link varchar(255) NOT NULL,
        image varchar(255) default NULL,
        visibility tinyint unsigned default 0,
        admin varchar(255) default NULL,
        address varchar(255) default NULL,
        added_tool tinyint unsigned default 1,
        target enum('_self','_blank') NOT NULL default '_self',
        category varchar(20) not null default 'authoring',
        session_id smallint default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);
    $sql = "ALTER TABLE `".$tbl_course_homepage . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    /*        Agenda tool	   */

    $sql = "
        CREATE TABLE `".$TABLETOOLAGENDA . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(255) NOT NULL,
        content text,
        start_date datetime NOT NULL default '0000-00-00 00:00:00',
        end_date datetime NOT NULL default '0000-00-00 00:00:00',
        parent_event_id INT NULL,
        session_id int unsigned NOT NULL default 0,
        all_day INT NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLAGENDA . "` ADD INDEX ( session_id ) ;";
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLAGENDAREPEAT. "` (
        $add_to_all_tables
        cal_id INT DEFAULT 0 NOT NULL,
        cal_type VARCHAR(20),
        cal_end INT,
        cal_frequency INT DEFAULT 1,
        cal_days CHAR(7),
        PRIMARY KEY (c_id, cal_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLAGENDAREPEATNOT."` (
        $add_to_all_tables
        cal_id INT NOT NULL,
        cal_date INT NOT NULL,
        PRIMARY KEY (c_id, cal_id, cal_date )
        )" . $charset_clause;
    Database::query($sql);

    // Agenda Attachment
    $sql = "CREATE TABLE  `".$TABLETOOLAGENDAATTACHMENT."` (
    			$add_to_all_tables
              id int NOT NULL auto_increment,
              path varchar(255) NOT NULL,
              comment text,
              size int NOT NULL default 0,
              agenda_id int NOT NULL,
              filename varchar(255) NOT NULL,
              PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    /*
        Document tool
    */

    $sql = "
        CREATE TABLE `".$TABLETOOLDOCUMENT . "` (
        	$add_to_all_tables
            id int unsigned NOT NULL auto_increment,
            path varchar(255) NOT NULL default '',
            comment text,
            title varchar(255) default NULL,
            filetype set('file','folder') NOT NULL default 'file',
            size int NOT NULL default 0,
            readonly TINYINT UNSIGNED NOT NULL,
            session_id int UNSIGNED NOT NULL default 0,
            PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    /*
        Student publications
    */
    $sql = "
        CREATE TABLE `".$TABLETOOLWORKS . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        url varchar(255) default NULL,
        title varchar(255) default NULL,
        description text default NULL,
        author varchar(255) default NULL,
        active tinyint default NULL,
        accepted tinyint default 0,
        post_group_id int DEFAULT 0 NOT NULL,
        sent_date datetime NOT NULL default '0000-00-00 00:00:00',
        filetype set('file','folder') NOT NULL default 'file',
        has_properties int UNSIGNED NOT NULL DEFAULT 0,
        view_properties tinyint NULL,
        qualification float(6,2) UNSIGNED NOT NULL DEFAULT 0,
        date_of_qualification datetime NOT NULL default '0000-00-00 00:00:00',
        parent_id INT UNSIGNED NOT NULL DEFAULT 0,
        qualificator_id INT UNSIGNED NOT NULL DEFAULT 0,
        weight float(6,2) UNSIGNED NOT NULL default 0,
        session_id INT UNSIGNED NOT NULL default 0,
        user_id INTEGER  NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLWORKSASS."` (
        $add_to_all_tables
        id int NOT NULL auto_increment,
        expires_on datetime NOT NULL default '0000-00-00 00:00:00',
        ends_on datetime NOT NULL default '0000-00-00 00:00:00',
        add_to_calendar tinyint NOT NULL,
        enable_qualification tinyint NOT NULL,
        publication_id int NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLWORKS . "` ADD INDEX ( session_id )" ;
    Database::query($sql);

    /*
            Links tool    
    */

    $sql = "
        CREATE TABLE `".$TABLETOOLLINK . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        url TEXT NOT NULL,
        title varchar(150) default NULL,
        description text,
        category_id smallint unsigned default NULL,
        display_order smallint unsigned NOT NULL default 0,
        on_homepage enum('0','1') NOT NULL default '0',
        target char(10) default '_self',
        session_id smallint default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLLINK . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLLINKCATEGORIES . "` (
        $add_to_all_tables
        id smallint unsigned NOT NULL auto_increment,
        category_title varchar(255) NOT NULL,
        description text,
        display_order mediumint unsigned NOT NULL default 0,
        session_id smallint default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLLINKCATEGORIES . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    /* Wiki   */

    $sql = "CREATE TABLE `".$TABLETOOLWIKI . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        page_id int NOT NULL default 0,
        reflink varchar(255) NOT NULL default 'index',
        title varchar(255) NOT NULL,
        content mediumtext NOT NULL,
        user_id int NOT NULL default 0,
        group_id int DEFAULT NULL,
        dtime datetime NOT NULL default '0000-00-00 00:00:00',
        addlock int NOT NULL default 1,
        editlock int NOT NULL default 0,
        visibility int NOT NULL default 1,
        addlock_disc int NOT NULL default 1,
        visibility_disc int NOT NULL default 1,
        ratinglock_disc int NOT NULL default 1,
        assignment int NOT NULL default 0,
        comment text NOT NULL,
        progress text NOT NULL,
        score int NULL default 0,
        version int default NULL,
        is_editing int NOT NULL default 0,
        time_edit datetime NOT NULL default '0000-00-00 00:00:00',
        hits int default 0,
        linksto text NOT NULL,
        tag text NOT NULL,
        user_ip varchar(39) NOT NULL,
        session_id smallint default 0,
        PRIMARY KEY (c_id, id),
        KEY reflink (reflink),
        KEY group_id (group_id),
        KEY page_id (page_id),
        KEY session_id (session_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLEWIKICONF . "` (
    	$add_to_all_tables
        page_id int NOT NULL default 0,
        task text NOT NULL,
        feedback1 text NOT NULL,
        feedback2 text NOT NULL,
        feedback3 text NOT NULL,
        fprogress1 varchar(3) NOT NULL,
        fprogress2 varchar(3) NOT NULL,
        fprogress3 varchar(3) NOT NULL,
        max_size int default NULL,
        max_text int default NULL,
        max_version int default NULL,
        startdate_assig datetime NOT NULL default '0000-00-00 00:00:00',
        enddate_assig datetime  NOT NULL default '0000-00-00 00:00:00',
        delayedsubmit int NOT NULL default 0,
        KEY page_id (page_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLEWIKIDISCUSS . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        publication_id int NOT NULL default 0,
        userc_id int NOT NULL default 0,
        comment text NOT NULL,
        p_score varchar(255) default NULL,
        dtime datetime NOT NULL default '0000-00-00 00:00:00',
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLEWIKIMAILCUE . "` (
    	$add_to_all_tables
        id int NOT NULL,
        user_id int NOT NULL,
        type text NOT NULL,
        group_id int DEFAULT NULL,
        session_id smallint default 0,
        KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    /*
        Online
    */

    $sql = "
        CREATE TABLE `".$TABLETOOLONLINECONNECTED . "` (
		$add_to_all_tables
        user_id int unsigned NOT NULL,
        last_connection datetime NOT NULL default '0000-00-00 00:00:00',
        PRIMARY KEY (c_id, user_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLONLINELINK . "` (
        $add_to_all_tables
        id smallint unsigned NOT NULL auto_increment,
        name char(50) NOT NULL default '',
        url char(100) NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLCHATCONNECTED . "` (
        $add_to_all_tables
        user_id int unsigned NOT NULL default '0',
        last_connection datetime NOT NULL default '0000-00-00 00:00:00',
        session_id  INT NOT NULL default 0,
        to_group_id INT NOT NULL default 0
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLCHATCONNECTED . "` ADD INDEX char_connected_index(user_id, session_id, to_group_id) ";
    Database::query($sql);

    /*    
        Groups tool    
    */

    Database::query("CREATE TABLE `".$TABLEGROUPS . "` (
    	$add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        name varchar(100) default NULL,
        category_id int unsigned NOT NULL default 0,
        description text,
        max_student smallint unsigned NOT NULL default 8,
        doc_state tinyint unsigned NOT NULL default 1,
        calendar_state tinyint unsigned NOT NULL default 0,
        work_state tinyint unsigned NOT NULL default 0,
        announcements_state tinyint unsigned NOT NULL default 0,
        forum_state tinyint unsigned NOT NULL default 0,
        wiki_state tinyint unsigned NOT NULL default 1,
        chat_state tinyint unsigned NOT NULL default 1,
        secret_directory varchar(255) default NULL,
        self_registration_allowed tinyint unsigned NOT NULL default '0',
        self_unregistration_allowed tinyint unsigned NOT NULL default '0',
        session_id smallint unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("ALTER TABLE `".$TABLEGROUPS . "` ADD INDEX ( session_id )");

    Database::query("CREATE TABLE `".$TABLEGROUPCATEGORIES . "` (
    	$add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(255) NOT NULL default '',
        description text NOT NULL,
        doc_state tinyint unsigned NOT NULL default 1,
        calendar_state tinyint unsigned NOT NULL default 1,
        work_state tinyint unsigned NOT NULL default 1,
        announcements_state tinyint unsigned NOT NULL default 1,
        forum_state tinyint unsigned NOT NULL default 1,
        wiki_state tinyint unsigned NOT NULL default 1,
        chat_state tinyint unsigned NOT NULL default 1,
        max_student smallint unsigned NOT NULL default 8,
        self_reg_allowed tinyint unsigned NOT NULL default 0,
        self_unreg_allowed tinyint unsigned NOT NULL default 0,
        groups_per_user smallint unsigned NOT NULL default 0,
        display_order smallint unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("CREATE TABLE `".$TABLEGROUPUSER . "` (
    	$add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        user_id int unsigned NOT NULL,
        group_id int unsigned NOT NULL default 0,
        status int NOT NULL default 0,
        role char(50) NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("CREATE TABLE `".$TABLEGROUPTUTOR . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        user_id int NOT NULL,
        group_id int NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("CREATE TABLE `".$TABLEITEMPROPERTY . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        tool varchar(100) NOT NULL default '',
        insert_user_id int unsigned NOT NULL default '0',
        insert_date datetime NOT NULL default '0000-00-00 00:00:00',
        lastedit_date datetime NOT NULL default '0000-00-00 00:00:00',
        ref int NOT NULL default '0',
        lastedit_type varchar(100) NOT NULL default '',
        lastedit_user_id int unsigned NOT NULL default '0',
        to_group_id int unsigned default NULL,
        to_user_id int unsigned default NULL,
        visibility tinyint NOT NULL default '1',
        start_visible datetime NOT NULL default '0000-00-00 00:00:00',
        end_visible datetime NOT NULL default '0000-00-00 00:00:00',
        id_session INT NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("ALTER TABLE `$TABLEITEMPROPERTY` ADD INDEX idx_item_property_toolref (tool,ref)");

    /*           Tool introductions    */
    Database::query("
        CREATE TABLE `".$TABLEINTROS . "` (
        $add_to_all_tables
        id varchar(50) NOT NULL,
        intro_text text NOT NULL,
        session_id INT  NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id, session_id)
        )" . $charset_clause);

    /*
    -----------------------------------------------------------
        Dropbox tool
    -----------------------------------------------------------
    */

    Database::query("
        CREATE TABLE `".$TABLETOOLDROPBOXFILE . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        uploader_id int unsigned NOT NULL default 0,
        filename varchar(250) NOT NULL default '',
        filesize int unsigned NOT NULL,
        title varchar(250) default '',
        description varchar(250) default '',
        author varchar(250) default '',
        upload_date datetime NOT NULL default '0000-00-00 00:00:00',
        last_upload_date datetime NOT NULL default '0000-00-00 00:00:00',
        cat_id int NOT NULL default 0,
        session_id SMALLINT UNSIGNED NOT NULL,
        PRIMARY KEY (c_id, id),
        UNIQUE KEY UN_filename (filename)
        )" . $charset_clause);

    Database::query("ALTER TABLE `$TABLETOOLDROPBOXFILE` ADD INDEX ( session_id )");

    Database::query("
        CREATE TABLE `".$TABLETOOLDROPBOXPOST . "` (
        $add_to_all_tables
        file_id int unsigned NOT NULL,
        dest_user_id int unsigned NOT NULL default 0,
        feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
        feedback text default '',
        cat_id int NOT NULL default 0,
        session_id SMALLINT UNSIGNED NOT NULL,
        PRIMARY KEY (c_id, file_id,dest_user_id)
        )" . $charset_clause);

    Database::query("ALTER TABLE `$TABLETOOLDROPBOXPOST` ADD INDEX ( session_id )");

    Database::query("
        CREATE TABLE `".$TABLETOOLDROPBOXPERSON . "` (
        $add_to_all_tables
        file_id int unsigned NOT NULL,
        user_id int unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, file_id,user_id)
        )" . $charset_clause);

    $sql = "CREATE TABLE `".$TABLETOOLDROPBOXCATEGORY."` (
    		  $add_to_all_tables
              cat_id int NOT NULL auto_increment,
              cat_name text NOT NULL,
              received tinyint unsigned NOT NULL default 0,
              sent tinyint unsigned NOT NULL default 0,
              user_id int NOT NULL default 0,
              session_id smallint NOT NULL default 0,
              PRIMARY KEY  (c_id, cat_id)
              )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLDROPBOXCATEGORY . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLETOOLDROPBOXFEEDBACK."` (
    			$add_to_all_tables
              feedback_id int NOT NULL auto_increment,
              file_id int NOT NULL default 0,
              author_user_id int NOT NULL default 0,
              feedback text NOT NULL,
              feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
              PRIMARY KEY  (c_id, feedback_id),
              KEY file_id (file_id),
              KEY author_user_id (author_user_id)
              )" . $charset_clause;
    Database::query($sql);

    /*
    -----------------------------------------------------------
        New learning path tool
    -----------------------------------------------------------
    */

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELP` (
    	$add_to_all_tables 
    	" .
        "id             int unsigned        auto_increment," .  // unique ID, generated by MySQL
        "lp_type        smallint unsigned   not null," .                    // lp_types can be found in the main database's lp_type table
        "name           varchar(255)        not null," .                    // name is the text name of the learning path (e.g. Word 2000)
        "ref            tinytext            null," .                        // ref for SCORM elements is the SCORM ID in imsmanifest. For other learnpath types, just ignore
        "description    text                null,".                         // textual description
        "path           text                not null," .                    // path, starting at the platforms root (so all paths should start with 'courses/...' for now)
        "force_commit   tinyint unsigned    not null default 0, " .         // stores the default behaviour regarding SCORM information
        "default_view_mod   char(32)        not null default 'embedded'," . // stores the default view mode (embedded or fullscreen)
        "default_encoding   char(32)        not null default 'UTF-8', " .   // stores the encoding detected at learning path reading
        "display_order  int unsigned        not null default 0," .          // order of learnpaths display in the learnpaths list - not really important
        "content_maker  tinytext            not null default ''," .         // the content make for this course (ENI, Articulate, ...)
        "content_local  varchar(32)         not null default 'local'," .    // content localisation ('local' or 'distant')
        "content_license    text            not null default ''," .         // content license
        "prevent_reinit tinyint unsigned    not null default 1," .          // stores the default behaviour regarding items re-initialisation when viewed a second time after success
        "js_lib         tinytext            not null default ''," .         // the JavaScript library to load for this lp
        "debug          tinyint unsigned    not null default 0," .          // stores the default behaviour regarding items re-initialisation when viewed a second time after success
        "theme          varchar(255)        not null default '', " .        // stores the theme of the LP
        "preview_image  varchar(255)        not null default '', " .        // stores the theme of the LP
        "author         varchar(255)        not null default '', " .        // stores the theme of the LP
        "session_id     int unsigned        not null default 0, " .         // the session_id
		"prerequisite  	int	unsigned not null  default 0," . // pre requisite for next lp
		"hide_toc_frame tinyint NOT NULL DEFAULT 0, ".
        "seriousgame_mode tinyint NOT NULL DEFAULT 0, ".
        "use_max_score  int unsigned        not null default 1, " .
        "autolunch      int unsigned        not null default 0, " .          // auto lunch LP
        "created_on     DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', " .   
        "modified_on    DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', " .   
        "publicated_on  DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', " .   
        "expired_on     DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',          
    	 PRIMARY KEY  (c_id, id),
        	)" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPVIEW` (
    	$add_to_all_tables" .
        "id             int unsigned        auto_increment," .  // unique ID from MySQL
        "lp_id          int unsigned        not null," .                    // learnpath ID from 'lp'
        "user_id        int unsigned        not null," .                    // user ID from main.user
        "view_count     smallint unsigned   not null default 0," .          // integer counting the amount of times this learning path has been attempted
        "last_item      int unsigned        not null default 0," .          // last item seen in this view
        "progress       int unsigned        default 0," .
        "session_id     int                 not null default 0,
         PRIMARY KEY  (c_id, id),
        
    	)" . $charset_clause; // lp's progress for this user

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (lp_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (user_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (session_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPITEM` (
    	$add_to_all_tables
    	" .
        "id              int unsigned       auto_increment," .  // unique ID from MySQL
        "lp_id          int unsigned        not null," .                    // lp_id from 'lp'
        "item_type      char(32)            not null default 'dokeos_document'," .  // can be dokeos_document, dokeos_chapter or scorm_asset, scorm_sco, scorm_chapter
        "ref            tinytext            not null default ''," .         // the ID given to this item in the imsmanifest file
        "title          varchar(511)        not null," .                    // the title/name of this item (to display in the T.O.C.)
        "description    varchar(511)        not null default ''," .         // the description of this item - deprecated
        "path           text                not null," .                    // the path to that item, starting at 'courses/...' level
        "min_score      float unsigned      not null default 0," .          // min score allowed
        "max_score      float unsigned      default 100," .                 // max score allowed
        "mastery_score  float unsigned      null," .                        // minimum score to pass the test
        "parent_item_id     int unsigned    not null default 0," .          // the item one level higher
        "previous_item_id   int unsigned    not null default 0," .          // the item before this one in the sequential learning order (MySQL id)
        "next_item_id       int unsigned    not null default 0," .          // the item after this one in the sequential learning order (MySQL id)
        "display_order      int unsigned    not null default 0," .          // this is needed for ordering items under the same parent (previous_item_id doesn't give correct order after reordering)
        "prerequisite   text                null default null," .           // prerequisites in AICC scripting language as defined in the SCORM norm (allow logical operators)
        "parameters     text                null," .                        // prerequisites in AICC scripting language as defined in the SCORM norm (allow logical operators)
        "launch_data    text                not null default ''," .         // data from imsmanifest <item>
        "max_time_allowed   char(13)        NULL default ''," .             // data from imsmanifest <adlcp:maxtimeallowed>
        "terms          TEXT                NULL," .                        // contains the indexing tags (search engine)
        "search_did     INT                 NULL,".                         // contains the internal search-engine id of this element
        "audio          VARCHAR(250),
        PRIMARY KEY  (c_id, id),
    
    	)" . $charset_clause;                   // contains the audio file that goes with the learning path step

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPITEM` ADD INDEX (lp_id)";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPITEMVIEW` (
    	$add_to_all_tables
    	" .
        "id             bigint unsigned auto_increment," .      // unique ID
        "lp_item_id     int unsigned    not null," .                        // item ID (MySQL id)
        "lp_view_id     int unsigned    not null," .                        // learning path view id (attempt)
        "view_count     int unsigned    not null default 0," .              // how many times this item has been viewed in the current attempt (generally 0 or 1)
        "start_time     int unsigned    not null," .                        // when did the user open it?
        "total_time     int unsigned    not null default 0," .              // after how many seconds did he close it?
        "score          float unsigned  not null default 0," .              // score returned by SCORM or other techs
        "status         char(32)        not null default 'not attempted'," .    // status for this item (SCORM)
		"suspend_data	longtext null default ''," .
        "lesson_location    text        null default ''," .
        "core_exit      varchar(32)     not null default 'none'," .
        "max_score      varchar(8)      default '',
        PRIMARY KEY  (c_id, id),        
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPITEMVIEW` ADD INDEX (lp_item_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPITEMVIEW` ADD INDEX (lp_view_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPIVINTERACTION`(
    	 $add_to_all_tables" .
        "id             bigint unsigned     auto_increment," .
        "order_id       smallint unsigned   not null default 0,".           // internal order (0->...) given by Dokeos
        "lp_iv_id       bigint unsigned     not null," .                    // identifier of the related sco_view
        "interaction_id varchar(255)        not null default ''," .         // sco-specific, given by the sco
        "interaction_type   varchar(255)    not null default ''," .         // literal values, SCORM-specific (see p.63 of SCORM 1.2 RTE)
        "weighting          double          not null default 0," .
        "completion_time    varchar(16)     not null default ''," .         // completion time for the interaction (timestamp in a day's time) - expected output format is scorm time
        "correct_responses  text            not null default ''," .         // actually a serialised array. See p.65 os SCORM 1.2 RTE)
        "student_response   text            not null default ''," .         // student response (format depends on type)
        "result         varchar(255)        not null default ''," .         // textual result
        "latency        varchar(16)         not null default ''," .          // time necessary for completion of the interaction
    	"PRIMARY KEY  (c_id, id),".
        ")" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPIVINTERACTION` ADD INDEX (lp_iv_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPIVOBJECTIVE`(
    	$add_to_all_tables" .
        "id             bigint unsigned     auto_increment," .
        "lp_iv_id       bigint unsigned     not null," .                    // identifier of the related sco_view
        "order_id       smallint unsigned   not null default 0,".           // internal order (0->...) given by Dokeos
        "objective_id   varchar(255)        not null default ''," .         // sco-specific, given by the sco
        "score_raw      float unsigned      not null default 0," .          // score
        "score_max      float unsigned      not null default 0," .          // max score
        "score_min      float unsigned      not null default 0," .          // min score
        "status         char(32)            not null default 'not attempted', " . //status, just as sco status
    	"PRIMARY KEY  (c_id, id),".
        ")" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `$TABLELPIVOBJECTIVE` ADD INDEX (lp_iv_id) ";
    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    /* Blogs */

    $sql = "
        CREATE TABLE `" . $tbl_blogs . "` (
            $add_to_all_tables
            blog_id smallint NOT NULL AUTO_INCREMENT ,
            blog_name varchar( 250 ) NOT NULL default '',
            blog_subtitle varchar( 250 ) default NULL ,
            date_creation datetime NOT NULL default '0000-00-00 00:00:00',
            visibility tinyint unsigned NOT NULL default 0,
            session_id smallint default 0,
            PRIMARY KEY (c_id, blog_id )
        )" . $charset_clause . " COMMENT = 'Table with blogs in this course';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "ALTER TABLE `".$tbl_blogs . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_comments . "` (
        	$add_to_all_tables
            comment_id int NOT NULL AUTO_INCREMENT ,
            title varchar( 250 ) NOT NULL default '',
            comment longtext NOT NULL ,
            author_id int NOT NULL default 0,
            date_creation datetime NOT NULL default '0000-00-00 00:00:00',
            blog_id mediumint NOT NULL default 0,
            post_id int NOT NULL default 0,
            task_id int default NULL ,
            parent_comment_id int NOT NULL default 0,
            PRIMARY KEY (c_id, comment_id )
        )" . $charset_clause . " COMMENT = 'Table with comments on posts in a blog';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_blogs_posts . "` (
        	$add_to_all_tables
            post_id int NOT NULL AUTO_INCREMENT ,
            title varchar( 250 ) NOT NULL default '',
            full_text longtext NOT NULL ,
            date_creation datetime NOT NULL default '0000-00-00 00:00:00',
            blog_id mediumint NOT NULL default 0,
            author_id int NOT NULL default 0,
            PRIMARY KEY (c_id, post_id )
        )" . $charset_clause . " COMMENT = 'Table with posts / blog.';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_blogs_rating . "` (
        	$add_to_all_tables
            rating_id int NOT NULL AUTO_INCREMENT ,
            blog_id int NOT NULL default 0,
            rating_type enum( 'post', 'comment' ) NOT NULL default 'post',
            item_id int NOT NULL default 0,
            user_id int NOT NULL default 0,
            rating mediumint NOT NULL default 0,
            PRIMARY KEY (c_id, rating_id )
        )" . $charset_clause . " COMMENT = 'Table with ratings for post/comments in a certain blog';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_blogs_rel_user . "` (
        	$add_to_all_tables
            blog_id int NOT NULL default 0,
            user_id int NOT NULL default 0,
            PRIMARY KEY ( c_id, blog_id , user_id )
        )" . $charset_clause . " COMMENT = 'Table representing users subscribed to a blog';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_blogs_tasks . "` (
        	$add_to_all_tables
            task_id mediumint NOT NULL AUTO_INCREMENT ,
            blog_id mediumint NOT NULL default 0,
            title varchar( 250 ) NOT NULL default '',
            description text NOT NULL ,
            color varchar( 10 ) NOT NULL default '',
            system_task tinyint unsigned NOT NULL default 0,
            PRIMARY KEY (c_id, task_id )
        )" . $charset_clause . " COMMENT = 'Table with tasks for a blog';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_blogs_tasks_rel_user . "` (
        	$add_to_all_tables
            blog_id mediumint NOT NULL default 0,
            user_id int NOT NULL default 0,
            task_id mediumint NOT NULL default 0,
            target_date date NOT NULL default '0000-00-00',
            PRIMARY KEY (c_id, blog_id , user_id , task_id )
        )" . $charset_clause . " COMMENT = 'Table with tasks assigned to a user in a blog';";

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql ="CREATE TABLE  `" .$tbl_blogs_attachment."` (
    	  $add_to_all_tables
          id int unsigned NOT NULL auto_increment,
          path varchar(255) NOT NULL COMMENT 'the real filename',
          comment text,
          size int NOT NULL default '0',
          post_id int NOT NULL,
          filename varchar(255) NOT NULL COMMENT 'the user s file name',
          blog_id int NOT NULL,
          comment_id int NOT NULL default '0',
          PRIMARY KEY  (c_id, id)
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_permission_group . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT ,
            group_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 250 ) NOT NULL default '',
            PRIMARY KEY (c_id, id)
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_permission_user . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT ,
            user_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 250 ) NOT NULL default '',
            PRIMARY KEY (c_id, id )
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_permission_task . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT ,
            task_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 250 ) NOT NULL default '',
            PRIMARY KEY (c_id, id )
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_role . "` (
        	$add_to_all_tables
            role_id int NOT NULL AUTO_INCREMENT ,
            role_name varchar( 250 ) NOT NULL default '',
            role_comment text,
            default_role tinyint default 0,
            PRIMARY KEY (c_id, role_id )
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_role_group . "` (
        	$add_to_all_tables
            role_id int NOT NULL default 0,
            scope varchar( 20 ) NOT NULL default 'course',
            group_id int NOT NULL default 0
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_role_permissions . "` (
        	$add_to_all_tables
            role_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 50 ) NOT NULL default '',
            default_perm tinyint NOT NULL default 0
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    $sql = "
        CREATE TABLE `" . $tbl_role_user . "` (
        	$add_to_all_tables
            role_id int NOT NULL default 0,
            scope varchar( 20 ) NOT NULL default 'course',
            user_id int NOT NULL default 0
        )" . $charset_clause;

    if (!Database::query($sql)) {
        error_log($sql, 0);
    }

    /*    
     * Course Config Settings 
     * 
     */

    Database::query("
        CREATE TABLE `".$TABLESETTING . "` (
        $add_to_all_tables
        id          int unsigned NOT NULL auto_increment,
        variable    varchar(255) NOT NULL default '',
        subkey      varchar(255) default NULL,
        type        varchar(255) default NULL,
        category    varchar(255) default NULL,
        value       varchar(255) NOT NULL default '',
        title       varchar(255) NOT NULL default '',
        comment     varchar(255) default NULL,
        subkeytext  varchar(255) default NULL,
        PRIMARY KEY (c_id, id)
         )" . $charset_clause);

    /*    
        Survey    
    */

    $sql = "CREATE TABLE `".$TABLESURVEY."` (
    		$add_to_all_tables
              survey_id int unsigned NOT NULL auto_increment,
              code varchar(20) default NULL,
              title text default NULL,
              subtitle text default NULL,
              author varchar(20) default NULL,
              lang varchar(20) default NULL,
              avail_from date default NULL,
              avail_till date default NULL,
              is_shared char(1) default '1',
              template varchar(20) default NULL,
              intro text,
              surveythanks text,
              creation_date datetime NOT NULL default '0000-00-00 00:00:00',
              invited int NOT NULL,
              answered int NOT NULL,
              invite_mail text NOT NULL,
              reminder_mail text NOT NULL,
              mail_subject VARCHAR( 255 ) NOT NULL,
              anonymous enum('0','1') NOT NULL default '0',
              access_condition TEXT DEFAULT NULL,
              shuffle bool NOT NULL default '0',
              one_question_per_page bool NOT NULL default '0',
              survey_version varchar(255) NOT NULL default '',
              parent_id int unsigned NOT NULL,
              survey_type int NOT NULL default 0,
              show_form_profile int NOT NULL default 0,
              form_fields TEXT NOT NULL,
              session_id SMALLINT unsigned NOT NULL default 0,
              PRIMARY KEY  (c_id, survey_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "ALTER TABLE `".$TABLESURVEY."` ADD INDEX ( session_id )";
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYINVITATION."` (
    		  $add_to_all_tables
              survey_invitation_id int unsigned NOT NULL auto_increment,
              survey_code varchar(20) NOT NULL,
              user varchar(250) NOT NULL,
              invitation_code varchar(250) NOT NULL,
              invitation_date datetime NOT NULL,
              reminder_date datetime NOT NULL,
              answered int NOT NULL default 0,
              session_id SMALLINT(5) UNSIGNED NOT NULL default 0,
              PRIMARY KEY  (c_id, survey_invitation_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYQUESTION."` (
    		  $add_to_all_tables
              question_id int unsigned NOT NULL auto_increment,
              survey_id int unsigned NOT NULL,
              survey_question text NOT NULL,
              survey_question_comment text NOT NULL,
              type varchar(250) NOT NULL,
              display varchar(10) NOT NULL,
              sort int NOT NULL,
              shared_question_id int,
              max_value int,
              survey_group_pri int unsigned NOT NULL default '0',
              survey_group_sec1 int unsigned NOT NULL default '0',
              survey_group_sec2 int unsigned NOT NULL default '0',
              PRIMARY KEY  (c_id, question_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql ="CREATE TABLE `".$TABLESURVEYQUESTIONOPTION."` (
    	$add_to_all_tables
      question_option_id int unsigned NOT NULL auto_increment,
      question_id int unsigned NOT NULL,
      survey_id int unsigned NOT NULL,
      option_text text NOT NULL,
      sort int NOT NULL,
      value int NOT NULL default '0',
      PRIMARY KEY  (c_id, question_option_id)
    )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYANSWER."` (
    		  $add_to_all_tables
              answer_id int unsigned NOT NULL auto_increment,
              survey_id int unsigned NOT NULL,
              question_id int unsigned NOT NULL,
              option_id TEXT NOT NULL,
              value int unsigned NOT NULL,
              user varchar(250) NOT NULL,
              PRIMARY KEY  (c_id, answer_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYGROUP."` (
				$add_to_all_tables    
              id int unsigned NOT NULL auto_increment,
              name varchar(20) NOT NULL,
              description varchar(255) NOT NULL,
              survey_id int unsigned NOT NULL,
              PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    // Table glosary
    $sql = "CREATE TABLE `".$TBL_GLOSSARY."` (
    		  $add_to_all_tables
              glossary_id int unsigned NOT NULL auto_increment,
              name varchar(255) NOT NULL,
              description text not null,
              display_order int,
              session_id smallint default 0,
              PRIMARY KEY  (c_id, glossary_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "ALTER TABLE `".$TBL_GLOSSARY . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Table notebook
    $sql = "CREATE TABLE `".$TBL_NOTEBOOK."` (
    		  $add_to_all_tables
              notebook_id int unsigned NOT NULL auto_increment,
              user_id int unsigned NOT NULL,
              course varchar(40) not null,
              session_id int NOT NULL default 0,
              title varchar(255) NOT NULL,
              description text NOT NULL,
              creation_date datetime NOT NULL default '0000-00-00 00:00:00',
              update_date datetime NOT NULL default '0000-00-00 00:00:00',
              status int,
              PRIMARY KEY  (c_id, notebook_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    /* Attendance tool */

    // Attendance table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE."` (
        	$add_to_all_tables
            id int NOT NULL auto_increment,
            name text NOT NULL,
            description TEXT NULL,
            active tinyint NOT NULL default 1,
            attendance_qualify_title varchar(255) NULL,
            attendance_qualify_max int NOT NULL default 0,
            attendance_weight float(6,2) NOT NULL default '0.0',
            session_id int NOT NULL default 0,
            locked int NOT NULL default 0,
            PRIMARY KEY  (c_id, id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE . "` ADD INDEX (session_id)";
    Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE . "` ADD INDEX (active)";
    Database::query($sql);

    // Attendance sheet table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE_SHEET."` (
        	$add_to_all_tables
            user_id int NOT NULL,
            attendance_calendar_id int NOT NULL,
            presence tinyint NOT NULL DEFAULT 0,
            PRIMARY KEY(c_id, user_id, attendance_calendar_id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE_SHEET . "` ADD INDEX (presence) ";
    Database::query($sql);

    // Attendance calendar table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE_CALENDAR."` (
        	$add_to_all_tables
            id int NOT NULL auto_increment,
            attendance_id int NOT NULL ,
            date_time datetime NOT NULL default '0000-00-00 00:00:00',
            done_attendance tinyint NOT NULL default 0,
            PRIMARY KEY(c_id, id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE_CALENDAR."` ADD INDEX (attendance_id)";
    Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE_CALENDAR."` ADD INDEX (done_attendance)";
    Database::query($sql);

    // Attendance result table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE_RESULT."` (
        	$add_to_all_tables
            id int NOT NULL auto_increment,
            user_id int NOT NULL,
            attendance_id int NOT NULL,
            score int NOT NULL DEFAULT 0,
            PRIMARY KEY  (c_id, id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_ATTENDANCE_RESULT."` ADD INDEX (attendance_id)";
    Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_ATTENDANCE_RESULT."` ADD INDEX (user_id)";
    Database::query($sql);
    
    // attendance sheet log table
    $sql = "CREATE TABLE `".$TBL_ATTENDANCE_SHEET_LOG."` (
    			  $add_to_all_tables
                  id int  NOT NULL auto_increment,
                  attendance_id int  NOT NULL DEFAULT 0,
                  lastedit_date datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
                  lastedit_type varchar(200)  NOT NULL,
                  lastedit_user_id int  NOT NULL DEFAULT 0,
                  calendar_date_value datetime NULL,
                  PRIMARY KEY (c_id, id)
                )" . $charset_clause;
    $result = Database::query($sql) or die(Database::error());
    

    // Thematic table
    $sql = "CREATE TABLE `".$TBL_THEMATIC."` (
    			$add_to_all_tables
                id int NOT NULL auto_increment,
                title varchar(255) NOT NULL,
                content text NULL,
                display_order int unsigned NOT NULL DEFAULT 0,
                active tinyint NOT NULL DEFAULT 0,
                session_id int NOT NULL DEFAULT 0,
                PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_THEMATIC."` ADD INDEX (active, session_id)";
    Database::query($sql);

    // thematic plan table
    $sql = "CREATE TABLE `".$TBL_THEMATIC_PLAN."` (
            	$add_to_all_tables
                id int NOT NULL auto_increment,
                thematic_id int NOT NULL,
                title varchar(255) NOT NULL,
                description text NULL,
                description_type int NOT NULL,
                PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_THEMATIC_PLAN."` ADD INDEX (thematic_id, description_type)";
    Database::query($sql);

    // thematic advance table
    $sql = "
            CREATE TABLE `".$TBL_THEMATIC_ADVANCE."` (
            	$add_to_all_tables
                id int NOT NULL auto_increment,
                thematic_id int NOT NULL,
                attendance_id int NOT NULL DEFAULT 0,
                content text NULL,
                start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                duration int NOT NULL DEFAULT 0,
                done_advance tinyint NOT NULL DEFAULT 0,
                PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_THEMATIC_ADVANCE."` ADD INDEX (thematic_id)";
    Database::query($sql);

    return 0;
}

function browse_folders($path, $files, $media) {
    if ($media == 'images') {
        $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
    }
    if ($media == 'audio') {
        $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/audio/';
    }
    if ($media == 'flash') {
        $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/flash/';
    }
    if ($media == 'video') {
        $code_path = api_get_path(SYS_CODE_PATH).'default_course_document/video/';
    }
    if (is_dir($path)) {
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if (is_dir($path.$file) && strpos($file, '.') !== 0) {
                $files[]['dir'] = str_replace($code_path, '', $path.$file.'/');
                $files = browse_folders($path.$file.'/', $files, $media);
            } elseif (is_file($path.$file) && strpos($file, '.') !== 0) {
                $files[]['file'] = str_replace($code_path, '', $path.$file);
            }
        }
    }
    return $files;
}

function sort_pictures($files, $type) {
    $pictures = array();
    foreach ($files as $key => $value){
        if ($value[$type] != '') {
            $pictures[][$type] = $value[$type];
        }
    }
    return $pictures;
}

/**
 * Fills the course repository with some
 * example content.
 * @version	 1.2
 */
function fill_course_repository($course_repository, $fill_with_exemplary_content = null) {

    if (is_null($fill_with_exemplary_content)) {
        $fill_with_exemplary_content = api_get_setting('example_material_course_creation') != 'false';
    }

    $sys_course_path = api_get_path(SYS_COURSE_PATH);
    $web_code_path = api_get_path(WEB_CODE_PATH);

    $perm = api_get_permissions_for_new_directories();
    $perm_file = api_get_permissions_for_new_files();

    $default_document_array = array();

    if ($fill_with_exemplary_content) {

        $img_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
        $audio_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/audio/';
        $flash_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/flash/';
        $video_code_path = api_get_path(SYS_CODE_PATH).'default_course_document/video/';
        $course_documents_folder_images = $sys_course_path.$course_repository.'/document/images/gallery/';
        $course_documents_folder_audio = $sys_course_path.$course_repository.'/document/audio/';
        $course_documents_folder_flash = $sys_course_path.$course_repository.'/document/flash/';
        $course_documents_folder_video = $sys_course_path.$course_repository.'/document/video/';

        /*
         * Images
         */
        $files = array();

        $files = browse_folders($img_code_path, $files, 'images');

        $pictures_array = sort_pictures($files, 'dir');
        $pictures_array = array_merge($pictures_array, sort_pictures($files, 'file'));

        if (!is_dir($course_documents_folder_images)) {
            mkdir($course_documents_folder_images,$perm);
        }

        $handle = opendir($img_code_path);

        foreach ($pictures_array as $key => $value) {
            if ($value['dir'] != '') {
                mkdir($course_documents_folder_images.$value['dir'], $perm);
            }
            if ($value['file'] != '') {
                copy($img_code_path.$value['file'], $course_documents_folder_images.$value['file']);
                chmod($course_documents_folder_images.$value['file'], $perm_file);
            }
        }

        // Trainer thumbnails fix.

        $path_thumb = mkdir($course_documents_folder_images.'trainer/.thumbs', $perm);
        $handle = opendir($img_code_path.'trainer/.thumbs/');

        while (false !== ($file = readdir($handle))) {
            if (is_file($img_code_path.'trainer/.thumbs/'.$file)) {
                copy($img_code_path.'trainer/.thumbs/'.$file, $course_documents_folder_images.'trainer/.thumbs/'.$file);
                chmod($course_documents_folder_images.'trainer/.thumbs/'.$file, $perm_file);
            }
        }

        $default_document_array['images'] = $pictures_array;

        /*
         * Audio
         */
        $files = array();

        $files = browse_folders($audio_code_path, $files, 'audio');

        $audio_array = sort_pictures($files, 'dir');
        $audio_array = array_merge($audio_array,sort_pictures($files, 'file'));

        if (!is_dir($course_documents_folder_audio)) {
            mkdir($course_documents_folder_audio, $perm);
        }

        $handle = opendir($audio_code_path);

        foreach ($audio_array as $key => $value){

            if ($value['dir'] != '') {
                mkdir($course_documents_folder_audio.$value['dir'], $perm);
            }
            if ($value['file'] != '') {
                copy($audio_code_path.$value['file'], $course_documents_folder_audio.$value['file']);
                chmod($course_documents_folder_audio.$value['file'], $perm_file);
            }

        }
        $default_document_array['audio'] = $audio_array;

        /*
         * Flash
         */
        $files = array();

        $files = browse_folders($flash_code_path, $files, 'flash');

        $flash_array = sort_pictures($files, 'dir');
        $flash_array = array_merge($flash_array, sort_pictures($files, 'file'));

        if (!is_dir($course_documents_folder_flash)) {
            mkdir($course_documents_folder_flash, $perm);
        }

        $handle = opendir($flash_code_path);

        foreach ($flash_array as $key => $value) {

            if ($value['dir'] != '') {
                mkdir($course_documents_folder_flash.$value['dir'], $perm);
            }
            if ($value['file'] != '') {
                copy($flash_code_path.$value['file'], $course_documents_folder_flash.$value['file']);
                chmod($course_documents_folder_flash.$value['file'], $perm_file);
            }

        }
        $default_document_array['flash'] = $flash_array;

        /*
         * Video
         */
        $files = array();

        $files = browse_folders($video_code_path, $files, 'video');

        $video_array = sort_pictures($files, 'dir');
        $video_array = array_merge($video_array, sort_pictures($files, 'file'));

        if (!is_dir($course_documents_folder_video)) {
            mkdir($course_documents_folder_video, $perm);
        }

        $handle = opendir($video_code_path);

        foreach ($video_array as $key => $value) {

            if ($value['dir'] != '') {
                @mkdir($course_documents_folder_video.$value['dir'], $perm);
            }
            if ($value['file'] != '') {
                copy($video_code_path.$value['file'], $course_documents_folder_video.$value['file']);
                chmod($course_documents_folder_video.$value['file'], $perm_file);
            }

        }
        $default_document_array['video'] = $video_array;

    }

    return $default_document_array;
}

/**
 * Function to convert a string from the Dokeos language files to a string ready
 * to insert into the database.
 * @author Bart Mollet (bart.mollet@hogent.be)
 * @param string $string The string to convert
 * @return string The string converted to insert into the database
 */
function lang2db($string) {
    $string = str_replace("\\'", "'", $string);
    $string = Database::escape_string($string);
    return $string;
}

/**
 * Fills the course database with some required content and example content.
 * @version 1.2
 */
function fill_Db_course($course_id, $course_repository, $language, $default_document_array = array(), $fill_with_exemplary_content = null) {
    if (is_null($fill_with_exemplary_content)) {
        $fill_with_exemplary_content = api_get_setting('example_material_course_creation') != 'false';
    }
    global $_configuration, $_user;
    $course_id = intval($course_id);
    if (empty($course_id)) {
    	return false;	
    }
    
    $tbl_course_homepage 	= Database::get_course_table(TABLE_TOOL_LIST);
    $TABLEINTROS 			= Database::get_course_table(TABLE_TOOL_INTRO);

    $TABLEGROUPS 			= Database::get_course_table(TABLE_GROUP);
    $TABLEGROUPCATEGORIES 	= Database::get_course_table(TABLE_GROUP_CATEGORY);
    $TABLEGROUPUSER 		= Database::get_course_table(TABLE_GROUP_USER);

    $TABLEITEMPROPERTY 		= Database::get_course_table(TABLE_ITEM_PROPERTY);

    $TABLETOOLCOURSEDESC 	= Database::get_course_table(TABLE_COURSE_DESCRIPTION);
    $TABLETOOLAGENDA 		= Database::get_course_table(TABLE_AGENDA);
    $TABLETOOLANNOUNCEMENTS = Database::get_course_table(TABLE_ANNOUNCEMENT);
    $TABLEADDEDRESOURCES 	= Database::get_course_table(TABLE_LINKED_RESOURCES);
    $TABLETOOLWORKS 		= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
    //$TABLETOOLWORKSUSER 	= Database::get_course_table(TABLE_TOOL_LIST)$course_db_name . 'stud_pub_rel_user';
    $TABLETOOLDOCUMENT 		= Database::get_course_table(TABLE_DOCUMENT);
    $TABLETOOLWIKI 			= Database::get_course_table(TABLE_WIKI);

    $TABLETOOLLINK 			= Database::get_course_table(TABLE_LINK);

    $TABLEQUIZ 				= Database::get_course_table(TABLE_QUIZ_TEST);
    $TABLEQUIZQUESTION 		= Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $TABLEQUIZQUESTIONLIST 	= Database::get_course_table(TABLE_QUIZ_QUESTION);
    $TABLEQUIZANSWERSLIST 	= Database::get_course_table(TABLE_QUIZ_ANSWER);
    $TABLESETTING 			= Database::get_course_table(TABLE_COURSE_SETTING);

    $TABLEFORUMCATEGORIES 	= Database::get_course_table(TABLE_FORUM_CATEGORY);
    $TABLEFORUMS 			= Database::get_course_table(TABLE_FORUM);
    $TABLEFORUMTHREADS 		= Database::get_course_table(TABLE_FORUM_THREAD);
    $TABLEFORUMPOSTS 		= Database::get_course_table(TABLE_FORUM_POST);

    $nom 	= $_user['lastName'];
    $prenom = $_user['firstName'];

    include api_get_path(SYS_CODE_PATH) . 'lang/english/create_course.inc.php';
    $file_to_include = 'lang/'.$language . '/create_course.inc.php';
    if (file_exists($file_to_include)) {
        include api_get_path(SYS_CODE_PATH) . $file_to_include;
    }

    //Database::select_db($course_db_name);
    /*
    
            All course tables are created.
            Next sections of the script:
            - insert links to all course tools so they can be accessed on the course homepage
            - fill the tool tables with examples
    
    */

    $visible_for_all = 1;
    $visible_for_course_admin = 0;
    $visible_for_platform_admin = 2;

    /*    
        Course homepage tools    
    */

    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_COURSE_DESCRIPTION . "','course_description/','info.gif','".string2binary(api_get_setting('course_create_active_tools', 'course_description')) . "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_CALENDAR_EVENT . "','calendar/agenda.php','agenda.gif','".string2binary(api_get_setting('course_create_active_tools', 'agenda')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_DOCUMENT . "','document/document.php','folder_document.gif','".string2binary(api_get_setting('course_create_active_tools', 'documents')) . "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_LEARNPATH . "','newscorm/lp_controller.php','scorms.gif','".string2binary(api_get_setting('course_create_active_tools', 'learning_path')) . "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_LINK . "','link/link.php','links.gif','".string2binary(api_get_setting('course_create_active_tools', 'links')) . "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_QUIZ . "','exercice/exercice.php','quiz.gif','".string2binary(api_get_setting('course_create_active_tools', 'quiz')) . "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_ANNOUNCEMENT . "','announcements/announcements.php','valves.gif','".string2binary(api_get_setting('course_create_active_tools', 'announcements')) . "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_FORUM . "','forum/index.php','forum.gif','".string2binary(api_get_setting('course_create_active_tools', 'forums')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_DROPBOX . "','dropbox/index.php','dropbox.gif','".string2binary(api_get_setting('course_create_active_tools', 'dropbox')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_USER . "','user/user.php','members.gif','".string2binary(api_get_setting('course_create_active_tools', 'users')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_GROUP . "','group/group.php','group.gif','".string2binary(api_get_setting('course_create_active_tools', 'groups')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_CHAT . "','chat/chat.php','chat.gif','".string2binary(api_get_setting('course_create_active_tools', 'chat')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_STUDENTPUBLICATION . "','work/work.php','works.gif','".string2binary(api_get_setting('course_create_active_tools', 'student_publications')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_SURVEY."','survey/survey_list.php','survey.gif','".string2binary(api_get_setting('course_create_active_tools', 'survey')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_WIKI ."','wiki/index.php','wiki.gif','".string2binary(api_get_setting('course_create_active_tools', 'wiki')) . "','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_GRADEBOOK."','gradebook/index.php','gradebook.gif','".string2binary(api_get_setting('course_create_active_tools', 'gradebook')). "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_GLOSSARY."','glossary/index.php','glossary.gif','".string2binary(api_get_setting('course_create_active_tools', 'glossary')). "','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_NOTEBOOK."','notebook/index.php','notebook.gif','".string2binary(api_get_setting('course_create_active_tools', 'notebook'))."','0','squaregrey.gif','NO','_self','interaction','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_ATTENDANCE."','attendance/index.php','attendance.gif','".string2binary(api_get_setting('course_create_active_tools', 'attendances'))."','0','squaregrey.gif','NO','_self','authoring','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_COURSE_PROGRESS."','course_progress/index.php','course_progress.gif','".string2binary(api_get_setting('course_create_active_tools', 'course_progress'))."','0','squaregrey.gif','NO','_self','authoring','0')");

    if (api_get_setting('service_visio', 'active') == 'true') {
        $mycheck = api_get_setting('service_visio', 'visio_host');
        if (!empty($mycheck)) {
            Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_VISIO_CONFERENCE . "','conference/index.php?type=conference','visio_meeting.gif','1','0','squaregrey.gif','NO','_self','interaction','0')");
            Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_VISIO_CLASSROOM . "','conference/index.php?type=classroom','visio.gif','1','0','squaregrey.gif','NO','_self','authoring','0')");
        }
    }

    if (api_get_setting('search_enabled') == 'true') {
        Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_SEARCH. "','search/','info.gif','".string2binary(api_get_setting('course_create_active_tools', 'enable_search')) . "','0','search.gif','NO','_self','authoring','0')");
    }

    // Smartblogs (Kevin Van Den Haute :: kevin@develop-it.be)
    $sql = "INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL,'" . TOOL_BLOGS . "','blog/blog_admin.php','blog_admin.gif','" . string2binary(api_get_setting('course_create_active_tools', 'blogs')) . "','1','squaregrey.gif','NO','_self','admin','0')";
    Database::query($sql);
    // end of Smartblogs

    /*  Course homepage tools for course admin only    */

    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_TRACKING . "','tracking/courseLog.php','statistics.gif','$visible_for_course_admin','1','', 'NO','_self','admin','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL, '" . TOOL_COURSE_SETTING . "','course_info/infocours.php','reference.gif','$visible_for_course_admin','1','', 'NO','_self','admin','0')");
    Database::query("INSERT INTO $tbl_course_homepage VALUES ($course_id, NULL,'".TOOL_COURSE_MAINTENANCE."','course_info/maintenance.php','backup.gif','$visible_for_course_admin','1','','NO','_self', 'admin','0')");

    /*    course_setting table (courseinfo tool)   */

    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_manager_on_new_doc',0,'work')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_on_new_doc_dropbox',0,'dropbox')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_edit_agenda',0,'agenda')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_edit_announcement',0,'announcement')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_manager_on_new_quiz',0,'quiz')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_image_forum',1,'forum')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'course_theme','','theme')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_learning_path_theme','1','theme')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_open_chat_window',1,'chat')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_to_teacher_on_new_user_in_course',0,'registration')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'allow_user_view_user_list',1,'user')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'display_info_advance_inside_homecourse',1,'thematic_advance')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'email_alert_students_on_new_homework',0,'work')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'enable_lp_auto_launch',0,'learning_path')");
    Database::query("INSERT INTO $TABLESETTING (c_id, variable,value,category) VALUES ($course_id, 'pdf_export_watermark_text','','learning_path')");                

    /*    Course homepage tools for platform admin only */
    /*    Group tool */

    Database::query("INSERT INTO $TABLEGROUPCATEGORIES  (c_id,  id , title , description , max_student , self_reg_allowed , self_unreg_allowed , groups_per_user , display_order ) 
    		VALUES ($course_id, '2', '".lang2db(get_lang('DefaultGroupCategory')) . "', '', '8', '0', '0', '0', '0');");

    /*    Example Material  */

    global $language_interface;
    // Example material should be in the same language as the course is.
    $language_interface_tmp = $language_interface;
    $language_interface = $language;

    /*    
        Documents    
    */

    //Database::query("INSERT INTO $TABLETOOLDOCUMENT (path,title,filetype,size) VALUES ('/example_document.html','example_document.html','file','3367')");
    // We need to add the document properties too!
    //$example_doc_id = Database :: insert_id();
    //Database::query("INSERT INTO $TABLEITEMPROPERTY  (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,1)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id,path,title,filetype,size) VALUES ($course_id,'/images','".get_lang('Images')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/images/gallery','".get_lang('DefaultCourseImages')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/shared_folder','".get_lang('UserFolders')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/audio','".get_lang('Audio')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/flash','".get_lang('Flash')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/video','".get_lang('Video')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'/chat_files','".get_lang('ChatFiles')."','folder','0')");
    $example_doc_id = Database :: insert_id();
    Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$example_doc_id,'DocumentAdded',1,0,NULL,0)");

    // FILL THE COURSE DOCUMENT WITH DEFAULT COURSE PICTURES
    $sys_course_path = api_get_path(SYS_COURSE_PATH);

    if (is_array($default_document_array) && count($default_document_array)>0) {
        foreach ($default_document_array as $media_type => $array_media) {
            if ($media_type == 'images') {
                $path_documents = '/images/gallery/';
                $course_documents_folder = $sys_course_path.$course_repository.'/document/images/gallery/';
            }
            if ($media_type == 'audio') {
                $path_documents = '/audio/';
                $course_documents_folder = $sys_course_path.$course_repository.'/document/audio/';
            }
            if ($media_type == 'flash') {
                $path_documents = '/flash/';
                $course_documents_folder = $sys_course_path.$course_repository.'/document/flash/';
            }
            if ($media_type == 'video') {
                $path_documents = '/video/';
                $course_documents_folder = $sys_course_path.$course_repository.'/document/video/';
            }
            if (is_array($array_media) && count($array_media)>0) {
                foreach ($array_media as $key => $value) {
                    if ($value['dir'] != '') {
                        $folder_path = substr($value['dir'], 0, strlen($value['dir']) - 1);
                        $temp = explode('/', $folder_path);
                        Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'$path_documents".$folder_path."','".$temp[count($temp)-1]."','folder','0')");
                        $image_id = Database :: insert_id();
                        Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$image_id,'DocumentAdded',1,0,NULL,0)");
                    }
        
                    if ($value['file'] != '') {
                        $temp = explode('/', $value['file']);
                        $file_size = filesize($course_documents_folder.$value['file']);
                        Database::query("INSERT INTO $TABLETOOLDOCUMENT (c_id, path,title,filetype,size) VALUES ($course_id,'$path_documents".$value["file"]."','".$temp[count($temp)-1]."','file','$file_size')");
                        $image_id = Database :: insert_id();
                        Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ($course_id,'document',1,NOW(),NOW(),$image_id,'DocumentAdded',1,0,NULL,1)");
                    }
                }
            }
        }
    }
    if ($fill_with_exemplary_content) {

        /*
        -----------------------------------------------------------
            Agenda tool
        -----------------------------------------------------------
        */

        Database::query("INSERT INTO $TABLETOOLAGENDA  VALUES ($course_id, NULL, '".lang2db(get_lang('AgendaCreationTitle')) . "', '".lang2db(get_lang('AgendaCreationContenu')) . "', now(), now(), NULL, 0)");
        // We need to add the item properties too!
        $insert_id = Database :: insert_id();
        $sql = "INSERT INTO $TABLEITEMPROPERTY  ($course_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_CALENDAR_EVENT . "',1,NOW(),NOW(),$insert_id,'AgendaAdded',1,0,NULL,1)";
        Database::query($sql);

        /*
        -----------------------------------------------------------
            Links tool
        -----------------------------------------------------------
        */

        $add_google_link_sql = "INSERT INTO $TABLETOOLLINK  (c_id, url, title, description, category_id, display_order, on_homepage, target)
                VALUES ($course_id, 'http://www.google.com','Google','".lang2db(get_lang('Google')) . "','0','0','0','_self')";
        Database::query($add_google_link_sql);

        // We need to add the item properties too!
        $insert_id = Database :: insert_id();
        $sql = "INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) 
        		VALUES ($course_id, '" . TOOL_LINK . "',1,NOW(),NOW(),$insert_id,'LinkAdded',1,0,NULL,1)";
        Database::query($sql);

        $add_wikipedia_link_sql = "INSERT INTO $TABLETOOLLINK  (c_id, url, title, description, category_id, display_order, on_homepage, target)
                VALUES ($course_id, 'http://www.wikipedia.org','Wikipedia','".lang2db(get_lang('Wikipedia')) . "','0','1','0','_self')";
        Database::query($add_wikipedia_link_sql);

        // We need to add the item properties too!
        $insert_id = Database :: insert_id();
        $sql = "INSERT INTO $TABLEITEMPROPERTY  (tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) VALUES ('" . TOOL_LINK . "',1,NOW(),NOW(),$insert_id,'LinkAdded',1,0,NULL,1)";
        Database::query($sql);

        /*
                    Annoucement tool
        */

        $sql = "INSERT INTO $TABLETOOLANNOUNCEMENTS  (c_id, title,content,end_date,display_order,email_sent) 
        		VALUES ($course_id, '".lang2db(get_lang('AnnouncementExampleTitle')) . "', '".lang2db(get_lang('AnnouncementEx')) . "', NOW(), '1','0')";
        Database::query($sql);

        // We need to add the item properties too!
        $insert_id = Database :: insert_id();
        $sql = "INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) 
        		VALUES ($course_id, '" . TOOL_ANNOUNCEMENT . "',1,NOW(),NOW(),$insert_id,'AnnouncementAdded',1,0,NULL,1)";
        Database::query($sql);

        /*
        -----------------------------------------------------------
            Introduction text
        -----------------------------------------------------------
        */

        $intro_text='<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="110" valign="middle" align="left"><img src="'.api_get_path(REL_CODE_PATH).'img/mascot.png" alt="Mr. Chamilo" title="Mr. Chamilo" /></td><td valign="middle" align="left">'.lang2db(get_lang('IntroductionText')).'</td></tr></table>';
        Database::query("INSERT INTO $TABLEINTROS  VALUES ($course_id, '" . TOOL_COURSE_HOMEPAGE . "','".$intro_text. "', 0)");
        Database::query("INSERT INTO $TABLEINTROS  VALUES ($course_id, '" . TOOL_STUDENTPUBLICATION . "','".lang2db(get_lang('IntroductionTwo')) . "', 0)");

        // Wiki intro
        $intro_wiki='<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="110" valign="top" align="left"></td><td valign="top" align="left">'.lang2db(get_lang('IntroductionWiki')).'</td></tr></table>';
        Database::query("INSERT INTO $TABLEINTROS  VALUES ($course_id, '" . TOOL_WIKI . "','".$intro_wiki. "', 0)");

        /*
        -----------------------------------------------------------
            Exercise tool
        -----------------------------------------------------------
        */

        Database::query("INSERT INTO $TABLEQUIZANSWERSLIST  VALUES ($course_id,  '1', '1', '".lang2db(get_lang('Ridiculise')) . "', '0', '".lang2db(get_lang('NoPsychology')) . "', '-5', '1','','','','')");
        Database::query("INSERT INTO $TABLEQUIZANSWERSLIST  VALUES ($course_id,  '2', '1', '".lang2db(get_lang('AdmitError')) . "', '0', '".lang2db(get_lang('NoSeduction')) . "', '-5', '2','','','','')");
        Database::query("INSERT INTO $TABLEQUIZANSWERSLIST  VALUES ($course_id,  '3', '1', '".lang2db(get_lang('Force')) . "', '1', '".lang2db(get_lang('Indeed')) . "', '5', '3','','','','')");
        Database::query("INSERT INTO $TABLEQUIZANSWERSLIST  VALUES ($course_id,  '4', '1', '".lang2db(get_lang('Contradiction')) . "', '1', '".lang2db(get_lang('NotFalse')) . "', '5', '4','','','','')");
        $html=Database::escape_string('<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="110" valign="top" align="left"><img src="'.api_get_path(WEB_CODE_PATH).'default_course_document/images/mr_dokeos/thinking.jpg"></td><td valign="top" align="left">'.get_lang('Antique').'</td></tr></table>');

        Database::query('INSERT INTO `'.$TABLEQUIZ . '` (c_id, title, description, type, random, random_answers, active, results_disabled ) 
        				VALUES ('.$course_id.', "'.lang2db(get_lang('ExerciceEx')) . '", "'.$html.'", "1", "0", "0", "1", "0")');
        Database::query("INSERT INTO $TABLEQUIZQUESTIONLIST  (c_id, id, question, description, ponderation, position, type, picture, level) 
        				VALUES ( '.$course_id.', '1', '".lang2db(get_lang('SocraticIrony')) . "', '".lang2db(get_lang('ManyAnswers')) . "', '10', '1', '2','',1)");
        Database::query("INSERT INTO $TABLEQUIZQUESTION  (c_id, question_id, exercice_id, question_order) VALUES ('.$course_id.', 1,1,1)");

        /*
        -----------------------------------------------------------
            Forum tool
        -----------------------------------------------------------
        */

        Database::query("INSERT INTO $TABLEFORUMCATEGORIES VALUES ($course_id, 1,'".lang2db(get_lang('ExampleForumCategory'))."', '', 1, 0, 0)");
        $insert_id = Database :: insert_id();
        Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) 
        				VALUES ($course_id, 'forum_category',1,NOW(),NOW(),$insert_id,'ForumCategoryAdded',1,0,NULL,1)");

        Database::query("INSERT INTO `$TABLEFORUMS` (c_id, forum_title, forum_comment, forum_threads,forum_posts,forum_last_post,forum_category, allow_anonymous, allow_edit,allow_attachments, allow_new_threads,default_view,forum_of_group,forum_group_public_private, forum_order,locked,session_id ) 
        				VALUES ($course_id, '".lang2db(get_lang('ExampleForum'))."', '', 0, 0, 0, 1, 0, 1, '0', 1, 'flat','0', 'public', 1, 0,0)");
        $insert_id = Database :: insert_id();
        Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) 
        				 VALUES ('$course_id, " . TOOL_FORUM . "',1,NOW(),NOW(),$insert_id,'ForumAdded',1,0,NULL,1)");

        Database::query("INSERT INTO $TABLEFORUMTHREADS (c_id, thread_id, thread_title, forum_id, thread_replies, thread_poster_id, thread_poster_name, thread_views, thread_last_post, thread_date, locked, thread_qualify_max) 
        				VALUES ($course_id, 1, '".lang2db(get_lang('ExampleThread'))."', 1, 0, 1, '', 0, 1, NOW(), 0, 10)");
        $insert_id = Database :: insert_id();
        Database::query("INSERT INTO $TABLEITEMPROPERTY  (c_id, tool,insert_user_id,insert_date,lastedit_date,ref,lastedit_type,lastedit_user_id,to_group_id,to_user_id,visibility) 
        				VALUES ($course_id, 'forum_thread',1,NOW(),NOW(),$insert_id,'ForumThreadAdded',1,0,NULL,1)");

        Database::query("INSERT INTO `$TABLEFORUMPOSTS` VALUES ($course_id, 1, '".lang2db(get_lang('ExampleThread'))."', '".lang2db(get_lang('ExampleThreadContent'))."', 1, 1, 1, '', NOW(), 0, 0, 1)");

    }
    
    // PLUGINS - if an installed plugin has a course_install.php file, execute it
    $installed_plugins = api_get_settings('Plugins','list',$_configuration['access_url']);
    $shortlist_installed = array();
    foreach ($installed_plugins as $plugin) {
        $shortlist_installed[] = $plugin['subkey'];
    }
    $shortlist_installed = array_flip(array_flip($shortlist_installed));
    foreach ($shortlist_installed as $plugin) {
        $pluginpath = api_get_path(SYS_PLUGIN_PATH).$plugin.'/course_install.php';
        if (is_file($pluginpath) && is_readable($pluginpath)) {
            //execute the install procedure
            include $pluginpath;
        }
    }
    //end of installed plugins alterations

    $language_interface = $language_interface_tmp;

    return 0;
};

/**
 * function string2binary converts the string "true" or "false" to the boolean true false (0 or 1)
 * This is used for the Chamilo Config Settings as these store true or false as string
 * and the api_get_setting('course_create_active_tools') should be 0 or 1 (used for
 * the visibility of the tool)
 * @param string    $variable
 * @author Patrick Cool, patrick.cool@ugent.be
 */
function string2binary($variable) {
    if ($variable == 'true') {
        return true;
    }
    if ($variable == 'false') {
        return false;
    }
}

/**
 * function register_course to create a record in the course table of the main database
 * @param string    $course_sys_code
 * @param string    $course_screen_code
 * @param string    $course_repository
 * @param string    $course_db_name
 * @param string    $tutor_name
 * @param string    $category
 * @param string    $title              complete name of course
 * @param string    $course_language    lang for this course
 * @param string    $uid                uid of owner
 * @param integer                       Expiration date in unix time representation
 * @param array                         Optional array of teachers' user ID
 * @return int      0
 */
function register_course($course_sys_code, $course_screen_code, $course_repository, $course_db_name, $titular, $category, $title, $course_language, $uid_creator, $expiration_date = '', $teachers = array(), $visibility = null) {
    global $defaultVisibilityForANewCourse, $error_msg;

    $TABLECOURSE		 	= Database :: get_main_table(TABLE_MAIN_COURSE);
    $TABLECOURSUSER 		= Database :: get_main_table(TABLE_MAIN_COURSE_USER);    

    $ok_to_register_course = true;

    // Check whether all the needed parameters are present.
    if (empty($course_sys_code)) {
        $error_msg[] = 'courseSysCode is missing';
        $ok_to_register_course = false;
    }
    if (empty($course_screen_code)) {
        $error_msg[] = 'courseScreenCode is missing';
        $ok_to_register_course = false;
    }
    
    if (empty($course_db_name)) {
        $error_msg[] = 'courseDbName is missing';
        //$ok_to_register_course = false;
    }
    if (empty($course_repository)) {
        $error_msg[] = 'courseRepository is missing';
        $ok_to_register_course = false;
    }
    if (empty($titular)) {
        //$error_msg[] = 'titular is missing';
        //$ok_to_register_course = false;
    }
    
    if (empty($title)) {
        $error_msg[] = 'title is missing';
        $ok_to_register_course = false;
    }
    if (empty($course_language)) {
        $error_msg[] = 'language is missing';
        $ok_to_register_course = false;
    }

    if (empty($expiration_date)) {
        $expiration_date = "NULL";
    } else {
        $expiration_date = "FROM_UNIXTIME(".$expiration_date . ")";
    }

    if ($visibility === null) {
        $visibility = $defaultVisibilityForANewCourse;
    } else {
        if ($visibility < 0 || $visibility > 3) {
            $error_msg[] = 'visibility is invalid';
            $ok_to_register_course = false;
        }
    }
    
    $course_id = 0;
    
    if ($ok_to_register_course) {
        $titular = addslashes($titular);
    
       // Here we must add 2 fields.
       $sql = "INSERT INTO ".$TABLECOURSE . " SET
                    code = '".Database :: escape_string($course_sys_code) . "',
                    db_name = '".Database :: escape_string($course_db_name) . "',
                    directory = '".Database :: escape_string($course_repository) . "',
                    course_language = '".Database :: escape_string($course_language) . "',
                    title = '".Database :: escape_string($title) . "',
                    description = '".lang2db(get_lang('CourseDescription')) . "',
                    category_code = '".Database :: escape_string($category) . "',
                    visibility = '".$visibility . "',
                    show_score = '1',
                    disk_quota = '".api_get_setting('default_document_quotum') . "',
                    creation_date = now(),
                    expiration_date = ".$expiration_date . ",
                    last_edit = now(),
                    last_visit = NULL,
                    tutor_name = '".Database :: escape_string($titular) . "',
                    visual_code = '".Database :: escape_string($course_screen_code) . "'";
        Database::query($sql);
		$course_id  = Database::get_last_insert_id();
		
        $sort = api_max_sort_value('0', api_get_user_id());
        
        $i_course_sort = CourseManager :: userCourseSort($uid_creator, $course_sys_code);

        $sql = "INSERT INTO ".$TABLECOURSUSER . " SET
                    course_code = '".addslashes($course_sys_code) . "',
                    user_id = '".Database::escape_string($uid_creator) . "',
                    status = '1',
                    role = '".lang2db(get_lang('Professor')) . "',
                    tutor_id='0',
                    sort='". ($i_course_sort) . "',
                    user_course_cat='0'";
        Database::query($sql);

        if (count($teachers) > 0) {
            foreach ($teachers as $key) {
                $sql = "INSERT INTO ".$TABLECOURSUSER . " SET
                    course_code = '".Database::escape_string($course_sys_code) . "',
                    user_id = '".Database::escape_string($key) . "',
                    status = '1',
                    role = '',
                    tutor_id='0',
                    sort='". ($sort +1) . "',
                    user_course_cat='0'";
                Database::query($sql);
            }
        }

        // Adding the course to an URL.
        global $_configuration;
        require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
        if ($_configuration['multiple_access_urls']) {
            $url_id = 1;
            if (api_get_current_access_url_id() != -1) {
                $url_id = api_get_current_access_url_id();
            }
            UrlManager::add_course_to_url($course_sys_code, $url_id);
        } else {
            UrlManager::add_course_to_url($course_sys_code, 1);
        }

        // Add event to the system log.        
        $user_id = api_get_user_id();
        event_system(LOG_COURSE_CREATE, LOG_COURSE_CODE, $course_sys_code, api_get_utc_datetime(), $user_id, $course_sys_code);

        $send_mail_to_admin = api_get_setting('send_email_to_admin_when_create_course');

        // @todo Improve code to send to all current portal administrators.
        if ($send_mail_to_admin == 'true') {
            $siteName = api_get_setting('siteName');
            $recipient_email = api_get_setting('emailAdministrator');
            $recipient_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'));
            $urlsite = api_get_path(WEB_PATH);
            $iname = api_get_setting('Institution');
            $subject = get_lang('NewCourseCreatedIn').' '.$siteName.' - '.$iname;
            $message =  get_lang('Dear').' '.$recipient_name.",\n\n".get_lang('MessageOfNewCourseToAdmin').' '.$siteName.' - '.$iname."\n";
            $message .= get_lang('CourseName').' '.$title."\n";
            $message .= get_lang('Category').' '.$category."\n";
            $message .= get_lang('Tutor').' '.$titular."\n";
            $message .= get_lang('Language').' '.$course_language;

            @api_mail($recipient_name, $recipient_email, $subject, $message, $siteName, $recipient_email);
        }

    }
    return $course_id;
}

/**
 * WARNING: This function always returns true.
 */
function checkArchive($path_to_archive) {
    return true;
}

/**
 * Extract properties of the files from a ZIP package, write them to disk and
 * return them as an array.
 * @todo this function seems not to be used
 * @param string        Absolute path to the ZIP file
 * @param bool          Whether the ZIP file is compressed (not implemented). Defaults to TRUE.
 * @return array        List of files properties from the ZIP package
 */
function readPropertiesInArchive($archive, $is_compressed = true) {
    include api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php';
    debug::printVar(dirname($archive), 'Zip : ');
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
    $zip_file = new pclZip($archive);
    $tmp_dir_name = dirname($archive) . '/tmp'.$uid.uniqid($uid);
    if (mkdir($tmp_dir_name, api_get_permissions_for_new_directories(), true)) {
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
