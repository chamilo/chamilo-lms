<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\Repository\CourseCategoryRepository;

/**
 * This script allows professors and administrative staff to create course sites.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Roan Embrechts, refactoring
 * @author Jose Manuel Abuin Mosquera <chema@cesga.es>, Centro de Supercomputacion de Galicia
 * "Course validation" feature, technical adaptation for Chamilo 1.8.8:
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>
 */

// Flag forcing the "current course" reset.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// Check access rights.
if (!api_is_allowed_to_create_course()) {
    api_not_allowed(true);
    exit;
}

// Section for the tabs.
$this_section = SECTION_COURSES;

$em = Database::getManager();
/** @var CourseCategoryRepository $courseCategoriesRepo */
$courseCategoriesRepo = $em->getRepository('ChamiloCoreBundle:CourseCategory');
// Get all possible teachers.
$accessUrlId = api_get_current_access_url_id();

// "Course validation" feature. This value affects the way of a new course creation:
// true  - the new course is requested only and it is created after approval;
// false - the new course is created immediately, after filling this form.
$course_validation_feature = false;
if (api_get_setting('course_validation') === 'true' &&
    !api_is_platform_admin()
) {
    $course_validation_feature = true;
}

$htmlHeadXtra[] = '<script>
    function setFocus(){
        $("#title").focus();
    }
    $(window).on("load", function () {
        setFocus();
    });
</script>';

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PATH).'user_portal.php',
    'name' => get_lang('MyCourses'),
];

// Displaying the header.
$tool_name = $course_validation_feature ? get_lang('CreateCourseRequest') : get_lang('CreateSite');

$tpl = new Template($tool_name);

// Build the form.
$form = new FormValidator('add_course');

// Form title
$form->addElement('header', $tool_name);

// Title
$form->addElement(
    'text',
    'title',
    [
        get_lang('CourseName'),
        get_lang('Ex'),
    ],
    [
        'id' => 'title',
    ]
);
$form->applyFilter('title', 'html_filter');
$form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

if (!api_get_configuration_value('course_creation_form_set_course_category_mandatory')) {
    $form->addButtonAdvancedSettings('advanced_params');
    $form->addElement(
        'html',
        '<div id="advanced_params_options" style="display:none">'
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
    $categoryToAvoid = '';
    if (!api_is_platform_admin()) {
        $categoryToAvoid = api_get_configuration_value('course_category_code_to_use_as_model');
    }

    /** @var CourseCategory $category */
    foreach ($categories as $category) {
        $categoryCode = $category->getCode();
        if (!empty($categoryToAvoid) && $categoryToAvoid == $categoryCode) {
            continue;
        }
        $categoriesOptions[$categoryCode] = $category->__toString();
    }

    $form->addSelect(
        'category_code',
        get_lang('CourseFaculty'),
        $categoriesOptions
    );
}

if (api_get_configuration_value('course_creation_form_set_course_category_mandatory')) {
    $form->addRule('category_code', get_lang('ThisFieldIsRequired'), 'required');
    $form->addButtonAdvancedSettings('advanced_params');
    $form->addElement(
        'html',
        '<div id="advanced_params_options" style="display:none">'
    );
}

// Course code
if (!api_get_configuration_value('course_creation_form_hide_course_code')) {
    $form->addText(
        'wanted_code',
        [
            get_lang('Code'),
            get_lang('OnlyLettersAndNumbers'),
        ],
        '',
        [
            'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE,
            'pattern' => '[a-zA-Z0-9]+',
            'title' => get_lang('OnlyLettersAndNumbers'),
        ]
    );
    $form->applyFilter('wanted_code', 'html_filter');
    $form->addRule(
        'wanted_code',
        get_lang('Max'),
        'maxlength',
        CourseManager::MAX_COURSE_LENGTH_CODE
    );
}

// The teacher
$titular = &$form->addElement('hidden', 'tutor_name', '');
if ($course_validation_feature) {
    // Description of the requested course.
    $form->addElement(
        'textarea',
        'description',
        get_lang('Description'),
        ['rows' => '3']
    );

    // Objectives of the requested course.
    $form->addElement(
        'textarea',
        'objetives',
        get_lang('Objectives'),
        ['rows' => '3']
    );

    // Target audience of the requested course.
    $form->addElement(
        'textarea',
        'target_audience',
        get_lang('TargetAudience'),
        ['rows' => '3']
    );
}

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

// Exemplary content checkbox.
$form->addElement(
    'checkbox',
    'exemplary_content',
    null,
    get_lang('FillWithExemplaryContent')
);

if ($course_validation_feature) {
    // A special URL to terms and conditions that is set
    // in the platform settings page.
    $terms_and_conditions_url = trim(
        api_get_setting('course_validation_terms_and_conditions_url')
    );

    // If the special setting is empty,
    // then we may get the URL from Chamilo's module "Terms and conditions",
    // if it is activated.
    if (empty($terms_and_conditions_url)) {
        if (api_get_setting('allow_terms_conditions') === 'true') {
            $terms_and_conditions_url = api_get_path(WEB_CODE_PATH).'auth/inscription.php?legal';
        }
    }

    if (!empty($terms_and_conditions_url)) {
        // Terms and conditions to be accepted before sending a course request.
        $form->addElement(
            'checkbox',
            'legal',
            null,
            get_lang('IAcceptTermsAndConditions'),
            1
        );

        $form->addRule(
            'legal',
            get_lang('YouHaveToAcceptTermsAndConditions'),
            'required'
        );
        // Link to terms and conditions.
        $link_terms_and_conditions = '
            <script>
            function MM_openBrWindow(theURL, winName, features) { //v2.0
                window.open(theURL,winName,features);
            }
            </script>
        ';
        $link_terms_and_conditions .= Display::url(
            get_lang('ReadTermsAndConditions'),
            '#',
            ['onclick' => "javascript:MM_openBrWindow('$terms_and_conditions_url', 'Conditions', 'scrollbars=yes, width=800');"]
        );
        $form->addElement('label', null, $link_terms_and_conditions);
    }
}

$obj = new GradeModel();
$obj->fill_grade_model_select_in_form($form);

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

//Extra fields to show and mandatory
$setExtraFieldsMandatory = api_get_configuration_value('course_creation_form_set_extra_fields_mandatory');
$extraFieldsToShow = api_get_configuration_value('course_creation_by_teacher_extra_fields_to_show');
$fillExtraField = api_get_configuration_value('course_creation_user_course_extra_field_relation_to_prefill');
if (false !== $extraFieldsToShow && !empty($extraFieldsToShow['fields'])) {
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
        $extraFieldsToShow['fields'],
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

    // Relation to prefill course extra field with user extra field
    if (false !== $fillExtraField && !empty($fillExtraField['fields'])) {
        foreach ($fillExtraField['fields'] as $courseVariable => $userVariable) {
            $extraValue = UserManager::get_extra_user_data_by_field(api_get_user_id(), $userVariable);
            $values['extra_'.$courseVariable] = $extraValue[$userVariable];
        }
    }
    $htmlHeadXtra[] = '
    <script>
    $(function() {
        '.$extra['jquery_ready_content'].'
    });
    </script>';
}

$form->addElement('html', '</div>');

// Submit button.
$form->addButtonCreate($course_validation_feature ? get_lang('CreateThisCourseRequest') : get_lang('CreateCourseArea'));

// The progress bar of this form.
$form->addProgress();

// Set default values.
if (isset($_user['language']) && $_user['language'] != '') {
    $values['course_language'] = $_user['language'];
} else {
    $values['course_language'] = api_get_setting('platformLanguage');
}

$form->setDefaults($values);
$message = null;
$content = null;

// Validate the form.
if ($form->validate()) {
    $course_values = $form->exportValues();

    $wanted_code = isset($course_values['wanted_code']) ? $course_values['wanted_code'] : '';
    $category_code = isset($course_values['category_code']) ? $course_values['category_code'] : '';
    $title = $course_values['title'];
    $course_language = $course_values['course_language'];
    $exemplary_content = !empty($course_values['exemplary_content']);

    if ($course_validation_feature) {
        $description = $course_values['description'];
        $objetives = $course_values['objetives'];
        $target_audience = $course_values['target_audience'];
    }

    if ($wanted_code == '') {
        $wanted_code = CourseManager::generate_course_code(
            api_substr($title, 0, CourseManager::MAX_COURSE_LENGTH_CODE)
        );
    }

    // Check whether the requested course code has already been occupied.
    if (!$course_validation_feature) {
        $course_code_ok = !CourseManager::course_code_exists($wanted_code);
    } else {
        $course_code_ok = !CourseRequestManager::course_code_exists($wanted_code);
    }

    if ($course_code_ok) {
        if (!$course_validation_feature) {
            $params = $course_values;
            $params['title'] = $title;
            $params['exemplary_content'] = $exemplary_content;
            $params['wanted_code'] = $wanted_code;
            $params['course_category'] = $category_code;
            $params['course_language'] = $course_language;
            $params['gradebook_model_id'] = isset($course_values['gradebook_model_id']) ? $course_values['gradebook_model_id'] : null;
            $params['course_template'] = isset($course_values['course_template']) ? $course_values['course_template'] : '';

            include_once api_get_path(SYS_CODE_PATH).'lang/english/trad4all.inc.php';
            $file_to_include = api_get_path(SYS_CODE_PATH).'lang/'.$course_language.'/trad4all.inc.php';

            if (file_exists($file_to_include)) {
                include $file_to_include;
            }

            $course_info = CourseManager::create_course($params);

            if (!empty($course_info)) {
                /*
                $directory  = $course_info['directory'];
                $title      = $course_info['title'];

                // Preparing a confirmation message.
                $link = api_get_path(WEB_COURSE_PATH).$directory.'/';

                $tpl->assign('course_url', $link);
                $tpl->assign('course_title', Display::url($title, $link));
                $tpl->assign('course_id', $course_info['code']);

                $add_course_tpl = $tpl->get_template('create_course/add_course.tpl');
                $message = $tpl->fetch($add_course_tpl);*/
                $splash = api_get_setting('course_creation_splash_screen');
                if ($splash === 'true') {
                    $url = api_get_path(WEB_CODE_PATH);
                    $url .= 'course_info/start.php?'.api_get_cidreq_params($course_info['code']);
                    $url .= '&first=1';
                    header('Location: '.$url);
                    exit;
                } else {
                    $url = api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/';
                    header('Location: '.$url);
                    exit;
                }
            } else {
                $message = Display::return_message(
                    get_lang('CourseCreationFailed'),
                    'error',
                    false
                );
                // Display the form.
                $content = $form->returnForm();
            }
        } else {
            // Create a request for a new course.
            $request_id = CourseRequestManager::create_course_request(
                $wanted_code,
                $title,
                $description,
                $category_code,
                $course_language,
                $objetives,
                $target_audience,
                api_get_user_id(),
                $exemplary_content
            );

            if ($request_id) {
                $course_request_info = CourseRequestManager::get_course_request_info($request_id);
                $message = (is_array($course_request_info) ? '<strong>'.$course_request_info['code'].'</strong> : ' : '').get_lang('CourseRequestCreated');
                $message = Display::return_message(
                    $message,
                    'confirmation',
                    false
                );
                $message .= Display::tag(
                    'div',
                    Display::url(
                        get_lang('Enter'),
                        api_get_path(WEB_PATH).'user_portal.php',
                        ['class' => 'btn btn-default']
                    ),
                    ['style' => 'float: left; margin:0px; padding: 0px;']
                );
            } else {
                $message = Display::return_message(
                    get_lang('CourseRequestCreationFailed'),
                    'error',
                    false
                );
                // Display the form.
                $content = $form->returnForm();
            }
        }
    } else {
        $message = Display::return_message(
            get_lang('CourseCodeAlreadyExists'),
            'error',
            false
        );
        // Display the form.
        $content = $form->returnForm();
    }
} else {
    if (!$course_validation_feature) {
        $message = Display::return_message(get_lang('Explanation'));
        // If the donation feature is enabled, show a message with a donate button
        if (api_get_configuration_value('course_creation_donate_message_show') == true) {
            $button = api_get_configuration_value('course_creation_donate_link');
            if (!empty($button)) {
                $message .= Display::return_message(get_lang('DonateToTheProject').'<br /><br /><div style="display:block; margin-left:42%;">'.$button.'</div>', 'warning', false);
            }
        }
    }
    // Display the form.
    $content = $form->returnForm();
}

$tpl->assign('message', $message);
$tpl->assign('content', $content);
$template = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template);
