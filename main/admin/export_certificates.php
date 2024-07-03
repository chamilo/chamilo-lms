<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

Display::display_header(null);

$form = new FormValidator('export_certificate');
$courses = CourseManager::get_courses_list(0, 0, 'title');
$options = [];
foreach ($courses as $course) {
    $options[$course['id']] = $course['title'];
}
$form->addElement('select', 'course', get_lang('Course'), $options);
$form->addElement('file', 'file', get_lang('File'));
$form->addButton('submit', get_lang('Submit'));
$form->display();

if ($form->validate()) {
    $values = $form->getSubmitValues();
    if (isset($_FILES['file']['tmp_name']) &&
        !empty($_FILES['file']['tmp_name'])
    ) {
        $users = Import::csv_reader($_FILES['file']['tmp_name']);
        $courseId = $values['course'];
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];

        $cats = Category::load(
            null,
            null,
            $courseCode,
            null,
            null,
            0,
            false
        );

        if (isset($cats[0])) {
            /** @var Category $cat */
            $userList = [];
            foreach ($users as $user) {
                $userInfo = api_get_user_info_from_official_code(
                    $user['official_code']
                );
                if (!empty($userInfo)) {
                    $userList[] = $userInfo;
                }
            }

            Category::exportAllCertificates(
                $cat->get_id(),
                $userList
            );
        }
    }
}

Display::display_footer();
