<?php
/* For licensing terms, see /license.txt */

/**
* 	This tool allows platform admins to export courses to CSV file
*	@package chamilo.admin
*/

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$tool_name = get_lang('ExportCourses');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

set_time_limit(0);

$archivePath = api_get_path(SYS_ARCHIVE_PATH);
$archiveURL = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

$course_list = CourseManager::get_courses_list();
$formSent = null;
$courses = $selected_courses = array();

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = $_POST['formSent'];
    $select_type = intval($_POST['select_type']);
    $file_type = $_POST['file_type'];

    if ($select_type == 2) {
        // Get selected courses from courses list in form sent
        $selected_courses = $_POST['course_code'];
        if (is_array($selected_courses)) {
            foreach ($course_list as $course) {
                if (!in_array($course['code'], $selected_courses)) {
                    continue;
                }
                $courses[] = $course;
            }
        }
    } else {
        // Get all courses
        $courses = $course_list;
    }

	if (!empty($courses)) {

        $archiveFile = 'export_courses_list_'.api_get_local_time();

        $listToExport[] = [
            'Code',
            'Title',
            'CourseCategory',
            'CourseCategoryName',
            'Teacher',
            'Language',
            'Users',
            'OtherTeachers'
        ];

        $dataToExport = [];

        foreach ($courses as $course) {
            $dataToExport['code'] = str_replace(';',',',$course['code']);
            $dataToExport['title'] = str_replace(';',',',$course['title']);
            $dataToExport['category_code'] = str_replace(';',',',$course['category_code']);
            $categoryInfo = CourseCategory::getCategory($course['category_code']);
            if ($categoryInfo) {
                $dataToExport['category_name'] = str_replace(';',',',$categoryInfo['name']);
            } else {
                $dataToExport['category_name'] = '';
            }
            $dataToExport['tutor_name'] = str_replace(';',',',$course['tutor_name']);
            $dataToExport['course_language'] = str_replace(';',',',$course['course_language']);

            $dataToExport['students'] = '';
            $dataToExport['teachers'] = '';

            $usersInCourse = CourseManager::get_user_list_from_course_code($course['code']);

            if (is_array($usersInCourse) && !empty($usersInCourse)) {
                foreach ($usersInCourse as $user) {
                    if ($user['status_rel'] == COURSEMANAGER) {
                        $dataToExport['teachers'] .= $user['username'] . '|';
                    } else {
                        $dataToExport['students'] .= $user['username'] . '|';
                    }
                }
            }
            $dataToExport['students'] = substr($dataToExport['students'], 0, -1);
            $dataToExport['teachers'] = substr($dataToExport['teachers'], 0, -1);

            $listToExport[] = $dataToExport;
        }

        switch ($file_type) {
            case 'xml':
                // Remove header
                unset($listToExport[0]);
                Export::arrayToXml($listToExport, $archiveFile);
                break;
            case 'csv':
                Export::arrayToCsv($listToExport, $archiveFile);
            case 'xls':
                Export::arrayToXls($listToExport, $archiveFile);
                break;
        }
	} else {
        Display::addFlash(
            Display::return_message(
                get_lang('ThereAreNotSelectedCoursesOrCoursesListIsEmpty')
            )
        );
	}
}


Display:: display_header($tool_name);

$form = new FormValidator('export', 'post', api_get_self());
$form->addHeader($tool_name);
$form->addHidden('formSent', 1);
$form->addElement(
	'radio',
	'select_type',
	get_lang('Option'),
	get_lang('ExportAllCoursesList'),
	 '1',
	 ['onclick' => "javascript: if(this.checked){document.getElementById('div-course-list').style.display='none';}"]
);

$form->addElement(
	'radio',
	'select_type',
	'',
	get_lang('ExportSelectedCoursesFromCoursesList'),
	'2',
	['onclick' => "javascript: if(this.checked){document.getElementById('div-course-list').style.display='block';}"]
);

if (!empty($course_list)) {
	$form->addHtml('<div id="div-course-list" style="display:none">');
	$coursesInList = [];
	foreach ($course_list as $course) {
		$coursesInList[$course['code']] = $course['title'].' ('.$course['code'].')';
	}

    $form->addSelect(
        'course_code',
        get_lang('WhichCoursesToExport'),
        $coursesInList,
        ['multiple' => 'multiple']
    );

	$form->addHtml('</div>');
}

$form->addElement('radio', 'file_type', get_lang('OutputFileType'), 'CSV' , 'csv', null);
$form->addElement('radio', 'file_type', '' , 'XLS' , 'xls', null);
$form->addElement('radio', 'file_type', null, 'XML', 'xml', null, array('id' => 'file_type_xml'));

$form->setDefaults(['select_type' => '1', 'file_type' => 'csv']);

$form->addButtonExport(get_lang('ExportCourses'));
$form->display();

Display :: display_footer();
