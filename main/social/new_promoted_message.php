<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

if (api_get_setting('allow_social_tool') !== 'true') {
    api_not_allowed(true);
}

$logInfo = [
    'tool' => 'Messages',
    'action' => 'add_new_promoted_message',
];
Event::registerLog($logInfo);

$allowSocial = api_get_setting('allow_social_tool') === 'true';
$nameTools = api_xml_http_response_encode(get_lang('Messages'));

$htmlHeadXtra[] = '<script>
var counter_image = 1;
function add_image_form() {
    // Multiple filepaths for image form
    var filepaths = document.getElementById("file_uploads");
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
    document.getElementById("filepath_"+counter_image).innerHTML = "<div class=\"form-group\" ><label class=\"col-sm-4\">'.get_lang('FilesAttachment').'</label><input class=\"col-sm-8\" type=\"file\" name=\"attach_"+counter_image+"\" /></div><div class=\"form-group\" ><label class=\"col-sm-4\">'.get_lang('Description').'</label><div class=\"col-sm-8\"><input style=\"width:100%\" type=\"text\" name=\"legend[]\" /></div></div>";
    if (filepaths.childNodes.length == 6) {
        var link_attach = document.getElementById("link-more-attach");
        if (link_attach) {
            link_attach.innerHTML="";
        }
    }
}
</script>';
$nameTools = get_lang('ComposeMessage');
$tpl = new Template(get_lang('ComposeMessage'));

/**
 * Shows the compose area + a list of users to select from.
 */
function show_compose_to_any($tpl)
{
    $default['user_list'] = 0;
    $html = manageForm($default, null, null, $tpl);

    return $html;
}

function show_compose_reply_to_message($message_id, $receiver_id, $tpl)
{
    $table = Database::get_main_table(TABLE_MESSAGE);
    $receiver_id = (int) $receiver_id;
    $message_id = (int) $message_id;

    $query = "SELECT user_sender_id
              FROM $table
              WHERE user_receiver_id = ".$receiver_id." AND id = ".$message_id;
    $result = Database::query($query);
    $row = Database::fetch_array($result, 'ASSOC');
    $userInfo = api_get_user_info($row['user_sender_id']);
    if (empty($row['user_sender_id']) || empty($userInfo)) {
        $html = get_lang('InvalidMessageId');

        return $html;
    }

    $default['users'] = [$row['user_sender_id']];
    $html = manageForm($default, null, $userInfo['complete_name_with_username'], $tpl);

    return $html;
}

function show_compose_to_user($receiver_id, $tpl)
{
    $userInfo = api_get_user_info($receiver_id);
    $html = get_lang('To').':&nbsp;<strong>'.$userInfo['complete_name'].'</strong>';
    $default['title'] = api_xml_http_response_encode(get_lang('EnterTitle'));
    $default['users'] = [$receiver_id];
    $html .= manageForm($default, null, '', $tpl);

    return $html;
}

/**
 * @param          $default
 * @param null     $select_from_user_list
 * @param string   $sent_to
 * @param Template $tpl
 *
 * @return string
 */
function manageForm($default, $select_from_user_list = null, $sent_to = '', $tpl = null)
{
    $form = new FormValidator(
        'compose_message',
        null,
        api_get_self(),
        null,
        ['enctype' => 'multipart/form-data']
    );

    $form->addText('title', get_lang('Subject'));
    $form->addHtmlEditor(
        'content',
        get_lang('Message'),
        false,
        false,
        ['ToolbarSet' => 'Messages', 'Width' => '100%', 'Height' => '250', 'style' => true]
    );

    $form->addLabel(
        '',
        '<div id="file_uploads"><div id="filepath_1">
            <div id="filepaths" class="form-horizontal">
                <div id="paths-file" class="form-group">
                <label class="col-sm-4">'.get_lang('FilesAttachment').'</label>
                <input class="col-sm-8" type="file" name="attach_1"/>
                </div>
            </div>
            <div id="paths-description" class="form-group">
                <label class="col-sm-4">'.get_lang('Description').'</label>
                <div class="col-sm-8">
                <input id="file-descrtiption" class="form-control" type="text" name="legend[]" />
                </div>
            </div>
        </div>
        </div>'
    );

    $form->addLabel(
        '',
        '<span id="link-more-attach"><a class="btn btn-default" href="javascript://" onclick="return add_image_form()">'.
        get_lang('AddOneMoreFile').'</a></span>&nbsp;('.
        sprintf(
            get_lang('MaximunFileSizeX'),
            format_file_size(api_get_setting('message_max_upload_filesize'))
        ).')'
    );

    $form->addButtonSend(get_lang('SendMessage'), 'compose');
    $form->setRequiredNote('<span class="form_required">*</span> <small>'.get_lang('ThisFieldIsRequired').'</small>');

    $form->setDefaults($default);
    $html = '';
    if ($form->validate()) {
        $check = true;
        if ($check) {
            $file_comments = $_POST['legend'];
            $title = $default['title'];
            $content = $default['content'];

            $res = MessageManager::send_message(
                api_get_user_id(),
                $title,
                $content,
                $_FILES,
                $file_comments,
                0,
                0,
                0,
                0,
                null,
                false,
                0,
                [],
                true,
                null,
                MESSAGE_STATUS_PROMOTED
            );

            if ($res) {
                Display::addFlash(Display::return_message(
                    get_lang('MessageSent'),
                    'confirmation',
                    false
                ));
            }

            MessageManager::cleanAudioMessage();
        }
        Security::clear_token();
        header('Location: '.api_get_path(WEB_PATH).'main/social/promoted_messages.php');
        exit;
    } else {
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $html .= $form->returnForm();
    }

    return $html;
}

$this_section = SECTION_SOCIAL;
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PATH).'main/social/home.php',
    'name' => get_lang('SocialNetwork'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PATH).'main/messages/inbox.php',
    'name' => get_lang('Messages'),
];

$social_right_content = null;

// LEFT COLUMN
$social_left_content = '';

// Block Social Menu
$social_menu_block = SocialManager::show_social_menu('messages');
$social_right_content .= '<div class="row">';
$social_right_content .= '<div class="col-md-12">';
$social_right_content .= '<div class="actions">';
$social_right_content .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
    Display::return_icon('back.png', get_lang('Back'), [], 32).'</a>';
$social_right_content .= '</div>';
$social_right_content .= '</div>';
$social_right_content .= '<div class="col-md-12">';

// MAIN CONTENT
if (!isset($_POST['compose'])) {
    if (isset($_GET['re_id'])) {
        $social_right_content .= show_compose_reply_to_message(
            $_GET['re_id'],
            api_get_user_id(),
            $tpl
        );
    } elseif (isset($_GET['send_to_user'])) {
        $social_right_content .= show_compose_to_user($_GET['send_to_user'], $tpl);
    } else {
        $social_right_content .= show_compose_to_any($tpl);
    }
} else {
    $default['title'] = $_POST['title'];
    $default['content'] = $_POST['content'];
    $social_right_content .= manageForm($default, null, null, $tpl);
}

$social_right_content .= '</div>';
$social_right_content .= '</div>';

// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
MessageManager::cleanAudioMessage();

$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/inbox.tpl');
$tpl->display($social_layout);
