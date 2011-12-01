<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file = 'gradebook';
require_once '../inc/global.inc.php';
$_in_course = true;
$course_code = api_get_course_id();
if ( empty ($course_code ) ) {
	$_in_course = false;
}

require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/catform.class.php';
api_block_anonymous_users();
block_students();

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';

$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready(function () {
    $("#skills").fcbkcomplete({
        json_url: "'.api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=find_skills",
        cache: false,
        filter_case: false,
        filter_hide: true,
        complete_text:"'.get_lang('StartToType').'",
        firstselected: true,
        //onremove: "testme",
        onselect:"check_skills",
        filter_selected: true,
        newel: true
    });
    
    $(".closebutton").click(function() {
        var skill_id = ($(this).attr("id")).split("_")[1];        
        if (skill_id) {            
            $.ajax({ 
                url: "'.api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=remove_skill", 
                data: "gradebook_id='.$edit_cat.'&skill_id="+skill_id,
                success: function(return_value) {                    
                    if (return_value == 1 ) {
                            $("#skill_"+skill_id).remove();
                    }
                }        
            });
        }
    });
});

function check_skills() {
    //selecting only selected users
    $("#skills option:selected").each(function() {
        var skill_id = $(this).val();        
        if (skill_id != "" ) {            
            $.ajax({ 
                url: "'.api_get_path(WEB_AJAX_PATH).'skill.ajax.php?a=skill_exists", 
                data: "skill_id="+skill_id,
                success: function(return_value) {                    
                if (return_value == 0 ) {
                        alert("'.get_lang('SkillDoesNotExist').'");                                                
                        //Deleting select option tag
                        $("#skills option[value="+skill_id+"]").remove();                        
                        //Deleting holder
                        $(".holder li").each(function () {
                            if ($(this).attr("rel") == skill_id) {
                                $(this).remove();
                            }
                        });                        
                    }                    
                },            
            });                
        }        
    });
}
</script>';

$get_select_cat = intval($_GET['selectcat']);

$catadd = new Category();
$my_user_id = api_get_user_id();
$catadd->set_user_id($my_user_id);
$catadd->set_parent_id($get_select_cat);
$catcourse = Category :: load ($get_select_cat);

if ($_in_course) {
	$catadd->set_course_code($course_code);
} else {
	$catadd->set_course_code($catcourse[0]->get_course_code());
}

$catadd->set_course_code(api_get_course_id());

$models                  = api_get_settings_options('grading_model');
$course_grading_model_id = api_get_course_setting('course_grading_model');
$grading_model = '';
if (!empty($course_grading_model_id)) {
    foreach($models as $option) {           
        if (intval($option['id']) == $course_grading_model_id) {
        $grading_model = $option['value'];
        }
    }
}       

$grading_contents = api_grading_model_functions($grading_model, 'to_array');




$form = new CatForm(CatForm :: TYPE_ADD, $catadd, 'add_cat_form', null, api_get_self() . '?selectcat='.$get_select_cat);

if ($form->validate()) {
	$values = $form->exportValues();
	$select_course=isset($values['select_course']) ? $values['select_course'] : array();
	$cat = new Category();
	if ($values['hid_parent_id'] == '0') {
		if ($select_course == 'COURSEINDEPENDENT') {
			$cat->set_name($values['name']);
			$cat->set_course_code(null);
		} else {
			$cat->set_course_code($select_course);
			$cat->set_name($values['name']);
		}
	} else {
		$cat->set_name($values['name']);
		$cat->set_course_code($values['course_code']);//?
	}
	//Always add the gradebook to the course
	$cat->set_course_code(api_get_course_id());	
    
    $cat->set_skills($values['skills']);
	
	$cat->set_description($values['description']);
	$cat->set_user_id($values['hid_user_id']);
	$cat->set_parent_id($values['hid_parent_id']);
	$cat->set_weight($values['weight']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$cat->set_visible($visible);
	$result = $cat->add();	
	header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?addcat=&selectcat=' . $cat->get_parent_id());
	exit;
}

if ( !$_in_course ) {
	$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$get_select_cat,'name' => get_lang('Gradebook'));
}
$interbreadcrumb[]= array (	'url' =>'index.php','name' => get_lang('ToolGradebook'));
Display :: display_header(get_lang('NewCategory'));

$display_form = true;

if (!empty($grading_contents)) {
    $count_items = count($grading_contents['items']);
    $cats  = Category :: load(null, null, $course_code, null, null, $session_id, false); //already init
    $cats_count = count($cats) - 1 ;         
        
    if ($cats_count >= $count_items) {
        Display::display_warning_message(get_lang('CheckYourGradingModelValues'));
        $display_form = false;
    }
}
if ($display_form)
    $form->display();

Display :: display_footer();
