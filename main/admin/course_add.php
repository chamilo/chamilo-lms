<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\Repository\CourseCategoryRepository;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$tool_name = get_lang('AddCourse');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'course_list.php', 'name' => get_lang('CourseList')];

$em = Database::getManager();
/** @var CourseCategoryRepository $courseCategoriesRepo */
$courseCategoriesRepo = $em->getRepository('ChamiloCoreBundle:CourseCategory');
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
if (!api_get_configuration_value('course_creation_form_hide_course_code')) {
    $form->addText(
        'visual_code',
        [
            get_lang('CourseCode'),
            get_lang('OnlyLettersAndNumbers'),
        ],
        false,
        [
            'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE,
            'pattern' => '[a-zA-Z0-9]+',
            'title' => get_lang('OnlyLettersAndNumbers'),
            'id' => 'visual_code',
        ]
    );

    $form->applyFilter('visual_code', 'api_strtoupper');
    $form->applyFilter('visual_code', 'html_filter');

    $form->addRule(
        'visual_code',
        get_lang('Max'),
        'maxlength',
        CourseManager::MAX_COURSE_LENGTH_CODE
    );
}

$countCategories = $courseCategoriesRepo->countAllInAccessUrl(
    $accessUrlId,
    api_get_configuration_value('allow_base_course_category')
);

if ($countCategories >= 100) {
    // Category code
    $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

    $form->addElement(
        'select_ajax',
        'category_code',
        get_lang('CourseFaculty'),
        null,
        ['url' => $url]
    );
} else {
    $categories = $courseCategoriesRepo->findAllInAccessUrl(
        $accessUrlId,
        api_get_configuration_value('allow_base_course_category')
    );
    $categoriesOptions = [null => get_lang('None')];
    /** @var CourseCategory $category */
    foreach ($categories as $category) {
        $categoriesOptions[$category->getCode()] = (string) $category;
    }
    $form->addSelect(
        'category_code',
        get_lang('CourseFaculty'),
        $categoriesOptions
    );
}

if (api_get_configuration_value('course_creation_form_set_course_category_mandatory')) {
    $form->addRule('category_code', get_lang('ThisFieldIsRequired'), 'required');
}

$currentTeacher = api_get_user_entity(api_get_user_id());

$form->addSelectAjax(
    'course_teachers',
    get_lang('CourseTeachers'),
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
    get_lang('CourseDepartment'),
    false,
    ['size' => '60', 'id' => 'department_name']
);
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

// Department URL
$form->addText(
    'department_url',
    get_lang('CourseDepartmentURL'),
    false,
    ['size' => '60', 'id' => 'department_url']
);
$form->applyFilter('department_url', 'html_filter');

// Course language.
$languages = api_get_languages();
if (count($languages['name']) === 1) {
    // If there's only one language available, there's no point in asking
    $form->addElement('hidden', 'course_language', $languages['folder'][0]);
} else {
    $form->addSelectLanguage(
        'course_language',
        get_lang('Ln'),
        [],
        ['style' => 'width:150px']
    );
}

if (api_get_setting('teacher_can_select_course_template') === 'true') {
    $form->addElement(
        'select_ajax',
        'course_template',
        [
            get_lang('CourseTemplate'),
            get_lang('PickACourseAsATemplateForThisNewCourse'),
        ],
        null,
        ['url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course']
    );
}

$form->addElement('checkbox', 'exemplary_content', '', get_lang('FillWithExemplaryContent'));

$group = [];
$group[] = $form->createElement('radio', 'visibility', get_lang('CourseAccess'), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$group[] = $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityHidden'), COURSE_VISIBILITY_HIDDEN);

$form->addGroup($group, '', get_lang('CourseAccess'));

$group = [];
$group[] = $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[] = $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group, '', get_lang('Subscription'));

$group = [];
$group[] = $form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[] = $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group, '', get_lang('Unsubscription'));

$form->addElement('text', 'disk_quota', [get_lang('CourseQuota'), null, get_lang('MB')], [
    'id' => 'disk_quota',
]);
$form->addRule('disk_quota', get_lang('ThisFieldShouldBeNumeric'), 'numeric');

$obj = new GradeModel();
$obj->fill_grade_model_select_in_form($form);

//Extra fields
$setExtraFieldsMandatory = api_get_configuration_value('course_creation_form_set_extra_fields_mandatory');
$fieldsRequired = [];
if (false !== $setExtraFieldsMandatory && !empty($setExtraFieldsMandatory['fields'])) {
    $fieldsRequired = $setExtraFieldsMandatory['fields'];
}
$extra_field = new ExtraField('course');
$extra = $extra_field->addElements(
    $form,
    0,
    [],
    false,
    false,
    [],
    [],
    [],
    false,
    false,
    [],
    [],
    false,
    [],
    $fieldsRequired
);

if (api_get_configuration_value('allow_course_multiple_languages')) {
    // Course Multiple language.
    $cbMultiLanguage = $form->getElementByName('extra_multiple_language');
    if (isset($cbMultiLanguage)) {
        foreach ($languages['folder'] as $langFolder) {
            $cbMultiLanguage->addOption(get_lang($langFolder), $langFolder);
        }
    }
}

$htmlHeadXtra[] = '
<script>

$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$form->addProgress();
$form->addButtonCreate(get_lang('CreateCourse'));

// Set some default values.
$values['course_language'] = api_get_setting('platformLanguage');
$values['disk_quota'] = round(api_get_setting('default_document_quotum') / 1024 / 1024, 1);

$default_course_visibility = api_get_setting('courses_default_creation_visibility');

if (isset($default_course_visibility)) {
    $values['visibility'] = api_get_setting('courses_default_creation_visibility');
} else {
    $values['visibility'] = COURSE_VISIBILITY_OPEN_PLATFORM;
}
$values['subscribe'] = 1;
$values['unsubscribe'] = 0;
$values['course_teachers'] = [$currentTeacher->getId()];

// Relation to prefill course extra field with user extra field
$fillExtraField = api_get_configuration_value('course_creation_user_course_extra_field_relation_to_prefill');
if (false !== $fillExtraField && !empty($fillExtraField['fields'])) {
    foreach ($fillExtraField['fields'] as $courseVariable => $userVariable) {
        $extraValue = UserManager::get_extra_user_data_by_field(api_get_user_id(), $userVariable);
        $values['extra_'.$courseVariable] = $extraValue[$userVariable];
    }
}

$form->setDefaults($values);

// Validate the form
if ($form->validate()) {
    $course = $form->exportValues();

    $course_teachers = isset($course['course_teachers']) ? $course['course_teachers'] : null;
    $course['disk_quota'] = $course['disk_quota'] * 1024 * 1024;
    $course['exemplary_content'] = empty($course['exemplary_content']) ? false : true;
    $course['teachers'] = $course_teachers;
    $course['wanted_code'] = isset($course['visual_code']) ? $course['visual_code'] : '';
    $course['gradebook_model_id'] = isset($course['gradebook_model_id']) ? $course['gradebook_model_id'] : null;
    // Fixing category code
    $course['course_category'] = isset($course['category_code']) ? $course['category_code'] : '';

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
                    get_lang('CourseXAdded'),
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
