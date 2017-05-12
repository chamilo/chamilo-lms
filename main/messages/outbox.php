<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (isset($_GET['messages_page_nr'])) {
	if (api_get_setting('allow_social_tool')=='true' &&
        api_get_setting('allow_message_tool')=='true'
    ) {
		$social_link = '';
		if ($_REQUEST['f']=='social') {
			$social_link = '&f=social';
		}
		header('Location:outbox.php?pager='.Security::remove_XSS($_GET['messages_page_nr']).$social_link.'');
		exit;
	}
}

if (api_get_setting('allow_message_tool') != 'true') {
	api_not_allowed();
}
//jquery thickbox already called from main/inc/header.inc.php

$htmlHeadXtra[] = '<script>
function enviar(miforma) {
	if(confirm("'.get_lang('SureYouWantToDeleteSelectedMessages', '').'"))
		miforma.submit();
}
function select_all(formita) {
   for (i=0;i<formita.elements.length;i++)
	{
      		if(formita.elements[i].type == "checkbox")
				formita.elements[i].checked=1
	}
}
function deselect_all(formita) {
   for (i=0;i<formita.elements.length;i++) {
        if(formita.elements[i].type == "checkbox")
            formita.elements[i].checked=0
	}
}
</script>';

/*
		MAIN CODE
*/
if (isset($_GET['f']) && $_GET['f'] === 'social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/social/home.php','name' => get_lang('Social'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Outbox'));
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/auth/profile.php','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Outbox'));
}

$actions = '';
if (api_get_setting('extended_profile') == 'true') {
    if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
        $actions .=  '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.
            Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'</a>';
    }
    if (api_get_setting('allow_message_tool') == 'true') {
        //echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';
        $actions .=  '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
            Display::return_icon('message_new.png',get_lang('ComposeMessage')).'</a>';
        $actions .=  '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
            Display::return_icon('inbox.png',get_lang('Inbox')).'</a>';
        $actions .=  '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
            Display::return_icon('outbox.png',get_lang('Outbox')).'</a>';
    }
}


$info_delete_outbox =array();
$info_delete_outbox = isset($_GET['form_delete_outbox']) ? explode(',',$_GET['form_delete_outbox']) : '';
$count_delete_outbox = count($info_delete_outbox) - 1;

if (isset($info_delete_outbox[0]) && trim($info_delete_outbox[0]) == 'delete') {
    for ($i = 1; $i <= $count_delete_outbox; $i++) {
		MessageManager::delete_message_by_user_sender(api_get_user_id(),$info_delete_outbox[$i]);
	}
    $message_box=get_lang('SelectedMessagesDeleted').
        '&nbsp
        <br><a href="../social/index.php?#remote-tab-3">'.
        get_lang('BackToOutbox').
        '</a>';
    Display::addFlash(Display::return_message(api_xml_http_response_encode($message_box), 'normal', false));
    exit;
}

$action = null;
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

$keyword = '';
$social_right_content = '';
if (api_get_setting('allow_social_tool') == 'true') {
    //Block Social Menu
    $social_menu_block = SocialManager::show_social_menu('messages');
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php?f=social">'.
        Display::return_icon('back.png', get_lang('Back'), array(), 32).'</a>';

    $form = MessageManager::getSearchForm(api_get_path(WEB_PATH).'main/messages/outbox.php');
    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $keyword = $values['keyword'];
    }
    $actionsRight = $form->returnForm();
    $social_right_content .= Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);
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
    for ($i = 0; $i < count($delete_list_id); $i++) {
        MessageManager::delete_message_by_user_sender(api_get_user_id(), $delete_list_id[$i]);
    }
    $delete_list_id=array();
    $social_right_content .= MessageManager::outbox_display($keyword);
} elseif ($action == 'deleteone') {
    $delete_list_id = array();
    $id = Security::remove_XSS($_GET['id']);
    MessageManager::delete_message_by_user_sender(api_get_user_id(), $id);
    $delete_list_id=array();
    $social_right_content .= MessageManager::outbox_display($keyword);
} else {
    $social_right_content .= MessageManager::outbox_display($keyword);
}

$tpl = new Template(get_lang('ComposeMessage'));
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if (api_get_setting('allow_social_tool') == 'true') {

    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('social/inbox.tpl');
    $tpl->display($social_layout);
} else {
    $content = $social_right_content;

    if ($actions) {
        $tpl->assign(
            'actions',
            Display::toolbarAction('toolbar', [$actions])
        );
    }

    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
