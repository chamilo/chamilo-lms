<?php
/* For licensing terms, see /license.txt */
/**	
	@author Julio Montoya <gugli100@gmail.com> BeezNest 2011
*	@package chamilo.timeline
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');
require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'timeline.lib.php';  

$htmlHeadXtra[] = api_get_jqgrid_js();

//$htmlHeadXtra[] = api_get_js('timeline/timeline-min.js');
//$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/timeline/timeline.css');

// setting breadcrumbs
//$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
//$interbreadcrumb[]=array('url' => 'career_dashboard.php','name' => get_lang('CareersAndPromotions'));

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();    

switch ($action) {
    case 'add':
        $interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('Timeline'));
        $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
        break;
    case 'edit':
        $interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('Timeline'));    
        $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
        break;
    case 'add_item':
        $interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('Timeline'));    
        $interbreadcrumb[]=array('url' => '#','name' => get_lang('item'));
        break;
    default:
        $interbreadcrumb[]=array('url' => '#','name' => get_lang('Timeline'));
}

//jqgrid will use this URL to do the selects
$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_timelines';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',        'width'=>'120',   'align'=>'left'),                        
                        array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left', 'sortable'=>'false')
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 

//With this function we can add actions to the jgrid (edit, delete, etc)
$htmlHeadXtra[] ='
<script>
$(function() {
    // grid definition see the $timeline->display() function
    '.Display::grid_js('timelines',  $url,$columns,$column_model,$extra_params, array(), null,true).'
});
</script>';

$timeline = new Timeline();

// Action handling: Add
switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }        
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $timeline->return_form($url, 'add');

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();       
                $res    = $timeline->save($values);            
                if ($res) {
                    $message = Display::return_message(get_lang('ItemAdded'),'success');
                }
            }        
            $content = $timeline->listing();
        } else {            
            $actions .= '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $content = $form->return_form();
        }
        break;        
    case 'edit':
        // Action handling: Editing 
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);
        $form = $timeline->return_form($url, 'edit');    

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();
                //$timeline->update_all_promotion_status_by_career_id($values['id'],$values['status']);               
                $res    = $timeline->update($values);
                $message = Display::return_message(sprintf(get_lang('ItemUpdated'), $values['name']), 'confirmation');                
            }            
            $timeline->display();
        } else {            
            $actions = '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $content = $form->return_form();
        }
        break;
    case 'add_item':
           // Action handling: Editing 
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&parent_id='.intval($_GET['parent_id']);
        $form = $timeline->return_item_form($url, 'edit');    
        
        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();
                $values['type'] = '';
                //$timeline->update_all_promotion_status_by_career_id($values['id'],$values['status']);               
                $res    = $timeline->save_item($values);
                $message = Display::return_message(sprintf(get_lang('ItemUpdated'), $values['name']), 'confirmation');                
            }            
            $timeline->display();
        } else {            
            $actions = '<a href="'.api_get_self().'">'.Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $content = $form->return_form();
        }
        break;
    case 'delete':
        // Action handling: delete
        if ($check) {
            $res = $timeline->delete($_GET['id']);
            if ($res) {
                $message = Display::return_message(get_lang('ItemDeleted'), 'success');
            }
        }
        $content = $timeline->listing();
        break;    
    default:
        $content = $timeline->listing();
        break;
}

$tpl = new Template($tool_name);

$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();