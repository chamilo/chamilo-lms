<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$_course = api_get_course_info();

// the dropbox file that contains additional functions
require_once 'dropbox_functions.inc.php';

/*	DOWNLOAD A FOLDER */
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();

if (isset($_GET['cat_id']) &&
    is_numeric($_GET['cat_id']) &&
    $_GET['action'] == 'downloadcategory' &&
    isset($_GET['sent_received'])
) {
    /** step 1: constructing the sql statement.
    Therefore we have to create to separate sql statements to find which files are in the category
    (depending if we zip-download a sent category or a received category)*/
    if ($_GET['sent_received'] == 'sent') {
        // here we also incorporate the person table to make sure that deleted sent documents are not included.
        $sql = "SELECT DISTINCT file.id, file.filename, file.title
                FROM ".Database::get_course_table(TABLE_DROPBOX_FILE)." file
                INNER JOIN ".Database::get_course_table(TABLE_DROPBOX_PERSON)." person
                ON (person.file_id=file.id AND file.c_id = $course_id AND person.c_id = $course_id)
                WHERE
                    file.uploader_id = $user_id AND
                    file.cat_id='".intval($_GET['cat_id'])."'  AND
                    person.user_id = $user_id";
    }

    if ($_GET['sent_received'] == 'received') {
        $sql = "SELECT DISTINCT file.id, file.filename, file.title
                FROM ".Database::get_course_table(TABLE_DROPBOX_FILE)." file
                INNER JOIN ".Database::get_course_table(TABLE_DROPBOX_PERSON)." person
                ON (person.file_id=file.id AND file.c_id = $course_id AND person.c_id = $course_id)
                INNER JOIN ".Database::get_course_table(TABLE_DROPBOX_POST)." post
                ON (post.file_id = file.id AND post.c_id = $course_id AND file.c_id = $course_id)
                WHERE
                    post.cat_id = ".intval($_GET['cat_id'])." AND
                    post.dest_user_id = $user_id";
    }
    $files_to_download = [];
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $files_to_download[] = $row['id'];
    }
    if (!is_array($files_to_download) || empty($files_to_download)) {
        header('Location: index.php?'.api_get_cidreq().'&view='.Security::remove_XSS($_GET['sent_received']).'&error=ErrorNoFilesInFolder');
        exit;
    }
    zip_download($files_to_download);
    exit;
}
/*	DOWNLOAD A FILE */
/* AUTHORIZATION */

// Check if the id makes sense
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    api_not_allowed(true);
    exit;
}

// Check if the user is allowed to download the file
$allowed_to_download = false;
if (user_can_download_file($_GET['id'], api_get_user_id())) {
    $allowed_to_download = true;
}

/*		ERROR IF NOT ALLOWED TO DOWNLOAD */
if (!$allowed_to_download) {
    api_not_allowed(
        true,
        Display::return_message(
            get_lang('YouAreNotAllowedToDownloadThisFile'),
            'error'
        )
    );
    exit;
} else {
    /*      DOWNLOAD THE FILE */
    // the user is allowed to download the file
    $_SESSION['_seen'][$_course['id']][TOOL_DROPBOX][] = intval($_GET['id']);

    $work = new Dropbox_Work($_GET['id']);
    //path to file as stored on server
    $path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'.$work->filename;
    if (!Security::check_abs_path(
        $path,
        api_get_path(SYS_COURSE_PATH).$_course['path'].'/dropbox/'
    )
    ) {
        api_not_allowed(true);
    }
    $file = $work->title;
    $result = DocumentManager::file_send_for_download($path, true, $file);
    if ($result === false) {
        api_not_allowed(true);
    }
    exit;
}
//@todo clean this file the code below is useless there are 2 exits in previous conditions ... maybe a bad copy/paste/merge?
exit;
