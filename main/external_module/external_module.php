<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

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
*	This script is used for adding hyperlinks to a course homepage.
*	It used to be able to link html documents as well,
*	which it displayed in context, but that was abandoned
*	because of changes in documents tool:
*
*	Html files are displayed by default with frames now in the documents tool
*	so the external module - html file include has to be refactored to
*	reuse documents tool code.
*
*	@package dokeos.external_module
==============================================================================
*/

// name of the language file that needs to be included 
$language_file='external_module';

$iconForImportedTools='external.gif';
$iconForInactiveImportedTools='external_inactive.gif';

include('../inc/global.inc.php');

$tbl_courseHome = Database::get_course_table(TABLE_TOOL_LIST);
$toolid = $_GET['id'];  // RH: all lines with $toolid added/changed by me

if($toolid)
{
        $nameTools = get_lang('EditLink');
        $noPHP_SELF = TRUE;  // RH: no click to self on edit
}
else    $nameTools = get_lang('AddLink');


$is_allowedToEdit=$is_courseAdmin;

$linkAdded=false;

if($is_allowedToEdit && $_POST['formSent'] && $toolid)  // RH: new section
{
	$name_link=trim(stripslashes($_POST['name_link']));
	$link=trim(stripslashes($_POST['link']));
	$target=($_POST['target'] == '_blank')?'_blank':'_self';

	if(empty($name_link)) $msgErr=get_lang('NoLinkName');
	elseif(empty($link) || $link == 'http://') $msgErr=get_lang('NoLinkURL');
	else
	{
		$sql =  "UPDATE $tbl_courseHome SET " .
		        "name='" .   Database::escape_string($name_link) .
		        "', link='" .    Database::escape_string($link) .
		        "', target='" .  Database::escape_string($target) .
		        "' WHERE id='" . Database::escape_string($id) . "'";

		api_sql_query($sql, __FILE__, __LINE__);

		$linkAdded = TRUE;
	}
}
elseif($is_allowedToEdit && $_POST['formSent'])
{
	$name_link=trim(stripslashes($_POST['name_link']));
	$link=trim(stripslashes($_POST['link']));
	$target=($_POST['target'] == '_blank')?'_blank':'_self';

	if(empty($name_link)) $msgErr=get_lang('NoLinkName');
	elseif(empty($link) || $link == 'http://') $msgErr=get_lang('NoLinkURL');
	else
	{
		if(!stristr($link,'http://'))
		{
			$link='http://'.$link;
		}

		api_sql_query("INSERT INTO $tbl_courseHome(name,link,image,visibility,admin,address,target) VALUES('".Database::escape_string($name_link)."','".Database::escape_string($link)."','$iconForImportedTools','1','0','$iconForInactiveImportedTools','$target')",__FILE__,__LINE__);

		$linkAdded=true;
	}
}

Display::display_header($nameTools,"External");
?>

<h3><?php echo $toolid ? get_lang('EditLink') : $nameTools; ?></h3>

<?php


if(!$is_allowedToEdit)
{
	api_not_allowed();
}

if($linkAdded)
{
	echo $toolid ? get_lang('LinkChanged') :sprintf(get_lang('OkSentLink'),api_get_path(WEB_COURSE_PATH).$_course['path']);
}
else
{
    if ($toolid)  // RH: new section
    {
    	$sql =  "SELECT name,link,target FROM $tbl_courseHome" .
        " WHERE id='" . Database::escape_string($id) . "'";

    	$result = api_sql_query($sql, __FILE__, __LINE__);

    	(Database::num_rows($result) == 1 && ($row = Database::fetch_array($result)))
    	    or die('? Could not fetch data with ' . htmlspecialchars($sql));
	}

?>

<p><?php echo $toolid ? get_lang('ChangePress') : get_lang('SubTitle'); ?></p>

<table border="0">
<form method="post" action="<?php echo $toolid ? api_get_self() . '?id=' . $id : api_get_self(); ?>">
<input type="hidden" name="formSent" value="1">

<?php
if(!empty($msgErr))
{
?>

<tr>
  <td colspan="2">

<?php
	Display::display_normal_message($msgErr); //main API
?>

  </td>
</tr>

<?php
}
?>

<tr>
  <td align="right"><?php echo get_lang('Link'); ?> :</td>
  <td><input type="text" name="link" size="50" value="<?php if($_POST['formSent']) echo htmlentities($link); else echo $toolid ? htmlspecialchars($row['link']) : 'http://'; ?>"></td>
</tr>
<tr>
  <td align="right"><?php echo get_lang('Name'); ?> :</td>
  <td><input type="text" name="name_link" size="50" value="<?php if($_POST['formSent']) echo htmlentities($name_link,ENT_QUOTES,$charset); else echo $toolid ? htmlspecialchars($row['name'],ENT_QUOTES,$charset) : ''; ?>"></td>
</tr>
<tr>
  <td align="right"><?php echo get_lang('LinkTarget'); ?> :</td>
  <td><select name="target">
  <option value="_self"><?php echo get_lang('SameWindow'); ?></option>
  <option value="_blank" <?php if(($_POST['formSent'] && $target == '_blank') || ($toolid && $row['target'] == '_blank')) echo 'selected="selected"'; ?>><?php echo get_lang('NewWindow'); ?></option>
  </select></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"></td>
</tr>
</table>

<?php
}

Display::display_footer();
?>