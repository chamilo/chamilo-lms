<?php
// $Id: languages.php 9246 2006-09-25 13:24:53Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) Bart Mollet, Hogeschool Gent

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
* This page allows the platform admin to decide which languages should
* be available in the language selection menu in the login page. This can be
* useful for countries with more than one official language (like Belgium: 
* Dutch, French and German) or international organisations that are active in	
* a limited number of countries. 
* 
* @author Patrick Cool, main author
* @author Roan EMbrechts, code cleaning
* @since Dokeos 1.6
* @package dokeos.admin
==============================================================================
*/

/*
============================================================================== 
	   INIT SECTION
============================================================================== 
*/
// language file to be included 
$langFile = 'admin';

// we are in the admin area so we do not need a course id
$cidReset = true;

// include global script
include ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
// setting the table that is needed for the styles management (there is a check if it exists later in this code)
$tbl_admin_languages = Database :: get_main_table(MAIN_LANGUAGE_TABLE);
$tbl_settings_current = Database :: get_main_table(MAIN_SETTINGS_CURRENT_TABLE);

/*
============================================================================== 
		STORING THE CHANGES
============================================================================== 
*/
// we change the availability
if ($_GET['action'] == 'makeunavailable')
{
	$sql_make_unavailable = "UPDATE $tbl_admin_languages SET available='0' WHERE id='{$_GET['id']}'";
	$result = api_sql_query($sql_make_unavailable);
}
if ($_GET['action'] == 'makeavailable')
{
	$sql_make_available = "UPDATE $tbl_admin_languages SET available='1' WHERE id='{$_GET['id']}'";
	$result = api_sql_query($sql_make_available);
}

if ($_POST['Submit'])
{
	// changing the name
	$sql_update = "UPDATE $tbl_admin_languages SET original_name='{$_POST['txt_name']}' WHERE id='{$_POST['edit_id']}'";
	$result = api_sql_query($sql_update);
	// changing the Platform language
	if ($_POST['platformlanguage'] && $_POST['platformlanguage'] <> '')
	{
		$sql_update_2 = "UPDATE $tbl_settings_current SET selected_value='{$_POST['platformlanguage']}' WHERE variable='platformLanguage'";
		$result_2 = api_sql_query($sql_update_2);
	}
}
elseif (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'makeavailable' :
			if (count($_POST['id']) > 0)
			{
				$ids = array ();
				foreach ($_POST['id'] as $index => $id)
				{
					$ids[] = mysql_real_escape_string($id);
				}
				$sql = "UPDATE $tbl_admin_languages SET available='1' WHERE id IN ('".implode("','", $ids)."')";
				api_sql_query($sql,__FILE__,__LINE__);
			}
			break;
		case 'makeunavailable' :
			if (count($_POST['id']) > 0)
			{
				$ids = array ();
				foreach ($_POST['id'] as $index => $id)
				{
					$ids[] = mysql_real_escape_string($id);
				}
				$sql = "UPDATE $tbl_admin_languages SET available='0' WHERE id IN ('".implode("','", $ids)."')";
				api_sql_query($sql,__FILE__,__LINE__);
			}
			break;
	}
}

/*
============================================================================== 
		MAIN CODE
============================================================================== 
*/
// setting the name of the tool
$tool_name = get_lang('PlatformLanguages');

// setting breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// including the header file (which includes the banner itself)
Display :: display_header($tool_name);

// displaying the naam of the tool 
//api_display_tool_title($tool_name);

// displaying the explanation for this tool
echo '<p>'.get_lang('PlatformLanguagesExplanation').'</p>';

// selecting all the languages	
$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = api_sql_query($sql_select);

/*
--------------------------------------
		DISPLAY THE TABLE
--------------------------------------
*/

// the table data
$language_data = array ();
while ($row = mysql_fetch_array($result_select))
{
	$row_td = array ();
	$row_td[] = $row['id'];
	// the first column is the original name of the language OR a form containing the original name
	if ($_GET['action'] == 'edit' and $row['id'] == $_GET['id'])
	{
		$row_td[] = '<input type="hidden" name="edit_id" value="'.$_GET['id'].'" /><input type="text" name="txt_name" value="'.$row['original_name'].'" /> '
			. '<input type="checkbox" name="platformlanguage" id="platformlanguage" value="'.$row['english_name'].'" /><label for="platformlanguage">'.$row['original_name'].' '.get_lang('AsPlatformLanguage').'</label> <input type="submit" name="Submit" value="'.get_lang('Ok').'" />';
	}
	else
	{
		$row_td[] = $row['original_name'];
	}
	// the second column
	$row_td[] = $row['english_name'];
	// the third column
	$row_td[] = $row['dokeos_folder'];
	// the fourth column with the visibility icon and the edit icon
	if ($row['available'] == 1)
	{
		$row_td[] = "<a href='".$_SERVER['PHP_SELF']."?action=makeinavailable&id=".$row['id']."'><img src='../img/visible.gif' border='0'></a> <a href='".$_SERVER['PHP_SELF']."?action=edit&id=".$row['id']."'><img src='../img/edit.gif' border='0'></a>";
	}
	else
	{
		$row_td[] = "<a href='".$_SERVER['PHP_SELF']."?action=makeavailable&id=".$row['id']."'><img src='../img/invisible.gif' border='0'></a> <a href='".$_SERVER['PHP_SELF']."?action=edit&id=".$row['id']."'><img src='../img/edit.gif' border='0'></a>";
	}

	$language_data[] = $row_td;
}

$table = new SortableTableFromArray($language_data, 1, count($language_data));
$table->set_header(0, '');
$table->set_header(1, get_lang('OriginalName'));
$table->set_header(2, get_lang('EnglishName'));
$table->set_header(3, get_lang('DokeosFolder'));
$table->set_header(4, get_lang('Properties'));
$form_actions = array ();
$form_actions['makeavailable'] = get_lang('MakeAvailable');
$form_actions['makeunavailable'] = get_lang('MakeUnavailable');
$table->set_form_actions($form_actions);
$table->display();

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>