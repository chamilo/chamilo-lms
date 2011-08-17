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
        
</script>';

$this_section = SECTION_SOCIAL;
$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[] = array('url' => 'groups.php','name' => get_lang('Groups'));
$interbreadcrumb[] = array('url' => '#','name' => get_lang('Thread'));
api_block_anonymous_users();

$group_id   = intval($_GET['id']);
$topic_id   = intval($_GET['topic_id']);

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

Display::display_header($tool_name, 'Groups');

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
            $res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id, $edit_message_id);
        } else {
            if ($_POST['action'] == 'add_message_group' && !$is_member) {
                api_not_allowed();
            }            
            $res = MessageManager::send_message(0, $title, $content, $_FILES, '', $group_id, $parent_id);
        }

        // display error messages
        if (is_string($res)) {
            Display::display_error_message($res);
        }

        if (!empty($res)) {
            $groups_user = GroupPortalManager::get_users_by_group($group_id);
            $group_info  = GroupPortalManager::get_group_data($group_id);
            $admin_user_info = api_get_user_info(1);
            $sender_name = api_get_person_name($admin_user_info['firstName'], $admin_user_info['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
            $sender_email = $admin_user_info['mail'];
            $subject = sprintf(get_lang('ThereIsANewMessageInTheGroupX'),$group_info['name']);
            $link = api_get_path(WEB_PATH).'main/social/groups.php?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
            $text_link = '<a href="'.$link.'">'.get_lang('ClickHereToSeeMessageGroup')."</a><br />\r\n<br />\r\n".get_lang('OrCopyPasteTheFollowingUrl')." <br />\r\n ".$link;

            $message = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $group_info['name'])."<br />$text_link";

            foreach ($groups_user as $group_user) {
                //if ($group_user == $current_user) continue;
                $group_user_info    = api_get_user_info($group_user['user_id']);
                $recipient_name     = api_get_person_name($group_user_info['firstName'], $group_user_info['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
                $recipient_email    = $group_user_info['mail'];
                @api_mail_html($recipient_name, $recipient_email, stripslashes($subject), $message, $sender_name, $sender_email);
            }
        }        
        $topic_id = intval($_GET['topic_id']);
        if ($_POST['action'] == 'add_message_group') {
            $topic_id = $res;    
        }        
    }
}


echo '<div id="social-content">';
    echo '<div id="social-content-left">';  
    //this include the social menu div
    SocialManager::show_social_menu('member_list', $group_id);
    echo '</div>';
    echo '<div id="social-content-right">';    
         //echo '<h4><a href="groups.php?id='.$group_id.'">'.$group_info['name'].'</a></h4>';            
        if (!empty($show_message)){
            Display::display_confirmation_message($show_message);
        }
        $content = MessageManager::display_message_for_group($group_id, $topic_id, $is_member);
        echo $content;
    echo '</div>';
echo '</div>';

Display :: display_footer();
