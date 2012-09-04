<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.admin
* 	@todo use formvalidator for the form, remove all the select harcoded values
*/

// name of the language file that needs to be included
$language_file = 'admin';

$cidReset = true;

// including the global Chamilo file
require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$formSent=0;
$errorMsg='';

$interbreadcrumb[] = array('url' => 'index.php',       'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';

$htmlHeadXtra = api_get_datetime_picker_js($htmlHeadXtra);

$id = null;
$url_action = api_get_self();
if (isset($_GET['id'])) {
   $id = intval($_GET['id']);
   $url_action = '?id='.$id;
}

$add_coach = null;

if ($id) {
    $tool_name = get_lang('EditSession');    
    SessionManager::protect_session_edit($id);    
    $session_info = api_get_session_info($id);    
    if (!empty($session_info['id_coach'])) {
        $user_info = api_get_user_info($session_info['id_coach']);        
        $add_coach = '$("#coach_id").trigger("addItem", [{"title": "'.$user_info['complete_name'].'", "value": "'.$session_info['id_coach'].'"}]);';
    }
    $button = get_lang('Update');    
} else {
    $tool_name = get_lang('AddSession');
    $button = get_lang('Add');
}


$url = api_get_path(WEB_AJAX_PATH).'admin.ajax.php?1=1';

$htmlHeadXtra[] ='
<script>
function check() {
    $("#coach_id option:selected").each(function() {
        var id = $(this).val();
        var name = $(this).text();        
        if (id != "" ) {
            $.ajax({ 
                async: false,
                url: "'.$url.'&a=user_exists", 
                data: "id="+id,
                success: function(return_value) {                    
                    if (return_value == 0 ) {                        
                        alert("'.get_lang('UserDoesNotExist').'");                        
                        //Deleting select option tag
                        $("#coach_id").find("option").remove();
                        
                        $(".holder li").each(function () {
                            if ($(this).attr("rel") == id) {
                                $(this).remove();
                            }
                        });
                    }
                },            
            });                
        }
    });
}

$(function() {

    $("#coach_id").fcbkcomplete({
        json_url: "'.$url.'&a=find_coaches",
        maxitems: 1,
        addontab: false,
        input_min_size: 1,
        cache: false,
        complete_text:"'.get_lang('StartToType').'",
        firstselected: false,        
        onselect: check,
        filter_selected: true,
        newel: true           
    });
    
    '.$add_coach.'        

    $("#display_end_date").datetimepicker({
        dateFormat: "yy-mm-dd"
    });
    
    $("#display_start_date").datetimepicker({
        dateFormat: "yy-mm-dd",
        hour: 9,        
        onSelect: function(selectedDateTime) {
            var start = $(this).datetimepicker("getDate");        
            $("#display_end_date").val(selectedDateTime);
        }
    });
    
    $("#access_start_date").datetimepicker({
        dateFormat: "yy-mm-dd",
        hour: 9,
        onSelect: function(selectedDateTime) {
            var start = $(this).datetimepicker("getDate");        
            $("#access_end_date").val(selectedDateTime);                    
        }
    });
    
    access_start_date_content = $("#access_end_date").val();
    
    if (access_start_date_content.length > 0) {
        $("#visibility_container").show();
    } else {
        $("#visibility_container").hide();
    }
    
    $("#access_end_date").datetimepicker({
        dateFormat: "yy-mm-dd",
        onSelect: function(selectedDateTime) {            
            $("#visibility_container").show();
        }
    });
    
    $("#access_end_date").on("change", function() {        
        content = $(this).val();
        if (content.length > 0) {
            $("#visibility_container").show();
        } else {
            $("#visibility_container").hide();
        }
        
    });
    
    $("#coach_access_start_date").datetimepicker({
        dateFormat: "yy-mm-dd",
        hour: 9,
        onSelect: function(selectedDateTime) {
            var start = $(this).datetimepicker("getDate");        
            $("#coach_access_end_date").val(selectedDateTime);
        }
    });
    
    $("#coach_access_end_date").datetimepicker({
        dateFormat: "yy-mm-dd"
    });

    var value = 1;
    $("#advanced_parameters").on("click", function() {    
        $("#options").toggle(function() {        
            if (value == 1) {
                $("#advanced_parameters").addClass("btn-hide");        
                value = 0;
            } else {
                $("#advanced_parameters").removeClass("btn-hide");      
                value = 1;
            }
        });
    });
    
});
</script>';

$form = new FormValidator('add_session', 'post', $url_action);
$form->addElement('header', $tool_name);

//Name
$form->addElement('text', 'name', get_lang('SessionName'), array('class' => 'span6'));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

if (empty($id)) {    
    $form->addRule('name', get_lang('SessionNameAlreadyExists'), 'callback', 'check_session_name');
} else {
    $form->addElement('hidden', 'id', $id);
}

$categories = SessionManager::get_all_session_category();

$select_categories = array();
if (!empty($categories)) {
    $select_categories = array('0' => get_lang('None'));
    foreach ($categories as $row) {
        $select_categories[$row['id']] = $row['name'];
    }
}

//Categories
$form->addElement('select', 'session_category_id', get_lang('SessionCategory'), $select_categories, array('id' => 'session_category_id', 'class' => 'chzn-select'));

//Coaches
//$coaches = SessionManager::get_user_list();
$form->addElement('select', 'id_coach', get_lang('CoachName'), array(),array('id' => 'coach_id'));
$form->addRule('id_coach', get_lang('ThisFieldIsRequired'), 'required');

$form->addElement('advanced_settings','<a class="btn btn-show" id="advanced_parameters" href="javascript://">'.get_lang('AdvancedParameters').'</a>');
$form->addElement('html','<div id="options" style="display:none">');

//Dates
$form->addElement('text', 'display_start_date', array(get_lang('SessionDisplayStartDate'), get_lang('SessionDisplayStartDateComment')), array('id' => 'display_start_date'));
$form->addElement('text', 'display_end_date', array(get_lang('SessionDisplayEndDate'), get_lang('SessionDisplayEndDateComment')), array('id' => 'display_end_date'));
$form->addRule(array('display_start_date', 'display_end_date'), get_lang('StartDateMustBeBeforeTheEndDate'), 'compare_datetime_text', '< allow_empty');
     
$form->addElement('text', 'access_start_date', array(get_lang('SessionStartDate'), get_lang('SessionStartDateComment')), array('id' => 'access_start_date'));
$form->addElement('text', 'access_end_date', array(get_lang('SessionEndDate'), get_lang('SessionEndDate')), array('id' => 'access_end_date'));
$form->addRule(array('access_start_date', 'access_end_date'), get_lang('StartDateMustBeBeforeTheEndDate'), 'compare_datetime_text', '< allow_empty');

//Visibility
$visibility_list = array(SESSION_VISIBLE_READ_ONLY=>get_lang('SessionReadOnly'), SESSION_VISIBLE=>get_lang('SessionAccessible'), SESSION_INVISIBLE=>api_ucfirst(get_lang('SessionNotAccessible')));

$form->addElement('html','<div id="visibility_container">');
$form->addElement('select', 'visibility', get_lang('SessionVisibility'), $visibility_list, array('id' => 'visibility'));
$form->addElement('html','</div>');

$form->addElement('text', 'coach_access_start_date', array(get_lang('SessionCoachStartDate'), get_lang('SessionCoachStartDateComment')), array('id' => 'coach_access_start_date'));
$form->addElement('text', 'coach_access_end_date', array(get_lang('SessionCoachEndDate'), get_lang('SessionCoachEndDateComment')), array('id' => 'coach_access_end_date'));
$form->addRule(array('coach_access_start_date', 'coach_access_end_date'), get_lang('StartDateMustBeBeforeTheEndDate'), 'compare_datetime_text', '< allow_empty');


$session_field = new SessionField();
$session_field->add_elements($form, $id);

$form->addElement('html','</div>');
      
$form->addElement('button', 'submit', $button);

if (!empty($session_info)) {
    $session_info['display_start_date'] = api_get_local_time($session_info['display_start_date'], null, null, true);
    $session_info['display_end_date'] = api_get_local_time($session_info['display_end_date'], null, null, true);
    $session_info['access_start_date'] = api_get_local_time($session_info['access_start_date'], null, null, true);
    $session_info['access_end_date'] = api_get_local_time($session_info['access_end_date'], null, null, true);
    $session_info['coach_access_start_date'] = api_get_local_time($session_info['coach_access_start_date'], null, null, true);
    $session_info['coach_access_end_date'] = api_get_local_time($session_info['coach_access_end_date'], null, null, true);    
    $form->setDefaults($session_info);
}

if ($form->validate()) {
    $params = $form->getSubmitValues();
    if (isset($params['id'])) {
        SessionManager::update($params);
        header('Location: resume_session.php?id_session='.$params['id']);
		exit;
    } else {
        $session_id = SessionManager::add($params);        
        if ($session_id) {
            // integer => no error on session creation
            header('Location: add_courses_to_session.php?id_session='.$session_id.'&add=true&msg=');
            exit;
        }
    }
}

function check_session_name($name) {    
    $session = SessionManager::get_session_by_name($name);    
    return empty($session) ? true : false;
}

Display::display_header($tool_name);

echo '<div class="actions">';
echo '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form->display();
Display::display_footer();