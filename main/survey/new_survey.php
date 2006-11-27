<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author 
* 	@version $Id: new_survey.php 10223 2006-11-27 14:45:59Z pcool $
*/

// name of the language file that needs to be included 
$language_file = 'admin';

require ('../inc/global.inc.php');
api_protect_admin_script();

require_once(api_get_path(LIBRARY_PATH).'/usermanager.lib.php');

$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('AdministrationTools'));
$interbredcrump[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
$tool_name = get_lang('Survey');
Display::display_header($tool_name);
?>

<?php

$group_table = Database :: get_main_table(TABLE_MAIN_GROUP);
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


