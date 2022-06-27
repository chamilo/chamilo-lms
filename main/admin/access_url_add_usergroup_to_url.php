<?php
/* For licensing terms, see /license.txt */
/**
 *  This script allows platform admins to add users to urls.
 *  It displays a list of users and a list of courses;
 *  you can select multiple users and courses and then click on.
 *
 *  @author Julio Montoya <gugli100@gmail.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_global_admin_script();
if (!api_get_multiple_access_url()) {
    header('Location: index.php');
    exit;
}

$userGroup = new UserGroup();
$firstLetterUserGroup = null;
$courses = [];
$url_list = [];

$tbl_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
$tool_name = get_lang('AddUserGroupToURL');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs')];

Display::display_header($tool_name);

echo '<div class="actions">';
echo Display::url(
    Display::return_icon('edit.png', get_lang('EditUserGroupToURL'), ''),
    api_get_path(WEB_CODE_PATH).'admin/access_url_edit_usergroup_to_url.php'
);
echo '</div>';

api_display_tool_title($tool_name);

if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $userGroups = is_array($_POST['user_group_list']) ? $_POST['user_group_list'] : [];
    $urlList = is_array($_POST['url_list']) ? $_POST['url_list'] : [];
    $firstLetterUserGroup = $_POST['first_letter_user_group'];

    if ($form_sent == 1) {
        if (count($userGroups) == 0 || count($urlList) == 0) {
            echo Display::return_message(get_lang('AtLeastOneUserGroupAndOneURL'), 'error');
        } else {
            UrlManager::addUserGroupListToUrl($userGroups, $urlList);
            echo Display::return_message(get_lang('UserGroupBelongURL'), 'confirm');
        }
    }
}

$firstLetterUser = null;
if ($userGroup->getTotalCount() > 1000) {
    //if there are too much num_courses to gracefully handle with the HTML select list,
    // assign a default filter on users names
    $firstLetterUser = 'A';
}

$dbUserGroups = $userGroup->filterByFirstLetter($firstLetterUserGroup);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active = 1 ORDER BY url";
$result = Database::query($sql);
$db_urls = Database::store_result($result);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
    <input type="hidden" name="form_sent" value="1"/>
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
    <tr>
        <td width="40%" align="center">
            <b><?php echo get_lang('UserGroupList'); ?></b>
            <br/><br/>
             <?php echo get_lang('FirstLetter'); ?> :
             <select name="first_letter_user_group" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
                <option value="">--</option>
                <?php
                    echo Display::get_alphabet_options($firstLetterUserGroup);
                    echo Display::get_numeric_options(0, 9, $firstLetterUserGroup);
                ?>
            </select>
        </td>
        <td width="20%">&nbsp;</td>
        <td width="40%" align="center">
            <b><?php echo get_lang('URLList'); ?> :</b>
        </td>
   </tr>
   <tr>
        <td width="40%" align="center">
        <select name="user_group_list[]" multiple="multiple" size="20" style="width:400px;">
		<?php foreach ($dbUserGroups as $item) {
                    ?>
			<option value="<?php echo $item['id']; ?>" <?php if (in_array($item['id'], $courses)) {
                        echo 'selected="selected"';
                    } ?>><?php echo $item['name']; ?>
            </option>
        <?php
                } ?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <button type="submit" class="add"> <?php echo get_lang('AddUserGroupToThatURL'); ?> </button>
   </td>
   <td width="40%" align="center">
    <select name="url_list[]" multiple="multiple" size="20" style="width:300px;">
        <?php foreach ($db_urls as $url_obj) {
                    ?>
        <option value="<?php echo $url_obj['id']; ?>" <?php if (in_array($url_obj['id'], $url_list)) {
                        echo 'selected="selected"';
                    } ?>><?php echo $url_obj['url']; ?>
        </option>
		<?php
                } ?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php

Display::display_footer();
