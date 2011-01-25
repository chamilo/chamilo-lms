<?php
// $Id: class_list.php 20441 2009-05-10 07:39:15Z ivantcholakov $
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

$language_file = 'admin';

$cidReset = true;
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

/**
 * Gets the total number of classes.
 */
function get_number_of_classes() {
    $tbl_class = Database :: get_main_table(TABLE_MAIN_CLASS);
    $sql = "SELECT COUNT(*) AS number_of_classes FROM $tbl_class";
    if (isset ($_GET['keyword'])) {
        $sql .= " WHERE (name LIKE '%".Database::escape_string(trim($_GET['keyword']))."%')";
    }
    $res = Database::query($sql);
    $obj = Database::fetch_object($res);
    return $obj->number_of_classes;
}

/**
 * Gets the information about some classes.
 * @param int $from
 * @param int $number_of_items
 * @param string $direction
 */
function get_class_data($from, $number_of_items, $column, $direction) {
    $tbl_class_user = Database::get_main_table(TABLE_MAIN_CLASS_USER);
    $tbl_class = Database :: get_main_table(TABLE_MAIN_CLASS);
    $from 				= Database::escape_string($from);
    $number_of_items 	= Database::escape_string($number_of_items);
    $column 			= Database::escape_string($column);
    $direction 			= Database::escape_string($direction);

    $sql = "SELECT 	id AS col0, name AS col1, COUNT(user_id) AS col2, id AS col3
        FROM $tbl_class
            LEFT JOIN $tbl_class_user ON id=class_id ";
    if (isset ($_GET['keyword'])) {
        $sql .= " WHERE (name LIKE '%".Database::escape_string(trim($_GET['keyword']))."%')";
    }
    $sql .= " GROUP BY id,name ORDER BY col$column $direction LIMIT $from,$number_of_items";
    $res = Database::query($sql);
    $classes = array ();
    while ($class = Database::fetch_row($res)) {
        $classes[] = $class;
    }
    return $classes;
}

/**
 * Filter for sortable table to display edit icons for class
 */
function modify_filter($class_id) {
    $class_id = Security::remove_XSS($class_id);
    $result = '<a href="class_information.php?id='.$class_id.'">'.Display::return_icon('synthese_view.gif', get_lang('Info')).'</a>';
    $result .= '<a href="class_edit.php?idclass='.$class_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
    $result .= '<a href="class_list.php?action=delete_class&amp;class_id='.$class_id.'" onclick="javascript: if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES))."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
    $result .= '<a href="subscribe_user2class.php?idclass='.$class_id.'">'.Display::return_icon('add_multiple_users.gif', get_lang('AddUsersToAClass')).'</a>';
    return $result;
}

require api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require api_get_path(LIBRARY_PATH).'classmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$tool_name = get_lang('ClassList');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        // Delete selected classes
        case 'delete_classes' :
            $classes = $_POST['class'];
            if (count($classes) > 0) {
                foreach ($classes as $index => $class_id) {
                    ClassManager :: delete_class($class_id);
                }
                Display :: display_normal_message(get_lang('ClassesDeleted'));
            }
            break;
    }
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_class':
            ClassManager :: delete_class($_GET['class_id']);
            Display :: display_normal_message(get_lang('ClassDeleted'));
            break;
        case 'show_message':
            Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));
            break;
    }
}

// Create a search-box
$form = new FormValidator('search_simple', 'get', '', '', null, false);
$renderer =& $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->addElement('text', 'keyword', get_lang('keyword'));
$form->addElement('submit', 'submit', get_lang('Search'));
$form->display();

// Create the sortable table with class information
$table = new SortableTable('classes', 'get_number_of_classes', 'get_class_data', 1);
$table->set_additional_parameters(array('keyword' => $_GET['keyword']));
$table->set_header(0, '', false);
$table->set_header(1, get_lang('ClassName'));
$table->set_header(2, get_lang('NumberOfUsers'));
$table->set_header(3, '', false);
$table->set_column_filter(3, 'modify_filter');
$table->set_form_actions(array ('delete_classes' => get_lang('DeleteSelectedClasses')), 'class');
$table->display();

// Displaying the footer.
Display :: display_footer();
