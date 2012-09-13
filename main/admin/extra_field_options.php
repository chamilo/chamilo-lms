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
$field_id = isset($_GET['field_id']) ? $_GET['field_id'] : null;
$type = 'session';

if (empty($field_id)) {
    api_not_allowed();
}

$extra_field = new ExtraField($type);
$extra_field_info = $extra_field->get($field_id);


$check = Security::check_token('request');
$token = Security::get_token();    

if ($action == 'add') {
    $interbreadcrumb[]=array('url' => 'session_fields.php','name' => get_lang('SessionFields'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[]=array('url' => 'session_fields.php','name' => get_lang('SessionFields'));    
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
} else {
    $interbreadcrumb[]=array('url' => 'session_fields.php','name' => get_lang('SessionFields'));
    $interbreadcrumb[]=array('url' => 'session_fields.php?action=edit&id='.$extra_field_info['id'],'name' => $extra_field_info['field_display_text']);
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('EditExtraFieldOptions'));
}

//jqgrid will use this URL to do the selects
$params = 'field_id='.$field_id.'&type='.$type;
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_extra_field_options&'.$params;

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('Value'),  get_lang('Order'), get_lang('Actions'));
  
//Column config
$column_model   = array(
                        array('name'=>'option_display_text', 'index'=>'option_display_text',      'width'=>'180',   'align'=>'left'),
                        array('name'=>'option_value',       'index'=>'option_value',          'width'=>'',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'option_order',         'index'=>'option_order',              'width'=>'',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'actions',            'index'=>'actions',                 'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 

//With this function we can add actions to the jgrid (edit, delete, etc)
$action_links = 'function action_formatter(cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&'.$params.'&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
                         '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&'.$params.'&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
                         '\'; 
                 }';
$htmlHeadXtra[]='
<script>
$(function() {
    // grid definition see the $obj->display() function
    '.Display::grid_js('extra_field_options',  $url, $columns, $column_model, $extra_params, array(), $action_links, true).'
    
});
</script>';

// The header.
Display::display_header($tool_name);

echo Display::page_header($extra_field_info['field_display_text']);

$obj = new ExtraFieldOption($type);
//$obj->field_id = $field_id;

// Action handling: Add
switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }        
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&'.$params;
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
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']).'&'.$params;
        $form = $obj->return_form($url, 'edit');    

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();                
                $res    = $obj->update($values);                
                Display::display_confirmation_message(sprintf(get_lang('ItemUpdated'), $values['name']), false);                
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