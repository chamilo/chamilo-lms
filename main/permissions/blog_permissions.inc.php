<?php

/**
* smartBlogs add-on: it must be possible to change rights for every single blog
* in a course.
*
* @author Toon Keppens
*/
// Init
$rights_full=array("article_add","article_delete","article_edit","article_rate","article_comments_add","article_comments_delete","article_comments_rate","task_management","member_management","role_management");
$rights_limited=array("Add","Edit","Delete");
$rights_blog=array("article_add","article_delete","article_edit","article_rate","article_comments_add","article_comments_delete","article_comments_rate","task_management","member_management","role_management");
$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);

// Get all user
$blog_users = Blog::get_blog_users(Database::escape_string($_GET['blog_id']));

// Remove the blog creater because he has all the rights automatically
// and we want to keep it that way.
$tbl_course_rel_user = $table=Database::get_main_table(TABLE_MAIN_COURSE_USER);
$sql = "SELECT user_id FROM $tbl_course_rel_user WHERE status = '1' AND course_code = '".$_SESSION['_cid']."'";
$result = Database::query($sql);
while ($user = Database::fetch_assoc($result)) {
	unset($blog_users[$user['user_id']]);
}

//$user_id=$userIdViewed;
if ($mainUserInfo['status']==1)
{
	$course_admin=1;
}

include_once('permissions_functions.inc.php');
include_once(api_get_path(LIBRARY_PATH) . "/groupmanager.lib.php");
// ---------------------------------------------------
// 			ACTIONS
// ---------------------------------------------------
if (isset($_GET['do']))
{
	if ( isset($_GET['permission']) AND isset($_GET['tool']) AND ($_GET['do']=='grant' OR $_GET['do']=='revoke'))
	{
		$result_message=store_one_permission('user', $_GET['do'], $_GET['user_id'], $_GET['tool'], $_GET['permission']);
	}
	if (isset($_GET['role']) AND ($_GET['do']=='grant' OR $_GET['do']=='revoke'))
	{
		$result_message=assign_role('user', $_GET['do'], $user_id, $_GET['role'], $_GET['scope']);
	}
}
/*
if (isset($result_message))
{
	Display::display_normal_message($result_message);
}
*/


// ------------------------------------------------------------------
// 			RETRIEVING THE PERMISSIONS OF THE ROLES OF THE USER
// ------------------------------------------------------------------
if (api_get_setting('user_roles')=='true')
{
	// course roles that are assigned to the user
	$current_user_role_permissions_of_user=get_roles_permissions('user',$user_id);
	$inherited_permissions=permission_array_merge($inherited_permissions,$current_user_role_permissions_of_user);
	// NOTE: deze array moet nog gemerged worden met de $inherited_permissions array
	// (heet momenteel nog $current_group_permissions_of_user omdat voorlopig enkel de
	// groepsge�rfde permissions in beschouwing worden genomen
	// dit moet ook de rol permissies van rollen die toegekend worden aan een gebruiker
	// en de rol permissies van rollen die toegekend worden aan de groepen van een gebruiker
	// omvatten.
	// NOTE: checken als de rollen brol wel degelijk geactiveerd is voordat we dit allemaal
	// ophalen.
	// platform roles that are assigned to the user
	$current_user_role_permissions_of_user=get_roles_permissions('user',$user_id, 'platform');
	$inherited_permissions=permission_array_merge($inherited_permissions,$current_user_role_permissions_of_user);
}
// ------------------------------------------------------------------
//	RETRIEVING THE PERMISSIONS OF THE ROLES OF THE GROUPS OF THE USER
// ------------------------------------------------------------------
if (api_get_setting('group_roles')=='true')
{
	// NOTE: DIT MOET NOG VERDER UITGEWERKT WORDEN
	foreach ($groups_of_user as $group)
	{
		$this_current_group_role_permissions_of_user=get_roles_permissions('user',$user_id);
		//$inherited_permissions[$tool][]=$permission;
	}
}


echo "<form method=\"post\" action=\"".str_replace('&', '&amp;', $_SERVER['REQUEST_URI'])."\">";

// ---------------------------------------------------
// 		DISPLAYING THE ROLES LIST
// ---------------------------------------------------

if (api_get_setting('user_roles')=='true')
{
	// the list of the roles for the user
	echo '<strong>'.get_lang('UserRoles').'</strong><br />';
	$current_user_course_roles=get_roles('user',$user_id);
	$current_user_platform_roles=get_roles('user',$user_id, 'platform');
	display_role_list($current_user_course_roles, $current_user_platform_roles);
	echo '<br />';
}

// ---------------------------------------------------
// 			DISPLAYING THE MATRIX (user permissions)
// ---------------------------------------------------
echo "<table class=\"data_table\">\n";

// the header
echo "\t<tr>\n";
	echo "\t\t<th rowspan=\"2\">".get_lang('Module')."</th>\n";
	echo "\t\t<th colspan=\"4\">".get_lang('ArticleManager')."</th>\n";
	echo "\t\t<th colspan=\"3\">".get_lang('CommentManager')."</th>\n";
	echo "\t\t<th colspan=\"3\">".get_lang('BlogManager')."</th>\n";
echo "\t</tr>\n";

// Subheader
echo "\t<tr>\n";
	echo "\t\t<th align='center'>".get_lang('Add')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Delete')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Edit')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Rate')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Add')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Delete')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Rate')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Tasks')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Members')."</th>\n";
	echo "\t\t<th align='center'>".get_lang('Roles')."</th>\n";
echo "\t</tr>\n";

// the main area with the checkboxes or images
foreach ($blog_users as $user_id => $user_name) // $blog_users contains all the users in this blog
{
	// ---------------------------------------------------
	// 			RETRIEVING THE PERMISSIONS OF THE USER
	// ---------------------------------------------------
	$current_user_permissions = array();
	$current_user_permissions = get_permissions('user', $user_id);

	echo "\t<tr>\n";
	echo "\t\t<td>\n";
		echo $user_name;
	echo "\t\t</td>\n";

	foreach ($rights_full as $key => $value)
	{

		echo "\t\t<td align='center'>\n";
		if (in_array($value,$rights_blog))
		{
			display_image_matrix_for_blogs($current_user_permissions, $user_id, 'BLOG_'.$blog_id, $value,$inherited_permissions, $course_admin);
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
echo "</form><br />";

// ---------------------------------------------------
// 			LEGEND
// ---------------------------------------------------
echo '<strong>'.get_lang('Legend').'</strong><br />';
echo '<img src="../img/wrong.gif" /> '.get_lang('UserHasPermissionNot').'<br />';
echo '<img src="../img/checkbox_on2.gif" /> '.get_lang('UserHasPermission').'<br />';
echo '<img src="../img/checkbox_on3.gif" /> '.get_lang('UserHasPermissionByRoleGroup').'<br />';

?>