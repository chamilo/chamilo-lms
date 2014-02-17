<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
// Language files that should be included
$language_file = array('userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

$this_section = SECTION_SOCIAL;

$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once $libpath.'group_portal_manager.lib.php';
require_once $libpath.'mail.lib.inc.php';

$htmlHeadXtra[] = '<script type="text/javascript">
var textarea = "";
var num_characters_permited = 255;
function textarea_maxlength(){
   num_characters = document.forms[0].description.value.length;
  if (num_characters > num_characters_permited){
      document.forms[0].description.value = textarea;
   }else{
      textarea = document.forms[0].description.value;
   }
}
</script>';

$group_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
$tool_name = get_lang('GroupEdit');

$interbreadcrumb[] = array('url' => 'home.php','name' => get_lang('Social'));
$interbreadcrumb[] = array('url' => 'groups.php','name' => get_lang('Groups'));

$table_group = Database::get_main_table(TABLE_MAIN_GROUP);
$group_data = GroupPortalManager::get_group_data($group_id);

if (empty($group_data)) {
    api_not_allowed();
}

//only group admins can edit the group
if (!GroupPortalManager::is_group_admin($group_id)) {
    api_not_allowed();
}

// Create the form
$form = new FormValidator('group_edit', 'post', '', '');
$form->addElement('hidden', 'id', $group_id);
$form = GroupPortalManager::setGroupForm($form, $group_data);
// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('ModifyInformation'), 'class="save"');

// Validate form
if ($form->validate()) {
    $group = $form->exportValues();
    $picture_element = $form->getElement('picture');
    $picture = $picture_element->getValue();
    $picture_uri = $group_data['picture_uri'];

    if ($group['delete_picture']) {
        $picture_uri = GroupPortalManager::delete_group_picture($group_id);
    } elseif (!empty($picture['name'])) {
        $picture_uri = GroupPortalManager::update_group_picture($group_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
    }

    $name 			= $group['name'];
    $description	= $group['description'];
    $url 			= $group['url'];
    $status 		= intval($group['visibility']);

    $allowMemberGroupToLeave = null;
    if (GroupPortalManager::canLeaveFeatureEnabled($group_data)) {
        $allowMemberGroupToLeave = isset($group['allow_members_leave_group']) ? true : false;
    }
    GroupPortalManager::update($group_id, $name, $description, $url, $status, $picture_uri, $allowMemberGroupToLeave);
    $tok = Security::get_token();
    header('Location: groups.php?id='.$group_id.'&action=show_message&message='.urlencode(get_lang('GroupUpdated')).'&sec_token='.$tok);
    exit();
}

// Group picture
$image_path = GroupPortalManager::get_group_picture_path_by_id($group_id, 'web');
$image_dir = $image_path['dir'];
$image = $image_path['file'];
$image_file = ($image != '' ? $image_dir.$image : api_get_path(WEB_CODE_PATH).'img/unknown_group.jpg');
$image_size = api_getimagesize($image_file);

// get the path,width and height from original picture
$big_image = $image_dir.'big_'.$image;
$big_image_size = api_getimagesize($big_image);
$big_image_width = $big_image_size['width'];
$big_image_height = $big_image_size['height'];
$url_big_image = $big_image.'?rnd='.time();

$social_left_content = SocialManager::show_social_menu('group_edit', $group_id);
$social_right_content = $form->return_form();

$tpl = new Template($tool_name);
$tpl->set_help('Groups');
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$tpl->display($social_layout);
