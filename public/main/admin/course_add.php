<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$tool_name = get_lang('Create a course');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'course_list.php', 'name' => get_lang('Course list')];

$em = Database::getManager();
// Get all possible teachers.
$accessUrlId = api_get_current_access_url_id();

// Build the form.
$form = new FormValidator('update_course');
$form->addElement('header', $tool_name);

// Title
$form->addText(
    'title',
    get_lang('Title'),
    true,
    [
        'aria-label' => get_lang('Title'),
    ]
);
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$form->addText(
    'visual_code',
    [
        get_lang('Code'),
        get_lang('Only letters (a-z) and numbers (0-9)'),
    ],
    false,
    [
        'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE,
        'pattern' => '[a-zA-Z0-9]+',
        'title' => get_lang('Only letters (a-z) and numbers (0-9)'),
        'id' => 'visual_code',
    ]
);

$form->applyFilter('visual_code', 'api_strtoupper');
$form->applyFilter('visual_code', 'html_filter');
$form->addSelectAjax(
    'course_categories',
    get_lang('Categories'),
    null,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category',
        'multiple' => 'multiple',
    ]
);

$form->addRule(
    'visual_code',
    get_lang('max. 20 characters, e.g. <i>INNOV21</i>'),
    'maxlength',
    CourseManager::MAX_COURSE_LENGTH_CODE
);

$currentTeacher = api_get_user_entity(api_get_user_id());

$form->addSelectAjax(
    'course_teachers',
    get_lang('Teachers'),
    [$currentTeacher->getId() => UserManager::formatUserFullName($currentTeacher, true)],
    [
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=teacher_to_basis_course',
        'id' => 'course_teachers',
        'multiple' => 'multiple',
    ]
);
$form->applyFilter('course_teachers', 'html_filter');

// Course department
$form->addText(
    'department_name',
    get_lang('Department'),
    false,
    ['size' => '60', 'id' => 'department_name']
);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

// Department URL
$form->addText(
    'department_url',
    get_lang('DepartmentURL'),
    false,
    ['size' => '60', 'id' => 'department_url']
);
$form->applyFilter('department_url', 'html_filter');

// Course language.
$languages = api_get_languages();
if (1 === count($languages)) {
    // If there's only one language available, there's no point in asking
    $form->addElement('hidden', 'course_language', $languages[0]);
} else {
    $form->addSelectLanguage(
        'course_language',
        get_lang('Language'),
        [],
        ['style' => 'width:150px']
    );
}

if ('true' === api_get_setting('teacher_can_select_course_template')) {
    $form->addElement(
        'select_ajax',
        'course_template',
        [
            get_lang('Course template'),
            get_lang('Pick a course as template for this new course'),
        ],
        null,
        ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course']
    );
}

$form->addElement('checkbox', 'exemplary_content', '', get_lang('Fill with demo content'));

CourseManager::addVisibilityOptions($form);

$group = [];
$group[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[] = $form->createElement('radio', 'subscribe', null, get_lang('This function is only available to trainers'), 0);
$form->addGroup($group, '', get_lang('Subscription'));

$group = [];
$group[] = $form->createElement('radio', 'unsubscribe', get_lang('Unsubscribe'), get_lang('Users are allowed to unsubscribe from this course'), 1);
$group[] = $form->createElement('radio', 'unsubscribe', null, get_lang('NotUsers are allowed to unsubscribe from this course'), 0);
$form->addGroup($group, '', get_lang('Unsubscribe'));

$form->addElement('text', 'disk_quota', [get_lang('Disk Space'), null, get_lang('MB')], [
    'id' => 'disk_quota',
]);
$form->addRule('disk_quota', get_lang('This field should be numeric'), 'numeric');

$obj = new GradeModel();
$obj->fill_grade_model_select_in_form($form);

//Extra fields
$extra_field = new ExtraField('course');
$extra = $extra_field->addElements($form);

$htmlHeadXtra[] = '
<script>

$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addProgress();
$form->addButtonCreate(get_lang('Create a course'));

// Set some default values.
$values['course_language'] = api_get_setting('platformLanguage');
$values['disk_quota'] = round(api_get_setting('default_document_quotum') / 1024 / 1024, 1);

$default_course_visibility = api_get_setting('courses_default_creation_visibility');

if (isset($default_course_visibility)) {
    $values['visibility'] = api_get_setting('courses_default_creation_visibility');
} else {
    $values['visibility'] = Course::OPEN_PLATFORM;
}
$values['subscribe'] = 1;
$values['unsubscribe'] = 0;
$values['course_teachers'] = [$currentTeacher->getId()];

$form->setDefaults($values);

// Validate the form
if ($form->validate()) {
    $course = $form->exportValues();

    $course_teachers = isset($course['course_teachers']) ? $course['course_teachers'] : null;
    $course['disk_quota'] = $course['disk_quota'] * 1024 * 1024;
    $course['exemplary_content'] = empty($course['exemplary_content']) ? false : true;
    $course['teachers'] = $course_teachers;
    $course['wanted_code'] = $course['visual_code'];
    $course['gradebook_model_id'] = isset($course['gradebook_model_id']) ? $course['gradebook_model_id'] : null;

    include_once api_get_path(SYS_CODE_PATH).'lang/english/trad4all.inc.php';
    $file_to_include = api_get_path(SYS_CODE_PATH).'lang/'.$course['course_language'].'/trad4all.inc.php';

    if (file_exists($file_to_include)) {
        include $file_to_include;
    }

    $courseInfo = CourseManager::create_course($course);
    if ($courseInfo && isset($courseInfo['course_public_url'])) {
        Display::addFlash(
            Display::return_message(
                sprintf(
                    get_lang('Course %s added'),
                    Display::url($courseInfo['title'], $courseInfo['course_public_url'])
                ),
                'confirmation',
                false
            )
        );
    }

    header('Location: course_list.php');
    exit;
}

// Display the form.
$content = $form->returnForm();

$tpl = new Template($tool_name);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
