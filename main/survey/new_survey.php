<?php
// $Id: user_list.php,v 1.24 2005/07/01 11:51:33 olivierb78 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
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
	@author Bart Mollet
*	@package dokeos.admin
============================================================================== 
*/
$langFile = 'admin';

require ('../inc/global.inc.php');
api_protect_admin_script();

require_once(api_get_path(LIBRARY_PATH).'/usermanager.lib.php');

$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('AdministrationTools'));
$interbredcrump[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
$tool_name = get_lang('Survey');
Display::display_header($tool_name);
?>

<?php

$group_table = Database :: get_main_table(MAIN_GROUP_TABLE);
	if (isset ($_GET['keyword']))
	{
		$keyword = addslashes($_GET['keyword']);
		$sql = "SELECT * FROM ".$group_table." WHERE groupname LIKE '%".$keyword."%'";
		$parameters = array ('keyword' => $_GET['keyword']);
	}
	
	else
	{
		$sql = "SELECT * FROM ".$group_table;
		$parameters = array ();
	}
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_num_rows($res) > 0)
	{
		$users = array ();
		while ($obj = mysql_fetch_object($res))
		{
			$group = array ();
			$group[] = '<input type="checkbox" name="group[]" value="'.$obj->group_id.'"/>';
			$group[] = $obj->group_id;
			$group[] = $obj->groupname;
			$group[] = $group;
		}
		$table_header[] = array('',false);
		$table_header[] = array (get_lang('serialno'), true);
		$table_header[] = array (get_lang('FirstName'), true);
		$table_header[] = array (get_lang('LastName'), true);
		$table_header[] = array (get_lang('LoginName'), true);
		$table_header[] = array ('', false);

echo '<form method="post" action="new_survey.php">';
		Display :: display_sortable_table($table_header, $users, array (), array (), $parameters);
		echo '<input type="submit" value="'.get_lang('Ok').'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."'".')) return false;"/>';
		echo '</form>';
}

Display::display_footer();
?> 


