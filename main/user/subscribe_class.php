<?php

/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.user
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('registration', 'admin');
include ('../inc/global.inc.php');
$this_section = SECTION_COURSES;


if (!api_is_allowed_to_edit()) {
    api_not_allowed();
    exit;
}

/*
  MAIN CODE
 */
$tool_name = get_lang("AddClassesToACourse");
//extra entries in breadcrumb
$interbreadcrumb[] = array("url" => "user.php", "name" => get_lang("ToolUser"));
$interbreadcrumb[] = array("url" => "class.php", "name" => get_lang("Classes"));
Display :: display_header($tool_name, "User");

echo Display::page_header($tool_name);

if (isset($_GET['register'])) {
    ClassManager::subscribe_to_course($_GET['class_id'], $_course['sysCode']);
    Display::display_normal_message(get_lang('ClassesSubscribed'));
}
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'subscribe' :
            if (is_array($_POST['class'])) {
                foreach ($_POST['class'] as $index => $class_id) {
                    ClassManager::subscribe_to_course($class_id, $_course['sysCode']);
                }
                Display::display_normal_message(get_lang('ClassesSubscribed'));
            }
            break;
    }
}

/*
  SHOW LIST OF USERS
 */

/**
 *  * Get the number of classes to display on the current page.
 */
function get_number_of_classes() {
    $class_table = Database :: get_main_table(TABLE_MAIN_CLASS);
    $course_class_table = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
    $sql = "SELECT * FROM $course_class_table WHERE course_code = '" . $_SESSION['_course']['id'] . "'";
    $res = Database::query($sql);
    $subscribed_classes = array();
    while ($obj = Database::fetch_object($res)) {
        $subscribed_classes[] = $obj->class_id;
    }
    $sql = "SELECT c.id	FROM $class_table c WHERE 1 = 1";
    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_GET['keyword']));
        $sql .= " AND (c.name LIKE '%" . $keyword . "%')";
    }
    if (count($subscribed_classes) > 0) {
        $sql .= " AND c.id NOT IN ('" . implode("','", $subscribed_classes) . "')";
    }
    $res = Database::query($sql);
    $result = Database::num_rows($res);
    return $result;
}

/**
 * Get the classes to display on the current page.
 */
function get_class_data($from, $number_of_items, $column, $direction) {
    $class_table = Database :: get_main_table(TABLE_MAIN_CLASS);
    $course_class_table = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
    $class_user_table = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
    $sql = "SELECT * FROM $course_class_table WHERE course_code = '" . $_SESSION['_course']['id'] . "'";
    $res = Database::query($sql);
    $subscribed_classes = array();
    while ($obj = Database::fetch_object($res)) {
        $subscribed_classes[] = $obj->class_id;
    }
    $sql = "SELECT
							c.id AS col0,
							c.name   AS col1,
							COUNT(cu.user_id) AS col2,
							c.id AS col3
						FROM $class_table c
						";
    $sql .= " LEFT JOIN $class_user_table cu ON cu.class_id = c.id";
    $sql .= " WHERE 1 = 1";
    if (isset($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_GET['keyword']));
        $sql .= " AND (c.name LIKE '%" . $keyword . "%')";
    }
    if (count($subscribed_classes) > 0) {
        $sql .= " AND c.id NOT IN ('" . implode("','", $subscribed_classes) . "')";
    }
    $sql .= " GROUP BY c.id, c.name ";
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database::query($sql);
    $classes = array();
    while ($class = Database::fetch_row($res)) {
        $classes[] = $class;
    }
    return $classes;
}

/**
 * Build the reg-column of the table
 * @param int $class_id The class id
 * @return string Some HTML-code
 */
function reg_filter($class_id) {
    $result = "<a href=\"" . api_get_self() . "?register=yes&amp;class_id=" . $class_id . "\">" . get_lang("reg") . "</a>";
    return $result;
}

// Build search-form
$form = new FormValidator('search_class', 'get', '', '', null, false);
$form->add_textfield('keyword', '', false);
$form->addElement('button', 'submit', get_lang('SearchButton'));

// Build table
$table = new SortableTable('users', 'get_number_of_classes', 'get_class_data', 1);
$parameters['keyword'] = Security::remove_XSS($_GET['keyword']);
$table->set_additional_parameters($parameters);
$col = 0;
$table->set_header($col++, '', false);
$table->set_header($col++, get_lang('ClassName'));
$table->set_header($col++, get_lang('NumberOfUsers'));
$table->set_header($col++, get_lang('reg'), false);
$table->set_column_filter($col - 1, 'reg_filter');
$table->set_form_actions(array('subscribe' => get_lang('reg')), 'class');

// Display form & table
$form->display();
$table->display();
Display :: display_footer();