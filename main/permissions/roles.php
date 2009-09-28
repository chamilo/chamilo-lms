<?php
include("../inc/claro_init_global.inc.php");
include_once('permissions_functions.inc.php');
include_once('all_permissions.inc.php');

$tool_name = get_lang('Roles'); // title of the page (should come from the language file)

Display::display_header($tool_name);
// ===================================================
// 			ACTIONS
// ===================================================

// storing all the permission for a given role when the checkbox approach is used
if ($_POST['StoreRolePermissions'])
{
	if (!empty($_POST['role_name']))
	{
		$table_role=Database::get_course_table(TABLE_ROLE);
		$sql="INSERT INTO $table_role (role_name, role_comment, default_role)
					VALUES ('".mysql_real_escape_string($_POST['role_name'])."','".mysql_real_escape_string($_POST['role_comment'])."','".mysql_real_escape_string($_POST['default_role'])."')";
		$result=mysql_query($sql) or die(mysql_error());
		$role_id=mysql_insert_id();
		$result_message=store_permissions('role', $role_id);
	}
	else
	{
		$result_message=get_lang('ErrorPleaseGiveRoleName');
	}
}
// storing a permission for a given role when the image approach is used
if (isset($_GET['action']) AND isset($_GET['permission']) AND isset($_GET['tool']))
{
	if ($_GET['action']=='grant' OR $_GET['action']=='revoke')
	{
		$result_message=store_one_permission('role', $_GET['action'], $role_id, $_GET['tool'], $_GET['permission']);
	}
}

// deleting a role
if (isset($_GET['action']) AND isset($_GET['role_id']) AND $_GET['action']=='delete')
{
	//deleting the assignments fo this role: users
	$table=Database::get_course_table(TABLE_ROLE_USER);
	$sql="DELETE FROM $table WHERE role_id='".mysql_real_escape_string($_GET['role_id'])."'";
	$result=Database::query($sql, __LINE__, __FILE__);

	// deleting the assignments of this role: groups
	$table=Database::get_course_table(TABLE_ROLE_GROUP);
	$sql="DELETE FROM $table WHERE role_id='".mysql_real_escape_string($_GET['role_id'])."'";
	$result=Database::query($sql, __LINE__, __FILE__);

	// deleting the permissions of this role
	$table=Database::get_course_table(TABLE_ROLE_PERMISSION);
	$sql="DELETE FROM $table WHERE role_id='".mysql_real_escape_string($_GET['role_id'])."'";
	$result=Database::query($sql, __LINE__, __FILE__);

	// deleting the role
	$table_role=Database::get_course_table(TABLE_ROLE);
	$sql="DELETE FROM $table_role WHERE role_id='".mysql_real_escape_string($_GET['role_id'])."'";
	$result=Database::query($sql, __LINE__, __FILE__);

	$result_message=get_lang('RoleDeleted');
}

// displaying the return message of the actions
if (isset($result_message))
{
	Display::display_normal_message($result_message);
}

// ===================================================
// 		ADDING A NEW ROLE (FORM AND LINK)
// ===================================================
echo '<img src="../img/add.png" /> <a href="roles.php?action=add">'.get_lang('AddRole').'</a>';

if ($_GET['action']=='add')
{
	echo "<form method=\"post\" action=\"".api_get_self()."\">";
	echo "\n<table>";
	echo "\n\t<tr>";
	echo "\n\t\t<td>";
	echo get_lang('RoleName');
	echo "\n\t\t</td>";
	echo "\n\t\t<td>";
	echo "\n\t\t\t<input type='text' name='role_name'>";
	echo "\n\t\t</td>";
	echo "\n\t</tr>";
	echo "\n\t<tr>";
	echo "\n\t\t<td>";
	echo get_lang('RoleComment');
	echo "\n\t\t</td>";
	echo "\n\t\t<td>";
	echo "\n\t\t\t<textarea name='role_comment'></textarea>";
	echo "\n\t\t</td>";
	echo "\n\t</tr>";
	echo "\n\t<tr>";
	echo "\n\t\t<td>";
	echo get_lang('DefaultRole');
	echo "\n\t\t</td>";
	echo "\n\t\t<td>";
	echo "\n\t\t\t<input type=\"checkbox\" name=\"default_role\" value=\"1\">";
	echo "\n\t\t</td>";
	echo "\n\t</tr>";
	echo "\n</table>";
	echo "<table class=\"data_table\">\n";

	// the header
	if (api_get_setting('permissions')=='limited')
	{
		$header_array=$rights_limited;
	}
	if (api_get_setting('permissions')=='full')
	{
		$header_array=$rights_full;
	}
	echo "\t<tr>\n";
	echo "\t\t<th>".get_lang('Module')."</th>\n";
	foreach ($header_array as $header_key=>$header_value)
	{
		echo "\t\t<th>".get_lang($header_value)."</th>\n";
	}
	echo "\t</tr>\n";

	// the main area with the checkboxes or images
	foreach ($tool_rights as $tool=>$rights) // $tool_rights contains all the possible tools and their rights
	{
		echo "\t<tr>\n";
		echo "\t\t<td>\n";
		echo get_lang($tool);
		echo "\t\t</td>\n";

		foreach ($header_array as $key=>$value)
		{
			echo "\t\t<td align='center'>\n";
			display_checkbox_matrix(array(), $tool, $value);
			echo "\t\t</td>\n";
		}
		echo "\t</tr>\n";
	}

	echo "</table>\n";

	echo "<input type=\"Submit\" name=\"StoreRolePermissions\" value=\"".get_lang('StorePermissions')."\">";
	echo "</form>";

}



// ===================================================
// 		DISPLAYING THE EXISTING ROLES
// ===================================================
// platform roles
$all_roles=get_all_roles('platform');
foreach ($all_roles as $role)
{
	echo '<div><a href="roles.php?action=view&amp;role_id='.$role['role_id'].'&amp;scope=platform">'.$role['role_name'].'</a></div>';
	echo '<div>'.$role['role_comment'].'</div><br />';
	if ($role['role_id']==$_GET['role_id'])
	{
		$current_role_info=$role;
	}
}
// course roles
$all_roles=get_all_roles();
foreach ($all_roles as $role)
{
	echo '<div><a href="roles.php?action=view&amp;role_id='.$role['role_id'].'">'.$role['role_name'].'</a><a href="roles.php?action=delete&amp;role_id='.$role['role_id'].'"><img src="../img/delete.gif" /></a></div>';
	echo '<div>'.$role['role_comment'].'</div><br />';
	if ($role['role_id']==$_GET['role_id'])
	{
		$current_role_info=$role;
	}
}

// ===================================================
// 		DISPLAYING THE PERMISSIONS OF A GIVEN ROLE
// ===================================================
if ($_GET['role_id'])
{
	$current_role_permissions=get_permissions('role',$_GET['role_id']);

	// ---------------------------------------------------
	// 			LIMITED OR FULL
	// ---------------------------------------------------
	$current_role_permissions=limited_or_full($current_role_permissions);
	if (api_get_setting('permissions')=='limited')
	{
		$header_array=$rights_limited;
	}
	if (api_get_setting('permissions')=='full')
	{
		$header_array=$rights_full;
	}
	// ---------------------------------------------------
	// 			DISPLAYING THE MATRIX
	// ---------------------------------------------------
	echo "<form method=\"post\" action=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."\">";

	// the list of the roles for the user
	echo get_lang('PermissionsOfRole').':'.$current_role_info['role_name'].'<br />';
	if ($_GET['scope']=='platform')
	{
		echo get_lang('IsPlatformRoleNotEditable').'<br />';
	}

	echo "<table class=\"data_table\">\n";

	// the header
	echo "\t<tr>\n";
	echo "\t\t<th>".get_lang('Module')."</th>\n";
	foreach ($header_array as $header_key=>$header_value)
	{
		echo "\t\t<th>".get_lang($header_value)."</th>\n";
	}
	echo "\t</tr>\n";

	// the main area with the checkboxes or images
	foreach ($tool_rights as $tool=>$rights) // $tool_rights contains all the possible tools and their rights
	{
		echo "\t<tr>\n";
		echo "\t\t<td>\n";
		echo get_lang($tool);
		echo "\t\t</td>\n";

		foreach ($header_array as $key=>$value)
		{
			echo "\t\t<td align='center'>\n";
			if (in_array($value,$rights))
			{
				if ($setting_visualisation=='checkbox')
				{
					display_checkbox_matrix($current_role_permissions, $tool, $value);
				}
				if ($setting_visualisation=='image')
				{
					if ($_GET['scope']=='platform')
					{
						$roles_editable=false;
					}
					else
					{
						$roles_editable=true;
					}
					display_image_matrix($current_role_permissions, $tool, $value, '','',$roles_editable);
				}
			}
			echo "\t\t</td>\n";
		}
		echo "\t</tr>\n";
	}

	echo "</table>\n";
	if ($setting_visualisation=='checkbox')
	{
		echo "<input type=\"Submit\" name=\"StoreRolePermissions\" value=\"".get_lang('StorePermissions')."\">";
	}
	echo "</form>";


}




/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>