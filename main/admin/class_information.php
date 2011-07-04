<?php // $Id: class_information.php 16954 2008-11-26 14:41:35Z pcool $
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
*	@author	 Bart Mollet
*	@package dokeos.admin
==============================================================================
*/

// Language files that should be included.
$language_file = 'admin';

$cidReset = true;

require '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

require api_get_path(LIBRARY_PATH).'classmanager.lib.php';

if (!isset($_GET['id'])) {
    api_not_allowed();
}

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'class_list.php', 'name' => get_lang('AdminClasses'));

$class_id = $_GET['id'];
$class = ClassManager::get_class_info($class_id);

$tool_name = $class['name'];
Display::display_header($tool_name);
//api_display_tool_title($tool_name);

/**
 * Show all users subscribed in this class.
 */
echo '<h4>'.get_lang('Users').'</h4>';
echo '<blockquote>';
$users = ClassManager::get_users($class_id);
if (count($users) > 0) {
    $is_western_name_order = api_is_western_name_order();
    $table_header[] = array (get_lang('OfficialCode'), true);
    if ($is_western_name_order) {
        $table_header[] = array (get_lang('FirstName'), true);
        $table_header[] = array (get_lang('LastName'), true);
    } else {
        $table_header[] = array (get_lang('LastName'), true);
        $table_header[] = array (get_lang('FirstName'), true);
    }
    $table_header[] = array (get_lang('Email'), true);
    $table_header[] = array (get_lang('Status'), true);
    $table_header[] = array ('', false);
    $data = array();
    foreach($users as $index => $user) {
        $row = array ();
        $row[] = $user['official_code'];
        if ($is_western_name_order) {
            $row[] = $user['firstname'];
            $row[] = $user['lastname'];
        } else {
            $row[] = $user['lastname'];
            $row[] = $user['firstname'];
        }
        $row[] = Display :: encrypted_mailto_link($user['email'], $user['email']);
        $row[] = $user['status'] == 5 ? get_lang('Student') : get_lang('Teacher');
        $row[] = '<a href="user_information.php?user_id='.$user['user_id'].'">'.Display::return_icon('synthese_view.gif').'</a>';
        $data[] = $row;
    }
    Display::display_sortable_table($table_header,$data,array(),array(),array('id'=>$_GET['id']));
} else {
    echo get_lang('NoUsersInClass');
}
echo '</blockquote>';

/**
 * Show all courses in which this class is subscribed.
 */
$courses = ClassManager::get_courses($class_id);
if (count($courses) > 0) {
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array ('', false);
    $data = array ();
    foreach( $courses as $index => $course) {
        $row = array ();
        $row[] = $course['visual_code'];
        $row[] = $course['title'];
        $row[] = '<a href="course_information.php?code='.$course['code'].'">'.Display::return_icon('info_small.gif', get_lang('Delete')).'</a>'.
                    '<a href="'.api_get_path(WEB_COURSE_PATH).$course['directory'].'">'.Display::return_icon('course_home.gif', get_lang('CourseHome')).'</a>' .
                    '<a href="course_edit.php?course_code='.$course['code'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        $data[] = $row;
    }
    echo '<p><b>'.get_lang('Courses').'</b></p>';
    echo '<blockquote>';
    Display :: display_sortable_table($header, $data, array (), array (), array('id'=>$_GET['id']));
    echo '</blockquote>';
} else {
    echo '<p>'.get_lang('NoCoursesForThisClass').'</p>';
}

// Displaying the footer.
Display::display_footer();
