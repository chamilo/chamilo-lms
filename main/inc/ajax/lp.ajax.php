<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';
api_protect_course_script(true);
$action = $_REQUEST['a'];

$course_id = api_get_course_int_id(); 
$tbl_lp_item = Database :: get_course_table(TABLE_LP_ITEM);        

switch ($action) {    
    case 'add_lp_item':
        if (api_is_allowed_to_edit(null, true)) {            
            if ($_SESSION['oLP']) {
                //Updating the lp.modified_on            
                $_SESSION['oLP']->set_modified_on();                
                echo $_SESSION['oLP']->add_item($_REQUEST['parent_id'], $_REQUEST['previous_id'], $_REQUEST['type'], $_REQUEST['id'], $_REQUEST['title']);                
            }
        }
        break;   
    case 'update_lp_item_order':
        if (api_is_allowed_to_edit(null, true)) {
            $new_order   = $_POST['new_order'];
            $sections	= explode('^', $new_order);            
            $new_array = array();
            $i = 0;
            foreach($sections as $items) {
            	list($id, $parent_id) = explode('|', $items);
            	$new_array[$i]['id'] = intval($id);
            	$new_array[$i]['parent_id'] = intval($parent_id);
            	$i++;
            }            
            $counter = 1;
            for ($i=0; $i < count($new_array); $i++) {
            	$params = array();   
            	$id = $new_array[$i]['id'];
            	if (empty($id)) {
            		continue;
            	}
            	$parent_id = isset($new_array[$i]['parent_id']) ? $new_array[$i]['parent_id'] : 0;
            	$params['display_order'] 	= $counter;            	
            	$params['previous_item_id']	= isset($new_array[$i-1]) &&  isset($new_array[$i-1]['id']) ? $new_array[$i-1]['id'] : 0;
            	$params['next_item_id']		= isset($new_array[$i+1]) &&  isset($new_array[$i+1]['id']) ? $new_array[$i+1]['id'] : 0;            	
            	$params['parent_item_id']	= $parent_id;            	
            	Database::update($tbl_lp_item, $params, array('id = ? AND c_id = ? '=> array(intval($id), $course_id)));
                $counter ++;                
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        break;
    default:
        echo '';
}
exit;