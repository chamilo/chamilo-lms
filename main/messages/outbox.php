<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/

// name of the language file that needs to be included
$language_file = array('registration','messages','userInfo');
$cidReset=true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (isset($_GET['messages_page_nr'])) {
	if (api_get_setting('allow_social_tool')=='true' &&  api_get_setting('allow_message_tool')=='true') {
		$social_link = '';
		if ($_REQUEST['f']=='social') {
			$social_link = '&f=social';
		}
		header('Location:outbox.php?pager='.Security::remove_XSS($_GET['messages_page_nr']).$social_link.'');
		exit;
	}
}

if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
//jquery thickbox already called from main/inc/header.inc.php

$htmlHeadXtra[]='<script language="javascript">
<!--
function enviar(miforma)
{
	if(confirm("'.get_lang('SureYouWantToDeleteSelectedMessages', '').'"))
		miforma.submit();
}
function select_all(formita)
{
   for (i=0;i<formita.elements.length;i++)
	{
      		if(formita.elements[i].type == "checkbox")
				formita.elements[i].checked=1
	}
}
function deselect_all(formita)
{
   for (i=0;i<formita.elements.length;i++)
	{
      		if(formita.elements[i].type == "checkbox")
				formita.elements[i].checked=0
	}
}
//-->
</script>';

/*
		MAIN CODE
*/
if ($_GET['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/social/home.php','name' => get_lang('Social'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Outbox'));
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/auth/profile.php','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Outbox'));
}

if ($_GET['f']=='social') {

} else {
	if (api_get_setting('extended_profile') == 'true') {

		if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
			$actions .=  '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'</a>';
		}
		if (api_get_setting('allow_message_tool') == 'true') {
			//echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';
			$actions .=  '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).'</a>';
            $actions .=  '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',get_lang('Inbox')).'</a>';
            $actions .=  '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',get_lang('Outbox')).'</a>';
		}
	}
}

$info_delete_outbox=array();
$info_delete_outbox=explode(',',$_GET['form_delete_outbox']);
$count_delete_outbox=(count($info_delete_outbox)-1);

if( trim($info_delete_outbox[0])=='delete' ) {
	for ($i=1;$i<=$count_delete_outbox;$i++) {
		MessageManager::delete_message_by_user_sender(api_get_user_id(),$info_delete_outbox[$i]);
	}
    $message_box=get_lang('SelectedMessagesDeleted').
        '&nbsp
        <br><a href="../social/index.php?#remote-tab-3">'.
        get_lang('BackToOutbox').
        '</a>';
    Display::display_normal_message(api_xml_http_response_encode($message_box),false);
    exit;
}

$action = null;
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

$social_right_content = '';
$user_info    = UserManager::get_user_info_by_id($user_id);
if (api_get_setting('allow_social_tool') == 'true') {
    //Block Social Avatar
    $social_avatar_block = '<div class="panel panel-info social-avatar">';
    $social_avatar_block .= SocialManager::show_social_avatar_block('messages');
    $social_avatar_block .= '<div class="lastname">'.$user_info['lastname'].'</div>';
    $social_avatar_block .= '<div class="firstname">'.$user_info['firstname'].'</div>';
    /* $social_avatar_block .= '<div class="username">'.Display::return_icon('user.png','','',ICON_SIZE_TINY).$user_info['username'].'</div>'; */
    $social_avatar_block .= '<div class="email">'.Display::return_icon('instant_message.png').'&nbsp;' .$user_info['email'].'</div>';
    $chat_status = $user_info['extra'];
    if(!empty($chat_status['user_chat_status'])){
        $social_avatar_block.= '<div class="status">'.Display::return_icon('online.png').get_lang('Chat')." (".get_lang('Online').')</div>';
    }else{
        $social_avatar_block.= '<div class="status">'.Display::return_icon('offline.png').get_lang('Chat')." (".get_lang('Offline').')</div>';
    }

    $editProfileUrl = api_get_path(WEB_CODE_PATH) . 'auth/profile.php';

    if (api_get_setting('sso_authentication') === 'true') {
        $subSSOClass = api_get_setting('sso_authentication_subclass');
        $objSSO = null;

        if (!empty($subSSOClass)) {
            require_once api_get_path(SYS_CODE_PATH) . 'auth/sso/sso.' . $subSSOClass . '.class.php';

            $subSSOClass = 'sso' . $subSSOClass;
            $objSSO = new $subSSOClass();
        } else {
            $objSSO = new sso();
        }

        $editProfileUrl = $objSSO->generateProfileEditingURL();
    }
    $social_avatar_block .= '<div class="edit-profile">
                            <a class="btn" href="' . $editProfileUrl . '">' . get_lang('EditProfile') . '</a>
                         </div>';
    $social_avatar_block .= '</div>';
    //Block Social Menu
    $social_menu_block = SocialManager::show_social_menu('messages');
    $social_right_content .= '<div class="span9">';
        $social_right_content .= '<div class="actions">';
        $social_right_content .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.Display::return_icon('back.png', get_lang('Back'), array(), 32).'</a>';
        $social_right_content .= '</div>';
    $social_right_content .= '</div>';
    $social_right_content .= '<div class="span9">';
}
//MAIN CONTENT
if ($action == 'delete') {
    $delete_list_id=array();
    if (isset($_POST['out'])) {
        $delete_list_id=$_POST['out'];
    }
    if (isset($_POST['id'])) {
        $delete_list_id=$_POST['id'];
    }
    for ($i=0;$i<count($delete_list_id);$i++) {
        MessageManager::delete_message_by_user_sender(api_get_user_id(), $delete_list_id[$i]);
    }
    $delete_list_id=array();
    $social_right_content .= MessageManager::outbox_display();

} elseif($action =='deleteone') {
    $delete_list_id=array();
    $id = Security::remove_XSS($_GET['id']);
    MessageManager::delete_message_by_user_sender(api_get_user_id(),$id);
    $delete_list_id=array();
    $social_right_content .= MessageManager::outbox_display();
} else {
    $social_right_content .= MessageManager::outbox_display();
}

if (api_get_setting('allow_social_tool') == 'true') {
    $social_right_content .= '</div>';
}

$tpl = new Template(get_lang('ComposeMessage'));
if (api_get_setting('allow_social_tool') == 'true') {
    $tpl->assign('social_avatar_block', $social_avatar_block);
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('social/home.tpl');
    $tpl->display($social_layout);
} else {
    $content = $social_right_content;
    $tpl->assign('actions', $actions);
    $tpl->assign('message', $show_message);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
