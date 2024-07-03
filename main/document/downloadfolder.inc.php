<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Functions and main code for the download folder feature.
 *
 * @package chamilo.document
 */
set_time_limit(0);

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

$sysCoursePath = api_get_path(SYS_COURSE_PATH);
$courseInfo = api_get_course_info();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$courseCode = api_get_course_id();

// Check if folder exists in current course.
$documentInfo = DocumentManager::get_document_data_by_id(
    $_GET['id'],
    $courseCode,
    false,
    0
);

if (!empty($sessionId)) {
    /* If no data found and session id exists
       try to look the file inside the session */

    if (empty($documentInfo)) {
        $documentInfo = DocumentManager::get_document_data_by_id(
            $_GET['id'],
            $courseCode,
            false,
            $sessionId
        );
    }
}

$path = $documentInfo['path'];

if (empty($path)) {
    $path = '/';
}

// A student should not be able to download a root shared directory
if (($path == '/shared_folder' ||
    $path == '/shared_folder_session_'.api_get_session_id()) &&
    (!api_is_allowed_to_edit() || !api_is_platform_admin())
) {
    api_not_allowed(true);
    exit;
}

// Creating a ZIP file.
$tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().".zip";

$zip = new PclZip($tempZipFile);
$doc_table = Database::get_course_table(TABLE_DOCUMENT);
$prop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);

// We need this path to clean it out of the zip file
// I'm not using dir name as it gives too much problems (cfr.)
$remove_dir = ($path != '/') ? substr($path, 0, strlen($path) - strlen(basename($path))) : '/';

// Put the files in the zip
// 2 possibilities: Admins get all files and folders in the selected folder (except for the deleted ones)
// Normal users get only visible files that are in visible folders
function fixDocumentNameCallback($p_event, &$p_header)
{
    global $remove_dir;
    $files = Session::read('doc_files_to_download');
    $storedFile = $remove_dir.$p_header['stored_filename'];

    if (!isset($files[$storedFile])) {
        return 0;
    }

    $documentData = $files[$storedFile];
    $documentNameFixed = DocumentManager::undoFixDocumentName(
        $documentData['path'],
        $documentData['c_id'],
        $documentData['session_id'],
        $documentData['to_group_id']
    );

    // Changes file.phps to file.php
    $basename = basename($documentNameFixed);
    $basenamePHPFixed = str_replace('.phps', '.php', $basename);
    $documentNameFixed = str_replace(
        $basename,
        $basenamePHPFixed,
        $documentNameFixed
    );

    if ($remove_dir != '/') {
        $documentNameFixed = str_replace($remove_dir, '/', $documentNameFixed);
        if (substr($documentNameFixed, 0, 1) == '/') {
            $documentNameFixed = substr($documentNameFixed, 1, api_strlen($documentNameFixed));
        }
    } else {
        $documentNameFixed = ltrim($documentNameFixed, '/');
    }

    $p_header['stored_filename'] = $documentNameFixed;

    return 1;
}

$groupJoin = '';
if (!empty($groupId)) {
    $table = Database::get_course_table(TABLE_GROUP);
    $groupJoin = " INNER JOIN $table g ON (g.iid = props.to_group_id AND g.c_id = docs.c_id)";
    $groupCondition = " g.id = ".$groupId;
} else {
    $groupCondition = " (props.to_group_id = 0 OR props.to_group_id IS NULL ) ";
}

$userIsSubscribed = CourseManager::is_user_subscribed_in_course(
    api_get_user_id(),
    $courseInfo['code']
);

$filesToZip = [];

// Admins are allowed to download invisible files
if (api_is_allowed_to_edit()) {
    // Set the path that will be used in the query
    if ($path == '/') {
        $querypath = ''; // To prevent ...path LIKE '//%'... in query
    } else {
        $querypath = $path;
    }
    $querypath = Database::escape_string($querypath);

    // Search for all files that are not deleted => visibility != 2
    $sql = "SELECT
                path,
                docs.session_id,
                docs.id,
                props.to_group_id,
                docs.c_id
            FROM $doc_table AS docs
            INNER JOIN $prop_table AS props
            ON
                docs.id = props.ref AND
                docs.c_id = props.c_id
                $groupJoin
			WHERE
			    props.tool ='".TOOL_DOCUMENT."' AND
                docs.path LIKE '".$querypath."/%' AND
                docs.filetype = 'file' AND
                props.visibility <> '2' AND
                $groupCondition AND
                (props.session_id IN ('0', '$sessionId') OR props.session_id IS NULL) AND
                docs.c_id = ".$courseId." ";

    $sql .= DocumentManager::getSessionFolderFilters($querypath, $sessionId);

    $result = Database::query($sql);
    $files = [];
    while ($row = Database::fetch_array($result)) {
        $files[$row['path']] = $row;
    }

    Session::write('doc_files_to_download', $files);

    foreach ($files as $not_deleted_file) {
        // Filtering folders and
        if (strpos($not_deleted_file['path'], 'chat_files') > 0 ||
            strpos($not_deleted_file['path'], 'shared_folder') > 0
        ) {
            if (!empty($sessionId)) {
                if ($not_deleted_file['session_id'] != $sessionId) {
                    continue;
                }
            }
        }
        $filesToZip[] = $sysCoursePath.$courseInfo['path'].'/document'.$not_deleted_file['path'];
    }
    $zip->add(
        $filesToZip,
        PCLZIP_OPT_REMOVE_PATH,
        $sysCoursePath.$courseInfo['path'].'/document'.$remove_dir,
        PCLZIP_CB_PRE_ADD,
        'fixDocumentNameCallback'
    );

    Session::erase('doc_files_to_download');
} else {
    // For other users, we need to create a zip  file with only visible files and folders
    if ($path == '/') {
        $querypath = ''; // To prevent ...path LIKE '//%'... in query
    } else {
        $querypath = $path;
    }

    /* A big problem: Visible files that are in a hidden folder are
       included when we do a query for visibility='v'
       So... I do it in a couple of steps:
       1st: Get all files that are visible in the given path
    */
    $querypath = Database::escape_string($querypath);
    $sql = "SELECT path, docs.session_id, docs.id, props.to_group_id, docs.c_id
            FROM $doc_table AS docs
            INNER JOIN $prop_table AS props
            ON
                docs.id = props.ref AND
                docs.c_id = props.c_id
                $groupJoin
            WHERE
                docs.c_id = $courseId AND
                props.tool = '".TOOL_DOCUMENT."' AND
                docs.path LIKE '".$querypath."/%' AND
                docs.filetype = 'file' AND
                (props.session_id IN ('0', '$sessionId') OR props.session_id IS NULL) AND
                $groupCondition
            ";

    $sql .= DocumentManager::getSessionFolderFilters($querypath, $sessionId);
    $result = Database::query($sql);

    $files = [];
    $all_visible_files_path = [];
    // Add them to an array
    while ($all_visible_files = Database::fetch_assoc($result)) {
        if (strpos($all_visible_files['path'], 'chat_files') > 0 ||
            strpos($all_visible_files['path'], 'shared_folder') > 0
        ) {
            if (!empty($sessionId)) {
                if ($all_visible_files['session_id'] != $sessionId) {
                    continue;
                }
            }
        }

        $isVisible = DocumentManager::is_visible_by_id(
            $all_visible_files['id'],
            $courseInfo,
            api_get_session_id(),
            api_get_user_id(),
            false,
            $userIsSubscribed
        );

        if (!$isVisible) {
            continue;
        }

        $all_visible_files_path[] = $all_visible_files['path'];
        $files[$all_visible_files['path']] = $all_visible_files;
    }

    // 2nd: Get all folders that are invisible in the given path
    $sql = "SELECT path, docs.session_id, docs.id, props.to_group_id, docs.c_id
            FROM $doc_table AS docs
            INNER JOIN $prop_table AS props
            ON
                docs.id = props.ref AND
                docs.c_id = props.c_id
            WHERE
                docs.c_id = $courseId AND
                props.tool = '".TOOL_DOCUMENT."' AND
                docs.path LIKE '".$querypath."/%' AND
                props.visibility <> '1' AND
                (props.session_id IN ('0', '$sessionId') OR props.session_id IS NULL) AND
                docs.filetype = 'folder'";
    $query2 = Database::query($sql);

    // If we get invisible folders, we have to filter out these results from all visible files we found
    if (Database::num_rows($query2) > 0) {
        //$files = [];
        // Add item to an array
        while ($invisible_folders = Database::fetch_assoc($query2)) {
            //3rd: Get all files that are in the found invisible folder (these are "invisible" too)
            $sql = "SELECT path, docs.id, props.to_group_id, docs.c_id
                    FROM $doc_table AS docs
                    INNER JOIN $prop_table AS props
                    ON
                        docs.id = props.ref AND
                        docs.c_id = props.c_id
                    WHERE
                        docs.c_id = $courseId AND
                        props.tool ='".TOOL_DOCUMENT."' AND
                        docs.path LIKE '".$invisible_folders['path']."/%' AND
                        docs.filetype = 'file' AND
                        (props.session_id IN ('0', '$sessionId') OR props.session_id IS NULL)
                    ";
            $query3 = Database::query($sql);
            // Add tem to an array
            while ($files_in_invisible_folder = Database::fetch_assoc($query3)) {
                $isVisible = DocumentManager::is_visible_by_id(
                    $files_in_invisible_folder['id'],
                    $courseInfo,
                    api_get_session_id(),
                    api_get_user_id(),
                    false,
                    $userIsSubscribed
                );

                if (!$isVisible) {
                    continue;
                }
                $files_in_invisible_folder_path[] = $files_in_invisible_folder['path'];
                $files[$files_in_invisible_folder['path']] = $files_in_invisible_folder;
            }
        }

        // Compare the array with visible files and the array with files in invisible folders
        // and keep the difference (= all visible files that are not in an invisible folder)
        $files_for_zipfile = diff(
            (array) $all_visible_files_path,
            (array) $files_in_invisible_folder_path
        );
    } else {
        // No invisible folders found, so all visible files can be added to the zipfile
        $files_for_zipfile = $all_visible_files_path;
    }

    Session::write('doc_files_to_download', $files);

    // Add all files in our final array to the zipfile
    for ($i = 0; $i < count($files_for_zipfile); $i++) {
        $filesToZip[] = $sysCoursePath.$courseInfo['path'].'/document'.$files_for_zipfile[$i];
    }
    $zip->add(
        $filesToZip,
        PCLZIP_OPT_REMOVE_PATH,
        $sysCoursePath.$courseInfo['path'].'/document'.$remove_dir,
        PCLZIP_CB_PRE_ADD,
        'fixDocumentNameCallback'
    );
    Session::erase('doc_files_to_download');
}

// Launch event
Event::event_download(
    ($path == '/') ? 'documents.zip (folder)' : basename($path).'.zip (folder)'
);

// Start download of created file
$name = ($path == '/') ? 'documents.zip' : $documentInfo['title'].'.zip';

if (Security::check_abs_path($tempZipFile, api_get_path(SYS_ARCHIVE_PATH))) {
    $result = DocumentManager::file_send_for_download($tempZipFile, true, $name);
    if ($result === false) {
        api_not_allowed(true);
    }
    @unlink($tempZipFile);
    exit;
} else {
    api_not_allowed(true);
}

/**
 * Returns the difference between two arrays, as an array of those key/values
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
    foreach ($arr1 as &$av) {
        if (!in_array($av, $arr2)) {
            $res[$r] = $av;
            $r++;
        }
    }

    return $res;
}
