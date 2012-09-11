<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$tool_name = null;

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();    

if ($action == 'add') {
    $interbreadcrumb[]=array('url' => 'session_fields.php','name' => get_lang('SessionFields'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[]=array('url' => 'session_fields.php','name' => get_lang('SessionFields'));    
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
} else {
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('SessionFields'));
}

//jqgrid will use this URL to do the selects
$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_session_fields';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('FieldLabel'),  get_lang('Type'), get_lang('FieldChangeability'), get_lang('Visibility'), get_lang('Filter'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'field_display_text', 'index'=>'field_display_text',      'width'=>'180',   'align'=>'left'),
                        array('name'=>'field_variable',     'index'=>'field_variable',          'width'=>'',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'field_type',         'index'=>'field_type',              'width'=>'',  'align'=>'left','sortable'=>'false'),    
                        array('name'=>'field_changeable',   'index'=>'field_changeable',        'width'=>'50',  'align'=>'left','sortable'=>'false'),    
                        array('name'=>'field_visible',      'index'=>'field_visible',           'width'=>'40',  'align'=>'left','sortable'=>'false'),    
                        array('name'=>'field_filter',       'index'=>'field_filter',            'width'=>'30',  'align'=>'left','sortable'=>'false'),    
                        array('name'=>'actions',            'index'=>'actions',                 'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
                         '\'; 
                 }';
$htmlHeadXtra[]='
<script>
$(function() {
    // grid definition see the $obj->display() function
    '.Display::grid_js('session_fields',  $url,$columns,$column_model,$extra_params, array(), $action_links,true).'
            
    $("#field_type").on("change", function() {      
        id = $(this).val();        
        switch(id) {
            case "1":
                $("#example").html("'.addslashes(Display::return_icon('userfield_text.png')).'"); 
                break;
            case "2":        
                $("#example").html("'.addslashes(Display::return_icon('userfield_text_area.png')).'"); 
                break;
            case "3":     
                $("#example").html("'.addslashes(Display::return_icon('add_user_field_howto.png')).'");            
                break;
            case "4":
                $("#example").html("'.addslashes(Display::return_icon('userfield_drop_down.png')).'");
                break;
            case "5":
                $("#example").html("'.addslashes(Display::return_icon('userfield_multidropdown.png')).'");
                break;
            case "6":
                $("#example").html("'.addslashes(Display::return_icon('userfield_data.png')).'");
                break;
            case "7":
                $("#example").html("'.addslashes(Display::return_icon('userfield_date_time.png')).'");
                break;
            case "8":
                $("#example").html("'.addslashes(Display::return_icon('userfield_doubleselect.png')).'");
                break;
            case "9":
                $("#example").html("'.addslashes(Display::return_icon('userfield_divider.png')).'");
                break;
            case "10":
                $("#example").html("'.addslashes(Display::return_icon('userfield_user_tag.png')).'");
                break;
            case "11":
                $("#example").html("'.addslashes(Display::return_icon('userfield_data.png')).'");                                            
                break;
        }
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

// The header.
Display::display_header($tool_name);

$obj = new SessionField();

// Action handling: Add
switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }        
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $obj->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {          
            if ($check) {
                $values = $form->exportValues();       
                $res    = $obj->save($values);            
                if ($res) {
                    Display::display_confirmation_message(get_lang('ItemAdded'));
                }
            }        
            $obj->display();
        } else {
            echo '<div class="actions">';
            echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'edit':
        // Action handling: Editing 
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);
        $form = $obj->return_form($url, 'edit');    

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();                
                $res    = $obj->update($values);                
                Display::display_confirmation_message(sprintf(get_lang('CareerXUnarchived'), $values['name']), false);                
            }            
            $obj->display();
        } else {
            echo '<div class="actions">';
            echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'delete':
        // Action handling: delete
        if ($check) {
            $res = $obj->delete($_GET['id']);
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemDeleted'));
            }
        }
        $obj->display();
        break;
    default:
        $obj->display();   
        break;
}
Display :: display_footer();