<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Chamilo\UserBundle\Entity\User;

/**
 * Class MessageManager
 *
 * This class provides methods for messages management.
 * Include/require it in your code to use its features.
 *
 * @package chamilo.library
 */
class MessageManager
{
    /**
     * Get count new messages for the current user from the database.
     * @return int
     */
    public static function getCountNewMessages()
    {
        $userId = api_get_user_id();
        if (empty($userId)) {
            return false;
        }

        static $count;
        if (!isset($count)) {
            $cacheAvailable = api_get_configuration_value('apc');
            if ($cacheAvailable === true) {
                $var = api_get_configuration_value('apc_prefix').'social_messages_unread_u_'.$userId;
                if (apcu_exists($var)) {
                    $count = apcu_fetch($var);
                } else {
                    $count = self::getCountNewMessagesFromDB($userId);
                    apcu_store($var, $count, 60);
                }
            } else {
                $count = self::getCountNewMessagesFromDB($userId);
            }
        }

        return $count;
    }
    /**
     * Execute the SQL necessary to know the number of messages in the database
     * @param   int $userId The user for which we need the unread messages count
     * @return  int The number of unread messages in the database for the given user
     */
    private static function getCountNewMessagesFromDB($userId)
    {
        if (empty($userId)) {
            return 0;
        }
        $table = Database::get_main_table(TABLE_MESSAGE);
        $sql = "SELECT COUNT(id) as count 
                FROM $table
                WHERE
                    user_receiver_id=".api_get_user_id()." AND
                    msg_status = " . MESSAGE_STATUS_UNREAD;
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        return $row['count'];
    }

    /**
     * Gets the total number of messages, used for the inbox sortable table
     */
    public static function get_number_of_messages($unread = false)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        if ($unread) {
            $condition_msg_status = ' msg_status = '.MESSAGE_STATUS_UNREAD.' ';
        } else {
            $condition_msg_status = ' msg_status IN('.MESSAGE_STATUS_NEW.','.MESSAGE_STATUS_UNREAD.') ';
        }

        $keyword = Session::read('message_search_keyword');
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (title like '%$keyword%' OR content LIKE '%$keyword%') ";
        }

        $sql = "SELECT COUNT(id) as number_messages
                FROM $table_message
                WHERE $condition_msg_status AND
                    user_receiver_id=".api_get_user_id()."
                    $keywordCondition
                ";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return $result['number_messages'];
    }

    /**
     * Gets information about some messages, used for the inbox sortable table
     * @param int $from
     * @param int $number_of_items
     * @param string $direction
     */
    public static function get_message_data($from, $number_of_items, $column, $direction)
    {
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        //forcing this order
        if (!isset($direction)) {
            $column = 3;
            $direction = 'DESC';
        } else {
            $column = intval($column);
            if (!in_array($direction, array('ASC', 'DESC'))) {
                $direction = 'ASC';
            }
        }

        $keyword = Session::read('message_search_keyword');
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (title like '%$keyword%' OR content LIKE '%$keyword%') ";
        }

        $table_message = Database::get_main_table(TABLE_MESSAGE);

        $sql = "SELECT 
                    id as col0, 
                    user_sender_id as col1, 
                    title as col2, 
                    send_date as col3, 
                    msg_status as col4
                FROM $table_message
                WHERE
                  user_receiver_id=".api_get_user_id()." AND
                  msg_status IN (0,1)
                  $keywordCondition
                ORDER BY col$column $direction
                LIMIT $from, $number_of_items";

        $sql_result = Database::query($sql);
        $i = 0;
        $message_list = array();

        while ($result = Database::fetch_row($sql_result)) {
            $message[0] = $result[0];
            $result[2] = Security::remove_XSS($result[2], STUDENT, true);
            $result[2] = cut($result[2], 80, true);

            if ($result[4] == 1) {
                $class = 'class = "unread"';
            } else {
                $class = 'class = "read"';
            }
            $link = '';
            if (isset($_GET['f']) && $_GET['f'] == 'social') {
                $link = '&f=social';
            }
            $userInfo = api_get_user_info($result[1]);
            $message[1] = '<a '.$class.' href="view_message.php?id='.$result[0].$link.'">'.$result[2].'</a><br />'.$userInfo['complete_name'];
            $message[3] = '<a href="new_message.php?re_id='.$result[0].$link.'">'.
                Display::return_icon('message_reply.png', get_lang('ReplyToMessage')).'</a>'.
                '&nbsp;&nbsp;<a onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;" href="inbox.php?action=deleteone&id='.$result[0].$link.'">'.Display::return_icon('delete.png', get_lang('DeleteMessage')).'</a>';

            $message[2] = api_convert_and_format_date($result[3], DATE_TIME_FORMAT_LONG); //date stays the same
            foreach ($message as $key => $value) {
                $message[$key] = api_xml_http_response_encode($value);
            }
            $message_list[] = $message;
            $i++;
        }

        return $message_list;
    }

    /**
     * Sends a message to a user/group
     *
     * @param int $receiver_user_id
     * @param string $subject
     * @param string $content
     * @param array $file_attachments files array($_FILES) (optional)
     * @param array $file_comments about attachment files (optional)
     * @param int $group_id (optional)
     * @param int $parent_id (optional)
     * @param int $edit_message_id id for updating the message (optional)
     * @param int $topic_id (optional) the default value is the current user_id
     * @param int $sender_id
     * @param bool $directMessage
     *
     * @return bool
     */
    public static function send_message(
        $receiver_user_id,
        $subject,
        $content,
        array $file_attachments = [],
        array $file_comments = [],
        $group_id = 0,
        $parent_id = 0,
        $edit_message_id = 0,
        $topic_id = 0,
        $sender_id = null,
        $directMessage = false
    ) {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $group_id = intval($group_id);
        $receiver_user_id = intval($receiver_user_id);
        $parent_id = intval($parent_id);
        $edit_message_id = intval($edit_message_id);
        $topic_id = intval($topic_id);

        if (!empty($receiver_user_id)) {
            $receiverUserInfo = api_get_user_info($receiver_user_id);

            // Disabling messages for inactive users.
            if ($receiverUserInfo['active'] == 0) {
                return false;
            }
        }

        if (empty($sender_id)) {
            $user_sender_id = api_get_user_id();
        } else {
            $user_sender_id = intval($sender_id);
        }

        $total_filesize = 0;
        if (is_array($file_attachments)) {
            foreach ($file_attachments as $file_attach) {
                $fileSize = isset($file_attach['size']) ? $file_attach['size'] : 0;
                if (is_array($fileSize)) {
                    foreach ($fileSize as $size) {
                        $total_filesize += $size;
                    }
                } else {
                    $total_filesize += $fileSize;
                }
            }
        }

        // Validating fields
        if (empty($subject) && empty($group_id)) {
            Display::addFlash(Display::return_message(get_lang('YouShouldWriteASubject'), 'warning'));
            return false;
        } else if ($total_filesize > intval(api_get_setting('message_max_upload_filesize'))) {
            $warning = sprintf(
                get_lang("FilesSizeExceedsX"),
                format_file_size(api_get_setting('message_max_upload_filesize'))
            );

            Display::addFlash(Display::return_message($warning, 'warning'));

            return false;
        }

        $inbox_last_id = null;

        //Just in case we replace the and \n and \n\r while saving in the DB
        //$content = str_replace(array("\n", "\n\r"), '<br />', $content);

        $now = api_get_utc_datetime();
        if (!empty($receiver_user_id) || !empty($group_id)) {
            // message for user friend
            //@todo it's possible to edit a message? yes, only for groups
            if ($edit_message_id) {
                $query = " UPDATE $table_message SET
                                update_date = '".$now."',
                                content = '".Database::escape_string($content)."'
                           WHERE id = '$edit_message_id' ";
                Database::query($query);
                $inbox_last_id = $edit_message_id;
            } else {
                $params = [
                    'user_sender_id' => $user_sender_id,
                    'user_receiver_id' => $receiver_user_id,
                    'msg_status' => '1',
                    'send_date' => $now,
                    'title' => $subject,
                    'content' => $content,
                    'group_id' => $group_id,
                    'parent_id' => $parent_id,
                    'update_date' => $now
                ];
                $inbox_last_id = Database::insert($table_message, $params);
            }

            // Save attachment file for inbox messages
            if (is_array($file_attachments)) {
                $i = 0;
                foreach ($file_attachments as $file_attach) {
                    if ($file_attach['error'] == 0) {
                        self::save_message_attachment_file(
                            $file_attach,
                            isset($file_comments[$i]) ? $file_comments[$i] : null,
                            $inbox_last_id,
                            null,
                            $receiver_user_id,
                            $group_id
                        );
                    }
                    $i++;
                }
            }

            if (empty($group_id)) {
                // message in outbox for user friend or group
                $params = [
                    'user_sender_id' => $user_sender_id,
                    'user_receiver_id' => $receiver_user_id,
                    'msg_status' => '4',
                    'send_date' => $now,
                    'title' => $subject,
                    'content' => $content,
                    'group_id' => $group_id,
                    'parent_id' => $parent_id,
                    'update_date' => $now
                ];
                $outbox_last_id = Database::insert($table_message, $params);

                // save attachment file for outbox messages
                if (is_array($file_attachments)) {
                    $o = 0;
                    foreach ($file_attachments as $file_attach) {
                        if ($file_attach['error'] == 0) {
                            $comment = isset($file_comments[$o]) ? $file_comments[$o] : '';
                            self::save_message_attachment_file(
                                $file_attach,
                                $comment,
                                $outbox_last_id,
                                $user_sender_id
                            );
                        }
                        $o++;
                    }
                }
            }

            // Load user settings.
            $notification = new Notification();
            $sender_info = api_get_user_info($user_sender_id);

            // add file attachment additional attributes
            foreach ($file_attachments as $index => $file_attach) {
                $file_attachments[$index]['path'] = $file_attach['tmp_name'];
                $file_attachments[$index]['filename'] = $file_attach['name'];
            }

            if (empty($group_id)) {
                $type = Notification::NOTIFICATION_TYPE_MESSAGE;
                if ($directMessage) {
                    $type = Notification::NOTIFICATION_TYPE_DIRECT_MESSAGE;
                }
                $notification->save_notification(
                    $type,
                    array($receiver_user_id),
                    $subject,
                    $content,
                    $sender_info,
                    $file_attachments
                );
            } else {
                $usergroup = new UserGroup();
                $group_info = $usergroup->get($group_id);
                $group_info['topic_id'] = $topic_id;
                $group_info['msg_id'] = $inbox_last_id;

                $user_list = $usergroup->get_users_by_group($group_id, false, array(), 0, 1000);

                // Adding more sense to the message group
                $subject = sprintf(get_lang('ThereIsANewMessageInTheGroupX'), $group_info['name']);

                $new_user_list = array();
                foreach ($user_list as $user_data) {
                    $new_user_list[] = $user_data['id'];
                }
                $group_info = array(
                    'group_info' => $group_info,
                    'user_info' => $sender_info,
                );
                $notification->save_notification(
                    Notification::NOTIFICATION_TYPE_GROUP,
                    $new_user_list,
                    $subject,
                    $content,
                    $group_info,
                    $file_attachments
                );
            }

            return $inbox_last_id;
        }

        return false;
    }

    /**
     * @param int $receiver_user_id
     * @param int $subject
     * @param string $message
     * @param int $sender_id
     * @param bool $sendCopyToDrhUsers send copy to related DRH users
     * @param bool $directMessage
     *
     * @return bool
     */
    public static function send_message_simple(
        $receiver_user_id,
        $subject,
        $message,
        $sender_id = null,
        $sendCopyToDrhUsers = false,
        $directMessage = false
    ) {
        $result = self::send_message(
            $receiver_user_id,
            $subject,
            $message,
            $_FILES ? $_FILES : [],
            [],
            null,
            null,
            null,
            null,
            $sender_id,
            $directMessage
        );

        if ($sendCopyToDrhUsers) {
            $userInfo = api_get_user_info($receiver_user_id);
            $drhList = UserManager::getDrhListFromUser($receiver_user_id);
            if (!empty($drhList)) {
                foreach ($drhList as $drhInfo) {
                    $message = sprintf(
                            get_lang('CopyOfMessageSentToXUser'),
                            $userInfo['complete_name']
                        ).' <br />'.$message;

                    self::send_message_simple(
                        $drhInfo['user_id'],
                        $subject,
                        $message,
                        $sender_id,
                        false,
                        $directMessage
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Update parent ids for other receiver user from current message in groups
     * @author Christian Fasanando Flores
     * @param  int $parent_id
     * @param  int $receiver_user_id
     * @param  int $message_id
     * @return void
     */
    public static function update_parent_ids_from_reply(
        $parent_id,
        $receiver_user_id,
        $message_id
    ) {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $parent_id = intval($parent_id);
        $receiver_user_id = intval($receiver_user_id);
        $message_id = intval($message_id);
        // first get data from message id (parent)
        $sql_message = "SELECT * FROM $table_message WHERE id = '$parent_id'";
        $rs_message = Database::query($sql_message);
        $row_message = Database::fetch_array($rs_message);

        // get message id from data found early for other receiver user
        $sql = "SELECT id FROM $table_message
                WHERE
                    user_sender_id ='{$row_message['user_sender_id']}' AND
                    title='{$row_message['title']}' AND
                    content='{$row_message['content']}' AND
                    group_id='{$row_message['group_id']}' AND
                    user_receiver_id='$receiver_user_id'";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        // update parent_id for other user receiver
        $sql = "UPDATE $table_message SET parent_id = ".$row['id']."
                WHERE id = $message_id";
        Database::query($sql);
    }

    /**
     * @param int $user_receiver_id
     * @param int $id
     * @return bool
     */
    public static function delete_message_by_user_receiver($user_receiver_id, $id)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        if ($id != strval(intval($id))) {
            return false;
        }
        $user_receiver_id = intval($user_receiver_id);
        $id = intval($id);
        $sql = "SELECT * FROM $table_message
                WHERE id=".$id." AND msg_status<>4";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            // delete attachment file
            self::delete_message_attachment_file($id, $user_receiver_id);
            // delete message
            $query = "UPDATE $table_message 
                      SET msg_status = 3
                      WHERE 
                        user_receiver_id=".$user_receiver_id." AND 
                        id = " . $id;
            Database::query($query);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Set status deleted
     * @author Isaac FLores Paz <isaac.flores@dokeos.com>
     * @param  int
     * @param  int
     * @return bool
     */
    public static function delete_message_by_user_sender($user_sender_id, $id)
    {
        if ($id != strval(intval($id))) {
            return false;
        }

        $table_message = Database::get_main_table(TABLE_MESSAGE);

        $id = intval($id);
        $user_sender_id = intval($user_sender_id);

        $sql = "SELECT * FROM $table_message WHERE id='$id'";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            // delete attachment file
            self::delete_message_attachment_file($id, $user_sender_id);
            // delete message
            $sql = "UPDATE $table_message 
                    SET msg_status=3
                    WHERE user_sender_id='$user_sender_id' AND id='$id'";
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * Saves a message attachment files
     * @param  array $file_attach $_FILES['name']
     * @param  string    a comment about the uploaded file
     * @param  int        message id
     * @param  int        receiver user id (optional)
     * @param  int        sender user id (optional)
     * @param  int        group id (optional)
     * @return void
     */
    public static function save_message_attachment_file(
        $file_attach,
        $file_comment,
        $message_id,
        $receiver_user_id = 0,
        $sender_user_id = 0,
        $group_id = 0
    ) {
        $tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        // Try to add an extension to the file if it hasn't one
        $new_file_name = add_ext_on_mime(stripslashes($file_attach['name']), $file_attach['type']);

        // user's file name
        $file_name = $file_attach['name'];
        if (!filter_extension($new_file_name)) {
            echo Display::return_message(get_lang('UplUnableToSaveFileFilteredExtension'), 'error');
        } else {
            $new_file_name = uniqid('');
            if (!empty($receiver_user_id)) {
                $message_user_id = $receiver_user_id;
            } else {
                $message_user_id = $sender_user_id;
            }

            // User-reserved directory where photos have to be placed.*
            $userGroup = new UserGroup();

            if (!empty($group_id)) {
                $path_user_info = $userGroup->get_group_picture_path_by_id($group_id, 'system', true);
            } else {
                $path_user_info['dir'] = UserManager::getUserPathById($message_user_id, 'system');
            }

            $path_message_attach = $path_user_info['dir'].'message_attachments/';

            // If this directory does not exist - we create it.
            if (!file_exists($path_message_attach)) {
                @mkdir($path_message_attach, api_get_permissions_for_new_directories(), true);
            }
            $new_path = $path_message_attach.$new_file_name;
            if (is_uploaded_file($file_attach['tmp_name'])) {
                @copy($file_attach['tmp_name'], $new_path);
            }

            // Storing the attachments if any
            $params = [
                'filename' => $file_name,
                'comment' => $file_comment,
                'path' => $new_file_name,
                'message_id' => $message_id,
                'size' => $file_attach['size']
            ];
            Database::insert($tbl_message_attach, $params);
        }
    }

    /**
     * Delete message attachment files (logically updating the row with a suffix _DELETE_id)
     * @param  int    message id
     * @param  int    message user id (receiver user id or sender user id)
     * @param  int    group id (optional)
     * @return void
     */
    public static function delete_message_attachment_file(
        $message_id,
        $message_uid,
        $group_id = 0
    ) {
        $message_id = intval($message_id);
        $message_uid = intval($message_uid);
        $table_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        $sql = "SELECT * FROM $table_message_attach 
                WHERE message_id = '$message_id'";
        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs)) {
            $path = $row['path'];
            $attach_id = $row['id'];
            $new_path = $path.'_DELETED_'.$attach_id;

            if (!empty($group_id)) {
                $userGroup = new UserGroup();
                $path_user_info = $userGroup->get_group_picture_path_by_id(
                    $group_id,
                    'system',
                    true
                );
            } else {
                $path_user_info['dir'] = UserManager::getUserPathById(
                    $message_uid,
                    'system'
                );
            }

            $path_message_attach = $path_user_info['dir'].'message_attachments/';
            if (is_file($path_message_attach.$path)) {
                if (rename($path_message_attach.$path, $path_message_attach.$new_path)) {
                    $sql = "UPDATE $table_message_attach set path='$new_path'
                            WHERE id ='$attach_id'";
                    Database::query($sql);
                }
            }
        }
    }

    /**
     * update messages by user id and message id
     * @param  int $user_id
     * @param  int $message_id
     * @return resource
     */
    public static function update_message($user_id, $message_id)
    {
        if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) {
            return false;
        }

        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = "UPDATE $table_message SET msg_status = '0'
                WHERE
                    msg_status<>4 AND
                    user_receiver_id=".intval($user_id)." AND
                    id='" . intval($message_id)."'";
        Database::query($sql);
    }

    /**
     * @param int $user_id
     * @param int $message_id
     * @param string $type
     * @return bool
     */
    public static function update_message_status($user_id, $message_id, $type)
    {
        $type = intval($type);
        if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) {
            return false;
        }
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $sql = "UPDATE $table_message SET
                    msg_status = '$type'
                WHERE
                    user_receiver_id=".intval($user_id)." AND
                    id='" . intval($message_id)."'";
        Database::query($sql);
    }

    /**
     * get messages by user id and message id
     * @param  int $user_id
     * @param  int $message_id
     * @return array
     */
    public static function get_message_by_user($user_id, $message_id)
    {
        if ($message_id != strval(intval($message_id)) || $user_id != strval(intval($user_id))) {
            return false;
        }
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $query = "SELECT * FROM $table_message
                  WHERE user_receiver_id=".intval($user_id)." AND id='".intval($message_id)."'";
        $result = Database::query($query);

        return $row = Database::fetch_array($result);
    }

    /**
     * get messages by group id
     * @param  int $group_id group id
     * @return array
     */
    public static function get_messages_by_group($group_id)
    {
        if ($group_id != strval(intval($group_id))) {
            return false;
        }

        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $group_id = intval($group_id);
        $sql = "SELECT * FROM $table_message
                WHERE
                    group_id= $group_id AND
                    msg_status NOT IN ('".MESSAGE_STATUS_OUTBOX."', '".MESSAGE_STATUS_DELETED."')
                ORDER BY id";
        $rs = Database::query($sql);
        $data = array();
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * get messages by group id
     * @param  int $group_id
     * @param int $message_id
     * @return array
     */
    public static function get_messages_by_group_by_message($group_id, $message_id)
    {
        if ($group_id != strval(intval($group_id))) {
            return false;
        }
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $group_id = intval($group_id);
        $sql = "SELECT * FROM $table_message
                WHERE
                    group_id = $group_id AND
                    msg_status NOT IN ('".MESSAGE_STATUS_OUTBOX."', '".MESSAGE_STATUS_DELETED."')
                ORDER BY id ";

        $rs = Database::query($sql);
        $data = array();
        $parents = array();
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs, 'ASSOC')) {
                if ($message_id == $row['parent_id'] || in_array($row['parent_id'], $parents)) {
                    $parents[] = $row['id'];
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * get messages by parent id optionally with limit
     * @param  int        parent id
     * @param  int        group id (optional)
     * @param  int        offset (optional)
     * @param  int        limit (optional)
     * @return array
     */
    public static function get_messages_by_parent($parent_id, $group_id = '', $offset = 0, $limit = 0)
    {
        if ($parent_id != strval(intval($parent_id))) {
            return false;
        }
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $parent_id = intval($parent_id);
        $condition_group_id = '';
        if ($group_id !== '') {
            $group_id = intval($group_id);
            $condition_group_id = " AND group_id = '$group_id' ";
        }

        $condition_limit = "";
        if ($offset && $limit) {
            $offset = ($offset - 1) * $limit;
            $condition_limit = " LIMIT $offset,$limit ";
        }

        $sql = "SELECT * FROM $table_message
                WHERE
                    parent_id='$parent_id' AND
                    msg_status <> ".MESSAGE_STATUS_OUTBOX."
                    $condition_group_id
                ORDER BY send_date DESC $condition_limit ";
        $rs = Database::query($sql);
        $data = array();
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $data[$row['id']] = $row;
            }
        }

        return $data;
    }

    /**
     * Gets information about if exist messages
     * @author Isaac FLores Paz <isaac.flores@dokeos.com>
     * @param  integer
     * @param  integer
     * @return boolean
     */
    public static function exist_message($user_id, $id)
    {
        if ($id != strval(intval($id)) || $user_id != strval(intval($user_id)))
            return false;
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $query = "SELECT id FROM $table_message
                  WHERE
                    user_receiver_id = ".intval($user_id)." AND
                    id = '" . intval($id)."'";
        $result = Database::query($query);
        $num = Database::num_rows($result);
        if ($num > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets information about messages sent
     * @param  integer
     * @param  integer
     * @param  string
     * @return array
     */
    public static function get_message_data_sent($from, $number_of_items, $column, $direction)
    {
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        if (!isset($direction)) {
            $column = 3;
            $direction = 'DESC';
        } else {
            $column = intval($column);
            if (!in_array($direction, array('ASC', 'DESC'))) {
                $direction = 'ASC';
            }
        }
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $request = api_is_xml_http_request();
        $keyword = Session::read('message_sent_search_keyword');
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (title like '%$keyword%' OR content LIKE '%$keyword%') ";
        }

        $sql = "SELECT
                    id as col0, 
                    user_sender_id as col1, 
                    title as col2, 
                    send_date as col3, 
                    user_receiver_id as col4, 
                    msg_status as col5
                FROM $table_message
                WHERE
                    user_sender_id=".api_get_user_id()." AND
                    msg_status=" . MESSAGE_STATUS_OUTBOX."
                    $keywordCondition
                ORDER BY col$column $direction
                LIMIT $from, $number_of_items";
        $sql_result = Database::query($sql);
        $i = 0;
        $message_list = array();
        while ($result = Database::fetch_row($sql_result)) {
            if ($request === true) {
                $message[0] = '<input type="checkbox" value='.$result[0].' name="out[]">';
            } else {
                $message[0] = ($result[0]);
            }
            $class = 'class = "read"';
            $result[2] = Security::remove_XSS($result[2]);
            $userInfo = api_get_user_info($result[4]);
            if ($request === true) {
                $message[1] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.$userInfo['complete_name'].'</a>';
                $message[2] = '<a onclick="show_sent_message('.$result[0].')" href="javascript:void(0)">'.str_replace("\\", "", $result[2]).'</a>';
                $message[3] = api_convert_and_format_date($result[3], DATE_TIME_FORMAT_LONG); //date stays the same
                $message[4] = '&nbsp;&nbsp;<a onclick="delete_one_message_outbox('.$result[0].')" href="javascript:void(0)"  >'.
                    Display::return_icon('delete.png', get_lang('DeleteMessage')).'</a>';
            } else {
                $link = '';
                if (isset($_GET['f']) && $_GET['f'] == 'social') {
                    $link = '&f=social';
                }
                $message[1] = '<a '.$class.' onclick="show_sent_message ('.$result[0].')" href="../messages/view_message.php?id_send='.$result[0].$link.'">'.$result[2].'</a><br />'.$userInfo['complete_name'];
                $message[2] = api_convert_and_format_date($result[3], DATE_TIME_FORMAT_LONG); //date stays the same
                $message[3] = '<a href="outbox.php?action=deleteone&id='.$result[0].'&'.$link.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmDeleteMessage')))."'".')) return false;" >'.
                    Display::return_icon('delete.png', get_lang('DeleteMessage')).'</a>';
            }

            foreach ($message as $key => $value) {
                $message[$key] = $value;
            }
            $message_list[] = $message;
            $i++;
        }

        return $message_list;
    }

    /**
     * Gets information about number messages sent
     * @author Isaac FLores Paz <isaac.flores@dokeos.com>
     * @param void
     * @return integer
     */
    public static function get_number_of_messages_sent()
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);

        $keyword = Session::read('message_sent_search_keyword');
        $keywordCondition = '';
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (title like '%$keyword%' OR content LIKE '%$keyword%') ";
        }

        $sql = "SELECT COUNT(id) as number_messages 
                FROM $table_message
                WHERE
                  msg_status=".MESSAGE_STATUS_OUTBOX." AND
                  user_sender_id=" . api_get_user_id()."
                  $keywordCondition
                ";
        $result = Database::query($sql);
        $result = Database::fetch_array($result);

        return $result['number_messages'];
    }

    /**
     * display message box in the inbox
     * @param int the message id
     * @param string inbox or outbox strings are available
     * @todo replace numbers with letters in the $row array pff...
     * @return string html with the message content
     */
    public static function show_message_box($message_id, $source = 'inbox')
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $message_id = intval($message_id);

        if ($source == 'outbox') {
            if (isset($message_id) && is_numeric($message_id)) {
                $query = "SELECT * FROM $table_message
                          WHERE
                            user_sender_id = ".api_get_user_id()." AND
                            id = " . $message_id." AND
                            msg_status = 4;";
                $result = Database::query($query);
            }
        } else {
            if (is_numeric($message_id) && !empty($message_id)) {
                $query = "UPDATE $table_message SET
                          msg_status = '".MESSAGE_STATUS_NEW."'
                          WHERE
                            user_receiver_id=" . api_get_user_id()." AND
                            id='" . $message_id."'";
                Database::query($query);

                $query = "SELECT * FROM $table_message
                          WHERE
                            msg_status<>4 AND
                            user_receiver_id=".api_get_user_id()." AND
                            id='" . $message_id."'";
                $result = Database::query($query);
            }
        }
        $row = Database::fetch_array($result, 'ASSOC');
        $user_sender_id = $row['user_sender_id'];

        // get file attachments by message id
        $files_attachments = self::get_links_message_attachment_files($message_id, $source);

        $title = Security::remove_XSS($row['title'], STUDENT, true);
        $content = Security::remove_XSS($row['content'], STUDENT, true);

        $from_user = api_get_user_info($user_sender_id);
        $name = $from_user['complete_name'];
        $message_content = Display::page_subheader(str_replace("\\", "", $title));
        $user_image = '';
        if (api_get_setting('allow_social_tool') == 'true') {
            $user_image = Display::img(
                $from_user['avatar_no_query'],
                $name,
                array('title' => $name, 'class' => 'img-responsive img-circle', 'style' => 'max-width:35px')
            );
        }

        $receiverUserInfo = api_get_user_info($row['user_receiver_id']);

        $message_content .= '<tr>';
        if (api_get_setting('allow_social_tool') == 'true') {
            $message_content .= '<div class="row">';
            if ($source == 'outbox') {
                $message_content .= '<div class="col-md-12">';
                $message_content .= '<ul class="list-message">';
                $message_content .= '<li>'.$user_image.'</li>';
                $message_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_sender_id.'">'.$name.'</a> ';
                $message_content .= api_strtolower(get_lang('To')).'&nbsp;<b>'.$receiverUserInfo['complete_name'].'</b></li>';
                $message_content .= '<li>'.Display::dateToStringAgoAndLongDate($row['send_date']).'</li>';
                $message_content .= '</ul>';
                $message_content .= '</div>';
            } else {
                $message_content .= '<div class="col-md-12">';
                $message_content .= '<ul class="list-message">';
                $message_content .= '<li>'.$user_image.'</li>';
                $message_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user_sender_id.'">'.$name.'</a> </li>';
                $message_content .= '<li>'.Display::dateToStringAgoAndLongDate($row['send_date']).'</li>';
                $message_content .= '</ul>';
                $message_content .= '</div>';
            }
            $message_content .= '</div>';
        } else {
            if ($source == 'outbox') {
                $message_content .= get_lang('From').':&nbsp;'.$name.'</b> '.api_strtolower(get_lang('To')).' <b>'.$receiverUserInfo['complete_name'].'</b>';
            } else {
                $message_content .= get_lang('From').':&nbsp;'.$name.'</b> '.api_strtolower(get_lang('To')).' <b>'.get_lang('Me').'</b>';
            }
        }

        $message_content .= '		        
		        <hr style="color:#ddd" />
		        <table width="100%">
		            <tr>
		              <td valign=top class="view-message-content">' . str_replace("\\", "", $content).'</td>
		            </tr>
		        </table>
		        <div id="message-attach">' . (!empty($files_attachments) ? implode('<br />', $files_attachments) : '').'</div>
		        <div style="padding: 15px 0px 5px 0px">';
        $social_link = '';
        if (isset($_GET['f']) && $_GET['f'] == 'social') {
            $social_link = 'f=social';
        }
        if ($source == 'outbox') {
            $message_content .= '<a href="outbox.php?'.$social_link.'">'.
                Display::return_icon('back.png', get_lang('ReturnToOutbox')).'</a> &nbsp';
        } else {
            $message_content .= '<a href="inbox.php?'.$social_link.'">'.
                Display::return_icon('back.png', get_lang('ReturnToInbox')).'</a> &nbsp';
            $message_content .= '<a href="new_message.php?re_id='.$message_id.'&'.$social_link.'">'.
                Display::return_icon('message_reply.png', get_lang('ReplyToMessage')).'</a> &nbsp';
        }
        $message_content .= '<a href="inbox.php?action=deleteone&id='.$message_id.'&'.$social_link.'" >'.
            Display::return_icon('delete.png', get_lang('DeleteMessage')).'</a>&nbsp';

        $message_content .= '</div></td>
		      <td width=10></td>
		    </tr>
		</table>';

        return $message_content;
    }

    /**
     * get user id by user email
     * @param string $user_email
     * @return int user id
     */
    public static function get_user_id_by_email($user_email)
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT user_id FROM '.$tbl_user.'
                WHERE email="' . Database::escape_string($user_email).'";';
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs, 'ASSOC');
        if (isset($row['user_id'])) {
            return $row['user_id'];
        } else {
            return null;
        }
    }

    /**
     * Displays messages of a group with nested view
     *
     * @param int $group_id
     */
    public static function display_messages_for_group($group_id)
    {
        global $my_group_role;

        $rows = self::get_messages_by_group($group_id);
        $topics_per_page = 10;
        $html_messages = '';
        $query_vars = array('id' => $group_id, 'topics_page_nr' => 0);

        if (is_array($rows) && count($rows) > 0) {
            // prepare array for topics with its items
            $topics = array();
            $x = 0;
            foreach ($rows as $index => $value) {
                if (empty($value['parent_id'])) {
                    $topics[$value['id']] = $value;
                }
            }

            $new_topics = array();

            foreach ($topics as $id => $value) {
                $rows = null;
                $rows = self::get_messages_by_group_by_message($group_id, $value['id']);
                if (!empty($rows)) {
                    $count = count(self::calculate_children($rows, $value['id']));
                } else {
                    $count = 0;
                }
                $value['count'] = $count;
                $new_topics[$id] = $value;
            }

            $array_html = array();

            foreach ($new_topics as $index => $topic) {
                $html = '';
                // topics
                $user_sender_info = api_get_user_info($topic['user_sender_id']);
                $name = $user_sender_info['complete_name'];
                $html .= '<div class="groups-messages">';
                $html .= '<div class="row">';

                $items = $topic['count'];
                $reply_label = ($items == 1) ? get_lang('GroupReply') : get_lang('GroupReplies');
                $label = '<i class="fa fa-envelope"></i> '.$items.' '.$reply_label;
                $topic['title'] = trim($topic['title']);

                if (empty($topic['title'])) {
                    $topic['title'] = get_lang('Untitled');
                }

                $html .= '<div class="col-xs-8 col-md-10">';
                $html .= Display::tag(
                    'h4',
                    Display::url(
                        Security::remove_XSS($topic['title'], STUDENT, true),
                        api_get_path(WEB_CODE_PATH).'social/group_topics.php?id='.$group_id.'&topic_id='.$topic['id']
                    ), array('class' => 'title')
                );
                $actions = '';
                if ($my_group_role == GROUP_USER_PERMISSION_ADMIN ||
                    $my_group_role == GROUP_USER_PERMISSION_MODERATOR
                ) {
                    $actions = '<br />'.Display::url(get_lang('Delete'), api_get_path(WEB_CODE_PATH).'social/group_topics.php?action=delete&id='.$group_id.'&topic_id='.$topic['id'], array('class' => 'btn btn-default'));
                }

                $date = '';
                if ($topic['send_date'] != $topic['update_date']) {
                    if (!empty($topic['update_date'])) {
                        $date .= '<i class="fa fa-calendar"></i> '.get_lang('LastUpdate').' '.date_to_str_ago($topic['update_date']);
                    }
                } else {
                    $date .= '<i class="fa fa-calendar"></i> '.get_lang('Created').' '.date_to_str_ago($topic['send_date']);
                }
                $html .= '<div class="date">'.$label.' - '.$date.$actions.'</div>';
                $html .= '</div>';

                $image = $user_sender_info['avatar'];

                $user_info = '<div class="author"><img class="img-responsive img-circle" src="'.$image.'" alt="'.$name.'"  width="64" height="64" title="'.$name.'" /></div>';
                $user_info .= '<div class="name"><a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$topic['user_sender_id'].'">'.$name.'&nbsp;</a></div>';

                $html .= '<div class="col-xs-4 col-md-2">';
                $html .= $user_info;
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';

                $array_html[] = array($html);
            }

            // grids for items and topics  with paginations
            $html_messages .= Display::return_sortable_grid(
                'topics',
                array(),
                $array_html,
                array(
                    'hide_navigation' => false,
                    'per_page' => $topics_per_page
                ),
                $query_vars,
                false,
                array(true, true, true, false),
                false
            );
        }

        return $html_messages;
    }

    /**
     * Displays messages of a group with nested view
     * @param $group_id
     * @param $topic_id
     * @param $is_member
     * @param $message_id
     * @return string
     */
    public static function display_message_for_group($group_id, $topic_id, $is_member, $message_id)
    {
        global $my_group_role;
        $main_message = self::get_message_by_id($topic_id);
        if (empty($main_message)) {
            return false;
        }
        $rows = self::get_messages_by_group_by_message($group_id, $topic_id);
        $rows = self::calculate_children($rows, $topic_id);
        $current_user_id = api_get_user_id();

        $items_per_page = 50;
        $query_vars = array('id' => $group_id, 'topic_id' => $topic_id, 'topics_page_nr' => 0);

        // Main message
        $links = '';
        $main_content = '';
        $html = '';
        $items_page_nr = null;

        $user_sender_info = api_get_user_info($main_message['user_sender_id']);
        $files_attachments = self::get_links_message_attachment_files($main_message['id']);
        $name = $user_sender_info['complete_name'];

        $topic_page_nr = isset($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : null;

        $links .= '<div class="pull-right">';
        $links .= '<div class="btn-group btn-group-sm">';

        if (($my_group_role == GROUP_USER_PERMISSION_ADMIN ||
                $my_group_role == GROUP_USER_PERMISSION_MODERATOR) ||
            $main_message['user_sender_id'] == $current_user_id
        ) {
            $urlEdit = api_get_path(WEB_CODE_PATH);
            $urlEdit .= 'social/message_for_group_form.inc.php?';
            $urlEdit .= http_build_query([
                'user_friend' => $current_user_id,
                'group_id' => $group_id,
                'message_id' => $main_message['id'],
                'action' => 'edit_message_group',
                'anchor_topic' => 'topic_'.$main_message['id'],
                'topics_page_nr' => $topic_page_nr,
                'items_page_nr' => $items_page_nr,
                'topic_id' => $main_message['id']
            ]);
            if (api_is_platform_admin()) {
                $links .= Display::url(
                    Display::returnFontAwesomeIcon('trash'),
                    'group_topics.php?action=delete&id='.$group_id.'&topic_id='.$topic_id,
                    [
                        'class' => 'btn btn-default'
                    ]
                );
            }
            $links .= Display::url(
                Display::returnFontAwesomeIcon('pencil'),
                $urlEdit,
                [
                    'class' => 'btn btn-default ajax',
                    'title' => get_lang('Edit'),
                    'data-title' => get_lang('Edit'),
                    'data-size' => 'lg'
                ]
            );
        }

        $urlReply = api_get_path(WEB_CODE_PATH);
        $urlReply .= 'social/message_for_group_form.inc.php?';
        $urlReply .= http_build_query([
            'user_friend' => api_get_user_id(),
            'group_id' => $group_id,
            'message_id' => $main_message['id'],
            'action' => 'reply_message_group',
            'anchor_topic' => 'topic_'.$main_message['id'],
            'topics_page_nr' => $topic_page_nr,
            'topic_id' => $main_message['id']
        ]);

        $links .= Display::url(
            Display::returnFontAwesomeIcon('commenting'),
            $urlReply,
            [
                'class' => 'btn btn-default ajax',
                'title' => get_lang('Reply'),
                'data-title' => get_lang('Reply'),
                'data-size' => 'lg'
            ]
        );
        $links .= '</div>';
        $links .= '</div>';

        $title = '<h4>'.Security::remove_XSS($main_message['title'], STUDENT, true).$links.'</h4>';

        $userPicture = $user_sender_info['avatar'];
        $main_content .= '<div class="row">';
        $main_content .= '<div class="col-md-2">';
        $main_content .= '<div class="avatar-author">';
        $main_content .= '<img width="60px" src="'.$userPicture.'" alt="'.$name.'" class="img-responsive img-circle" title="'.$name.'" />';
        $main_content .= '</div>';
        $main_content .= '</div>';

        $date = '';
        if ($main_message['send_date'] != $main_message['update_date']) {
            if (!empty($main_message['update_date'])) {
                $date = '<div class="date"> '.Display::returnFontAwesomeIcon('calendar').' '.get_lang('LastUpdate').' '.date_to_str_ago($main_message['update_date']).'</div>';
            }
        } else {
            $date = '<div class="date"> '.Display::returnFontAwesomeIcon('calendar').' '.get_lang('Created').' '.date_to_str_ago($main_message['send_date']).'</div>';
        }
        $attachment = '<div class="message-attach">'.(!empty($files_attachments) ? implode('<br />', $files_attachments) : '').'</div>';
        $main_content .= '<div class="col-md-10">';
        $user_link = '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$main_message['user_sender_id'].'">'.$name.'</a>';
        $main_content .= '<div class="message-content"> ';
        $main_content .= '<div class="username">'.$user_link.'</div>';
        $main_content .= $date;
        $main_content .= '<div class="message">'.$main_message['content'].$attachment.'</div></div>';
        $main_content .= '</div>';
        $main_content .= '</div>';

        $html .= Display::div(
            Display::div(
                $title.$main_content,
                array('class' => 'message-topic')
            ),
            array('class' => 'sm-groups-message')
        );

        $topic_id = $main_message['id'];

        if (is_array($rows) && count($rows) > 0) {
            $topics = $rows;
            $array_html_items = array();
            foreach ($topics as $index => $topic) {
                if (empty($topic['id'])) {
                    continue;
                }
                $items_page_nr = isset($_GET['items_'.$topic['id'].'_page_nr']) ? intval($_GET['items_'.$topic['id'].'_page_nr']) : null;
                $links = '';
                $links .= '<div class="pull-right">';
                $html_items = '';
                $user_sender_info = api_get_user_info($topic['user_sender_id']);
                $files_attachments = self::get_links_message_attachment_files($topic['id']);
                $name = $user_sender_info['complete_name'];

                $links .= '<div class="btn-group btn-group-sm">';
                if (($my_group_role == GROUP_USER_PERMISSION_ADMIN || $my_group_role == GROUP_USER_PERMISSION_MODERATOR) || $topic['user_sender_id'] == $current_user_id) {
                    $links .= '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?height=400&width=800&&user_friend='.$current_user_id.'&group_id='.$group_id.'&message_id='.$topic['id'].'&action=edit_message_group&anchor_topic=topic_'.$topic_id.'&topics_page_nr='.$topic_page_nr.'&items_page_nr='.$items_page_nr.'&topic_id='.$topic_id.'" class="ajax btn btn-default" data-size="lg" data-title="'.get_lang('Edit').'" title="'.get_lang('Edit').'">'.
                        Display::returnFontAwesomeIcon('pencil').'</a>';
                }
                $links .= '<a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?height=400&width=800&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&message_id='.$topic['id'].'&action=reply_message_group&anchor_topic=topic_'.$topic_id.'&topics_page_nr='.$topic_page_nr.'&items_page_nr='.$items_page_nr.'&topic_id='.$topic_id.'" class="ajax btn btn-default" data-size="lg" data-title="'.get_lang('Reply').'" title="'.get_lang('Reply').'">';
                $links .= Display::returnFontAwesomeIcon('commenting').'</a>';
                $links .= '</div>';
                $links .= '</div>';

                $userPicture = $user_sender_info['avatar'];
                $user_link = '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$topic['user_sender_id'].'">'.$name.'&nbsp</a>';
                $html_items .= '<div class="row">';
                $html_items .= '<div class="col-md-2">';
                $html_items .= '<div class="avatar-author"><img width="60px" src="'.$userPicture.'" alt="'.$name.'" class="img-responsive img-circle" title="'.$name.'" /></div>';
                $html_items .= '</div>';

                $date = '';
                if ($topic['send_date'] != $topic['update_date']) {
                    if (!empty($topic['update_date'])) {
                        $date = '<div class="date"> '.Display::returnFontAwesomeIcon('calendar').' '.get_lang('LastUpdate').' '.date_to_str_ago($topic['update_date']).'</div>';
                    }
                } else {
                    $date = '<div class="date"> '.Display::returnFontAwesomeIcon('calendar').get_lang('Created').' '.date_to_str_ago($topic['send_date']).'</div>';
                }
                $attachment = '<div class="message-attach">'.(!empty($files_attachments) ? implode('<br />', $files_attachments) : '').'</div>';
                $html_items .= '<div class="col-md-10">';
                $html_items .= '<div class="message-content">';
                $html_items .= $links;
                $html_items .= '<div class="username">'.$user_link.'</div>';
                $html_items .= $date;
                $html_items .= '<div class="message">'.Security::remove_XSS($topic['content'], STUDENT, true).'</div>'.$attachment.'</div>';
                $html_items .= '</div>';
                $html_items .= '</div>';

                $base_padding = 20;

                if ($topic['indent_cnt'] == 0) {
                    $indent = $base_padding;
                } else {
                    $indent = intval($topic['indent_cnt']) * $base_padding + $base_padding;
                }

                $html_items = Display::div($html_items, array('class' => 'message-post', 'id' => 'msg_'.$topic['id']));
                $html_items = Display::div($html_items, array('class' => '', 'style' => 'margin-left:'.$indent.'px'));
                $array_html_items[] = array($html_items);
            }

            // grids for items with paginations
            $options = array('hide_navigation' => false, 'per_page' => $items_per_page);
            $visibility = array(true, true, true, false);

            $style_class = array(
                'item' => array('class' => 'user-post'),
                'main' => array('class' => 'user-list'),
            );
            if (!empty($array_html_items)) {
                $html .= Display::return_sortable_grid(
                    'items_'.$topic['id'],
                    array(),
                    $array_html_items,
                    $options,
                    $query_vars,
                    null,
                    $visibility,
                    false,
                    $style_class
                );
            }
        }

        return $html;
    }

    /**
     * Add children to messages by id is used for nested view messages
     * @param array $rows rows of messages
     * @return array $first_seed new list adding the item children
     */
    public static function calculate_children($rows, $first_seed)
    {
        $rows_with_children = array();
        foreach ($rows as $row) {
            $rows_with_children[$row["id"]] = $row;
            $rows_with_children[$row["parent_id"]]["children"][] = $row["id"];
        }
        $rows = $rows_with_children;
        $sorted_rows = array(0 => array());
        self::message_recursive_sort($rows, $sorted_rows, $first_seed);
        unset($sorted_rows[0]);

        return $sorted_rows;
    }

    /**
     * Sort recursively the messages, is used for for nested view messages
     * @param array  original rows of messages
     * @param array  list recursive of messages
     * @param int   seed for calculate the indent
     * @param int   indent for nested view
     * @return void
     */
    public static function message_recursive_sort($rows, &$messages, $seed = 0, $indent = 0)
    {
        if ($seed > 0 && isset($rows[$seed]["id"])) {
            $messages[$rows[$seed]["id"]] = $rows[$seed];
            $messages[$rows[$seed]["id"]]["indent_cnt"] = $indent;
            $indent++;
        }

        if (isset($rows[$seed]["children"])) {
            foreach ($rows[$seed]["children"] as $child) {
                self::message_recursive_sort($rows, $messages, $child, $indent);
            }
        }
    }

    /**
     * Sort date by desc from a multi-dimensional array
     * @param array $array1 first array to compare
     * @param array $array2 second array to compare
     * @return bool
     */
    public function order_desc_date($array1, $array2)
    {
        return strcmp($array2['send_date'], $array1['send_date']);
    }

    /**
     * Get array of links (download) for message attachment files
     * @param int $message_id
     * @param string $type message list (inbox/outbox)
     * @return array
     */
    public static function get_links_message_attachment_files($message_id, $type = '')
    {
        $tbl_message_attach = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);
        $message_id = intval($message_id);

        // get file attachments by message id
        $links_attach_file = array();
        if (!empty($message_id)) {

            $sql = "SELECT * FROM $tbl_message_attach
                    WHERE message_id = '$message_id'";

            $rs_file = Database::query($sql);
            if (Database::num_rows($rs_file) > 0) {
                $attach_icon = Display::return_icon('attachment.gif', '');
                $archiveURL = api_get_path(WEB_CODE_PATH).'messages/download.php?type='.$type.'&file=';
                while ($row_file = Database::fetch_array($rs_file)) {
                    $archiveFile = $row_file['path'];
                    $filename = $row_file['filename'];
                    $filesize = format_file_size($row_file['size']);
                    $filecomment = Security::remove_XSS($row_file['comment']);
                    $filename = Security::remove_XSS($filename);
                    $links_attach_file[] = $attach_icon.'&nbsp;<a href="'.$archiveURL.$archiveFile.'">'.$filename.'</a>&nbsp;('.$filesize.')'.(!empty($filecomment) ? '&nbsp;-&nbsp;<i>'.$filecomment.'</i>' : '');
                }
            }
        }
        return $links_attach_file;
    }

    /**
     * Get message list by id
     * @param int $message_id
     * @return array
     */
    public static function get_message_by_id($message_id)
    {
        $tbl_message = Database::get_main_table(TABLE_MESSAGE);
        $message_id = intval($message_id);
        $sql = "SELECT * FROM $tbl_message
                WHERE 
                    id = '$message_id' AND 
                    msg_status <> '".MESSAGE_STATUS_DELETED."' ";
        $res = Database::query($sql);
        $item = array();
        if (Database::num_rows($res) > 0) {
            $item = Database::fetch_array($res, 'ASSOC');
        }
        return $item;
    }

    /**
     *
     * @return string
     */
    public static function generate_message_form()
    {
        $form = new FormValidator('send_message');
        $form->addText('subject', get_lang('Subject'), false, ['id' => 'subject_id']);
        $form->addTextarea('content', get_lang('Message'), ['id' => 'content_id', 'rows' => '5']);

        return $form->returnForm();
    }

    /**
     * @param $id
     * @param array $params
     * @return string
     */
    public static function generate_invitation_form($id, $params = array())
    {
        $form = new FormValidator('send_invitation');
        $form->addTextarea('content', get_lang('AddPersonalMessage'), ['id' => 'content_invitation_id', 'rows' => 5]);
        return $form->returnForm();
    }

    //@todo this functions should be in the message class
    /**
     * @param string $keyword
     * @return string
     */
    public static function inbox_display($keyword = '')
    {
        $success = get_lang('SelectedMessagesDeleted');
        $success_read = get_lang('SelectedMessagesRead');
        $success_unread = get_lang('SelectedMessagesUnRead');
        $html = '';

        Session::write('message_search_keyword', $keyword);

        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'mark_as_unread' :
                    $number_of_selected_messages = count($_POST['id']);
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $message_id) {
                            self::update_message_status(api_get_user_id(), $message_id, MESSAGE_STATUS_UNREAD);
                        }
                    }
                    $html .= Display::return_message(api_xml_http_response_encode($success_unread), 'normal', false);
                    break;
                case 'mark_as_read' :
                    $number_of_selected_messages = count($_POST['id']);
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $message_id) {
                            self::update_message_status(api_get_user_id(), $message_id, MESSAGE_STATUS_NEW);
                        }
                    }
                    $html .= Display::return_message(api_xml_http_response_encode($success_read), 'normal', false);
                    break;
                case 'delete' :
                    $number_of_selected_messages = count($_POST['id']);
                    foreach ($_POST['id'] as $index => $message_id) {
                        self::delete_message_by_user_receiver(api_get_user_id(), $message_id);
                    }
                    $html .= Display::return_message(api_xml_http_response_encode($success), 'normal', false);
                    break;
                case 'deleteone' :
                    self::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
                    $html .= Display::return_message(api_xml_http_response_encode($success), 'confirmation', false);
                    break;
            }
        }

        // display sortable table with messages of the current user
        $table = new SortableTable(
            'message_inbox',
            array('MessageManager', 'get_number_of_messages'),
            array('MessageManager', 'get_message_data'),
            3,
            20,
            'DESC'
        );
        $table->set_header(0, '', false, array('style' => 'width:15px;'));
        $table->set_header(1, get_lang('Messages'), false);
        $table->set_header(2, get_lang('Date'), true, array('style' => 'width:180px;'));
        $table->set_header(3, get_lang('Modify'), false, array('style' => 'width:70px;'));

        if (isset($_REQUEST['f']) && $_REQUEST['f'] == 'social') {
            $parameters['f'] = 'social';
            $table->set_additional_parameters($parameters);
        }
        $table->set_form_actions(
            array(
                'delete' => get_lang('DeleteSelectedMessages'),
                'mark_as_unread' => get_lang('MailMarkSelectedAsUnread'),
                'mark_as_read' => get_lang('MailMarkSelectedAsRead'),
            )
        );
        $html .= $table->return_table();

        Session::erase('message_search_keyword');

        return $html;
    }

    /**
     * @param string $keyword
     * @return null|string
     */
    public static function outbox_display($keyword = '')
    {
        $social_link = false;
        if (isset($_REQUEST['f']) && $_REQUEST['f'] == 'social') {
            $social_link = 'f=social';
        }

        Session::write('message_sent_search_keyword', $keyword);

        $success = get_lang('SelectedMessagesDeleted').'&nbsp</b><br /><a href="outbox.php?'.$social_link.'">'.get_lang('BackToOutbox').'</a>';

        $html = null;
        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'delete':
                    $number_of_selected_messages = count($_POST['id']);
                    if ($number_of_selected_messages != 0) {
                        foreach ($_POST['id'] as $index => $message_id) {
                            self::delete_message_by_user_receiver(api_get_user_id(), $message_id);
                        }
                    }
                    $html .= Display::return_message(api_xml_http_response_encode($success), 'normal', false);
                    break;
                case 'deleteone':
                    self::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']);
                    $html .= Display::return_message(api_xml_http_response_encode($success), 'normal', false);
                    $html .= '<br/>';
                    break;
            }
        }

        // display sortable table with messages of the current user
        $table = new SortableTable(
            'message_outbox',
            array('MessageManager', 'get_number_of_messages_sent'),
            array('MessageManager', 'get_message_data_sent'),
            3,
            20,
            'DESC'
        );

        $parameters['f'] = isset($_GET['f']) && $_GET['f'] == 'social' ? 'social' : null;
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false, array('style' => 'width:15px;'));

        $table->set_header(1, get_lang('Messages'), false);
        $table->set_header(2, get_lang('Date'), true, array('style' => 'width:160px;'));
        $table->set_header(3, get_lang('Modify'), false, array('style' => 'width:70px;'));

        $table->set_form_actions(array('delete' => get_lang('DeleteSelectedMessages')));
        $html .= $table->return_table();

        Session::erase('message_sent_search_keyword');

        return $html;
    }

    /**
     * Get the count of the last received messages for a user
     * @param int $userId The user id
     * @param int $lastId The id of the last received message
     * @return int The count of new messages
     */
    public static function countMessagesFromLastReceivedMessage($userId, $lastId = 0)
    {
        $userId = intval($userId);
        $lastId = intval($lastId);

        if (empty($userId)) {
            return 0;
        }

        $messagesTable = Database::get_main_table(TABLE_MESSAGE);

        $conditions = array(
            'where' => array(
                'user_receiver_id = ?' => $userId,
                'AND msg_status = ?' => MESSAGE_STATUS_UNREAD,
                'AND id > ?' => $lastId
            )
        );

        $result = Database::select('COUNT(1) AS qty', $messagesTable, $conditions);

        if (!empty($result)) {
            $row = current($result);

            return $row['qty'];
        }

        return 0;
    }

    /**
     * Get the data of the last received messages for a user
     * @param int $userId The user id
     * @param int $lastId The id of the last received message
     * @return array
     */
    public static function getMessagesFromLastReceivedMessage($userId, $lastId = 0)
    {
        $userId = intval($userId);
        $lastId = intval($lastId);

        if (empty($userId)) {
            return 0;
        }

        $messagesTable = Database::get_main_table(TABLE_MESSAGE);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $sql = "SELECT m.*, u.user_id, u.lastname, u.firstname
                FROM $messagesTable as m
                INNER JOIN $userTable as u
                ON m.user_sender_id = u.user_id
                WHERE
                    m.user_receiver_id = $userId AND
                    m.msg_status = ".MESSAGE_STATUS_UNREAD."
                    AND m.id > $lastId
                ORDER BY m.send_date DESC";

        $result = Database::query($sql);

        $messages = [];
        if ($result !== false) {
            while ($row = Database::fetch_assoc($result)) {
                $messages[] = $row;
            }
        }

        return $messages;
    }

    /**
     * Check whether a message has attachments
     * @param int $messageId The message id
     * @return boolean Whether the message has attachments return true. Otherwise return false
     */
    public static function hasAttachments($messageId)
    {
        $messageId = intval($messageId);

        if (empty($messageId)) {
            return false;
        }

        $messageAttachmentTable = Database::get_main_table(TABLE_MESSAGE_ATTACHMENT);

        $conditions = array(
            'where' => array(
                'message_id = ?' => $messageId
            )
        );

        $result = Database::select('COUNT(1) AS qty', $messageAttachmentTable, $conditions, 'first');

        if (!empty($result)) {
            if ($result['qty'] > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $url
     *
     * @return FormValidator
     */
    public static function getSearchForm($url)
    {
        $form = new FormValidator(
            'search',
            'post',
            $url,
            null,
            [],
            FormValidator::LAYOUT_INLINE
        );

        $form->addElement('text', 'keyword', false, array(
            'aria-label' => get_lang('Search')
        ));
        $form->addButtonSearch(get_lang('Search'));

        return $form;
    }

    /**
     * Send a notification to all admins when a new user is registered
     * @param User $user
     */
    public static function sendNotificationByRegisteredUser(User $user)
    {
        $tplMailBody = new Template(
            null,
            false,
            false,
            false,
            false,
            false,
            false
        );
        $tplMailBody->assign('user', $user);
        $tplMailBody->assign('is_western_name_order', api_is_western_name_order());
        $tplMailBody->assign('manageUrl', api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user->getId());

        $layoutContent = $tplMailBody->get_template('mail/new_user_mail_to_admin.tpl');

        $emailsubject = '['.get_lang('UserRegistered').'] '.$user->getUsername();
        $emailbody = $tplMailBody->fetch($layoutContent);

        $admins = UserManager::get_all_administrators();

        foreach ($admins as $admin_info) {
            self::send_message(
                $admin_info['user_id'],
                $emailsubject,
                $emailbody,
                [],
                [],
                null,
                null,
                null,
                null,
                $user->getId()
            );
        }
    }

    /**
     * Get the error log from failed mailing
     * This assumes a complex setup where you have a cron script regularly copying the mail queue log
     * into app/cache/mail/mailq.
     * This can be done with a cron command like (check the location of your mail log file first):
     * @example 0,30 * * * * root cp /var/log/exim4/mainlog /var/www/chamilo/app/cache/mail/mailq
     * @return array|bool
     */
    public static function failedSentMailErrors()
    {
        $base = api_get_path(SYS_ARCHIVE_PATH).'mail/';
        $mailq = $base.'mailq';

        if (!file_exists($mailq) || !is_readable($mailq)) {
            return false;
        }

        $file = fopen($mailq, 'r');
        $i = 1;
        while (!feof($file)) {
            $line = fgets($file);
            //$line = trim($line);

            if (trim($line) == '') {
                continue;
            }

            //Get the mail code, something like 1WBumL-0002xg-FF
            if (preg_match('/(.*)\s((.*)-(.*)-(.*))\s<(.*)$/', $line, $codeMatches)) {
                $mail_queue[$i]['code'] = $codeMatches[2];
            }

            $fullMail = $base.$mail_queue[$i]['code'];
            $mailFile = fopen($fullMail, 'r');

            //Get the reason of mail fail
            $iX = 1;

            while (!feof($mailFile)) {
                $mailLine = fgets($mailFile);
                #if ($iX == 4 && preg_match('/(.*):\s(.*)$/', $mailLine, $matches)) {
                if (
                    $iX == 2 &&
                    preg_match('/(.*)(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s(.*)/', $mailLine, $detailsMatches)
                ) {
                    $mail_queue[$i]['reason'] = $detailsMatches[3];
                }

                $iX++;
            }

            fclose($mailFile);

            //Get the time of mail fail
            if (preg_match('/^\s?(\d+)(\D+)\s+(.*)$/', $line, $timeMatches)) {
                $mail_queue[$i]['time'] = $timeMatches[1].$timeMatches[2];
            } elseif (preg_match('/^(\s+)((.*)@(.*))\s+(.*)$/', $line, $emailMatches)) {
                $mail_queue[$i]['mail'] = $emailMatches[2];
                $i++;
            }
        }

        fclose($file);

        return array_reverse($mail_queue);
    }
}
