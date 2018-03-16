<?php
/* For licensing terms, see /license.txt */

/**
 * Functions and main code for the download folder feature.
 *
 * @todo use ids instead of the path like the document tool
 *
 * @package chamilo.work
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$workId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$current_course_tool = TOOL_STUDENTPUBLICATION;
$_course = api_get_course_info();

if (empty($_course)) {
    api_not_allowed();
}

require_once 'work.lib.php';

$work_data = get_work_data_by_id($workId);
$groupId = api_get_group_id();

if (empty($work_data)) {
    api_not_allowed();
}

// Prevent some stuff.
if (empty($path)) {
    $path = '/';
}

if (empty($_course) || empty($_course['path'])) {
    api_not_allowed();
}

$sys_course_path = api_get_path(SYS_COURSE_PATH);

// Creating a ZIP file
$temp_zip_file = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'.zip';
$zip_folder = new PclZip($temp_zip_file);

$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$prop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);

// Put the files in the zip
// 2 possibilities: admins get all files and folders in the selected folder (except for the deleted ones)
// normal users get only visible files that are in visible folders

//admins are allowed to download invisible files
$files = [];
$course_id = api_get_course_int_id();
$sessionId = api_get_session_id();

$sessionCondition = api_get_session_condition($sessionId, true, false, 'props.session_id');

$filenameCondition = null;
if (array_key_exists('filename', $work_data)) {
    $filenameCondition = ", filename";
}

$groupIid = 0;
if ($groupId) {
    $groupInfo = GroupManager::get_group_properties($groupId);
    $groupIid = $groupInfo['iid'];
}

if (api_is_allowed_to_edit() || api_is_coach()) {
    //Search for all files that are not deleted => visibility != 2
    $sql = "SELECT DISTINCT
                url,
                title,
                description,
                insert_user_id,
                sent_date,
                contains_file
                $filenameCondition
            FROM $tbl_student_publication AS work
            INNER JOIN $prop_table AS props
            ON (work.id = props.ref AND props.c_id = work.c_id)
            INNER JOIN $tableUser as u
            ON (
                work.user_id = u.user_id
            )
            WHERE
 			    props.tool = 'work' AND
 			    props.c_id = $course_id AND
                work.c_id = $course_id AND
                work.parent_id = $workId AND
                work.filetype = 'file' AND
                props.visibility <> '2' AND
                work.active IN (0, 1) AND
                work.post_group_id = $groupIid
                $sessionCondition
            ";
} else {
    $courseInfo = api_get_course_info();
    protectWork($courseInfo, $workId);
    $userCondition = '';

    // All users
    if ($courseInfo['show_score'] == 0) {
        // Do another filter
    } else {
        // Only teachers
        $userCondition = " AND props.insert_user_id = ".api_get_user_id();
    }

    //for other users, we need to create a zipfile with only visible files and folders
    $sql = "SELECT DISTINCT
                url,
                title,
                description,
                insert_user_id,
                sent_date,
                contains_file
                $filenameCondition
            FROM $tbl_student_publication AS work
            INNER JOIN $prop_table AS props
            ON (
                props.c_id = work.c_id AND 
                work.id = props.ref
            )
            WHERE
                props.c_id = $course_id AND
                work.c_id = $course_id AND                
                props.tool = 'work' AND
                work.accepted = 1 AND
                work.active = 1 AND
                work.parent_id = $workId AND
                work.filetype = 'file' AND
                props.visibility = '1' AND
                work.post_group_id = $groupIid
                $userCondition
            ";
}
$query = Database::query($sql);

//add tem to the zip file
while ($not_deleted_file = Database::fetch_assoc($query)) {
    $userInfo = api_get_user_info($not_deleted_file['insert_user_id']);
    $insert_date = api_get_local_time($not_deleted_file['sent_date']);
    $insert_date = str_replace([':', '-', ' '], '_', $insert_date);

    $title = basename($not_deleted_file['title']);
    if (!empty($filenameCondition)) {
        if (isset($not_deleted_file['filename']) && !empty($not_deleted_file['filename'])) {
            $title = $not_deleted_file['filename'];
        }
    }
    $filename = $insert_date.'_'.$userInfo['username'].'_'.$title;
    $filename = api_replace_dangerous_char($filename);
    // File exists
    if (file_exists($sys_course_path.$_course['path'].'/'.$not_deleted_file['url']) &&
        !empty($not_deleted_file['url'])
    ) {
        $files[basename($not_deleted_file['url'])] = $filename;
        $addStatus = $zip_folder->add(
            $sys_course_path.$_course['path'].'/'.$not_deleted_file['url'],
            PCLZIP_OPT_REMOVE_PATH,
            $sys_course_path.$_course['path'].'/work',
            PCLZIP_CB_PRE_ADD,
            'my_pre_add_callback'
        );
    } else {
        // Convert texts in html files
        $filename = trim($filename).".html";
        $work_temp = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'_'.$filename;
        file_put_contents($work_temp, $not_deleted_file['description']);
        $files[basename($work_temp)] = $filename;
        $addStatus = $zip_folder->add(
            $work_temp,
            PCLZIP_OPT_REMOVE_PATH,
            api_get_path(SYS_ARCHIVE_PATH),
            PCLZIP_CB_PRE_ADD,
            'my_pre_add_callback'
        );
        @unlink($work_temp);
    }
}

if (!empty($files)) {
    $fileName = api_replace_dangerous_char($work_data['title']);
    // Logging
    Event::event_download($fileName.'.zip (folder)');

    //start download of created file
    $name = $fileName.'.zip';

    if (Security::check_abs_path($temp_zip_file, api_get_path(SYS_ARCHIVE_PATH))) {
        DocumentManager::file_send_for_download($temp_zip_file, true, $name);
        @unlink($temp_zip_file);
        exit;
    }
} else {
    exit;
}

/* Extra function (only used here) */
function my_pre_add_callback($p_event, &$p_header)
{
    global $files;
    if (isset($files[basename($p_header['stored_filename'])])) {
        $p_header['stored_filename'] = $files[basename($p_header['stored_filename'])];

        return 1;
    }

    return 0;
}

/**
 * Return the difference between two arrays, as an array of those key/values
 * Use this as array_diff doesn't give the.
 *
 * @param array $arr1 first array
 * @param array $arr2 second array
 *
 * @return array difference between the two arrays
 */
function diff($arr1, $arr2)
{
    $res = [];
    $r = 0;
    foreach ($arr1 as $av) {
        if (!in_array($av, $arr2)) {
            $res[$r] = $av;
            $r++;
        }
    }

    return $res;
}
