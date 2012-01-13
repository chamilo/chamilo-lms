<?php
/* For licensing terms, see /license.txt */

/**
* Who is online list
*/

// language files that should be included
$language_file = array('index', 'registration', 'messages', 'userInfo');

if (!isset($_GET['cidReq'])) {
	$cidReset = true;
}

// including necessary files
require_once './main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

//social tab
$this_section = SECTION_SOCIAL;
// table definitions
$track_user_table = Database::get_main_table(TABLE_MAIN_USER);

$htmlHeadXtra[] = '<script type="text/javascript">
	function show_image(image,width,height) {
		width = parseInt(width) + 20;
		height = parseInt(height) + 20;
		window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
	}

</script>';
//jquery thickbox already called from main/inc/header.inc.php
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready(function (){
	$("input#id_btn_send_invitation").bind("click", function(){
		if (confirm("'.get_lang('SendMessageInvitation', '').'")) {
			$("#form_register_friend").submit();
		}
	});
});
function change_panel (mypanel_id,myuser_id) {
		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("#id_content_panel").html("'.get_lang('Loading', '').'"); },
			type: "POST",
			url: "main/messages/send_message.php",
			data: "panel_id="+mypanel_id+"&user_id="+myuser_id,
			success: function(datos) {
			 $("div#id_content_panel_init").html(datos);
			 $("div#display_response_id").html("");
			}
		});
}
function action_database_panel(option_id,myuser_id) {

	if (option_id==5) {
		my_txt_subject=$("#txt_subject_id").val();
	} else {
		my_txt_subject="clear";
	}
	my_txt_content=$("#txt_area_invite").val();
	if (my_txt_content.length==0 || my_txt_subject.length==0) {
		$("#display_response_id").html("&nbsp;&nbsp;&nbsp;'.get_lang('MessageEmptyMessageOrSubject', '').'");
		setTimeout("message_information_display()",3000);
		return false;
	}
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#display_response_id").html("'.get_lang('Loading', '').'"); },
		type: "POST",
		url: "main/messages/send_message.php",
		data: "panel_id="+option_id+"&user_id="+myuser_id+"&txt_subject="+my_txt_subject+"&txt_content="+my_txt_content,
		success: function(datos) {
		 $("#display_response_id").html(datos);
		}
	});
}
function display_hide () {
		setTimeout("hide_display_message()",3000);
}
function message_information_display() {
	$("#display_response_id").html("");
}
function hide_display_message () {
	$("div#display_response_id").html("");
	try {
		$("#txt_subject_id").val("");
		$("#txt_area_invite").val("");
	}catch(e) {
		$("#txt_area_invite").val("");
	}
}
       
function show_icon_edit(element_html) { 
    ident="#edit_image";
    $(ident).show();
}       

function hide_icon_edit(element_html)  {
    ident="#edit_image";
    $(ident).hide();
}       
        
</script>';

if ($_GET['chatid'] != '') {
	//send out call request
	$time = time();
	$time = date("Y-m-d H:i:s", $time);
	$chatid = addslashes($_GET['chatid']);
	if ($_GET['chatid'] == strval(intval($_GET['chatid']))) {
		$sql = "update $track_user_table set chatcall_user_id = '".Database::escape_string($_user['user_id'])."', chatcall_date = '".Database::escape_string($time)."', chatcall_text = '' where (user_id = ".(int)Database::escape_string($chatid).")";
		$result = Database::query($sql);
		//redirect caller to chat		
		header("Location: ".api_get_path(WEB_CODE_PATH)."chat/chat.php?".api_get_cidreq()."&origin=whoisonline&target=".Security::remove_XSS($chatid));
		exit;
	}
}


// This if statement prevents users accessing the who's online feature when it has been disabled.
if ((api_get_setting('showonline', 'world') == 'true' && !$_user['user_id']) || ((api_get_setting('showonline', 'users') == 'true' || api_get_setting('showonline', 'course') == 'true') && $_user['user_id'])) {

	if(isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
		$user_list = who_is_online_in_this_course($_user['user_id'], api_get_setting('time_limit_whosonline'), $_GET['cidReq']);
	} else {
		$user_list = who_is_online(api_get_setting('time_limit_whosonline'));		
	}

	$total = count($user_list);
	if (!isset($_GET['id'])) {
	    $header_title  = get_lang('UsersOnLineList');
        if (!api_get_user_id()) { 
            $header_title = null;
        }
		Display::display_header($header_title);
		if (api_get_setting('allow_social_tool') == 'true') {
			if (!api_is_anonymous()) {
				echo '<div id="social-content-left">';
				//this include the social menu div
				SocialManager::show_social_menu('whoisonline');
				echo '</div>';
			}			
		} else {
			echo '<div class="actions-title">';
			echo get_lang('UsersOnLineList');
			echo '</div>';
		}
	}

	if ($user_list) {
		if (!isset($_GET['id'])) {
			if (api_get_setting('allow_social_tool') == 'true') {
				echo '<div id="social-content-right">';			
				//this include the social menu div
				if (!api_is_anonymous()) {
				    $query = isset($_GET['q']) ? $_GET['q']: null;				    
					echo UserManager::get_search_form($query);
				}
			}
			echo Display::tag('h2', get_lang('UsersOnLineList'));
			SocialManager::display_user_list($user_list);			
			if (api_get_setting('allow_social_tool') == 'true') {				
				echo '</div>';
			}			
		} else {
			//individual user information - also displays header info
			SocialManager::display_individual_user($_GET['id']);
		}
	} elseif (isset($_GET['id'])) {
		Display::display_header(get_lang('UsersOnLineList'));
		echo '<div class="actions-title">';
		echo get_lang('UsersOnLineList');
		echo '</div>';
	}
} else {
	Display::display_header(get_lang('UsersOnLineList'));
	Display::display_error_message(get_lang('AccessNotAllowed'));
}
$referer = empty($_GET['referer']) ? 'index.php' : api_htmlentities(strip_tags($_GET['referer']), ENT_QUOTES);

Display::display_footer();