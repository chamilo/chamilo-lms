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
	
	var $window_list = array();
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_MAIN_CHAT);
		$this->window_list = $_SESSION['window_list'] = isset($_SESSION['window_list']) ? $_SESSION['window_list'] : array();
	}    
	
	public function start_session() {				
		$items = array();		
		
		//unset($_SESSION['openChatBoxes']); unset($_SESSION['tsChatBoxes']); unset($_SESSION['chatHistory']);
		/*$items = array();		
		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $user_id => $void) {				
				$item = self::box_session($user_id);			
				if (!empty($item)) {
					$items[$user_id] = $item;
				}
			}
		}*/	
		if (isset($_SESSION['chatHistory'])) {
			$items = $_SESSION['chatHistory'];
		}
		$return = array('me' => get_lang('Me'), 'items' => $items);		
		echo json_encode($return);
		exit;
	}

	
	public function heartbeat() {		
		
		$to_user_id		= api_get_user_id();
		$my_user_info   = api_get_user_info();
		
		$minutes = 60;
		$now = time() - $minutes*60;
		$now = api_get_utc_datetime($now);
		
		
		//OR  sent > '$now'
		$sql = "SELECT * FROM ".$this->table." 
					WHERE	to_user = '".intval($to_user_id)."' AND ( recd  = 0 ) ORDER BY id ASC";
		$result = Database::query($sql);
		
		$chat_list = array();
		
		while ($chat = Database::fetch_array($result,'ASSOC')) {	
			$chat_list[$chat['from_user']]['items'][] = $chat;
		}		
		//var_dump($chat_list);
		
		$chatBoxes = array();	
		
		$items = array();
		
		if (isset($_SESSION['chatHistory'])) {
			foreach($_SESSION['chatHistory'] as $user_id => $data) {
				if (!empty($data)) {							
					//$items[$user_id] = $data;					
				}
			}
		}	
		
		foreach ($chat_list as $from_user_id => $rows) {
			$rows = $rows['items'];
			$user_info = api_get_user_info($from_user_id, true);
			
			//Cleaning tsChatBoxes
			unset($_SESSION['tsChatBoxes'][$from_user_id]);
			
			foreach ($rows as $chat) {
				$chat['message'] = Security::remove_XSS($chat['message']);
				
				$item = array(	's'			=> '0', 
								'f'			=> $from_user_id, 
								'm'			=> $chat['message'], 
								'online'	=> $user_info['user_is_online'], 
								'username'	=> $user_info['complete_name'],							
								'id'		=> $chat['id']								
							);
				$items[$from_user_id][] = $item;				
				$_SESSION['openChatBoxes'][$from_user_id] = api_strtotime($chat['sent']);				
			}
			$_SESSION['chatHistory'][$from_user_id][] = $item;					
			
		}
		
		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $user_id => $time) {
				if (!isset($_SESSION['tsChatBoxes'][$user_id])) {
					$now = time() - $time;
					$time = api_convert_and_format_date($time, DATE_TIME_FORMAT_SHORT_TIME_FIRST);
					$message = sprintf(get_lang('SentAtX'), $time);
					
					if ($now > 180) {						
						$item = array('s' => '2', 'f' => $user_id, 'm' => $message);			
						
						if (isset($_SESSION['chatHistory'][$user_id])) {
							$_SESSION['chatHistory'][$user_id][] = $item;					
						}		
						//$_SESSION['chatHistory'][$user_id][] = $item;
						$_SESSION['tsChatBoxes'][$user_id] = 1;
					}			
				}
			}
		}
		
		

		/*
		var_dump($_SESSION['openChatBoxes']);
		var_dump($_SESSION['tsChatBoxes']);
		var_dump($_SESSION['chatHistory']);
		var_dump($items);
		*/
		//print_r($_SESSION['chatHistory']);
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
	function box_session($user_id) {
		$items = array();
		if (isset($_SESSION['chatHistory'][$user_id])) {
			$items = $_SESSION['chatHistory'][$user_id];
		}
		return $items;
	}
	
	function save_window($user_id){
		$this->window_list[$user_id] = true; 
		$_SESSION['window_list']  = $this->window_list;
	}
	
	function send($from_user_id, $to_user_id, $message) {
		
		$this->save_window($to_user_id);
	
		$_SESSION['openChatBoxes'][$to_user_id] = api_get_utc_datetime();
		$messagesan = self::sanitize($message);

		if (!isset($_SESSION['chatHistory'][$to_user_id])) {
			$_SESSION['chatHistory'][$to_user_id] = array();
		}
		/*
		$user_info = api_get_user_info($to_user_id);		
		$complete_name = $user_info['complete_name'];
		*/
		$item = array (	"s"			=> "1", 
						"f"			=> $from_user_id,
						"m"			=> $messagesan,
						"username"	=> get_lang('Me')
					);
		$_SESSION['chatHistory'][$to_user_id][] = $item;	
				
		unset($_SESSION['tsChatBoxes'][$to_user_id]);
		
		$params = array();
		$params['from_user']	= intval($from_user_id);
		$params['to_user']		= intval($to_user_id);
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
		unset($_SESSION['chatHistory'][$_POST['chatbox']]);		
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