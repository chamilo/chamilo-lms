<?php
/**
 * This files contains the common functions for the permissions.
 *
 * A list of all the functions (in no particular order)
 * ----------------------------------------------------
 *    store_permissions($content,$id)
 *    get_permissions($content,$id)
 *    limited_or_full($current_permissions)
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @package chamilo.permissions
 */

/**
 * This function stores the permissions in the correct table.
 * Since Checkboxes are used we do not know which ones are unchecked.
 * That's why we first delete them all (for the given user/group/role
 * and afterwards we store the checked ones only.
 *
 * @param $content are we storing rights for a user, a group or a role (the database depends on it)
 * @param $id the id of the user, group or role
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function store_permissions($content, $id)
{
    $course_id = api_get_course_int_id();

    // Which database are we using (depending on the $content parameter)
    if ($content == 'user') {
        $table = Database::get_course_table(TABLE_PERMISSION_USER);
        $id_field = user_id;
    }
    if ($content == 'group') {
        $table = Database::get_course_table(TABLE_PERMISSION_GROUP);
        $id_field = group_id;
    }
    if ($content == 'role') {
        $table = Database::get_course_table(TABLE_ROLE_PERMISSION);
        $id_field = role_id;
    }

    // We first delete all the existing permissions for that user/group/role
    $sql = "DELETE FROM $table  WHERE c_id = $course_id AND $id_field = '".Database::escape_string($id)."'";
    $result = Database::query($sql);

    // looping through the post values to find the permission (containing the string permission* )
    foreach ($_POST as $key => $value) {
        if (strstr($key, "permission*")) {
            list($brol, $tool, $action) = explode("*", $key);
            $sql = "INSERT INTO $table (c_id, $id_field,tool,action) VALUES ($course_id, '".Database::escape_string($id)."','".Database::escape_string($tool)."','".Database::escape_string($action)."')";
            $result = Database::query($sql);
        }
    }

    return get_lang('PermissionsStored');
}

/**
 * This function stores one permission in the correct table.
 *
 * @param $content are we storing rights for a user, a group or a role (the database depends on it)
 * @param $action are we granting or revoking a permission?
 * @param $id the id of the user, group or role
 * @param $tool the tool
 * @param $permission the permission the user, group or role has been granted or revoked
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function store_one_permission($content, $action, $id, $tool, $permission)
{
    global $rights_full;
    $course_id = api_get_course_int_id();
    // for some reason I don't know, he can't get to the $rights_full array, so commented the following lines out.

    // check
    //if(!in_array($permission, $rights_full))
    //{
    //	return get_lang('Error');
    //}

    // Which database are we using (depending on the $content parameter)

    if ($content == 'user') {
        $table = Database::get_course_table(TABLE_PERMISSION_USER);
        $id_field = user_id;
    }
    if ($content == 'group') {
        $table = Database::get_course_table(TABLE_PERMISSION_GROUP);
        $id_field = group_id;
    }
    if ($content == 'role') {
        $table = Database::get_course_table(TABLE_ROLE_PERMISSION);
        $id_field = role_id;
    }

    // grating a right
    if ($action == 'grant') {
        $sql = "INSERT INTO $table (c_id, $id_field,tool,action) VALUES ($course_id, '".Database::escape_string($id)."','".Database::escape_string($tool)."','".Database::escape_string($permission)."')";
        $result = Database::query($sql);
        if ($result) {
            $result_message = get_lang('PermissionGranted');
        }
    }
    if ($action == 'revoke') {
        $sql = "DELETE FROM $table WHERE c_id = $course_id AND $id_field = '".Database::escape_string($id)."' AND tool='".Database::escape_string($tool)."' AND action='".Database::escape_string($permission)."'";
        $result = Database::query($sql);
        if ($result) {
            $result_message = get_lang('PermissionRevoked');
        }
    }

    return $result_message;
}

/**
 * This function retrieves the existing permissions of a user, group or role.
 *
 * @param string $content are we retrieving the rights of a user, a group or a role (the database depends on it)
 * @param int    $id      the id of the user, group or role
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function get_permissions($content, $id)
{
    $course_id = api_get_course_int_id();
    $currentpermissions = [];
    // Which database are we using (depending on the $content parameter)
    $course_id_condition = " c_id = $course_id AND ";
    if ($content == 'user') {
        $table = Database::get_course_table(TABLE_PERMISSION_USER);
        $id_field = 'user_id';
    } elseif ($content == 'group') {
        $table = Database::get_course_table(TABLE_PERMISSION_GROUP);
        $id_field = 'group_id';
    } elseif ($content == 'role') {
        $table = Database::get_course_table(TABLE_ROLE_PERMISSION);
        $id_field = 'role_id';
    } elseif ($content == 'platform_role') {
        $table = Database::get_main_table(TABLE_ROLE_PERMISSION);
        $id_field = 'role_id';
        $course_id_condition = '';
    } elseif ($content == 'task') {
        $table = Database::get_course_table(TABLE_BLOGS_TASKS_PERMISSIONS);
        $id_field = 'task_id';
    }

    // finding all the permissions. We store this in a multidimensional array
    // where the first dimension is the tool.
    $sql = "
        SELECT * FROM ".$table."
        WHERE $course_id_condition ".$id_field."='".Database::escape_string($id)."'";
    $result = Database::query($sql);

    while ($row = Database::fetch_array($result)) {
        $currentpermissions[$row['tool']][] = $row['action'];
    }

    return $currentpermissions;
}

/**
 * the array that contains the current permission a user, group or role has will now be changed depending on
 * the Dokeos Config Setting for the permissions (limited [add, edit, delete] or full [view, add, edit, delete, move, visibility].
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 *
 * @todo currently there is a setting user_permissions and group_permissions. We should merge this in one config setting.
 */
function limited_or_full($current_permissions)
{
    if (api_get_setting('permissions') == 'limited') {
        foreach ($current_permissions as $tool => $tool_rights) {
            // we loop through the possible permissions of a tool and unset the entry if it is view
            // if it is visibility or move we have to grant the edit right
            foreach ($tool_rights as $key => $value) {
                if ($value == 'View') {
                    unset($current_permissions[$tool][$key]);
                }
                if ($value == 'Visibility' or $value == 'Move') {
                    if (!in_array('Edit', $current_permissions[$tool])) {
                        $current_permissions[$tool][] = 'Edit';
                    }
                    unset($current_permissions[$tool][$key]);
                }
                //else
                //{
                //	$current_permissions[$tool][]=$value;
                //}
            }
        }

        return $current_permissions;
    }
    if (api_get_setting('permissions') == 'full') {
        return $current_permissions;
    }
}
/**
 * This function displays a checked or unchecked checkbox. The checkbox will be checked if the
 * user, group or role has the permission for the given tool, unchecked if the user, group or role
 * does not have the right.
 *
 * @param $permission_array the array that contains all the permissions of the user, group, role
 * @param $tool the tool we want to check a permission for
 * @param $permission the permission we want to check for
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function display_checkbox_matrix($permission_array, $tool, $permission, $inherited_permissions = [])
{
    $checked = "";
    if (is_array($permission_array[$tool]) and in_array($permission, $permission_array[$tool])) {
        $checked = "checked";
    }
    echo "\t\t\t<input type=\"checkbox\" name=\"permission*$tool*$permission\" $checked>\n";
}

/**
 * This function displays a checked or unchecked image. The image will be checked if the
 * user, group or role has the permission for the given tool, unchecked if the user, group or role
 * does not have the right.
 *
 * @param $permission_array the array that contains all the permissions of the user, group, role
 * @param $tool the tool we want to check a permission for
 * @param $permission the permission we want to check for
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function display_image_matrix($permission_array, $tool, $permission, $inherited_permissions = [], $course_admin = false, $editable = true)
{
    if ($course_admin) {
        echo "\t\t\t<img src=\"../img/checkbox_on3.gif\" border=\"0\"/ title=\"".get_lang('PermissionGrantedByGroupOrRole')."\">";
    } else {
        if (in_array($permission, $inherited_permissions[$tool])) {
            echo "\t\t\t<img src=\"../img/checkbox_on3.gif\" border=\"0\"/ title=\"".get_lang('PermissionGrantedByGroupOrRole')."\">";
        } else {
            if (is_array($permission_array[$tool]) and in_array($permission, $permission_array[$tool])) {
                if ($editable) {
                    $url = api_get_self();
                    $urlparameters = '';
                    foreach ($_GET as $key => $value) {
                        $parameter[$key] = $value;
                    }
                    $parameter['action'] = 'revoke';
                    $parameter['permission'] = $permission;
                    $parameter['tool'] = $tool;
                    foreach ($parameter as $key => $value) {
                        $urlparameters .= $key.'='.$value.'&amp;';
                    }
                    $url = $url.'?'.$urlparameters;

                    echo "\t\t\t <a href=\"".$url."\">";
                }
                echo "<img src=\"../img/checkbox_on2.gif\" border=\"0\"/>";
                if ($editable) {
                    echo "</a>";
                }
            } else {
                if ($editable) {
                    $url = api_get_self();
                    $urlparameters = '';
                    foreach ($_GET as $key => $value) {
                        $parameter[$key] = $value;
                    }
                    $parameter['action'] = 'grant';
                    $parameter['permission'] = $permission;
                    $parameter['tool'] = $tool;
                    foreach ($parameter as $key => $value) {
                        $urlparameters .= $key.'='.$value.'&amp;';
                    }
                    $url = $url.'?'.$urlparameters;

                    //echo "\t\t\t <a href=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."&amp;action=grant&amp;permission=$permission&amp;tool=$tool\">";
                    echo "\t\t\t <a href=\"".$url."\">";
                }
                echo "<img src=\"../img/wrong.gif\" border=\"0\"/>";
                if ($editable) {
                    echo "</a>";
                }
            }
        }
    }
}

/**
 * Slightly modified:  Toon Keppens
 * This function displays a checked or unchecked image. The image will be checked if the
 * user, group or role has the permission for the given tool, unchecked if the user, group or role
 * does not have the right.
 *
 * @param $permission_array the array that contains all the permissions of the user, group, role
 * @param $tool the tool we want to check a permission for
 * @param $permission the permission we want to check for
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function display_image_matrix_for_blogs($permission_array, $user_id, $tool, $permission, $inherited_permissions = [], $course_admin = false, $editable = true)
{
    if ($course_admin) {
        echo "\t\t\t<img src=\"../img/checkbox_on3.gif\" border=\"0\"/ title=\"".get_lang('PermissionGrantedByGroupOrRole')."\">";
    } else {
        if (!empty($inherited_permissions) and in_array($permission, $inherited_permissions[$tool])) {
            echo "\t\t\t<img src=\"../img/checkbox_on3.gif\" border=\"0\"/ title=\"".get_lang('PermissionGrantedByGroupOrRole')."\">";
        } else {
            if (is_array($permission_array[$tool]) and in_array($permission, $permission_array[$tool])) {
                if ($editable) {
                    $url = api_get_self();
                    $urlparameters = '';
                    foreach ($_GET as $key => $value) {
                        $parameter[$key] = $value;
                    }
                    $parameter['action'] = 'manage_rights';
                    $parameter['do'] = 'revoke';
                    $parameter['permission'] = $permission;
                    $parameter['tool'] = $tool;
                    $parameter['user_id'] = $user_id;
                    foreach ($parameter as $key => $value) {
                        $urlparameters .= $key.'='.$value.'&amp;';
                    }
                    $url = $url.'?'.$urlparameters;

                    echo "\t\t\t <a href=\"".$url."\">";
                }
                echo "<img src=\"../img/checkbox_on2.gif\" border=\"0\"/ title=\"".get_lang('UserHasPermission')."\">";
                if ($editable) {
                    echo "</a>";
                }
            } else {
                if ($editable) {
                    $url = api_get_self();
                    $urlparameters = '';
                    foreach ($_GET as $key => $value) {
                        $parameter[$key] = $value;
                    }
                    $parameter['action'] = 'manage_rights';
                    $parameter['do'] = 'grant';
                    $parameter['permission'] = $permission;
                    $parameter['tool'] = $tool;
                    $parameter['user_id'] = $user_id;
                    foreach ($parameter as $key => $value) {
                        $urlparameters .= $key.'='.$value.'&amp;';
                    }
                    $url = $url.'?'.$urlparameters;

                    //echo "\t\t\t <a href=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."&amp;action=grant&amp;permission=$permission&amp;tool=$tool\">";
                    echo "\t\t\t <a href=\"".$url."\">";
                }
                echo "<img src=\"../img/wrong.gif\" border=\"0\"/ title=\"".get_lang('UserHasPermissionNot')."\">";
                if ($editable) {
                    echo "</a>";
                }
            }
        }
    }
}

/**
 * This function displays a list off all the roles of the course (and those defined by the platform admin).
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function display_role_list($current_course_roles, $current_platform_roles)
{
    global $setting_visualisation;
    $course_id = api_get_course_int_id();

    $coures_roles_table = Database::get_course_table(TABLE_ROLE);

    // course roles
    $sql = "SELECT * FROM $coures_roles_table WHERE c_id = $course_id ";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if (in_array($row['role_id'], $current_course_roles)) {
            $checked = 'checked';
            $image = 'checkbox_on2.gif';
            $action = 'revoke';
        } else {
            $checked = '';
            $image = 'wrong.gif';
            $action = 'grant';
        }
        if ($setting_visualisation == 'checkbox') {
            echo "<input type=\"checkbox\" name=\"role*course*".$row['role_id']."\" $checked>";
        }
        if ($setting_visualisation == 'image') {
            echo "<a href=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."&amp;action=$action&amp;role=".$row['role_id']."&amp;scope=course\"><img src=\"../img/".$image."\" border=\"0\"/></a>";
        }

        echo $row['role_name']." <a href=\"../permissions/roles.php?role_id=".$row['role_id']."&amp;scope=course\"><img src=\"../img/edit.gif\" /></a><br />\n";
        echo $row['role_comment']."<br />\n";
    }
}

/**
 * This function gets all the current roles of the user or group.
 *
 * @param $content are we finding the roles for a user or a group (the database depends on it)
 * @param $id the id of the user or group
 *
 * @return array that contains the name of the roles the user has
 *
 * @todo consider having a separate table that contains only an id and a name of the role.
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function get_roles($content, $id, $scope = 'course')
{
    $course_id = api_get_course_int_id();
    if ($content == 'user') {
        $table = Database::get_course_table(TABLE_ROLE_USER);
        $id_field = user_id;
    }
    if ($content == 'group') {
        $table = Database::get_course_table(TABLE_ROLE_GROUP);
        $id_field = 'group_id';
    }
    $table_role = Database::get_course_table(TABLE_ROLE);

    $current_roles = [];
    //$sql="SELECT role.role_id FROM $table role_group_user, $table_role role WHERE role_group_user.$id_field = '$id' AND role_group_user.role_id=role.role_id AND role_group_user.scope='".$scope."'";$sql="SELECT role.role_id FROM $table role_group_user, $table_role role WHERE role_group_user.$id_field = '$id' AND role_group_user.role_id=role.role_id AND role_group_user.scope='".$scope."'";
    $sql = "SELECT role_id FROM $table WHERE c_id = $course_id AND $id_field = '$id' AND scope='".$scope."'";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $current_roles[] = $row['role_id'];
    }

    return $current_roles;
}

/**
 * This function gets all the current roles of the user or group.
 *
 * @return array that contains the name of the roles the user has
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function get_all_roles($content = 'course')
{
    $course_id = api_get_course_int_id();
    $course_id_condition = " WHERE c_id = $course_id ";

    if ($content == 'course') {
        $table_role = Database::get_course_table(TABLE_ROLE);
    }
    if ($content == 'platform') {
        $table_role = Database::get_main_table(TABLE_ROLE);
        $course_id_condition = '';
    }

    $current_roles = [];
    $sql = "SELECT * FROM $table_role $course_id_condition ";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $roles[] = $row;
    }

    return $roles;
}

/**
 * This function gets all the roles that are defined.
 *
 * @param string $content are we finding the roles for a user or a group (the database depends on it)
 * @param int    $id      the id of the user or group
 * @param string $scope   Deprecated parameter allowing use of 'platform' scope - the corresponding tables don't exist anymore so the scope is always set to 'course'
 *
 * @return array that contains the name of the roles the user has
 *
 * @todo consider having a separate table that contains only an id and a name of the role.
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 *
 * @version 1.0
 */
function get_roles_permissions($content, $id, $scope = 'course')
{
    $course_id = api_get_course_int_id();
    if ($content == 'user') {
        $table = Database::get_course_table(TABLE_ROLE_USER);
        $id_field = 'user_id';
    }

    if ($content == 'group') {
        $table = Database::get_course_table(TABLE_ROLE_GROUP);
        $id_field = 'group_id';
    }

    // course roles or platform roles
    $scope = 'course';
    if ($scope == 'course') {
        $table_role = Database::get_course_table(TABLE_ROLE);
        $table_role_permissions = Database::get_course_table(TABLE_ROLE_PERMISSION);

        $role_condition = " role.c_id = $course_id AND role_permissions.c_id = $course_id AND ";
    }

    if ($scope == 'platform') {
        $table_role = Database::get_main_table(TABLE_ROLE);
        $table_role_permissions = Database::get_main_table(TABLE_ROLE_PERMISSION);
        $role_condition = '';
    }

    $sql = "
        SELECT *
        FROM
            ".$table." role_group_user,
            ".$table_role." role,
            ".$table_role_permissions." role_permissions
        WHERE
            role_group_user.c_id = $course_id AND
            $role_condition
            role_group_user.scope = '".$scope."' AND
            role_group_user.".$id_field." = '".$id."' AND
            role_group_user.role_id = role.role_id AND
            role.role_id = role_permissions.role_id";

    $result = Database::query($sql);
    $current_role_permissions = [];
    while ($row = Database::fetch_array($result)) {
        $current_role_permissions[$row['tool']][] = $row['action'];
    }

    return $current_role_permissions;
}

/**
 * This function is called when we assign a role to a user or a group.
 *
 * @param $content are we assigning a role to a group or a user
 * @param $action we can grant a role to a group or user or revoke it
 * @param $id the user_id of the user or the group_id of the group
 * @param $role_id the id of the role we are giving to a user or a group
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University
 */
function assign_role($content, $action, $id, $role_id, $scope = 'course')
{
    $course_id = api_get_course_int_id();
    // Which database are we using (depending on the $content parameter)
    if ($content == 'user') {
        $table = Database::get_course_table(TABLE_ROLE_USER);
        $id_field = 'user_id';
    } elseif ($content == 'group') {
        $table = Database::get_course_table(TABLE_ROLE_GROUP);
        $id_field = 'group_id';
    } else {
        return get_lang('Error');
    }

    // grating a right
    if ($action == 'grant') {
        $sql = "INSERT INTO $table (c_id, role_id, scope, $id_field) VALUES ($course_id, '".Database::escape_string($role_id)."','".Database::escape_string($scope)."','".Database::escape_string($id)."')";
        $result = Database::query($sql);
        if ($result) {
            $result_message = get_lang('RoleGranted');
        }
    }

    if ($action == 'revoke') {
        $sql = "DELETE FROM $table WHERE c_id = $course_id AND $id_field = '".Database::escape_string($id)."' AND role_id='".Database::escape_string($role_id)."'";
        $result = Database::query($sql);
        if ($result) {
            $result_message = get_lang('RoleRevoked');
        }
    }

    return $result_message;
}

/**
 * This function merges permission arrays. Each permission array has the
 * following structure
 * a permission array has a tool contanst as a key and an array as a value.
 * This value array consists of all the permissions that are granted in that tool.
 */
function permission_array_merge($array1, $array2)
{
    foreach ($array2 as $tool => $permissions) {
        foreach ($permissions as $permissionkey => $permissionvalue) {
            $array1[$tool][] = $permissionvalue;
        }
    }

    return $array1;
}

function my_print_r($array)
{
    echo '<pre>';
    print_r($array);
    echo '</pre>';
}
