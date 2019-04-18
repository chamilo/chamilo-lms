<?php
/* For licensing terms, see /license.txt */

/**
 * Form for group message.
 *
 * @package chamilo.social
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$tok = Security::get_token();

if (isset($_REQUEST['user_friend'])) {
    $info_user_friend = [];
    $info_path_friend = [];
    $userfriend_id = intval($_REQUEST['user_friend']);
    $info_user_friend = api_get_user_info($userfriend_id);
    $info_path_friend = UserManager::get_user_picture_path_by_id($userfriend_id, 'web');
}

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$message_id = isset($_GET['message_id']) ? intval($_GET['message_id']) : null;
$actions = ['add_message_group', 'edit_message_group', 'reply_message_group'];
$allowed_action = isset($_GET['action']) && in_array($_GET['action'], $actions) ? Security::remove_XSS($_GET['action']) : '';

$to_group = '';
$subject = '';
$message = '';
$usergroup = new UserGroup();
if (!empty($group_id) && $allowed_action) {
    $group_info = $usergroup->get($group_id);
    $is_member = $usergroup->is_group_member($group_id);

    if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED && !$is_member) {
        api_not_allowed(true);
    }

    $to_group = $group_info['name'];
    if (!empty($message_id)) {
        $message_info = MessageManager::get_message_by_id($message_id);
        if ($allowed_action == 'reply_message_group') {
            $subject = get_lang('Reply').': '.api_xml_http_response_encode($message_info['title']);
        } else {
            $subject = api_xml_http_response_encode($message_info['title']);
            $message = api_xml_http_response_encode($message_info['content']);
        }
    }
}

$page_item = !empty($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : 1;
$param_item_page = isset($_GET['items_page_nr']) && isset($_GET['topic_id']) ? ('&items_'.intval($_GET['topic_id']).'_page_nr='.(!empty($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : 1)) : '';
if (isset($_GET['topic_id'])) {
    $param_item_page .= '&topic_id='.intval($_GET['topic_id']);
}
$page_topic = isset($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : 1;
$anchor_topic = isset($_GET['anchor_topic']) ? Security::remove_XSS($_GET['anchor_topic']) : null;

$url = api_get_path(WEB_CODE_PATH).'social/group_topics.php?id='.$group_id.'&anchor_topic='.$anchor_topic.'&topics_page_nr='.$page_topic.$param_item_page;

$form = new FormValidator(
    'form',
    'post',
    $url,
    null,
    ['enctype' => 'multipart/form-data']
);
$form->addHidden('action', $allowed_action);
$form->addHidden('group_id', $group_id);
$form->addHidden('parent_id', $message_id);
$form->addHidden('message_id', $message_id);
$form->addHidden('token', $tok);

$tpl = new Template(get_lang('Groups'));

if (api_get_setting('allow_message_tool') === 'true') {
    // Normal message
    $user_info = api_get_user_info($userfriend_id);
    $height = 180;
    if ($allowed_action === 'add_message_group') {
        $form->addElement('text', 'title', get_lang('Title'));
        $height = 140;
    }
    $config = ['ToolbarSet' => 'Messages'];
    $form->addHtmlEditor('content', get_lang('Message'), true, false, $config);

    $form->addElement(
        'label',
        get_lang('AttachmentFiles'),
        '<div id="link-more-attach">
            <a class="btn btn-default" href="javascript://" onclick="return add_image_form()">
                '.get_lang('AddOneMoreFile').'
            </a>
        </div>'
    );

    $form->addElement('label', null, '<div id="filepaths"></div>');
    $form->addElement(
        'file',
        'attach_1',
        sprintf(
            get_lang('MaximunFileSizeX'),
            format_file_size(api_get_setting('message_max_upload_filesize'))
        )
    );
    $form->addButtonSend(get_lang('SendMessage'));

    $form->setDefaults(['content' => $message, 'title' => $subject]);
    $tpl->assign('content', $form->returnForm());
}

$tpl->displayBlankTemplateNoHeader();
