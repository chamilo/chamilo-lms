<?php
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once 'webservices/WSApp.class.php';
require_once 'webservices/AppWebService.class.php';

use ChamiloSession as Session;

$c_id = isset($_POST['c_id']) ? Security::remove_XSS($_POST['c_id']) : null;
$file_comment = isset($_POST['comment']) ? Security::remove_XSS($_POST['comment']) : '';
$post_id = isset($_POST['post_id']) ? Security::remove_XSS($_POST['post_id']) : 0;
$_course = api_get_course_info_by_id($c_id);
$agenda_forum_attachment = Database::get_course_table(TABLE_FORUM_ATTACHMENT);

if ($_FILES['forum_upload_file']['size']) {
    $uploadFileName = $_FILES['forum_upload_file']['name'];
    
    $new_file_name = add_ext_on_mime(
        stripslashes($uploadFileName),
        $uploadFileType
    );
    
    if (!filter_extension($new_file_name)) {
        $json = [
                'status' => true,
                'statusFile' => false,
                'message' => get_lang('UplUnableToSaveFileFilteredExtension'),
        ];
    } else {
        $course_dir = $_course['path'].'/upload/forum';
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $updir = $sys_course_path.$course_dir;
        
        $new_file_name = uniqid('');
        $new_path = $updir.'/'.$new_file_name;
        $result = @move_uploaded_file($_FILES['forum_upload_file']['tmp_name'], $new_path);
        $safe_file_comment = Database::escape_string($file_comment);
        $safe_file_name = Database::escape_string($uploadFileName);
        $safe_new_file_name = Database::escape_string($new_file_name);
        $last_id = intval($last_id);
        // Storing the attachments if any.
        if (!$result) {
            return;
        }
        
        $last_id_file = Database::insert(
            $agenda_forum_attachment,
            [
                'c_id' => $c_id,
                'filename' => $safe_file_name,
                'comment' => $safe_file_comment,
                'path' => $safe_new_file_name,
                'post_id' => $post_id,
                'size' => intval($_FILES['forum_upload_file']['size']),
            ]
        );
        
        api_item_property_update(
            $_course,
            TOOL_FORUM_ATTACH,
            $last_id_file,
            'ForumAttachmentAdded',
            api_get_user_id()
        );
        
        return json_encode(['success' => true]);
    }
} else {
    // Retornar error
    return json_encode(['success' => false]);
}
