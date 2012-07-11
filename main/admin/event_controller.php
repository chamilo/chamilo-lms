<?php
// name of the language file that needs to be included
$language_file = array('admin', 'events');
$cidReset = true;

require_once '../inc/global.inc.php';

if (api_get_setting('activate_email_template') != 'true') {
    api_not_allowed();
}

class eventController { // extends Controller {
    public function showAction() {
               
    }
    
    public function newAction() {
        
    }
    
    public function addAction() {
        
    }
    
    public function listingAction() {
        $event_email_template = new EventEmailTemplate();
        return $event_email_template->display(); 
    }
    
    public function deleteAction($id) {
         $event_email_template = new EventEmailTemplate();
        return $event_email_template->delete($id); 
    }    
}

$event_controller = new eventController();
$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'show':
        $event_controller->showAction();
        break;
    case 'add':
        $event_controller->addAction();
        break;
    case 'new':
        $event_controller->newAction();
        break;
    case 'delete' :
        $event_controller->deleteAction($_GET['id']);
        $content = $event_controller->listingAction();
        break;
    default:
    case 'listing':
        $content = $event_controller->listingAction();
        break;
}

//jqgrid will use this URL to do the selects
$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_event_email_template';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Subject'), get_lang('EventTypeName'), get_lang('Language'), get_lang('Status'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'subject',        'index'=>'subject',        'width'=>'80',   'align'=>'left'),
//                        array('name'=>'message',        'index'=>'message', 'width'=>'500',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'event_type_name',        'index'=>'event_type_name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'language_id',        'index'=>'language_id',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'activated',        'index'=>'activated',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'actions',        'index'=>'actions',     'width'=>'100')
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto';

$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = '<script>
$(function() {
    '.Display::grid_js('event_email_template',  $url,$columns,$column_model,$extra_params, array(), $action_links,true).' 
});
</script>';

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Events'));

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();