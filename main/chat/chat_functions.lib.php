<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.chat
 */
use Michelf\MarkdownExtra;

/**
 * @author isaac flores paz
 * @param integer $user_id
 *
 * @return boolean
 * @todo this function need more parameters seems not to be use anymore
 * @deprecated fix this function or create another
 */
function user_connected_in_chat($user_id)
{
 	$tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);
    $user_id 	= intval($user_id);
 	$session_id = api_get_session_id();
    $group_id   = api_get_group_id();
    $course_id  = api_get_course_int_id();

	if (!empty($group_id)) {
		$extra_condition = " AND to_group_id = '$group_id'";
	} else {
		$extra_condition = api_get_session_condition($session_id);
	}

 	$sql = 'SELECT COUNT(*) AS count
 	        FROM '.$tbl_chat_connected .' c
 	        WHERE
 	            c_id = '.$course_id.' AND
 	            user_id='.$user_id.$extra_condition;
 	$result = Database::query($sql);
 	$count  = Database::fetch_array($result,'ASSOC');

 	return $count['count'] == 1;
}

/**
 * @param integer
 * @return void
 */
function exit_of_chat($user_id)
{
    $user_id = intval($user_id);
    $list_course = CourseManager::get_courses_list_by_user_id($user_id);
    $tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);

    foreach ($list_course as $course) {
        $response = user_connected_in_chat($user_id);

        $sql = 'DELETE FROM '.$tbl_chat_connected.'
                WHERE c_id = '.$course['real_id'].' AND user_id = '.$user_id;
        Database::query($sql);

    }
}

/**
 * @return void
 */
function disconnect_user_of_chat()
{
    $list_info_user_in_chat = users_list_in_chat();
    $course_id = api_get_course_int_id();
    $groupId = api_get_group_id();
    $now = time();
    $cd_date = date('Y-m-d', $now);
    $cdate_h = date('H', $now);
    $cdate_m = date('i', $now);
    $cdate_s = date('s', $now);
	$cd_count_time_seconds = $cdate_h*3600 + $cdate_m*60 + $cdate_s;

    if (is_array($list_info_user_in_chat) && count($list_info_user_in_chat) > 0) {
        foreach ($list_info_user_in_chat as $list_info_user) {
            $date_db_date = date('Y-m-d', api_strtotime($list_info_user['last_connection'], 'UTC'));
            $date_db_h  = date('H', api_strtotime($list_info_user['last_connection'], 'UTC'));
            $date_db_m  = date('i', api_strtotime($list_info_user['last_connection'], 'UTC'));
            $date_db_s  = date('s', api_strtotime($list_info_user['last_connection'], 'UTC'));
            $date_count_time_seconds = $date_db_h * 3600 + $date_db_m * 60 + $date_db_s;
            if ($cd_date == $date_db_date) {
                if (($cd_count_time_seconds - $date_count_time_seconds) > 5) {
                    $tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);
                    $sql = 'DELETE FROM '.$tbl_chat_connected.'
                            WHERE
                                c_id = '.$course_id.' AND
                                user_id = '.$list_info_user['user_id'].' AND
                                to_group_id = '.$groupId.'
                            ';
                    Database::query($sql);
                }
            }
        }
    }
}

/**
 * @return array user list in chat
 */
function users_list_in_chat()
{
	$list_users_in_chat = array();
 	$tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);
    $course_id = api_get_course_int_id();

 	$session_id = api_get_session_id();
    $group_id   = api_get_group_id();

	if (!empty($group_id)) {
		$extra_condition = " WHERE to_group_id = '$group_id'";
	} else{
		$extra_condition = api_get_session_condition($session_id, false);
	}
    $extra_condition.= " AND c_id = $course_id ";
 	$sql = 'SELECT user_id, last_connection FROM '.$tbl_chat_connected.$extra_condition;
 	$result = Database::query($sql);
 	while ($row = Database::fetch_array($result, 'ASSOC')) {
 		$list_users_in_chat[] = $row;
 	}

 	return $list_users_in_chat;
}

/**
 * @param string $message
 * @param array $_course
 * @param int $group_id
 * @param int $session_id
 * @param bool $preview
 */
function saveMessage($message, $userId, $_course, $session_id, $group_id, $preview = true)
{
    $userInfo = api_get_user_info($userId);
    $fullName = $userInfo['complete_name'];
    $isMaster = (bool)api_is_course_admin();

    $document_path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
    if (!empty($group_id)) {
        $group_info = GroupManager :: get_group_properties($group_id);
        $basepath_chat = $group_info['directory'].'/chat_files';
    } else {
        $basepath_chat = '/chat_files';
    }
    $chat_path = $document_path.$basepath_chat.'/';

    if (!is_dir($chat_path)) {
        if (is_file($chat_path)) {
            @unlink($chat_path);
        }
    }

    $date_now = date('Y-m-d');
    $message = trim($message);
    $timeNow = date('d/m/y H:i:s');

    if (!empty($group_id)) {
        $basename_chat = 'messages-'.$date_now.'_gid-'.$group_id;
    } elseif (!empty($session_id)) {
        $basename_chat = 'messages-'.$date_now.'_sid-'.$session_id;
    } else {
        $basename_chat = 'messages-'.$date_now;
    }

    if (!api_is_anonymous()) {
        if (!empty($message)) {
            Emojione\Emojione::$imagePathPNG = api_get_path(WEB_LIBRARY_PATH).'javascript/emojione/png/';
            Emojione\Emojione::$ascii = true;

            // Parsing emojis
            $message = Emojione\Emojione::toImage($message);
            // Parsing text to understand markdown (code highlight)
            $message = MarkdownExtra::defaultTransform($message);
            // Security XSS
            $message = Security::remove_XSS($message);

            if ($preview == true) {
                return $message;
            }

            if (!file_exists($chat_path.$basename_chat.'.log.html')) {
                $doc_id = add_document(
                    $_course,
                    $basepath_chat . '/' . $basename_chat . '.log.html',
                    'file',
                    0,
                    $basename_chat . '.log.html'
                );
                api_item_property_update(
                    $_course,
                    TOOL_DOCUMENT,
                    $doc_id,
                    'DocumentAdded',
                    $userId,
                    $group_id,
                    null,
                    null,
                    null,
                    $session_id
                );
                api_item_property_update(
                    $_course,
                    TOOL_DOCUMENT,
                    $doc_id,
                    'invisible',
                    $userId,
                    $group_id,
                    null,
                    null,
                    null,
                    $session_id
                );
                item_property_update_on_folder(
                    $_course,
                    $basepath_chat,
                    $userId
                );
            } else {
                $doc_id = DocumentManager::get_document_id(
                    $_course,
                    $basepath_chat.'/'.$basename_chat.'.log.html'
                );
            }

            $fp = fopen($chat_path.$basename_chat.'.log.html', 'a');
            $userPhoto = UserManager::getUserPicture($userId, USER_IMAGE_SIZE_MEDIUM);
            $filePhoto = '<img class="chat-image" src="'.$userPhoto.'"/>';
            if ($isMaster) {
                fputs($fp, '<div class="message-teacher"><div class="content-message"><div class="chat-message-block-name">'.$fullName.'</div><div class="chat-message-block-content">'.$message.'</div><div class="message-date">'.$timeNow.'</div></div><div class="icon-message"></div>'.$filePhoto.'</div>'."\n");
            } else {
                fputs($fp, '<div class="message-student">'.$filePhoto.'<div class="icon-message"></div><div class="content-message"><div class="chat-message-block-name">'.$fullName.'</div><div class="chat-message-block-content">'.$message.'</div><div class="message-date">'.$timeNow.'</div></div></div>'."\n");
            }
            fclose($fp);

            $chat_size = filesize($chat_path.$basename_chat.'.log.html');

            update_existing_document($_course, $doc_id, $chat_size);
            item_property_update_on_folder($_course, $basepath_chat, $userId);
        }
    }
}
