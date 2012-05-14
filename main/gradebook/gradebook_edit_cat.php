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
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/catform.class.php';
require_once api_get_path(LIBRARY_PATH).'grade_model.lib.php';

api_block_anonymous_users();
block_students();

$edit_cat = isset($_REQUEST['editcat']) ? $_REQUEST['editcat'] : '';


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


$catedit  = Category :: load($edit_cat);
$form     = new CatForm(CatForm :: TYPE_EDIT, $catedit[0], 'edit_cat_form');

if ($form->validate()) {
	$values = $form->getSubmitValues();
    if (isset($values['skills'])) {
        //$res    = $gradebook->update_skills_to_gradebook($values['hid_id'], $values['skills']);
    }
    
	$cat = new Category();
	$cat->set_id($values['hid_id']);
	$cat->set_name($values['name']);
	if (empty ($values['course_code'])) {
		$cat->set_course_code(null);
	}else {
		$cat->set_course_code($values['course_code']);
	}
    
    $cat->set_grade_model_id($values['grade_model_id']);
	$cat->set_description($values['description']);	
	$cat->set_skills($values['skills']);    
	$cat->set_user_id($values['hid_user_id']);
	$cat->set_parent_id($values['hid_parent_id']);
	$cat->set_weight($values['weight']);
    
	if ($values['hid_parent_id'] == 0 ) {
		$cat->set_certificate_min_score($values['certif_min_score']);
	}
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$cat->set_visible($visible);
	$cat->save();
    $parent_id = $cat->get_parent_id();
    
    if ($parent_id == 0) {        
        //do something           
        if (isset($values['grade_model_id']) && !empty($values['grade_model_id'])) {
            $obj = new GradeModel();                             
            $components = $obj->get_components($values['grade_model_id']);

            foreach ($components as $component) {
                $gradebook =  new Gradebook();
                $params = array();

                $params['name']             = $component['acronym'];
                $params['description']      = $component['title'];
                $params['user_id']          = api_get_user_id();
                $params['parent_id']        = $cat->get_id();
                $params['weight']           = $component['percentage']/100*$values['weight'];
                $params['session_id']       = api_get_session_id();
                $params['course_code']      = api_get_course_id();
                $params['grade_model_id']   = api_get_session_id();

                $gradebook->save($params);                
            }
        }
        
    }
    
    
	header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?editcat=&selectcat=' . $cat->get_parent_id());
	exit;
}
$selectcat = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';
$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$selectcat,'name' => get_lang('Gradebook'));
$this_section = SECTION_COURSES;
Display :: display_header(get_lang('EditCategory'));
$form->display();
Display :: display_footer();