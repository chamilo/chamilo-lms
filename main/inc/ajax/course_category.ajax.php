<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = $_REQUEST['a'];

switch ($action) {
    case 'show_courses':
        $categoryId = (int) $_REQUEST['id'];
        $categoryInfo = CourseCategory::getCategoryById($categoryId);
        if (!empty($categoryInfo)) {
            $courses = CourseCategory::getCoursesInCategory($categoryInfo['code'], '', false, false);

            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
            $headers = [
                get_lang('Name'),
            ];
            $row = 0;
            $column = 0;
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $result = '';
            foreach ($courses as $course) {
                $row++;
                $table->setCellContents($row, 0, $course['title']);
            }

            echo $table->toHtml();
            exit;
        }
        break;
}
exit;
