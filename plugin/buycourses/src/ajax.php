<?php
/* For license terms, see /license.txt */
/**
 * AJAX script to get courses descriptions
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';

$language_file = array('course_description');

// Get the name of the database course.		
$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION);

$code = Database::escape_string($_GET['code']);
$course_info = api_get_course_info($code);
echo Display::tag('h2', $course_info['name']);
echo '<br />';

$sql = "SELECT * FROM $tbl_course_description
    WHERE c_id = " . intval($course_info['real_id']) . "
    AND session_id = 0 ORDER BY id";

$result = Database::query($sql);
if (Database::num_rows($result) > 0) {
    while ($description = Database::fetch_object($result)) {
        $descriptions[$description->id] = $description;
    }
    // Function that displays the details of the course description in html.
    echo CourseManager::get_details_course_description_html($descriptions, api_get_system_encoding(), false);
} else {
    echo get_lang('NoDescription');
}		
