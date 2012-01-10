<?php
/* For licensing terms, see /license.txt */
/**
*	This is the array library for Chamilo.
*	Include/require it in your code to use its functionality.
*
*	@package chamilo.library
*/

class Chat extends Model {
	
	var $table;
    var $columns = array('id', 'from_user','to_user','message','sent','recd');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_MAIN_CHAT);
	}    
	
	function heartbeat() {		
		$to_user_id = api_get_user_id();
		$my_user_info  = api_get_user_info();
		
		$sql = "SELECT * FROM ".$this->table." WHERE (to_user = '".intval($to_user_id)."' AND recd = 0) ORDER BY id ASC";
		$result = Database::query($sql);
		
		$chatBoxes = array();		
		$items = array();
		$_SESSION['chatHistory'] = null;
		
		while ($chat = Database::fetch_array($result,'ASSOC')) {
			if (!isset($_SESSION['openChatBoxes'][$chat['from_user']]) && isset($_SESSION['chatHistory'][$chat['from_user']])) {				
				$items = $_SESSION['chatHistory'][$chat['from_user']];				
			}
			$user_info = api_get_user_info($chat['from_user'], true);			
			
			//$chat['message'] = self::sanitize($chat['message']);
			$chat['message'] = Security::remove_XSS($chat['message']);
			$item = array('s' => '0', 'f' => $chat['from_user'], 'm' => $chat['message'], 'online' => $user_info['user_is_online'], 'username' => $user_info['complete_name']);
			$items []= $item;
			
			
			if (!isset($_SESSION['chatHistory'][$chat['from_user']])) {
				$_SESSION['chatHistory'][$chat['from_user']] = array();				
			}
			$_SESSION['chatHistory'][$chat['from_user']][] = $item;

			unset($_SESSION['tsChatBoxes'][$chat['from_user']]);
			$_SESSION['openChatBoxes'][$chat['from_user']] = $chat['sent'];
		}

		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $chatbox => $time) {
				if (!isset($_SESSION['tsChatBoxes'][$chatbox])) {
					$now = time() - strtotime($time);
					$time = date('g:iA M dS', strtotime($time));

					$message = get_lang('SentAt')." ".$time;
					if ($now > 180) {
						$user_info = api_get_user_info($chatbox, true);
						$item = array('s' => '2', 'f' => $chatbox, 'm' => $message, 'online' => $user_info['user_is_online'], 'username' => $user_info['complete_name']);
						$items [] = ($item);
						if (!isset($_SESSION['chatHistory'][$chatbox])) {
							$_SESSION['chatHistory'][$chatbox] = '';
						}

						$_SESSION['chatHistory'][$chatbox][] = ($item);
						$_SESSION['tsChatBoxes'][$chatbox] = 1;
					}			
				}
			}
		}

		$sql = "UPDATE ".$this->table." SET recd = 1 WHERE to_user = '".$to_user_id."' AND recd = 0";
		$query = Database::query($sql);
		
		if ($items != '') {
			//$items = substr($items, 0, -1);
		}		
		echo json_encode(array('items' => $items));
	}
	
	/* 
	 * chatBoxSession
	 */
	function box_session($chatbox) {
		$items = array();
		if (isset($_SESSION['chatHistory'][$chatbox])) {
			$items = $_SESSION['chatHistory'][$chatbox];
		}
		return $items;
	}

	function start_session() {
		$items = '';
		
		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $chatbox => $void) {
				$items = self::box_session($chatbox);
			}
		}		
		if ($items != '') {
			//$items = substr($items, 0, -1);
		}		
		$return = array('username' => get_lang('Me'), 'user_id' => api_get_user_id(), 'items' => $items);
		echo json_encode($return);
		exit;
	}

	function send($from_user_id, $to_user_id, $message) {
	
		$_SESSION['openChatBoxes'][$to_user_id] = api_get_utc_datetime();

		$messagesan = self::sanitize($message);

		if (!isset($_SESSION['chatHistory'][$to_user_id])) {
			$_SESSION['chatHistory'][$to_user_id] = array();
		}
		$user_info = api_get_user_info($to);
		
		$complete_name = $user_info['complete_name'];

		$_SESSION['chatHistory'][$to_user_id][] = (
												array(	"s" => "1", 
														"f" => $to,
														"m" => $messagesan,
														"username" => $complete_name
														)
												);


		unset($_SESSION['tsChatBoxes'][$to_user_id]);
		
		$params = array();
		$params['from_user']	= $from_user_id;
		$params['to_user']		= $to_user_id;
		$params['message']		= $message;
		$params['sent']			= api_get_utc_datetime();
		
		if (!empty($from_user_id) && !empty($to_user_id)) {		
			$this->save($params);
		}
		echo "1";
		exit;
	}

	function close() {
		unset($_SESSION['openChatBoxes'][$_POST['chatbox']]);
		echo "1";
		exit;
	}

	function sanitize($text) {
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = str_replace("\n\r","\n",$text);
		$text = str_replace("\r\n","\n",$text);
		$text = str_replace("\n","<br>",$text);
		return $text;
	}
}