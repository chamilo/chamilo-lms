<?php
/* For licensing terms, see /license.txt */

/**
 * Form for group message
 * @package chamilo.social
 */

$language_file = array('registration', 'messages', 'userInfo', 'admin');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed();
}

$tok = Security::get_token();

if (isset($_REQUEST['user_friend'])) {
    $userfriend_id = intval($_REQUEST['user_friend']);
    // panel=1  send message
    // panel=2  send invitation
    $panel = Security::remove_XSS($_REQUEST['view_panel']);
    $info_user_friend = api_get_user_info($userfriend_id);
    $info_path_friend = UserManager::get_user_picture_path_by_id(
        $userfriend_id,
        'web',
        false,
        true
    );
}

$group_id = intval($_GET['group_id']);
$message_id = isset($_GET['message_id']) ? intval($_GET['message_id']) : null;

$actions = array(
    'add_message_group',
    'edit_message_group',
    'reply_message_group'
);

$allowed_action = (isset($_GET['action']) && in_array($_GET['action'], $actions)) ? Security::remove_XSS($_GET['action']) : '';
$to_group = '';
$subject = '';
$message = '';
if (!empty($group_id) && $allowed_action) {
    $group_info = GroupPortalManager::get_group_data($group_id);
    $is_member = GroupPortalManager::is_group_member($group_id);

    if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED && !$is_member) {
        api_not_allowed(true);
    }

    $to_group = $group_info['name'];
    if (!empty($message_id)) {
        $message_info = MessageManager::get_message_by_id($message_id);
        if ($allowed_action == 'reply_message_group') {
            $subject = get_lang('Reply') . ': ' . api_xml_http_response_encode(
                    $message_info['title']
                );
            //$message  = api_xml_http_response_encode($message_info['content']);
        } else {
            $subject = api_xml_http_response_encode($message_info['title']);
            $message = api_xml_http_response_encode($message_info['content']);
        }
    }
}

$page_item = !empty($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : 1;
$param_item_page = isset($_GET['items_page_nr']) && isset($_GET['topic_id']) ? ('&items_' . intval($_GET['topic_id']) . '_page_nr=' . (!empty($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : 1)) : '';
$param_item_page .= isset($_GET['topic_id']) ? '&topic_id=' . intval($_GET['topic_id']) : null;
$page_topic = !empty($_GET['topics_page_nr']) ? intval($_GET['topics_page_nr']) : 1;
$anchor = isset($_GET['anchor_topic']) ? Security::remove_XSS($_GET['anchor_topic']) : null;


$form = new FormValidator(
    'add_post',
    'post',
    api_get_path(WEB_CODE_PATH)."social/group_topics.php?id=$group_id&anchor_topic=$anchor&topics_page_nr=$page_topic"."$param_item_page",
    null,
    array('enctype' => 'multipart/form-data')
);
$form->addHidden('action', $allowed_action);
$form->addHidden('group_id', $group_id);
$form->addHidden('parent_id', $message_id);
$form->addHidden('message_id',$message_id );
$form->addHidden('token', $tok);

if (api_get_setting('allow_message_tool') == 'true') {
    //normal message
    $user_info = api_get_user_info($userfriend_id);
    $height = 180;
    if ($allowed_action == 'add_message_group') {
        $form->add_textfield('title', get_lang('Title'));
        $height = 140;
    }

    $config = array();
    $config['ToolbarSet'] = 'Messages';
    $form->add_html_editor(
        'content',
        get_lang('Content'),
        false,
        false,
        $config
    );
    $form->add_html('<span id="filepaths"><div id="filepath_1">');
    $form->add_file('attach_1', get_lang('AttachmentFiles'));
    $form->add_html('</div></span>');

    $form->add_label(null,
        ' <div id="link-more-attach">
        <a href="javascript://" onclick="return add_image_form()">
            ' . get_lang('AddOneMoreFile') . '</a>
        </div>'
    );
    $form->add_label(null,
        api_xml_http_response_encode(
            sprintf(
                get_lang('MaximunFileSizeX'),
                format_file_size(
                    api_get_setting('message_max_upload_filesize')
                )
            )
        )
    );

    $form->addElement('style_submit_button', 'submit', get_lang('SendMessage'));
    Display::display_no_header();
    //Display::display_reduced_header();
    $form->display();
}
/*
                                ?>
                                <button class="btn save"
                                        onclick="if(validate_text_empty(this.form.title.value,'<?php echo get_lang(
                                            'YouShouldWriteASubject'
                                        ) ?>')){return false;}" type="submit"
                                        value="<?php echo api_xml_http_response_encode(
                                            get_lang('SendMessage')
                                        ); ?>"><?php echo api_xml_http_response_encode(
                                        get_lang('SendMessage')
                                    ) ?></button>
                            <?php } else { ?>
                                <button class="btn save" type="submit"
                                        value="<?php echo api_xml_http_response_encode(
                                            get_lang('SendMessage')
                                        ); ?>"><?php echo api_xml_http_response_encode(
                                        get_lang('SendMessage')
                                    ) ?></button>
                            <?php } ?>
                        <?php } ?>
                    </dl>
            </td>
        </tr>
        </div>
    </table>
</form>
*/
