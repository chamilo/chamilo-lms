<?php
/* For licensing terms, see /license.txt */
/**
*	This is the array library for Chamilo.
*	Include/require it in your code to use its functionality.
*
*	@package chamilo.library
*/

class Chat {
	function __construct() {
		
	}
	
	function heartbeat() {
		$to_user_id = api_get_user_id();
	
		$sql = "select * from chat where (chat.to = '".intval($to_user_id)."' AND recd = 0) order by id ASC";
		$result = Database::query($sql);
		$items = '';

		$chatBoxes = array();
		$items = array();
		while ($chat = Database::fetch_array($result,'ASSOC')) {
			if (!isset($_SESSION['openChatBoxes'][$chat['from']]) && isset($_SESSION['chatHistory'][$chat['from']])) {
				$items = $_SESSION['chatHistory'][$chat['from']];
			}
			$chat['message'] = sanitize($chat['message']);
			$item = array('s' => '0', 'f' => $chat['from'], 'm' => $chat['message'] );
			$items[] = $item;

			if (!isset($_SESSION['chatHistory'][$chat['from']])) {
				$_SESSION['chatHistory'][$chat['from']] = '';
			}

			$_SESSION['chatHistory'][$chat['from']] .= json_encode($item);


			unset($_SESSION['tsChatBoxes'][$chat['from']]);
			$_SESSION['openChatBoxes'][$chat['from']] = $chat['sent'];
		}

		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $chatbox => $time) {
				if (!isset($_SESSION['tsChatBoxes'][$chatbox])) {
					$now = time()-strtotime($time);
					$time = date('g:iA M dS', strtotime($time));

					$message = "Sent at $time";
					if ($now > 180) {

						$item = array('s' => '2', 'f' => $chatbox, 'm' => $message);
						$items[] = $item;
						if (!isset($_SESSION['chatHistory'][$chatbox])) {
							$_SESSION['chatHistory'][$chatbox] = '';
						}

						$_SESSION['chatHistory'][$chatbox] .= json_encode($item);
						$_SESSION['tsChatBoxes'][$chatbox] = 1;
					}			
				}
			}
		}


		$sql = "update chat set recd = 1 where chat.to = '".mysql_real_escape_string($to_user_id)."' and recd = 0";
		$query = Database::query($sql);
		if ($items != '') {
			//$items = substr($items, 0, -1);
		}	
	
		echo json_encode(array('items' => $items));
	}
	
	function chatBoxSession($chatbox) {
		$items = '';
		if (isset($_SESSION['chatHistory'][$chatbox])) {
			$items = $_SESSION['chatHistory'][$chatbox];
		}
		return $items;
	}

	function start_session() {
		$items = '';
		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $chatbox => $void) {
				$items .= chatBoxSession($chatbox);
			}
		}
		
		if ($items != '') {
			$items = substr($items, 0, -1);
		}
		
		$return = array('username' => api_get_user_id(), 'items' => $items);
		echo json_encode($return);
		exit;
	}

	function send($from_user_id, $to_user_id, $message) {
		$from = $_SESSION['username'];
		$to = $_POST['to'];
		$message = $_POST['message'];

		$_SESSION['openChatBoxes'][$_POST['to']] = date('Y-m-d H:i:s', time());

		$messagesan = sanitize($message);

		if (!isset($_SESSION['chatHistory'][$_POST['to']])) {
			$_SESSION['chatHistory'][$_POST['to']] = '';
		}

		$_SESSION['chatHistory'][$_POST['to']] .= <<<EOD
						   {
				"s": "1",
				"f": "{$to}",
				"m": "{$messagesan}"
		   },
EOD;


		unset($_SESSION['tsChatBoxes'][$_POST['to']]);

		$sql = "insert into chat (chat.from,chat.to,message,sent) values ('".mysql_real_escape_string($from)."', '".mysql_real_escape_string($to)."','".mysql_real_escape_string($message)."',NOW())";
		$query = mysql_query($sql);
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