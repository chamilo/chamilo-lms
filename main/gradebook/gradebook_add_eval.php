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
require_once 'lib/fe/evalform.class.php';
api_block_anonymous_users();
block_students();

$select_cat=isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';
$is_allowedToEdit = $is_courseAdmin;
$evaladd = new Evaluation();
$evaladd->set_user_id($_user['user_id']);
if (isset ($_GET['selectcat']) && (!empty ($_GET['selectcat']))) {
	$evaladd->set_category_id($_GET['selectcat']);
	$cat = Category :: load($_GET['selectcat']);
	$evaladd->set_course_code($cat[0]->get_course_code());
} else {
	$evaladd->set_category_id(0);
}
$form = new EvalForm(EvalForm :: TYPE_ADD, $evaladd, null, 'add_eval_form',null,api_get_self() . '?selectcat=' .$select_cat);
if ($form->validate()) {
	$values = $form->exportValues();
	$eval = new Evaluation();
	$eval->set_name($values['name']);
	$eval->set_description($values['description']);
	$eval->set_user_id($values['hid_user_id']);
	
	if (!empty ($values['hid_course_code'])) {
		$eval->set_course_code($values['hid_course_code']);
	}
	
	//Always add the gradebook to the course
	$eval->set_course_code(api_get_course_id());
	
	$eval->set_category_id($values['hid_category_id']);
	$eval->set_weight($values['weight']);
	
	$eval->set_max($values['max']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$eval->set_visible($visible);
	$eval->add();
	if ($eval->get_course_code() == null) {
		if ($values['adduser'] == 1) {
            //Disabling code when course code is null see issue #2705
			//header('Location: gradebook_add_user.php?selecteval=' . $eval->get_id());
			exit;
		} else {
			header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=' . $eval->get_category_id());
			exit;
		}
	} else {
		$val_addresult=isset($values['addresult'])?$values['addresult']:null;
		if ($val_addresult == 1) {
			header('Location: gradebook_add_result.php?selecteval=' . $eval->get_id());
			exit;
		} else {
			header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=' . $eval->get_category_id());
			exit;
		}
	}
}

$interbreadcrumb[] = array (
	'url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$select_cat,
	'name' => get_lang('Gradebook'
));
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready( function() {

    $("#hid_category_id").change(function(){
        
       $("#hid_category_id option:selected").each(function () {
           var cat_id = $(this).val();
            $.ajax({ 
                url: "'.api_get_path(WEB_AJAX_PATH).'gradebook.ajax.php?a=get_gradebook_weight", 
                data: "cat_id="+cat_id,
                success: function(return_value) {
                    if (return_value != 0 ) {
                        $("#max_weight").html(return_value);                                             
                    }                    
                },            
            });    
       });
    });
});
</script>';

Display :: display_header(get_lang('NewEvaluation'));
if ($evaladd->get_course_code() == null) {
	Display :: display_normal_message(get_lang('CourseIndependentEvaluation'),false);
}
$form->display();
Display :: display_footer();