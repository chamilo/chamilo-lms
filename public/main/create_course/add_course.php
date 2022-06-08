<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;

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
$courseCategoriesRepo = $em->getRepository(CourseCategory::class);
// Get all possible teachers.
$accessUrlId = api_get_current_access_url_id();

// "Course validation" feature. This value affects the way of a new course creation:
// true  - the new course is requested only and it is created after approval;
// false - the new course is created immediately, after filling this form.
$course_validation_feature = false;
if ('true' === api_get_setting('course_validation') &&
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

// Displaying the header.
$tool_name = $course_validation_feature ? get_lang('Create a course request') : get_lang('Add a new course');

$tpl = new Template($tool_name);

// Build the form.
$form = new FormValidator('add_course');

// Form title
$form->addElement('header', $tool_name);

// Title
$form->addText(
    'title',
    [
        get_lang('Course Name'),
        get_lang('Write a short and striking course name, For example: Innovation Management'),
    ],
    true);
$form->applyFilter('title', 'html_filter');

$form->addButtonAdvancedSettings('advanced_params');
$form->addElement(
    'html',
    '<div id="advanced_params_options" style="display:none">'
);

// Picture
$form->addFile(
    'picture',
    [
        get_lang('Add a picture'),
    ],
    [
        'id' => 'picture',
        'class' => 'picture-form',
        'crop_image' => true,
    ]
);

$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);

$countCategories = $courseCategoriesRepo->countAllInAccessUrl(
    $accessUrlId,
    api_get_configuration_value('allow_base_course_category')
);

if ($countCategories >= 100) {
    // Category code
    $url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';
    $form->addElement(
        'select_ajax',
        'category_id',
        get_lang('Category'),
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
        'category_id',
        get_lang('Category'),
        $categoriesOptions
    );
}

// Course code
$form->addText(
    'wanted_code',
    [
        get_lang('Course code'),
        get_lang('Only letters (a-z) and numbers (0-9)'),
    ],
    '',
    [
        'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE,
        'pattern' => '[a-zA-Z0-9]+',
        'title' => get_lang('Only letters (a-z) and numbers (0-9)'),
    ]
);
$form->applyFilter('wanted_code', 'html_filter');
$form->addRule(
    'wanted_code',
    get_lang('max. 20 characters, e.g. <i>INNOV21</i>'),
    'maxlength',
    CourseManager::MAX_COURSE_LENGTH_CODE
);

// The teacher
$form->addElement('hidden', 'tutor_name', '');
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
        get_lang('Target audience'),
        ['rows' => '3']
    );
}

// Course language.
$languages = api_get_languages();
if (1 === count($languages)) {
    // If there's only one language available, there's no point in asking
    $form->addElement('hidden', 'course_language', $languages['folder'][0]);
} else {
    $form->addSelectLanguage(
        'course_language',
        get_lang('Language'),
        [],
        ['style' => 'width:150px']
    );
}

// Exemplary content checkbox.
$form->addElement(
    'checkbox',
    'exemplary_content',
    null,
    get_lang('Fill with demo content')
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
        if ('true' === api_get_setting('allow_terms_conditions')) {
            $terms_and_conditions_url = api_get_path(WEB_CODE_PATH).'auth/inscription.php?legal';
        }
    }

    if (!empty($terms_and_conditions_url)) {
        // Terms and conditions to be accepted before sending a course request.
        $form->addElement(
            'checkbox',
            'legal',
            null,
            get_lang('I have read and I accept the Terms and Conditions'),
            1
        );

        $form->addRule(
            'legal',
            get_lang('You have to accept our Terms and Conditions to proceed.'),
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
            get_lang('Read the Terms and Conditions'),
            '#',
            ['onclick' => "javascript:MM_openBrWindow('$terms_and_conditions_url', 'Conditions', 'scrollbars=yes, width=800');"]
        );
        $form->addElement('label', null, $link_terms_and_conditions);
    }
}

$obj = new GradeModel();
$obj->fill_grade_model_select_in_form($form);

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

$form->addElement('html', '</div>');

// Submit button.
$form->addButtonCreate($course_validation_feature ? get_lang('Create this course request') : get_lang('Create this course'));

// The progress bar of this form.
$form->addProgress();

// Set default values.
if (isset($_user['language']) && '' != $_user['language']) {
    $values['course_language'] = $_user['language'];
} else {
    $values['course_language'] = api_get_setting('platformLanguage');
}

$form->setDefaults($values);
$message = null;
$formContent = null;

// Validate the form.
if ($form->validate()) {
    $course_values = $form->exportValues();

    $wanted_code = $course_values['wanted_code'];
    $category_code = isset($course_values['category_id']) ? (int) $course_values['category_id'] : '';
    $title = $course_values['title'];
    $course_language = $course_values['course_language'];
    $exemplary_content = !empty($course_values['exemplary_content']);

    if ($course_validation_feature) {
        $description = $course_values['description'];
        $objetives = $course_values['objetives'];
        $target_audience = $course_values['target_audience'];
    }

    if ('' == $wanted_code) {
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
            $params = [];
            $params['title'] = $title;
            $params['exemplary_content'] = $exemplary_content;
            $params['wanted_code'] = $wanted_code;
            $params['course_id'] = $category_code;
            $params['course_language'] = $course_language;
            $params['gradebook_model_id'] = isset($course_values['gradebook_model_id']) ? $course_values['gradebook_model_id'] : null;
            $params['course_template'] = isset($course_values['course_template']) ? $course_values['course_template'] : '';

            $course = CourseManager::create_course($params);

            if (null !== $course) {
                $request = Container::getRequest();

                if ($request->files->has('picture')) {
                    $uploadFile = $request->files->get('picture');
                    // @todo add in repository
                    $file = Container::getIllustrationRepository()->addIllustration(
                        $course,
                        api_get_user_entity(api_get_user_id()),
                        $uploadFile
                    );
                    if ($file) {
                        $file->setCrop($course_values['picture_crop_result_for_resource']);
                        $em->persist($file);
                        $em->flush();
                    }
                }

                $splash = api_get_setting('course_creation_splash_screen');
                if ('true' === $splash) {
                    $url = Container::getRouter()->generate(
                        'chamilo_core_course_welcome',
                        ['cid' => $course->getId()]
                    );
                    header('Location: '.$url);
                    exit;
                } else {
                    $url = api_get_course_url($course->getId());
                    header('Location: '.$url);
                    exit;
                }
            } else {
                $message = Display::return_message(
                    get_lang('The course has not been created due to an internal error.'),
                    'error',
                    false
                );
                // Display the form.
                $formContent = $form->returnForm();
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
                $message = (is_array($course_request_info) ? '<strong>'.$course_request_info['code'].'</strong> : ' : '').get_lang('Your request for a new course has been sent successfully. You may receive a reply soon, within one or two days.');
                $message = Display::return_message(
                    $message,
                    'confirmation',
                    false
                );
                $message .= Display::tag(
                    'div',
                    Display::url(
                        get_lang('Back to courses list'),
                        api_get_path(WEB_PATH).'user_portal.php',
                        ['class' => 'btn btn--primary']
                    ),
                    ['style' => 'float: left; margin:0px; padding: 0px;']
                );
            } else {
                $message = Display::return_message(
                    get_lang('The course request has not been created due to an internal error.'),
                    'error',
                    false
                );
                // Display the form.
                $formContent = $form->returnForm();
            }
        }
    } else {
        $message = Display::return_message(
            get_lang('CourseCourse codeAlreadyExists'),
            'error',
            false
        );
        // Display the form.
        $formContent = $form->returnForm();
    }
} else {
    if (!$course_validation_feature) {
        $message = Display::return_message(get_lang('Once you click on "Create a course", a course is created with a section for Tests, Project based learning, Assessments, Courses, Dropbox, Agenda and much more. Logging in as teacher provides you with editing privileges for this course.'));
        // If the donation feature is enabled, show a message with a donate button
        if (true == api_get_configuration_value('course_creation_donate_message_show')) {
            $button = api_get_configuration_value('course_creation_donate_link');
            if (!empty($button)) {
                $message .= Display::return_message(get_lang('DonateToTheProject').'<br /><br /><div style="display:block; margin-left:42%;">'.$button.'</div>', 'warning', false);
            }
        }
    }
    // Display the form.
    $formContent = $form->returnForm();
}

$tpl->assign('form', $formContent);
$layout = $tpl->fetch($tpl->get_template('create_course/add_course.html.twig'));
$tpl->assign('message', $message);
$tpl->assign('content', $layout);
$tpl->display_one_col_template();
