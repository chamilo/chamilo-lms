<?php
/**
 * @package chamilo.permissions
 */
include_once 'permissions_functions.inc.php';
include_once 'all_permissions.inc.php';
$group_id = api_get_group_id();

echo $group_id;
// 			ACTIONS
if ($_POST['StoreGroupPermissions'] and $setting_visualisation == 'checkbox') {
    $result_message = store_permissions('group', $group_id);
    if ($result_message) {
        echo Display::return_message($result_message);
    }
}
if (isset($_GET['action'])) {
    if (($_GET['action'] == 'grant' or $_GET['action'] == 'revoke') and isset($_GET['permission']) and isset($_GET['tool'])) {
        $result_message = store_one_permission('group', $_GET['action'], $group_id, $_GET['tool'], $_GET['permission']);
    }
    if (isset($_GET['role']) and ($_GET['action'] == 'grant' or $_GET['action'] == 'revoke')) {
        $result_message = assign_role('group', $_GET['action'], $group_id, $_GET['role'], $_GET['scope']);
        echo 'hier';
    }
}
if (isset($result_message)) {
    echo Display::return_message($result_message);
}

// 			RETRIEVING THE PERMISSIONS
$current_group_permissions = [];
$current_group_permissions = get_permissions('group', $group_id);
// @todo current group permissions and current role permissions

//   INHERITED PERMISSIONS (group roles)
$group_course_roles_permissions = get_roles_permissions('group', $group_id, 'course');
$group_platform_roles_permissions = get_roles_permissions('group', $group_id, 'platform');
$inherited_permissions = permission_array_merge($group_course_roles_permissions, $group_platform_roles_permissions);

// 			LIMITED OR FULL
$current_group_permissions = limited_or_full($current_group_permissions);
$inherited_permissions = limited_or_full($inherited_permissions);
if (api_get_setting('permissions') == 'limited') {
    $header_array = $rights_limited;
}
if (api_get_setting('permissions') == 'full') {
    $header_array = $rights_full;
}

echo "<form method=\"post\" action=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."\">";
// 		DISPLAYING THE ROLES LIST
if (api_get_setting('group_roles') == 'true') {
    // the list of the roles for the user
    echo '<strong>'.get_lang('GroupRoles').'</strong><br />';
    $current_group_course_roles = get_roles('group', $group_id);
    $current_group_platform_roles = get_roles('group', $group_id, 'platform');
    display_role_list($current_group_course_roles, $current_group_platform_roles);
    echo '<br />';
}

// 		DISPLAYING THE MATRIX (group permissions)
echo "<table class=\"table table-hover table-striped data_table\">\n";

// the header
echo "\t<tr>\n";
echo "\t\t<th>".get_lang('Module')."</th>\n";
foreach ($header_array as $header_key => $header_value) {
    echo "\t\t<th>".get_lang($header_value)."</th>\n";
}
echo "\t</tr>\n";

// the main area with the checkboxes or images
foreach ($tool_rights as $tool => $rights) { // $tool_rights contains all the possible tools and their rights
    echo "\t<tr>\n";
    echo "\t\t<td>\n";
    echo get_lang($tool);
    echo "\t\t</td>\n";

    foreach ($header_array as $key => $value) {
        echo "\t\t<td align='center'>\n";
        if (in_array($value, $rights)) {
            if ($setting_visualisation == 'checkbox') {
                //display_checkbox_matrix($current_group_permissions, $tool, $value);
                display_checkbox_matrix(
                    $current_group_permissions,
                    $tool,
                    $value,
                    $inherited_permissions,
                    $course_admin
                );
            }
            if ($setting_visualisation == 'image') {
                //display_image_matrix($current_group_permissions, $tool, $value);
                display_image_matrix(
                    $current_group_permissions,
                    $tool,
                    $value,
                    $inherited_permissions,
                    $course_admin
                );
            }
        }
        // note: in a later stage this part will be replaced by a function
        // so that we can easily switch between a checkbox approach or an image approach
        // where every click is in fact a change of status. In the checkbox approach you first have to
        // do the changes and then store them by clicking the submit button.
        echo "\t\t</td>\n";
    }
    echo "\t</tr>\n";
}

echo "</table>\n";
if ($setting_visualisation == 'checkbox') {
    echo "<input type=\"Submit\" name=\"StoreGroupPermissions\" value=\"".get_lang('StorePermissions')."\">";
}
echo "</form>";

// 			LEGEND
echo '<strong>'.get_lang('Legend').'</strong><br />';
echo '<img src="../img/wrong.gif" /> '.get_lang('UserHasPermissionNot').'<br />';
echo '<img src="../img/checkbox_on2.gif" /> '.get_lang('UserHasPermission').'<br />';
echo '<img src="../img/checkbox_on3.gif" /> '.get_lang('UserHasPermissionByRoleGroup').'<br />';
