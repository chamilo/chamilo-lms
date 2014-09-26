<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.admin
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Form\CourseType;

$cidReset = true;
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$tool_name = get_lang('AddCourse');
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'course_list.php', 'name' => get_lang('CourseList'));

/* MAIN CODE */

global $_configuration;

$group = Container::getGroupManager()->findGroupByName('teachers');

$teachers = array();
$users = $group->getUsers();
/** @var Chamilo\UserBundle\Entity\User $user */
foreach ($users as $user) {
    $teachers[$user->getId()] = $user->getCompleteName();
}

// Build the form.
$form = new FormValidator('update_course');
$form->addElement('header', $tool_name);

// Title
$form->add_textfield('title', get_lang('Title'), true, array ('class' => 'span6'));
$form->applyFilter('title', 'html_filter');
$form->applyFilter('title', 'trim');

// Code
$form->add_textfield('visual_code', array(get_lang('CourseCode'), get_lang('OnlyLettersAndNumbers')) , false, array('class' => 'span3', 'maxlength' => CourseManager::MAX_COURSE_LENGTH_CODE));

$form->applyFilter('visual_code', 'api_strtoupper');
$form->applyFilter('visual_code', 'html_filter');
$form->addRule('visual_code', get_lang('Max'), 'maxlength', CourseManager::MAX_COURSE_LENGTH_CODE);

$form->addElement('select', 'course_teachers', get_lang('CourseTeachers'), $teachers, ' id="course_teachers" class="chzn-select"  style="width:350px" multiple="multiple" ');
$form->applyFilter('course_teachers', 'html_filter');

//This function fills the category_code select ...
$url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_category';

$form->addElement(
    'select_ajax',
    'category_code',
    get_lang('CourseFaculty'),
    null,
    array(
        'url' => $url
    //    'formatResult' => 'function(item) { return item.name + "'" +item.code; }'
    )
);

// Course department
$form->add_textfield('department_name', get_lang('CourseDepartment'), false, array ('size' => '60'));
$form->applyFilter('department_name', 'html_filter');
$form->applyFilter('department_name', 'trim');

// Department URL
$form->add_textfield('department_url', get_lang('CourseDepartmentURL'), false, array ('size' => '60'));
$form->applyFilter('department_url', 'html_filter');

$form->addElement('select_language', 'course_language', get_lang('CourseLanguage'));
$form->applyFilter('select_language', 'html_filter');

$form->addElement('checkbox', 'exemplary_content', '', get_lang('FillWithExemplaryContent'));

$group = array();
$group[]= $form->createElement('radio', 'visibility', get_lang('CourseAccess'), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityHidden'), COURSE_VISIBILITY_HIDDEN);

$form->addGroup($group,'', get_lang('CourseAccess'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[]= $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group,'', get_lang('Subscription'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[]= $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group,'', get_lang('Unsubscription'), '<br />');

$form->addElement('text','disk_quota',array(get_lang('CourseQuota'), null, get_lang('MB')));
$form->addRule('disk_quota', get_lang('ThisFieldShouldBeNumeric'), 'numeric');

$obj = new GradeModel();
$obj->fill_grade_model_select_in_form($form);

$form->add_progress_bar();
$form->addElement('style_submit_button', 'submit', get_lang('CreateCourse'), 'class="add"');

// Set some default values.
$values['course_language']  = Container::getTranslator()->getLocale();

//    api_get_setting('platformLanguage');
$values['disk_quota']       = round(api_get_setting('document.default_document_quotum')/1024/1024, 1);

$default_course_visibility = api_get_setting('course.courses_default_creation_visibility');

if (isset($default_course_visibility)) {
    $values['visibility'] = api_get_setting('course.courses_default_creation_visibility');
} else {
    $values['visibility'] = COURSE_VISIBILITY_OPEN_PLATFORM;
}

$values['subscribe'] = 1;
$values['unsubscribe'] = 0;

$form->setDefaults($values);
/*
// Validate the form
if ($form->validate()) {
    $course          = $form->exportValues();
    //$teacher_id      = $course['tutor_id'];
    $course_teachers = isset($course['course_teachers']) ? $course['course_teachers'] : array();

    $course['disk_quota'] = $course['disk_quota']*1024*1024;

    $course['exemplary_content']    = empty($course['exemplary_content']) ? false : true;
    $course['teachers']             = $course_teachers;
    //$course['user_id']              = $teacher_id;
    $course['wanted_code']          = $course['visual_code'];
    $course['gradebook_model_id']   = isset($course['gradebook_model_id']) ? $course['gradebook_model_id'] : null;
    // Fixing category code
    $course['course_category'] = $course['category_code'];
    $course_info = CourseManager::create_course($course);

    header('Location: course_list.php'.($course_info===false?'?action=show_msg&warn='.api_get_last_failure():''));
    exit;
}*/

// Display the form.
$content = $form->return_form();

//echo $content;

$em = Container::getEntityManager();
$request = Container::getRequest();

$course = new Course();
$builder = Container::getFormFactory()->createBuilder(
    new CourseType(),
    $course
);

$form = $builder->getForm();
$form->handleRequest($request);

if ($form->isValid()) {
    $course = $form->getData();
    $em->persist($course);
    $em->flush();
    Container::addFlash(get_lang('Updated'));
    $url = Container::getRouter()->generate(
        'main',
        array('name' => 'admin/course_list.php')
    );
    header('Location: '.$url);
    exit;
}

echo Container::getTemplate()->render(
    'ChamiloCoreBundle:Legacy:form.html.twig',
    array(
        'form' => $form->createView(),
        'url' => api_get_self()
    )
);
