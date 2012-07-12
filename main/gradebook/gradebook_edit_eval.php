<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/evalform.class.php';
api_block_anonymous_users();
block_students();

$evaledit = Evaluation :: load($_GET['editeval']);
if ($evaledit[0]->is_locked() && !api_is_platform_admin()) {
    api_not_allowed();
}
$form = new EvalForm(EvalForm :: TYPE_EDIT, $evaledit[0], null, 'edit_eval_form',null,api_get_self() . '?editeval=' . Security::remove_XSS($_GET['editeval']));
if ($form->validate()) {
	$values = $form->exportValues();
	$eval = new Evaluation();
	$eval->set_id($values['hid_id']);
	$eval->set_name($values['name']);
	$eval->set_description($values['description']);
	$eval->set_user_id($values['hid_user_id']);
	$eval->set_course_code($values['hid_course_code']);
	$eval->set_category_id($values['hid_category_id']);
        
    $parent_cat = Category :: load($values['hid_category_id']);                
    
    /*$final_weight = null;
    if ($parent_cat[0]->get_parent_id() == 0) {
        $final_weight = $values['weight_mask'];    
    } else {
        $cat = Category :: load($parent_cat[0]->get_parent_id());
        $global_weight = $cat[0]->get_weight();
        $final_weight = $values['weight_mask']/$global_weight*$parent_cat[0]->get_weight();        
    }*/   
    $final_weight = $values['weight_mask'];
    
    $eval->set_weight($final_weight);
    
	$eval->set_max($values['max']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$eval->set_visible($visible);
	$eval->save();
	header('Location: '.$_SESSION['gradebook_dest'].'?editeval=&selectcat=' . $eval->get_category_id());
	exit;
}
$selectcat_inter=isset($_GET['selectcat'])?Security::remove_XSS($_GET['selectcat']):'';
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.$selectcat_inter,
	'name' => get_lang('Gradebook'
));

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

Display :: display_header(get_lang('EditEvaluation'));
$form->display();
Display :: display_footer();
