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
require_once ('gradebook_functions_users.inc.php');


/**
 * Adds a resource to the unique gradebook of a given course
 * @param   string  Course code
 * @param   int     Resource type (use constants defined in linkfactory.class.php)
 * @param   int     Resource ID in the corresponding tool
 * @param   string  Resource name to show in the gradebook
 * @param   int     Resource weight to set in the gradebook
 * @param   int     Resource max
 * @param   string  Resource description
 * @param   string  Date
 * @param   int     Visibility (0 hidden, 1 shown)
 * @param   int     Session ID (optional or 0 if not defined)
 * @return  boolean True on success, false on failure
 */
function add_resource_to_course_gradebook($course_code, $resource_type, $resource_id, $resource_name='', $weight=0, $max=0, $resource_description='', $date=null, $visible=0, $session_id = 0) {
    /* See defines in lib/be/linkfactory.class.php
    define('LINK_EXERCISE',1);
    define('LINK_DROPBOX',2);
    define('LINK_STUDENTPUBLICATION',3);
    define('LINK_LEARNPATH',4);
    define('LINK_FORUM_THREAD',5),
    define('LINK_WORK',6);
    */
    $category = 0;
    require_once (api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php');
    $link= LinkFactory :: create($resource_type);
    $link->set_user_id(api_get_user_id());
    $link->set_course_code($course_code);
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    $sql = "SELECT * FROM $t WHERE course_code = '".Database::escape_string($course_code)."' ";
    if (!empty($session_id)) {
    	$sql .= " AND session_id = ".(int)$session_id;
    } else {
    	$sql .= " AND (session_id IS NULL OR session_id = 0) ";
    }
    $sql .= " ORDER BY id";
    $res = Database::query($sql,__FILE__,__LINE__);
    if (Database::num_rows($res)<1){
        //there is no unique category for this course+session combination,
        // => create one
        $cat= new Category();
        if (!empty($session_id)) {
        	$my_session_id=api_get_session_id();
            $s_name = api_get_session_name($my_session_id);
            $cat->set_name($course_code.' - '.get_lang('Session').' '.$s_name);
            $cat->set_session_id($session_id);
        } else {
            $cat->set_name($course_code);
        }
        $cat->set_course_code($course_code);
        $cat->set_description(null);
        $cat->set_user_id(api_get_user_id());
        $cat->set_parent_id(0);
        $cat->set_weight(100);
        $cat->set_visible(0);
        $can_edit = api_is_allowed_to_edit(true, true);
        if ($can_edit) {
            $cat->add();
        }
        $category = $cat->get_id();
        unset ($cat);
    } else {
        $row = Database::fetch_array($res);
        $category = $row['id'];
    }
    $link->set_category_id($category);

    if ($link->needs_name_and_description()) {
    	$link->set_name($resource_name);
    } else {
    	$link->set_ref_id($resource_id);
    }
    $link->set_weight($weight);

    if ($link->needs_max()) {
    	$link->set_max($max);
    }
    if (isset($date)) {
        $link->set_date($date);
    }
    if ($link->needs_name_and_description()) {
    	$link->set_description($resource_description);
    }

    $link->set_visible(empty ($visible) ? 0 : 1);

    if (!empty($session_id)) {
    	$link->set_session_id($session_id);
    }
    $link->add();
    return true;
}

function block_students() {
	if (!api_is_allowed_to_create_course()) {
		require_once (api_get_path(INCLUDE_PATH)."header.inc.php");
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
function get_course_name_from_code($code) {
	$tbl_main_categories= Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql= 'SELECT title,code FROM ' . $tbl_main_categories . 'WHERE code = "' . $code . '"';
	$result= Database::query($sql,__FILE__,__LINE__);
	if ($col= Database::fetch_array($result)) {
		return $col['title'];
	}
}
/**
 * Builds an img tag for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function build_type_icon_tag($kind) {
	return '<img src="' . get_icon_file_name ($kind) . '" border="0" hspace="5" align="middle" alt="" />';
}


/**
 * Returns the icon filename for a gradebook item
 * @param string $type value returned by a gradebookitem's get_icon_name()
 */
function get_icon_file_name ($type) {
	if ($type == 'cat') {
		return api_get_path(WEB_CODE_PATH) . 'img/folder_document.gif';
	} elseif ($type == 'evalempty') {
		return api_get_path(WEB_CODE_PATH) . 'img/empty.gif';
	} elseif ($type == 'evalnotempty') {
		return api_get_path(WEB_CODE_PATH) . 'img/gradebook_eval_not_empty.gif';
	} elseif ($type == 'link') {
		return api_get_path(WEB_CODE_PATH) . 'img/link.gif';
	} else {
		return null;
	}
}



/**
 * Builds the course or platform admin icons to edit a category
 * @param object $cat category object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_cat($cat, $selectcat) {

	$show_message=$cat->show_message_resource_delete($cat->get_course_code());
	if ($show_message===false) {
		$visibility_icon= ($cat->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($cat->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		$modify_icons= '<a href="gradebook_edit_cat.php?editcat=' . $cat->get_id() . ' &amp;cidReq='.$cat->get_course_code().'"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$cat->get_course_code().'" onclick="return confirmation();"><img src="../img/delete.gif" border="0" title="' . get_lang('DeleteAll') . '" alt="" /></a>';

		//no move ability for root categories
		if ($cat->is_movable()) {
			$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movecat=' . $cat->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$cat->get_course_code().'"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		} else {
			//$modify_icons .= '&nbsp;<img src="../img/deplacer_fichier_na.gif" border="0" title="' . get_lang('Move') . '" alt="" />';
		}
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblecat=' . $cat->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' "><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';

		return $modify_icons;
	}
}
/**
 * Builds the course or platform admin icons to edit an evaluation
 * @param object $eval evaluation object
 * @param int $selectcat id of selected category
 */
function build_edit_icons_eval($eval, $selectcat) {
	$status=CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
	$eval->get_course_code();
	$cat=new Category();
	$message_eval=$cat->show_message_resource_delete($eval->get_course_code());
	if ($message_eval===false) {
		$visibility_icon= ($eval->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($eval->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		$modify_icons= '<a href="gradebook_edit_eval.php?editeval=' . $eval->get_id() . ' &amp;cidReq='.$eval->get_course_code().'"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deleteeval=' . $eval->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().'" onclick="return confirmation();"><img src="../img/delete.gif" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
		//$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?moveeval=' . $eval->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visibleeval=' . $eval->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' "><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
		if ($status==1 || is_null($status)){
			$modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog=' . $eval->get_id() . '&amp;selectcat=' . $selectcat . ' &amp;cidReq='.$eval->get_course_code().'"><img src="../img/file_txt_small.gif" border="0" title="' . get_lang('GradebookQualifyLog') . '" alt="" /></a>';
		}
		return $modify_icons;
	}
}
/**
 * Builds the course or platform admin icons to edit a link
 * @param object $linkobject
 * @param int $selectcat id of selected category
 */
function build_edit_icons_link($link, $selectcat) {

	$link->get_course_code();
	$cat=new Category();
	$message_link=$cat->show_message_resource_delete($link->get_course_code());
	if ($message_link===false) {
		$visibility_icon= ($link->is_visible() == 0) ? 'invisible' : 'visible';
		$visibility_command= ($link->is_visible() == 0) ? 'set_visible' : 'set_invisible';
		$modify_icons= '<a href="gradebook_edit_link.php?editlink=' . $link->get_id() . ' &amp;cidReq='.$link->get_course_code().'"><img src="../img/edit.gif" border="0" title="' . get_lang('Modify') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?deletelink=' . $link->get_id() . '&selectcat=' . $selectcat . ' &amp;cidReq='.$link->get_course_code().'" onclick="return confirmation();"><img src="../img/delete.gif" border="0" title="' . get_lang('Delete') . '" alt="" /></a>';
		//$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?movelink=' . $link->get_id() . '&selectcat=' . $selectcat . '"><img src="../img/deplacer_fichier.gif" border="0" title="' . get_lang('Move') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="' . api_get_self() . '?visiblelink=' . $link->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . ' "><img src="../img/' . $visibility_icon . '.gif" border="0" title="' . get_lang('Visible') . '" alt="" /></a>';
		$modify_icons .= '&nbsp;<a href="gradebook_showlog_link.php?visiblelink=' . $link->get_id() . '&amp;selectcat=' . $selectcat . '&amp;cidReq='.$link->get_course_code().'"><img src="../img/file_txt_small.gif" border="0" title="' . get_lang('GradebookQualifyLog') . '" alt="" /></a>';
		//if (api_is_course_admin() == true) {
			//$modify_icons .= '&nbsp;<a href="gradebook_showlog_eval.php?visiblelog=' . $eval->get_id() . '&amp;' . $visibility_command . '=&amp;selectcat=' . $selectcat . '"><img src="../img/file_txt_small.gif" border="0" title="' . get_lang('GradebookQualifyLog') . '" alt="" /></a>';
		//}
		return $modify_icons;
	}
}

/**
 * Checks if a resource is in the unique gradebook of a given course
 * @param    string  Course code
 * @param    int     Resource type (use constants defined in linkfactory.class.php)
 * @param    int     Resource ID in the corresponding tool
 * @param    int     Session ID (optional -  0 if not defined)
 * @return   int     false on error or link ID
 */
function is_resource_in_course_gradebook($course_code, $resource_type, $resource_id, $session_id = 0) {
    /* See defines in lib/be/linkfactory.class.php
    define('LINK_EXERCISE',1);
    define('LINK_DROPBOX',2);
    define('LINK_STUDENTPUBLICATION',3);
    define('LINK_LEARNPATH',4);
    define('LINK_FORUM_THREAD',5),
    define('LINK_WORK',6);
    */
    require_once(api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php');
    require_once (api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php');
	require_once(api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php');
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $t = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    $sql = "SELECT * FROM $t WHERE course_code = '".Database::escape_string($course_code)."' ";
    if (!empty($session_id)) {
        $sql .= " AND session_id = ".(int)$session_id;
    } else {
        $sql .= " AND (session_id IS NULL OR session_id = 0) ";
    }
    $sql .= " ORDER BY id";
    $res = Database::query($sql,__FILE__,__LINE__);
    if (Database::num_rows($res)<1) {
    	return false;
    }
    $row = Database::fetch_array($res);
    $category = $row['id'];
    $sql = "SELECT * FROM $l l WHERE l.category_id = $category AND type = ".(int) $resource_type." and ref_id = ".(int) $resource_id;
    $res = Database::query($sql,__FILE__,__LINE__);
    if (Database::num_rows($res)<1) {
    	return false;
    }
    $row = Database::fetch_array($res);
    return $row['id'];
}
/**
 * Remove a resource from the unique gradebook of a given course
 * @param    int     Link/Resource ID
 * @return   bool    false on error, true on success
 */
function remove_resource_from_course_gradebook($link_id) {
    if ( empty($link_id) ) { return false; }
    require_once (api_get_path(SYS_CODE_PATH).'gradebook/lib/be.inc.php');
    // TODO find the corresponding category (the first one for this course, ordered by ID)
    $l = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
    $sql = "DELETE FROM $l WHERE id = ".(int)$link_id;
    $res = Database::query($sql,__FILE__,__LINE__);
    return true;
}
/**
 * return the database name
 * @param    int
 * @return   String
 */
function get_database_name_by_link_id($id_link) {
	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
	$res=Database::query('SELECT db_name from '.$course_table.' c inner join '.$tbl_grade_links.' l
	on c.code=l.course_code WHERE l.id='.$id_link.' OR l.category_id='.$id_link);
	$my_db_name=Database::fetch_array($res,'ASSOC');
	return $my_db_name['db_name'];
}
