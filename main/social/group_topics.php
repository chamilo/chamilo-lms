<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

$language_file = array('userInfo', 'forum');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$group_id	= intval($_GET['id']);
$topic_id   = intval($_GET['topic_id']);
$message_id = intval($_GET['msg_id']);

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
	api_not_allowed(true);

} else {
	$group_info = GroupPortalManager::get_group_data($group_id);

	if (empty($group_info)) {
		api_not_allowed(true);
	}
	$is_member = GroupPortalManager::is_group_member($group_id);
	if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED && !$is_member ) {
		api_not_allowed(true);
	}
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
    $group_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $group_id);
    
    if (api_is_platform_admin() || in_array($group_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {        
        GroupPortalManager::delete_topic($group_id, $topic_id);
        header("Location: groups.php?id=$group_id&action=show_message&msg=topic_deleted");
    }
}

// save message group
if (isset($_POST['token']) && $_POST['token'] === $_SESSION['sec_token']) {
	
	if (isset($_POST['action'])) {
		$title        = isset($_POST['title']) ? $_POST['title'] : null;
		$content      = $_POST['content'];
		$group_id     = intval($_POST['group_id']);
		$parent_id    = intval($_POST['parent_id']);

		if ($_POST['action'] == 'reply_message_group') {
			$title = cut($content, 50);
		}
		if ($_POST['action'] == 'edit_message_group') {
			$edit_message_id =  intval($_POST['message_id']);			
			$res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id, $edit_message_id, 0, $topic_id);
		} else {
			if ($_POST['action'] == 'add_message_group' && !$is_member) {
				api_not_allowed();
			}
			$res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id, 0, $topic_id);
		}

		// display error messages
		if (!$res) {
			$social_right_content .= Display::return_message(get_lang('Error'),'error');
		}
		$topic_id = intval($_GET['topic_id']);
		if ($_POST['action'] == 'add_message_group') {
			$topic_id = $res;
		}
		$message_id = $res;
	}
}

$htmlHeadXtra[] = '<script type="text/javascript"> 

var counter_image = 1;
function remove_image_form(id_elem1) {
	var elem1 = document.getElementById(id_elem1);
	elem1.parentNode.removeChild(elem1);
	counter_image--;
	var filepaths = document.getElementById("filepaths");
	if (filepaths.childNodes.length < 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML=\'<a href="javascript://" onclick="return add_image_form()">'.get_lang('AddOneMoreFile').'</a>\';
		}
	}
}

function add_image_form() {
	// Multiple filepaths for image form
	var filepaths = document.getElementById("filepaths");
	if (document.getElementById("filepath_"+counter_image)) {
		counter_image = counter_image + 1;
	}  else {
		counter_image = counter_image;
	}
	var elem1 = document.createElement("div");
	elem1.setAttribute("id","filepath_"+counter_image);
	filepaths.appendChild(elem1);
	id_elem1 = "filepath_"+counter_image;
	id_elem1 = "\'"+id_elem1+"\'";
	document.getElementById("filepath_"+counter_image).innerHTML = "<input type=\"file\" name=\"attach_"+counter_image+"\"  size=\"20\" />&nbsp;<a href=\"javascript:remove_image_form("+id_elem1+")\"><img src=\"'.api_get_path(WEB_CODE_PATH).'img/delete.gif\"></a>";

	if (filepaths.childNodes.length == 3) {
		var link_attach = document.getElementById("link-more-attach");
		if (link_attach) {
			link_attach.innerHTML="";
		}
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

function validate_text_empty(str,msg) {
	var str = str.replace(/^\s*|\s*$/g,"");
	if (str.length == 0) {
		alert(msg);
		return true;
	}
}


$(document).ready(function() {
	if ( $("#msg_'.$message_id.'").length) {
		$("html,body").animate({
			scrollTop: $("#msg_'.$message_id.'").offset().top
		})
	}
	   
	$(\'.group_message_popup\').live(\'click\', function() {
		var url     = this.href;
	    var dialog  = $("#dialog");
	    if ($("#dialog").length == 0) {
	    	dialog  = $(\'<div id="dialog" style="display:hidden"></div>\').appendTo(\'body\');
		}
	            
	    // load remote content
	    dialog.load(
	    	url,                    
	        {},
	        	function(responseText, textStatus, XMLHttpRequest) {
	                        dialog.dialog({
	                            modal	: true, 
	            				width	: 520, 
	            				height	: 400,	            				
	                        });	                    
				});
	            //prevent the browser to follow the link
	            return false;
	        });
        });
        
        
</script>';

$this_section = SECTION_SOCIAL;
$interbreadcrumb[] = array ('url' =>'home.php',      'name' => get_lang('Social'));
$interbreadcrumb[] = array('url' => 'groups.php',   'name' => get_lang('Groups'));
$interbreadcrumb[] = array('url' => '#',            'name' => get_lang('Thread'));

$social_right_content = '<a href="groups.php?id='.$group_id.'">'.Security::remove_XSS($group_info['name'], STUDENT, true).'</a> &raquo; <a href="groups.php?id='.$group_id.'#tabs_2">'.get_lang('Discussions').'</a>';
$social_left_content .= SocialManager::show_social_menu('member_list', $group_id);
         
if (!empty($show_message)) {
    $social_right_content .= Display::return_message($show_message, 'confirmation');
}
$social_right_content .= MessageManager::display_message_for_group($group_id, $topic_id, $is_member, $message_id);


$tpl = new Template($tool_name);
$tpl->set_help('Groups');
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
