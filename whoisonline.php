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

$_SESSION['who_is_online_counter'] = 2;

$htmlHeadXtra[] = api_get_js('jquery.endless-scroll.js');
//social tab
$this_section = SECTION_SOCIAL;
// table definitions
$track_user_table = Database::get_main_table(TABLE_MAIN_USER);
$htmlHeadXtra[] = '<script>
    
function show_image(image,width,height) {
    width = parseInt(width) + 20;
    height = parseInt(height) + 20;
    window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}

$(document).ready(function (){
	$("input#id_btn_send_invitation").bind("click", function(){
		if (confirm("'.get_lang('SendMessageInvitation', '').'")) {
			$("#form_register_friend").submit();
		}
	});
});

function display_hide () {
		setTimeout("hide_display_message()",3000);
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

$(document).ready(function() {

    $("#link_load_more_items").live("click", function() {
        page = $("#link_load_more_items").attr("data_link");
        $.ajax({
                beforeSend: function(objeto) {
                    $("#display_response_id").html("'.addslashes(get_lang('Loading')).'"); 
                },
                type: "GET",
                url: "main/inc/ajax/online.ajax.php?a=load_online_user",
                data: "online_page_nr="+page,
                success: function(data) {   
                    $("#display_response_id").html("");
                    if (data != "end") {
                        $("#link_load_more_items").remove();
                        var last = $("#online_grid_container li:last");
                        last.after(data);
                    } else {
                        $("#link_load_more_items").remove();
                    }
                }
            });           
    });
});        
</script>';

if ($_GET['chatid'] != '') {
	//send out call request
	$time = time();
	$time = date("Y-m-d H:i:s", $time);
	$chatid = intval($_GET['chatid']);
	if ($_GET['chatid'] == strval(intval($_GET['chatid']))) {
		$sql = "UPDATE $track_user_table SET chatcall_user_id = '".Database::escape_string($_user['user_id'])."', chatcall_date = '".Database::escape_string($time)."', chatcall_text = '' where (user_id = ".(int)Database::escape_string($chatid).")";
		$result = Database::query($sql);
		//redirect caller to chat		
		header("Location: ".api_get_path(WEB_CODE_PATH)."chat/chat.php?".api_get_cidreq()."&origin=whoisonline&target=".Security::remove_XSS($chatid));
		exit;
	}
}

// This if statement prevents users accessing the who's online feature when it has been disabled.
if ((api_get_setting('showonline', 'world') == 'true' && !$_user['user_id']) || ((api_get_setting('showonline', 'users') == 'true' || api_get_setting('showonline', 'course') == 'true') && $_user['user_id'])) {

	if(isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
		$user_list = who_is_online_in_this_course(0, 9, api_get_user_id(), api_get_setting('time_limit_whosonline'), $_GET['cidReq']);
	} else {
		$user_list = who_is_online(0, 9);		
	}
        
	if (!isset($_GET['id'])) {	    		
		if (api_get_setting('allow_social_tool') == 'true') {
			if (!api_is_anonymous()) {				
				//this include the social menu div
				$social_left_content = SocialManager::show_social_menu('whoisonline');				
			}			
		}
	}

	if ($user_list) {
		if (!isset($_GET['id'])) {
			if (api_get_setting('allow_social_tool') == 'true') {				
				if (!api_is_anonymous()) {
				    $query = isset($_GET['q']) ? $_GET['q']: null;				    
					$social_right_content .= '<div class="span9">'.UserManager::get_search_form($query).'</div>';
				}
			}			
			$social_right_content .= SocialManager::display_user_list($user_list);							
		}
	}
    
    if (isset($_GET['id'])) {        
        if (api_get_setting('allow_social_tool') == 'true') {	
            header("Location: ".api_get_path(WEB_CODE_PATH)."social/profile.php?u=".intval($_GET['id']));
            exit;
        } else {
            SocialManager::display_individual_user($_GET['id']);    
        }
    }
} else {	
	api_not_allowed();
    exit;
}

$tpl = new Template(get_lang('UsersOnLineList'));

if (api_get_setting('allow_social_tool') == 'true' && !api_is_anonymous()) {
    $tpl->assign('social_left_content', $social_left_content);
    //$tpl->assign('social_left_menu', $social_left_menu);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('layout/social_layout.tpl');
    $content = $tpl->fetch($social_layout);
} else {
    $content = $social_right_content;
}

$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('header', get_lang('UsersOnLineList'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();