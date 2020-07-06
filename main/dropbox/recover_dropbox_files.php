<?php

/* For licensing terms, see /license.txt */

require_once 'dropbox_init.inc.php';

$file_tbl = Database::get_course_table(TABLE_DROPBOX_FILE);
$person_tbl = Database::get_course_table(TABLE_DROPBOX_PERSON);
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();
$session_id = api_get_session_id();

if (empty($course_id)) {
    api_not_allowed();
}

if (!api_is_allowed_to_session_edit(false, true)) {
    api_not_allowed();
}

echo Display::page_subheader(get_lang('RecoverDropboxFiles'));
if (isset($_GET['recover_id']) && !empty($_GET['recover_id'])) {
    $recover_id = (int) $_GET['recover_id'];

    $sql = "INSERT INTO $person_tbl VALUES('$course_id', $recover_id, $user_id)";
    $result = Database::query($sql);
    if ($result) {
        echo Display::return_message(get_lang('Recovered'), 'confirm');
    }
}

$sql = "SELECT * FROM $file_tbl
        WHERE c_id = $course_id AND session_id = $session_id";
$result = Database::query($sql);

if (Database::num_rows($result)) {
    $files = Database::store_result($result);
    $rows = [];
    foreach ($files as $file) {
        //Check if I have this file:
        $sql = "SELECT * FROM $person_tbl
                WHERE c_id = $course_id AND user_id = $user_id AND file_id = {$file['id']}";
        $result_person = Database::query($sql);
        if (Database::num_rows($result_person) == 0) {
            $rows[] = [
                $file['filename'],
                api_convert_and_format_date($file['upload_date']),
                Display::url(
                    get_lang('Recover'),
                    api_get_self().'?recover_id='.$file['id'],
                    ['class' => 'btn btn-default']
                ),
            ];
        }
    }
    $headers = [
        get_lang('FileName'),
        get_lang('UploadedDate'),
        get_lang('Action'),
    ];
    echo Display::table($headers, $rows);
}
Display::display_footer();
