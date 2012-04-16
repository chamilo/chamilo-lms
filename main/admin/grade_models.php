<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'grade_model.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();    

if ($action == 'add') {    
    $interbreadcrumb[]=array('url' => 'grade_models.php','name' => get_lang('GradeModel'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[]=array('url' => 'grade_models.php','name' => get_lang('GradeModel'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
} else {
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('GradeModel'));
}

$htmlHeadXtra[]= '<script>
    
function plusItem(item) {
        if (item != 1) {
		document.getElementById(item).style.display = "inline";
    	document.getElementById("plus-"+item).style.display = "none";
   	 	document.getElementById("min-"+(item-1)).style.display = "none";
   	 	document.getElementById("min-"+(item)).style.display = "inline";
   	 	document.getElementById("plus-"+(item+1)).style.display = "inline";
	 	//document.getElementById("txta-"+(item)).value = "100";
	 	//document.getElementById("txta-"+(item-1)).value = "";
        }
  }
  
function minItem(item) {
    if (item != 1) {
     document.getElementById(item).style.display = "none";
	 //document.getElementById("txta-"+item).value = "";
	 //document.getElementById("txtb-"+item).value = "";
     document.getElementById("plus-"+item).style.display = "inline";
     document.getElementById("min-"+(item-1)).style.display = "inline";
	 //document.getElementById("txta-"+(item-1)).value = "100";
	}
	if (item = 1) {
		document.getElementById("min-"+(item)).style.display = "none";
	}
}
</script>';

// The header.
Display::display_header($tool_name);

//jqgrid will use this URL to do the selects
$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_grade_models';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'), get_lang('Description'), get_lang('Actions'));

//Column config
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'description',    'index'=>'description', 'width'=>'500',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false')
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
?>
<script>
$(function() {
<?php 
    // grid definition see the $obj->display() function
    echo Display::grid_js('grade_model',  $url, $columns, $column_model, $extra_params, array(), $action_links,true);       
?> 
});
</script>
<?php
$obj = new GradeModel();

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
                Display::display_confirmation_message(get_lang('ItemUpdated'), false);                
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