<?php
// $Id: subscribe_user2class.php 12269 2007-05-03 14:17:37Z elixir_julian $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'admin';

$cidReset = true;

require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'classmanager.lib.php');

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$course = $_GET['course'];
$class_id = intval($_GET['idclass']);
$form_sent = 0;
$error_message = '';
$first_letter_left = '';
$first_letter_right = '';
$left_user_list = array();
$right_user_list = array ();

// Database table definitions
$tbl_class 		= Database :: get_main_table(TABLE_MAIN_CLASS);
$tbl_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
$tbl_user 		= Database :: get_main_table(TABLE_MAIN_USER);

$sql = "SELECT name FROM $tbl_class WHERE id='$class_id'";
$result = Database::query($sql, __FILE__, __LINE__);

if (!list ($class_name) = mysql_fetch_row($result))
{
	header('Location: class_list.php?filtreCours='.urlencode($course));
	exit ();
}

$noPHP_SELF = true;

$tool_name = get_lang('AddUsersToAClass').' ('.$class_name.')';

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => "class_list.php?filtreCours=".urlencode($course), "name" => get_lang('AdminClasses'));

if ($_POST['formSent'])
{
	$form_sent = $_POST['formSent'];
	$first_letter_left = $_POST['firstLetterLeft'];
	$first_letter_right = $_POST['firstLetterRight'];
	$left_user_list = is_array($_POST['LeftUserList']) ? $_POST['LeftUserList'] : array();
	$right_user_list = is_array($_POST['RightUserList']) ? $_POST['RightUserList'] : array();
	$add_to_class = empty ($_POST['addToClass']) ? 0 : 1;
	$remove_from_class = empty ($_POST['removeFromClass']) ? 0 : 1;
	if ($form_sent == 1)
	{
		if ($add_to_class)
		{
			if (count($left_user_list) == 0)
			{
				$error_message = get_lang('AtLeastOneUser');
			}
			else
			{
				foreach ($left_user_list as $user_id)
				{
					ClassManager :: add_user($user_id, $class_id);
				}
				header('Location: class_list.php?filtreCours='.urlencode($course));
				exit ();
			}
		}
		elseif ($remove_from_class)
		{
			if (count($right_user_list) == 0)
				$error_message = get_lang('AtLeastOneUser');
			else
			{
				foreach ($right_user_list as $index => $user_id)
				{
					ClassManager :: unsubscribe_user($user_id, $class_id);
				}
				header('Location: class_list.php?filtreCours='.urlencode($course));
				exit ();
			}
		}
	}
}
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
$target_name = api_sort_by_first_name() ? 'firstname' : 'lastname';
$sql = "SELECT u.user_id,lastname,firstname,username FROM $tbl_user u LEFT JOIN $tbl_class_user cu ON u.user_id=cu.user_id AND class_id='$class_id' WHERE ".$target_name." LIKE '".$first_letter_left."%' AND class_id IS NULL ORDER BY ". (count($left_user_list) > 0 ? "(user_id IN(".implode(',', $left_user_list).")) DESC," : "")." ".$target_name;
$result = Database::query($sql, __FILE__, __LINE__);
$left_users = Database::store_result($result);
$sql = "SELECT u.user_id,lastname,firstname,username FROM $tbl_user u,$tbl_class_user cu WHERE cu.user_id=u.user_id AND class_id='$class_id' AND ".$target_name." LIKE '".$first_letter_right."%' ORDER BY ". (count($right_user_list) > 0 ? "(user_id IN(".implode(',', $right_user_list).")) DESC," : "")." ".$target_name;
$result = Database::query($sql, __FILE__, __LINE__);
$right_users = Database::store_result($result);
if (!empty ($error_message))
{
	Display :: display_normal_message($error_message);
}
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?course=<?php echo urlencode($course); ?>&amp;idclass=<?php echo $class_id; ?>" style="margin:0px;">
 <input type="hidden" name="formSent" value="1"/>
 <table border="0" cellpadding="5" cellspacing="0" width="100%">
  <tr>
   <td width="40%" align="center">
    <b><?php echo get_lang('UsersOutsideClass'); ?> :</b>
    <br/><br/>
    <?php echo get_lang('FirstLetterUser'); ?> :
    <select name="firstLetterLeft" onchange="javascript:document.formulaire.formSent.value='2'; document.formulaire.submit();">
     <option value="">--</option>
      <?php
      echo Display :: get_alphabet_options($first_letter_left);
      ?>
    </select>
   </td>
   <td width="20%">&nbsp;</td>
   <td width="40%" align="center">
    <b><?php echo get_lang('UsersInsideClass'); ?> :</b>
    <br/><br/>
    <?php echo get_lang('FirstLetterUser'); ?> :
    <select name="firstLetterRight" onchange="javascript:document.formulaire.formSent.value='2'; document.formulaire.submit();">
     <option value="">--</option>
<?php
echo Display :: get_alphabet_options($first_letter_right);
?>
   </select>
   </td>
  </tr>
  <tr>
   <td width="40%" align="center">
    <select name="LeftUserList[]" multiple="multiple" size="20" style="width:230px;">
<?php
foreach ($left_users as $user)
{
?>
     <option value="<?php echo $user['user_id']; ?>" <?php if (in_array($user['user_id'],$left_user_list)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')'; ?></option>
<?php
}
?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
	<input type="submit" name="addToClass" value="<?php echo get_lang('AddToClass'); ?> &gt;&gt;"/>
	<br/><br/>
	<input type="submit" name="removeFromClass" value="&lt;&lt; <?php echo get_lang('RemoveFromClass'); ?>"/>
   </td>
   <td width="40%" align="center">
    <select name="RightUserList[]" multiple="multiple" size="20" style="width:230px;">
<?php
foreach ($right_users as $user)
{
?>
     <option value="<?php echo $user['user_id']; ?>" <?php if (in_array($user['user_id'],$right_user_list)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')'; ?></option>
<?php
}
?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>