<?php

$user_id = $userIdViewed;
if (1 == $mainUserInfo['status']) {
    $course_admin = 1;
}
include_once 'permissions_functions.inc.php';
include_once 'all_permissions.inc.php';
include_once api_get_library_path()."/groupmanager.lib.php";
include_once api_get_library_path()."/blog.lib.php";
// 			ACTIONS
if ($_POST['StoreUser permissions'] and 'checkbox' == $setting_visualisation) {
    $result_message = store_permissions('user', $user_id);
    if ($result_message) {
        echo Display::return_message($result_message);
    }
}
if (isset($_GET['action'])) {
    if (isset($_GET['permission']) and isset($_GET['tool']) and ('grant' == $_GET['action'] or 'revoke' == $_GET['action'])) {
        $result_message = store_one_permission('user', $_GET['action'], $user_id, $_GET['tool'], $_GET['permission']);
    }
    if (isset($_GET['role']) and ('grant' == $_GET['action'] or 'revoke' == $_GET['action'])) {
        $result_message = assign_role('user', $_GET['action'], $user_id, $_GET['role'], $_GET['scope']);
    }
}

if (isset($result_message)) {
    echo Display::return_message($result_message);
}

// ---------------------------------------------------
// 			RETRIEVING THE PERMISSIONS OF THE USER
// ---------------------------------------------------
$current_user_permissions = [];
$current_user_permissions = get_permissions('user', $user_id);

//   INHERITED PERMISSIONS (group permissions, user roles, group roles)

// 			RETRIEVING THE PERMISSIONS OF THE GROUPS OF THE USER
$groups_of_user = [];
$groups_of_user = GroupManager::get_group_ids($_course['real_id'], $user_id);
foreach ($groups_of_user as $group) {
    $this_group_permissions = get_permissions('group', $group);
    foreach ($this_group_permissions as $tool => $permissions) {
        foreach ($permissions as $permission) {
            $inherited_group_permissions[$tool][] = $permission;
        }
    }
}
$inherited_permissions = $inherited_group_permissions;

// 			RETRIEVING THE PERMISSIONS OF THE ROLES OF THE USER
if ('true' == api_get_setting('user_roles')) {
    // course roles that are assigned to the user
    $current_user_role_permissions_of_user = get_roles_permissions('user', $user_id);
    $inherited_permissions = permission_array_merge($inherited_permissions, $current_user_role_permissions_of_user);
    // NOTE: deze array moet nog gemerged worden met de $inherited_permissions array
    // (heet momenteel nog $current_group_permissions_of_user omdat voorlopig enkel de
    // groepsge�rfde permissions in beschouwing worden genomen
    // dit moet ook de rol permissies van rollen die toegekend worden aan een gebruiker
    // en de rol permissies van rollen die toegekend worden aan de groepen van een gebruiker
    // omvatten.
    // NOTE: checken als de rollen brol wel degelijk geactiveerd is voordat we dit allemaal
    // ophalen.
    // platform roles that are assigned to the user
    $current_user_role_permissions_of_user = get_roles_permissions('user', $user_id, 'platform');
    $inherited_permissions = permission_array_merge($inherited_permissions, $current_user_role_permissions_of_user);
}
//	RETRIEVING THE PERMISSIONS OF THE ROLES OF THE GROUPS OF THE USER
if ('true' == api_get_setting('group_roles')) {
    // NOTE: DIT MOET NOG VERDER UITGEWERKT WORDEN
    foreach ($groups_of_user as $group) {
        $this_current_group_role_permissions_of_user = get_roles_permissions('user', $user_id);
        //$inherited_permissions[$tool][]=$permission;
    }
}

// 			LIMITED OR FULL
$current_user_permissions = limited_or_full($current_user_permissions);
$inherited_permissions = limited_or_full($inherited_permissions);
if ('limited' == api_get_setting('permissions')) {
    $header_array = $rights_limited;
}
if ('full' == api_get_setting('permissions')) {
    $header_array = $rights_full;
}

echo "<form method=\"post\" action=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."\">";
// 		DISPLAYING THE ROLES LIST

if ('true' == api_get_setting('user_roles')) {
    // the list of the roles for the user
    echo '<strong>'.get_lang('User roles').'</strong><br />';
    $current_user_course_roles = get_roles('user', $user_id);
    $current_user_platform_roles = get_roles('user', $user_id, 'platform');
    display_role_list($current_user_course_roles, $current_user_platform_roles);
    echo '<br />';
}

// ---------------------------------------------------
// 			DISPLAYING THE MATRIX (user permissions)
// ---------------------------------------------------
echo '<strong>'.get_lang('User permissions').'</strong>';
echo "<table class=\"data_table\">\n";

// the header
echo "\t<tr>\n";
echo "\t\t<th>".get_lang('Module')."</th>\n";
foreach ($header_array as $header_key => $header_value) {
    echo "\t\t<th>".get_lang($header_value)."</th>\n";
}
echo "\t</tr>\n";

// the main area with the checkboxes or images
// $tool_rights contains all the possible tools and their rights
foreach ($tool_rights as $tool => $rights) {
    echo "\t<tr>\n";
    echo "\t\t<td>\n";
    if (strstr($tool, 'BLOG')) {
        // Not dealing with a real tool here, get name of this blog
        // Strip blog id
        $tmp = strpos($tool, '_') + 1;
        $blog_id = substr($tool, $tmp, strlen($tool));
        // Get title
        echo get_lang('Blog').": ".Blog::getBlogTitle($blog_id);
    } else {
        echo get_lang($tool);
    }

    echo "\t\t</td>\n";

    foreach ($header_array as $key => $value) {
        echo "\t\t<td align='center'>\n";
        if (in_array($value, $rights)) {
            if ('checkbox' == $setting_visualisation) {
                display_checkbox_matrix(
                    $current_user_permissions,
                    $tool,
                    $value,
                    $inherited_permissions,
                    $course_admin
                );
            }
            if ('image' == $setting_visualisation) {
                display_image_matrix(
                    $current_user_permissions,
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
if ('checkbox' == $setting_visualisation) {
    echo "<input type=\"Submit\" name=\"StoreUser permissions\" value=\"".get_lang('Store permissions')."\">";
}
echo "</form><br />";

// 			LEGEND
echo '<strong>'.get_lang('Legend').'</strong><br />';
echo '<img src="../img/wrong.gif" /> '.get_lang('The user hasn\'t rights').'<br />';
echo '<img src="../img/checkbox_on2.gif" /> '.get_lang('The user has rights').'<br />';
echo '<img src="../img/checkbox_on3.gif" /> '.get_lang('The user has rightsByRoleGroup').'<br />';
