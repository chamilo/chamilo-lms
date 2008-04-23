<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/*
* These are functions used in gradebook
*
* @author Stijn Konings <konings.stijn@skynet.be>, Hogeschool Ghent
* @version april 2007
*/
include_once ('gradebook_functions_users.inc.php');


function block_students()
{
	if (!api_is_allowed_to_create_course())
	{
		include (api_get_path(INCLUDE_PATH)."header.inc.php");
		api_not_allowed();
	}
}

/**
 * Returns the info header for the user result page
 * @param $userid
 */

/** 
 * Returns the course name from a given code
 * @param string $code
 */
function get_course_name_from_code($code)
{
	$tbl_main_categories= Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql= 'SELECT title,code FROM ' . $tbl_main_categories . 'WHERE code = "' . $code . '"';
	$result= api_sql_query($sql,__FILE__,__LINE__);
	if ($col= Database::fetch_array($result))
	{
		return $col['title'];
	}
}
/**
 * Builds an img tag for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function build_type_icon_tag($kind)
{
	return '<img src="' . get_icon_file_name ($kind) . '" border="0" hspace="5" align="middle" alt="" />';
}


/**
 * Returns the icon filename for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function get_icon_file_name ($type)
{
	if ($type == 'cat')
		return api_get_path(WEB_CODE_PATH) . 'img/folder_document.gif';
	elseif ($type == 'evalempty')
		return api_get_path(WEB_CODE_PATH) . 'img/empty.gif';
	elseif ($type == 'evalnotempty')
		return api_get_path(WEB_CODE_PATH) . 'img/gradebook_eval_not_empty.gif';
	elseif ($type == 'link')
		return api_get_path(WEB_CODE_PATH) . 'img/link.gif';
	else
		return null;
}



/**
 * Builds the course or platform admin icons to edit a category
 * @param object $cat category object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_cat($cat, $selectcat)
{
	$visibility_icon= ($cat->is_visible() == 0) ? 'invisible' : 'visible';
	$visibility_command= ($cat->is_visible() == 0) ? 'set_visible' : 'set_invisible';
	$modify_icons= '<a href="gradebook_edit_cat.php?editcat=' . $cat->get_id() . '"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . '" onclick="return confirmation();"><img src="../img/delete.gif" border="0" title="' . get_lang('DeleteAll') . '" alt="" /></a>';
	//no move ability for root categories
	if ($cat->is_movable())
	{
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
	} else
	{
		$modify_icons .= '&nbsp;<img src="../img/deplacer_fichier_na.gif" border="0" title="' . get_lang('Move') . '" alt="" />';
	}
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblecat=' . $cat->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . '"><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
	return $modify_icons;
}
/**
 * Builds the course or platform admin icons to edit an evaluation
 * @param object $eval evaluation object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_eval($eval, $selectcat)
{
	$visibility_icon= ($eval->is_visible() == 0) ? 'invisible' : 'visible';
	$visibility_command= ($eval->is_visible() == 0) ? 'set_visible' : 'set_invisible';
	$modify_icons= '<a href="gradebook_edit_eval.php?editeval=' . $eval->get_id() . '"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deleteeval=' . $eval->get_id() . '&selectcat=' . $selectcat . '" onclick="return confirmation();"><img src="../img/delete.gif" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?moveeval=' . $eval->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visibleeval=' . $eval->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . '"><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
	return $modify_icons;
}
/**
 * Builds the course or platform admin icons to edit a link
 * @param object $linkobject
 * @param int $selectcat id of selected category
 */
function build_edit_icons_link($link, $selectcat)
{
	$visibility_icon= ($link->is_visible() == 0) ? 'invisible' : 'visible';
	$visibility_command= ($link->is_visible() == 0) ? 'set_visible' : 'set_invisible';
	$modify_icons= '<a href="gradebook_edit_link.php?editlink=' . $link->get_id() . '"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletelink=' . $link->get_id() . '&selectcat=' . $selectcat . '" onclick="return confirmation();"><img src="../img/delete.gif" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movelink=' . $link->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
	$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblelink=' . $link->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . '"><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
	return $modify_icons;
}
?>
