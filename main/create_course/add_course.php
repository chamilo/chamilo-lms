<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows professors and administrative staff to create course sites.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Roan Embrechts, refactoring
 * @package chamilo.create_course
 * "Course validation" feature:
 * @author Jose Manuel Abuin Mosquera <chema@cesga.es>, Centro de Supercomputacion de Galicia
 * "Course validation" feature, technical adaptation for Chamilo 1.8.8:
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>
 */
/**
 * Code
 */

// Name of the language file that needs to be included.
$language_file = array('create_course', 'registration','admin','exercice', 'course_description', 'course_info');

// Flag forcing the "current course" reset.
$cidReset = true;

// Including the global initialization file.
require_once '../inc/global.inc.php';

// Section for the tabs.
$this_section = SECTION_COURSES;

// "Course validation" feature. This value affects the way of a new course creation:
// true  - the new course is requested only and it is created after approval;
// false - the new course is created immedialely, after filling this form.
$course_validation_feature = api_get_setting('course_validation') == 'true';

// Require additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(CONFIGURATION_PATH).'course_info.conf.php';

if ($course_validation_feature) {
    require_once api_get_path(LIBRARY_PATH).'course_request.lib.php';
    require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
}

$htmlHeadXtra[] = '<script type="text/javascript">
    function setFocus(){
        $("#title").focus();
    }
    $(window).load(function () {
        setFocus();
    });    
    
    function advanced_parameters() {
        if(document.getElementById(\'options\').style.display == \'none\') {
            document.getElementById(\'options\').style.display = \'block\';
            document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
        } else {
            document.getElementById(\'options\').style.display = \'none\';
            document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
        }
    }
</script>';

$interbreadcrumb[] = array('url' => api_get_path(WEB_PATH).'user_portal.php', 'name' => get_lang('MyCourses'));

// Displaying the header.
$tool_name = $course_validation_feature ? get_lang('CreateCourseRequest') : get_lang('CreateSite');

$tpl = new Template($tool_name);

if (api_get_setting('allow_users_to_create_courses') == 'false' && !api_is_platform_admin()) {
    api_not_allowed(true);
}

// Check access rights.
if (!api_is_allowed_to_create_course()) {
    api_not_allowed(true);
    exit;
}

// Build the form.
$form = new FormValidator('add_course');

// Form title
$form->addElement('header', $tool_name);

// Title
$form->addElement('text', 'title', array(get_lang('CourseName'), get_lang('Ex')), array('class' => 'span6', 'id' => 'title'));
$form->applyFilter('title', 'html_filter');
$form->addRule('title', get_lang('ThisFieldIsRequired'), 'required');

$advanced = '<a href="javascript://" onclick=" return advanced_parameters()"><span id="img_plus_and_minus"><div style="vertical-align:top;" ><img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</div></span></a>';
$form -> addElement('advanced_settings',$advanced);
$form -> addElement('html','<div id="options" style="display:none">');

// Course category.
$categories_select = $form->addElement('select', 'category_code', array(get_lang('Fac'), get_lang('TargetFac')), array(), array('id'=> 'category_code','class'=>'chzn-select', 'style'=>'width:350px'));
$form->applyFilter('category_code', 'html_filter');
$categories_select->addOption('-','');
CourseManager::select_and_sort_categories($categories_select);


// Course code
$form->add_textfield('wanted_code', array(get_lang('Code'), get_lang('OnlyLettersAndNumbers')), '', array('class' => 'span3', 'maxlength' => MAX_COURSE_LENGTH_CODE));
$form->applyFilter('wanted_code', 'html_filter');
$form->addRule('wanted_code', get_lang('Max'), 'maxlength', MAX_COURSE_LENGTH_CODE);

/*if ($course_validation_feature) {
    $form->addRule('wanted_code', get_lang('ThisFieldIsRequired'), 'required');
}*/

// The teacher
//get_lang('ExplicationTrainers')
$titular = & $form->add_textfield('tutor_name', array(get_lang('Professor'), null), null, array('size' => '60', 'disabled' => 'disabled'));
//$form->applyFilter('tutor_name', 'html_filter');

if ($course_validation_feature) {

    // Description of the requested course.
    $form->addElement('textarea', 'description', get_lang('Description'), array('class' => 'span6', 'rows' => '3'));
    //$form->addRule('description', get_lang('ThisFieldIsRequired'), 'required');

    // Objectives of the requested course.
    $form->addElement('textarea', 'objetives', get_lang('Objectives'), array('class' => 'span6', 'rows' => '3'));
    //$form->addRule('objetives', get_lang('ThisFieldIsRequired'), 'required');

    // Target audience of the requested course.
    $form->addElement('textarea', 'target_audience', get_lang('TargetAudience'), array('class' => 'span6', 'rows' => '3'));
    //$form->addRule('target_audience', get_lang('ThisFieldIsRequired'), 'required');
}

// Course language.
$form->addElement('select_language', 'course_language', get_lang('Ln'), array(), array('style'=>'width:150px'));
$form->applyFilter('select_language', 'html_filter');

// Exemplary content checkbox.
$form->addElement('checkbox', 'exemplary_content', null, get_lang('FillWithExemplaryContent'));

if ($course_validation_feature) {

    // A special URL to terms and conditions that is set in the platform settings page.
    $terms_and_conditions_url = trim(api_get_setting('course_validation_terms_and_conditions_url'));

    // If the special setting is empty, then we may get the URL from Chamilo's module "Terms and conditions", if it is activated.
    if (empty($terms_and_conditions_url)) {
        if (api_get_setting('allow_terms_conditions') == 'true') {
            $terms_and_conditions_url = api_get_path(WEB_CODE_PATH).'auth/inscription.php?legal';
        }
    }

    if (!empty($terms_and_conditions_url)) {
        // Terms and conditions to be accepted before sending a course request.
        $form->addElement('checkbox', 'legal', get_lang('IAcceptTermsAndConditions'), '', 1);
        $form->addRule('legal', get_lang('YouHaveToAcceptTermsAndConditions'), 'required');
        // Link to terms and conditions.
        $link_terms_and_conditions = '<script type="text/JavaScript">
        <!--
        function MM_openBrWindow(theURL,winName,features) { //v2.0
            window.open(theURL,winName,features);
        }
        //-->
        </script>
        <div class="row">
        <div class="formw">
        <a href="#" onclick="javascript: MM_openBrWindow(\''.$terms_and_conditions_url.'\',\'Conditions\',\'scrollbars=yes, width=800\')">';
        $link_terms_and_conditions .= get_lang('ReadTermsAndConditions').'</a></div></div>';
        $form->addElement('html', $link_terms_and_conditions);
    }
}

$form -> addElement('html','</div>');

// Submit button.
$form->addElement('style_submit_button', null, $course_validation_feature ? get_lang('CreateThisCourseRequest') : get_lang('CreateCourseArea'), 'class="add"');

// The progress bar of this form.
$form->add_progress_bar();

// Set default values.
if (isset($_user['language']) && $_user['language'] != '') {
    $values['course_language'] = $_user['language'];
} else {
    $values['course_language'] = api_get_setting('platformLanguage');
}
$values['tutor_name'] = api_get_person_name($_user['firstName'], $_user['lastName'], null, null, $values['course_language']);

$form->setDefaults($values);

// Validate the form.
if ($form->validate()) {
    $course_values = $form->exportValues();
    
    $wanted_code        = $course_values['wanted_code'];
    //$tutor_name         = $course_values['tutor_name'];
    $category_code      = $course_values['category_code'];
    $title              = $course_values['title'];
    $course_language    = $course_values['course_language'];
    $exemplary_content  = !empty($course_values['exemplary_content']);

    if ($course_validation_feature) {
        $description     = $course_values['description'];
        $objetives       = $course_values['objetives'];
        $target_audience = $course_values['target_audience'];
        $status           = '0';
    }

    if ($wanted_code == '') {
        $wanted_code = generate_course_code(api_substr($title, 0, MAX_COURSE_LENGTH_CODE));
    }
    
    // Check whether the requested course code has already been occupied.
    if (!$course_validation_feature) {
        $course_code_ok = !CourseManager::course_code_exists($wanted_code);
    } else {
        $course_code_ok = !CourseRequestManager::course_code_exists($wanted_code);
    }
    
    if ($course_code_ok) {
        if (!$course_validation_feature) {       
              
            $params = array();
            
            $params['title']                = $title;
            $params['exemplary_content']    = $exemplary_content;
            $params['wanted_code']          = $wanted_code;            
            $params['category_code']        = $category_code;
            $params['course_language']      = $course_language;            
             
            $course_info = CourseManager::create_course($params); 
     
            if (!empty($course_info)) {
                
                $directory  = $course_info['directory'];          
                $title      = $course_info['title'];  

                // Preparing a confirmation message.
                $link = api_get_path(WEB_COURSE_PATH).$directory.'/';
             
                $tpl->assign('course_url', $link);                
                $tpl->assign('course_title', Display::url($title, $link));   
                $tpl->assign('course_id', $course_info['code']);
                $add_course_tpl = $tpl->get_template('create_course/add_course.tpl');
                $message = $tpl->fetch($add_course_tpl);    
                
            } else {                
                $message = Display :: return_message(get_lang('CourseCreationFailed'), 'error', false);
                // Display the form.
                $content = $form->return_form();
            }
        } else {
            // Create a request for a new course.
            $request_id = CourseRequestManager::create_course_request($wanted_code, $title, $description, $category_code, $course_language, $objetives, $target_audience, api_get_user_id(), $exemplary_content);

            if ($request_id) {
                $course_request_info = CourseRequestManager::get_course_request_info($request_id);
                $message = (is_array($course_request_info) ? '<strong>'.$course_request_info['code'].'</strong> : ' : '').get_lang('CourseRequestCreated');
                $message = Display :: return_message($message, 'confirmation', false);
                $message .=  '<div style="float: left; margin:0px; padding: 0px;">' .
                    '<a class="btn" href="'.api_get_path(WEB_PATH).'user_portal.php">'.get_lang('Enter').'</a>' .
                    '</div>';
            } else {
                $message = Display :: return_message(get_lang('CourseRequestCreationFailed'), 'error', false);
                // Display the form.
                $content = $form->return_form();
            }
        }
    } else {
        $message = Display :: return_message(get_lang('CourseCodeAlreadyExists'), 'error', false);
        // Display the form.
        $content = $form->return_form();
    }

} else {
    if (!$course_validation_feature) {
        $message = Display :: return_message(get_lang('Explanation'));
    }    
    // Display the form.
    $content = $form->return_form();    
}

      
                
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$template = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template);
