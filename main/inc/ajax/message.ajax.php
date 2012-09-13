<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$language_file = array('messages','userInfo');

require_once '../global.inc.php';

$action = $_GET['a'];

switch ($action) {
    case 'send_message':        
        $result = MessageManager::send_message($_REQUEST['user_id'], $_REQUEST['subject'], $_REQUEST['content']);
        if ($result) {
            echo Display::display_confirmation_message(get_lang('MessageHasBeenSent'));
        } else {
            echo Display::display_error_message(get_lang('ErrorSendingMessage'));
        }
        break;
    case 'send_invitation':
        SocialManager::send_invitation_friend_user($_REQUEST['user_id'], $_REQUEST['subject'], $_REQUEST['content']);                
        break;
	case 'find_users':
		if (api_is_anonymous()) {
			echo '';
			break;
		}
		$track_online_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
		$tbl_my_user		= Database::get_main_table(TABLE_MAIN_USER);
		$tbl_my_user_friend = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
		$tbl_user 			= Database::get_main_table(TABLE_MAIN_USER);
		$search				= Database::escape_string($_REQUEST['tag']);		

		$user_id            = api_get_user_id();
		$is_western_name_order = api_is_western_name_order();

		if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool') == 'true') {
			//all users            
			if (api_get_setting('allow_send_message_to_all_platform_users') == 'true' || api_is_platform_admin() ) {     
				$sql = 'SELECT DISTINCT u.user_id as id, '.($is_western_name_order ? 'concat(u.firstname," ",u.lastname," ","( ",u.email," )")' : 'concat(u.lastname," ",u.firstname," ","( ",u.email," )")').' as name
				FROM '.$tbl_user.' u
		 		WHERE u.status <> 6  AND u.user_id <>'.$user_id.' AND '.($is_western_name_order ? 'concat(u.firstname, " ", u.lastname)' : 'concat(u.lastname, " ", u.firstname)').' LIKE CONCAT("%","'.$search.'","%") ';
			} else {				
				//only my contacts
				$sql = 'SELECT DISTINCT u.user_id as id, '.($is_western_name_order ? 'concat(u.firstname," ",u.lastname," ","( ",u.email," )")' : 'concat(u.lastname," ",u.firstname," ","( ",u.email," )")').' as name
				FROM '.$tbl_my_user_friend.' uf INNER JOIN '.$tbl_my_user.' AS u  ON uf.friend_user_id = u.user_id ' .
		 		'WHERE u.status <> 6 AND relation_type NOT IN('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') 
		 		       AND uf.user_id = '.$user_id.' AND friend_user_id<>'.$user_id.' 
		 		       AND '.($is_western_name_order ? 'concat(u.firstname, " ", u.lastname)' : 'concat(u.lastname, " ", u.firstname)').' LIKE CONCAT("%","'.$search.'","%") ';
			}
		} elseif (api_get_setting('allow_social_tool')=='false' && api_get_setting('allow_message_tool')=='true') {
			
			$time_limit = api_get_setting('time_limit_whosonline');
            
            $online_time 	= time() - $time_limit*60;
            $limit_date		= api_get_utc_datetime($online_time);

			$sql='SELECT DISTINCT u.user_id as id, '.($is_western_name_order ? 'concat(u.firstname," ",u.lastname," ","( ",u.email," )")' : 'concat(u.lastname," ",u.firstname," ","( ",u.email," )")').' as name
			 FROM '.$tbl_my_user.' u INNER JOIN '.$track_online_table.' t ON u.user_id=t.login_user_id
			 WHERE login_date >= "'.$limit_date.'" AND '.($is_western_name_order ? 'concat(u.firstname, " ", u.lastname)' : 'concat(u.lastname, " ", u.firstname)').' LIKE CONCAT("%","'.$search.'","%") ';
		}		
		$sql .=' LIMIT 20';
		$result=Database::query($sql);

		if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result,'ASSOC')) {
				$return[] = array('caption'=>$row['name'], 'value'=>$row['id']);
			}
		}
		echo json_encode($return);
		break;
	default:
		echo '';

}
exit;