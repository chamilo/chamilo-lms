<?php
/* For licensing terms, see /license.txt */
/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action
* @author Christian Fasanando <christian1827@gmail.com>
* @author Julio Montoya <gugli100@gmail.com> Bugfixes session support
* @package chamilo.course_progress
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array ('course_description', 'pedaSuggest', 'userInfo', 'admin');

// including files
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'thematic.lib.php';
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once 'thematic_controller.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
//require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

// current section
$this_section = SECTION_COURSES;

// protect a course script
api_protect_course_script(true);

// defining constants
define('ADD_THEMATIC_PLAN', 6);

// get actions
$actions = array('thematic_details', 'thematic_list', 'thematic_add', 'thematic_edit', 'thematic_copy', 'thematic_delete', 'moveup', 'movedown',
				 'thematic_plan_list', 'thematic_plan_add', 'thematic_plan_edit', 'thematic_plan_delete',
				 'thematic_advance_list', 'thematic_advance_add', 'thematic_advance_edit', 'thematic_advance_delete');

$action  = 'thematic_details';
if (isset($_GET['action']) && in_array($_GET['action'],$actions)) {
	$action = $_GET['action'];
}

if (isset($_POST['action']) && $_POST['action'] == 'thematic_delete_select') {
	$action = 'thematic_delete_select';
}

if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') {
	$action = 'thematic_details';
}

if ($action == 'thematic_details' || $action == 'thematic_list') {
	$_SESSION['thematic_control'] = $action;
}

// get thematic id
if (isset($_GET['thematic_id'])) {
	$thematic_id = intval($_GET['thematic_id']);
}

// get thematic plan description type
if (isset($_GET['description_type'])) {
	$description_type = intval($_GET['description_type']);
}

// instance thematic object for using like library here
$thematic = new Thematic();

// thematic controller object
$thematic_controller = new ThematicController();

if (!empty($thematic_id)) {
	// thematic data by id
	$thematic_data = $thematic->get_thematic_list($thematic_id);
}

// get default thematic plan title
$default_thematic_plan_title = $thematic->get_default_thematic_plan_title();


$htmlHeadXtra[] = api_get_jquery_ui_js(); 
 
$htmlHeadXtra[] = '<script language="javascript">

function datetime_by_attendance(selected_value) {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {},
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'thematic.ajax.php?a=get_datetime_by_attendance",
		data: "attendance_id="+selected_value+"&thematic_advance_id='.$thematic_id.'",
		success: function(datos) {
		 $("#div_datetime_attendance").html(datos);
		}
	});
}

$(document).ready(function() {
	
    $(".thematic_plan_opener").live("click", function() {
        var url = this.href;
        var dialog = $("#dialog");
                
        if ($("#dialog").length == 0) {
            dialog = $(\'<div id="dialog" style="display:hidden"></div> \').appendTo(\'body\');
        }
        
        // load remote content
        dialog.load(
                url,
                {},
                function(responseText, textStatus, XMLHttpRequest) {
                    dialog.dialog({
	                    width:	720, 
	                    height:	550, 
	                    modal:	true,
	                    buttons: {
								'.addslashes(get_lang('Save')).' : function() {
								var serialize_form_content = $("#thematic_plan_add").serialize();		
								$.ajax({
									type: "POST",
									url: "'.api_get_path(WEB_AJAX_PATH).'thematic.ajax.php?a=save_thematic_plan",
									data: serialize_form_content,
									success: function(data) {										
										var thematic_id = $("input[name=\"thematic_id\"]").val();
										$("#thematic_plan_"+thematic_id ).html(data);
									}
								});
								dialog.dialog("close");
							}
						}
	                });
				}
		);
        //prevent the browser to follow the link
        return false;
    }); 
});    


function update_done_thematic_advance(selected_value) {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {},
		type: "GET",
		url: "'.api_get_path(WEB_AJAX_PATH).'thematic.ajax.php?a=update_done_thematic_advance",
		data: "thematic_advance_id="+selected_value,
		success: function(data) {
			$("#div_result").html(data);
		}
	});

	// clean all radios
	
	for (var i=0; i< $(".done_thematic").length;i++) {
		var id_radio_thematic = $(".done_thematic").get(i).id;		
		$("#td_"+id_radio_thematic).css({"background-color":"#FFF"});
	}

	// set background to previous radios
	for (var i=0; i < $(".done_thematic").length;i++) {
		var id_radio_thematic = $(".done_thematic").get(i).id;
		$("#td_"+id_radio_thematic).css({"background-color":"#E5EDF9"});
		if ($(".done_thematic").get(i).value == selected_value) {
			break;
		}
	}

}

function check_per_attendance(obj) {
	if (obj.checked) {
		document.getElementById(\'div_datetime_by_attendance\').style.display=\'block\';
		document.getElementById(\'div_custom_datetime\').style.display=\'none\';
	} else {
		document.getElementById(\'div_datetime_by_attendance\').style.display=\'none\';
		document.getElementById(\'div_custom_datetime\').style.display=\'block\';
	}
}

function check_per_custom_date(obj) {
	if (obj.checked) {
		document.getElementById(\'div_custom_datetime\').style.display=\'block\';
		document.getElementById(\'div_datetime_by_attendance\').style.display=\'none\';
	} else {
		document.getElementById(\'div_custom_datetime\').style.display=\'none\';
		document.getElementById(\'div_datetime_by_attendance\').style.display=\'block\';
	}
}

</script>';

if ($action == 'thematic_list') {
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('ThematicControl'));
}
if ($action == 'thematic_add') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action='.$_SESSION['thematic_control'], 'name' => get_lang('ThematicControl'));
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('NewThematicSection'));
}
if ($action == 'thematic_edit') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action='.$_SESSION['thematic_control'], 'name' => get_lang('ThematicControl'));
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('EditThematicSection'));
}
if ($action == 'thematic_details') {
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('ThematicControl'));
}
if ($action == 'thematic_plan_list' || $action == 'thematic_plan_delete') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action='.$_SESSION['thematic_control'], 'name' => get_lang('ThematicControl'));
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('ThematicPlan').' ('.$thematic_data['title'].') ');	
}
if ($action == 'thematic_plan_add' || $action == 'thematic_plan_edit') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action='.$_SESSION['thematic_control'], 'name' => get_lang('ThematicControl'));
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic_id, 'name' => get_lang('ThematicPlan').' ('.$thematic_data['title'].')');
	if ($description_type >= ADD_THEMATIC_PLAN) {
		$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('NewBloc'));
	} else {
		$interbreadcrumb[] = array ('url' => '#', 'name' => $default_thematic_plan_title[$description_type]);
	}
}
if ($action == 'thematic_advance_list' || $action == 'thematic_advance_delete') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action='.$_SESSION['thematic_control'], 'name' => get_lang('ThematicControl'));
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('ThematicAdvance').' ('.$thematic_data['title'].')');
	
}
if ($action == 'thematic_advance_add' || $action == 'thematic_advance_edit') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action='.$_SESSION['thematic_control'], 'name' => get_lang('ThematicControl'));
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic_id, 'name' => get_lang('ThematicAdvance').' ('.$thematic_data['title'].')');
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('NewThematicAdvance'));
}

// Distpacher actions to controller
switch ($action) {
	case 'thematic_add'				:
	case 'thematic_edit'			:
	case 'thematic_delete'			:
	case 'thematic_delete_select'	:
    case 'thematic_copy'            :	
	case 'moveup'					:
	case 'movedown'					:    
        if (!api_is_allowed_to_edit(null,true)) {
        	api_not_allowed();
        }
	case 'thematic_list'			:
    case 'thematic_details'         :	
        $thematic_controller->thematic($action);
		break;	
	case 'thematic_plan_add'		:
	case 'thematic_plan_edit'		:
	case 'thematic_plan_delete'		:	
        if (!api_is_allowed_to_edit(null,true)) {
            api_not_allowed();
        }	
    case 'thematic_plan_list'       :
        $thematic_controller->thematic_plan($action);
        break;
	
	case 'thematic_advance_add'		:
	case 'thematic_advance_edit'	:
	case 'thematic_advance_delete'	:
        if (!api_is_allowed_to_edit(null,true)) {
            api_not_allowed();            
        }
    case 'thematic_advance_list'    : 
        $thematic_controller->thematic_advance($action);
        break;
}
