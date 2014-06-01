<?php
/* For licensing terms, see /license.txt */

/**
 *	Allows to type the messages that will be displayed on chat_chat.php
 *
 *	@author Olivier Brouckaert
 * 	Modified by Alex Aragón (BeezNest)
 *	@package chamilo.chat
 */
/**
 * Code
 */

/*		INIT SECTION */

define('FRAME', 'message');

$language_file = array('chat');

require_once '../inc/global.inc.php';

$course = api_get_course_id();
$session_id = api_get_session_id();
$group_id 	= api_get_group_id();

// Juan Carlos Raña inserted smileys and self-closing window.
?>
<script >
function insert_smile(text) {
	if (text.createTextRange) {
		text.smile = document.selection.createRange().duplicate();
	}
}

function insert(text) {
	var chat = document.formMessage.message;
	if (chat.createTextRange && chat.smile) {
		var smile = chat.smile;
		smile.text = smile.text.charAt(smile.text.length - 1) == ' ' ? text + ' ' : text;
	}
	else chat.value += text;
	chat.focus(smile)
}

function close_chat_window() {
	var chat_window = top.window.self;
	chat_window.opener = top.window.self;
	chat_window.top.close();
}

</script>

<?php

// Mode open in a new window: close the window when there isn't an user login

if (empty($_user['user_id'])) {
	echo '<script languaje="javascript" type="text/javascript"> close_chat_window(); </script>';
} else {
	api_protect_course_script();
}

// if we have the session set up
if (!empty($course) && !empty($_user['user_id'])) {
	require_once api_get_path(LIBRARY_PATH).'document.lib.php';
	require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

	/*	Constants and variables */

    $tbl_user	= Database::get_main_table(TABLE_MAIN_USER);
    $sent 		= isset($_REQUEST['sent']) ? $_REQUEST['sent'] : null;

    /*	MAIN CODE */

    $query = "SELECT user_id, lastname, firstname, username, picture_uri FROM $tbl_user WHERE user_id='".intval($_user['user_id'])."'";
    $result = Database::query($query);

    list($pseudo_user) = Database::fetch_row($result);

    $isAllowed = !(empty($pseudo_user) || !$_cid);
    $isMaster = (bool)$is_courseAdmin;

    $user_id = Database::result($result, 0, 'user_id');
    $firstname = Database::result($result, 0, 'firstname');
    $lastname  = Database::result($result, 0, 'lastname');
    $picture  = Database::result($result, 0, 'picture_uri');

    $date_now = date('Y-m-d');

    $basepath_chat = '';
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

    require 'header_frame.inc.php';
    $chat_size = 0;

	// Define emoticons
    $emoticon_text1 = ':-)';
    $emoticon_img1  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_smile.png" alt="'.get_lang('Smile').'" title="'.get_lang('Smile').'" />';
    $emoticon_text2 = ':-D';
    $emoticon_img2  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_biggrin.png" alt="'.get_lang('BigGrin').'" title="'.get_lang('BigGrin').'" />';
    $emoticon_text3 = ';-)';
    $emoticon_img3  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_wink.png" alt="'.get_lang('Wink').'" title="'.get_lang('Wink').'" />';
    $emoticon_text4 = ':-P';
    $emoticon_img4  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_razz.png" alt="'.get_lang('Avid').'" title="'.get_lang('Avid').'" />';
    $emoticon_text5 = '8-)';
    $emoticon_img5  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_cool.png" alt="'.get_lang('Cool').'" title="'.get_lang('Cool').'" />';
    $emoticon_text6 = ':-o)';
    $emoticon_img6  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_surprised.png" alt="'.get_lang('Surprised').'" title="'.get_lang('Surprised').'" />';
    $emoticon_text7 = '=;';
    $emoticon_img7  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_hand.png" alt="'.get_lang('Hand').'" title="'.get_lang('Hand').'" />';
    $emoticon_text8 = '=8-o';
    $emoticon_img8  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_eek.png" alt="'.get_lang('Amazing').'" title="'.get_lang('Amazing').'" />';
	$emoticon_text9 = ':-|)';
	$emoticon_img9  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_neutral.png" alt="'.get_lang('Neutral').'" title="'.get_lang('Neutral').'" />';
	$emoticon_text10 = ':-k';
	$emoticon_img10  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_think.png" alt="'.get_lang('Think').'" title="'.get_lang('Think').'" />';
	$emoticon_text11 = ':-?';
	$emoticon_img11  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_confused.png" alt="'.get_lang('Confused').'" title="'.get_lang('Confused').'" />';
	$emoticon_text12 = ':-8';
	$emoticon_img12  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_redface.png" alt="'.get_lang('Redface').'" title="'.get_lang('Redface').'" />';
	$emoticon_text13 = ':-L';
	$emoticon_img13  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_shhh.png" alt="'.get_lang('Silence').'" title="'.get_lang('Silence').'" />';
	$emoticon_text14 = ':-#)';
	$emoticon_img14  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_silenced.png" alt="'.get_lang('Silenced').'" title="'.get_lang('Silenced').'" />';
	$emoticon_text15 = ':-(';
	$emoticon_img15  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_sad.png" alt="'.get_lang('Sad').'" title="'.get_lang('Sad').'" />';
	$emoticon_text16 = ';-(';
	$emoticon_img16  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_mourn.png" alt="'.get_lang('Weep').'" title="'.get_lang('Weep').'" />';
	$emoticon_text17 = ':-[8';
	$emoticon_img17  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_angry.png" alt="'.get_lang('Angry').'" title="'.get_lang('Angry').'" />';
	$emoticon_text18 = '--)';
	$emoticon_img18  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_arrow.png" alt="'.get_lang('Arrow').'" title="'.get_lang('Arrow').'" />';
	$emoticon_text19 = ':!:';
	$emoticon_img19  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_exclaim.png" alt="'.get_lang('Exclamation').'" title="'.get_lang('Exclamation').'" />';
	$emoticon_text20 = ':?:';
	$emoticon_img20  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_question.png" alt="'.get_lang('Question').'" title="'.get_lang('Question').'" />';
	$emoticon_text21 = '0-';
	$emoticon_img21  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/icon_idea.png" alt="'.get_lang('Idea').'" title="'.get_lang('Idea').'" />';

	$emoticon_text201 = '*';
	$emoticon_img201  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/waiting.png" alt="'.get_lang('AskPermissionSpeak').'" title="'.get_lang('AskPermissionSpeak').'" />';
	$emoticon_text202 = ':speak:';
	$emoticon_img202  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/flag_green_small.png" alt="'.get_lang('GiveTheFloorTo').'" title="'.get_lang('GiveTheFloorTo').'" />';
	$emoticon_text203 = ':pause:';
	$emoticon_img203  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/flag_yellow_small.png" alt="'.get_lang('Pause').'" title="'.get_lang('Pause').'" />';
	$emoticon_text204 = ':stop:';
	$emoticon_img204  = '<img src="'.api_get_path(WEB_IMG_PATH).'emoticons/flag_red_small.png" alt="'.get_lang('Stop').'" title="'.get_lang('Stop').'" />';

	if ($sent) {
		$message = Security::remove_XSS(trim(htmlspecialchars(stripslashes($_POST['message']), ENT_QUOTES, $charset)));
		$message = str_replace($emoticon_text1, $emoticon_img1, $message);
		$message = str_replace($emoticon_text2, $emoticon_img2, $message);
		$message = str_replace($emoticon_text3, $emoticon_img3, $message);
		$message = str_replace($emoticon_text4, $emoticon_img4, $message);
		$message = str_replace($emoticon_text5, $emoticon_img5, $message);
		$message = str_replace($emoticon_text6, $emoticon_img6, $message);
		$message = str_replace($emoticon_text7, $emoticon_img7, $message);
		$message = str_replace($emoticon_text8, $emoticon_img8, $message);
		$message = str_replace($emoticon_text9, $emoticon_img9, $message);
		$message = str_replace($emoticon_text10, $emoticon_img10, $message);
		$message = str_replace($emoticon_text11, $emoticon_img11, $message);
		$message = str_replace($emoticon_text12, $emoticon_img12, $message);
		$message = str_replace($emoticon_text13, $emoticon_img13, $message);
		$message = str_replace($emoticon_text14, $emoticon_img14, $message);
		$message = str_replace($emoticon_text15, $emoticon_img15, $message);
		$message = str_replace($emoticon_text16, $emoticon_img16, $message);
		$message = str_replace($emoticon_text17, $emoticon_img17, $message);
		$message = str_replace($emoticon_text18, $emoticon_img18, $message);
 		$message = str_replace($emoticon_text19, $emoticon_img19, $message);
		$message = str_replace($emoticon_text20, $emoticon_img20, $message);
		$message = str_replace($emoticon_text21, $emoticon_img21, $message);
		//
		$message = str_replace($emoticon_text201, $emoticon_img201, $message);
		$message = str_replace($emoticon_text202, $emoticon_img202, $message);
		$message = str_replace($emoticon_text203, $emoticon_img203, $message);
		$message = str_replace($emoticon_text204, $emoticon_img204, $message);

		$timeNow = date('d/m/y H:i:s');

		$basename_chat = '';
		if (!empty($group_id)) {
			$basename_chat = 'messages-'.$date_now.'_gid-'.$group_id;
		} elseif (!empty($session_id)) {
			$basename_chat = 'messages-'.$date_now.'_sid-'.$session_id;
		} else {
			$basename_chat = 'messages-'.$date_now;
		}

		if (!api_is_anonymous()) {
			if (!empty($message)) {
				//$message = make_clickable($message);
                if (!file_exists($chat_path.$basename_chat.'.log.html')) {
					$doc_id = add_document($_course, $basepath_chat.'/'.$basename_chat.'.log.html', 'file', 0, $basename_chat.'.log.html');

					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $group_id, null, null, null, $session_id);
					api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', $_user['user_id'], $group_id, null, null, null, $session_id);
					item_property_update_on_folder($_course, $basepath_chat, $_user['user_id']);
				} else {
					$doc_id = DocumentManager::get_document_id($_course, $basepath_chat.'/'.$basename_chat.'.log.html');
				}

				$fp = fopen($chat_path.$basename_chat.'.log.html', 'a');
					// view user picture
					$userImage = UserManager::get_user_picture_path_by_id($user_id, 'web', false, true);
                                        if (substr($userImage['file'],0,7) != 'unknown') {
    					$userPhoto = $userImage['dir'].'medium_'.$userImage['file'];
                                        } else {
    					$userPhoto = $userImage['dir'].$userImage['file'];
                                        }
					$filePhoto = '<img class="chat-image" src="'.$userPhoto.'"/>';

				if ($isMaster) {
					fputs($fp, '<div class="message-teacher"><div class="content-message"><div>'.$message.'</div><div class="message-date">'.$timeNow.'</div></div><div class="icon-message"></div>'.$filePhoto.'</div>'."\n");
				} else {
					fputs($fp, '<div class="message-student">'.$filePhoto.'<div class="icon-message"></div><div class="content-message"><div>'.$message.'</div><div class="message-date">'.$timeNow.'</div></div></div>'."\n");
				}
				fclose($fp);

				$chat_size = filesize($chat_path.$basename_chat.'.log.html');

				update_existing_document($_course, $doc_id, $chat_size);
				item_property_update_on_folder($_course, $basepath_chat, $_user['user_id']);
			}
		}
	}
	?>
	<form name="formMessage" method="post" action="<?php echo api_get_self().'?'.api_get_cidreq(); ?>" onsubmit="javascript: if(document.formMessage.message.value == '') { alert('<?php echo addslashes(api_htmlentities(get_lang('TypeMessage'), ENT_QUOTES)); ?>'); document.formMessage.message.focus(); return false; }" autocomplete="off">
	<input type="hidden" name="sent" value="1">
	<div class="message-form-chat">
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr>
        <td width="320" valign="middle">
		<?php $talkboxsize=(api_get_course_setting('allow_open_chat_window')) ? 'width: 350px; height: 80px' : 'width: 450px; height: 35px'; ?>
        <textarea id="message" class="message-text" name="message" style=" <?php echo $talkboxsize; ?>" onkeydown="send_message(event);" onclick="javascript: insert_smile(this);"></textarea>
        </td>
        <td>
        <button id="send" type="submit" value="<?php echo get_lang('Send'); ?>" class="btn-send"><?php echo get_lang('Send'); ?></button>
        </td>
	</tr>
	</table>
        <?php
        echo  '<div class="emoticons-chat">';
		echo  "<a href=\"javascript:insert('".$emoticon_text1."')\">".$emoticon_img1."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text2."')\">".$emoticon_img2."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text3."')\">".$emoticon_img3."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text4."')\">".$emoticon_img4."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text5."')\">".$emoticon_img5."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text6."')\">".$emoticon_img6."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text7."')\">".$emoticon_img7."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text8."')\">".$emoticon_img8."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text9."')\">".$emoticon_img9."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text10."')\">".$emoticon_img10."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text11."')\">".$emoticon_img11."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text12."')\">".$emoticon_img12."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text13."')\">".$emoticon_img13."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text14."')\">".$emoticon_img14."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text15."')\">".$emoticon_img15."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text16."')\">".$emoticon_img16."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text17."')\">".$emoticon_img17."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text18."')\">".$emoticon_img18."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text19."')\">".$emoticon_img19."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text20."')\">".$emoticon_img20."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text21."')\">".$emoticon_img21."</a>";
		?>
        <?php
		echo  "<a href=\"javascript:insert('".$emoticon_text201."')\">".$emoticon_img201."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text202."')\">".$emoticon_img202."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text203."')\">".$emoticon_img203."</a>";
		echo  "<a href=\"javascript:insert('".$emoticon_text204."')\">".$emoticon_img204."</a>";
		echo '</div>';
		?>
    </div>
    </form>
<?php
}
require 'footer_frame.inc.php';
