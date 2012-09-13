<?php

$language_file = array('admin', 'registration', 'userInfo');
require_once '../global.inc.php';
$action = $_GET['a'];

switch ($action) {	
    case 'get_second_select_options':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;
        $field_id = isset($_REQUEST['field_id']) ? $_REQUEST['field_id'] : null;
        $option_value_id = isset($_REQUEST['option_value_id']) ? $_REQUEST['option_value_id'] : null;
        
        if (!empty($type) && !empty($field_id) && !empty($option_value_id)) {
            $field_options = new ExtraFieldOption($type);
            echo $field_options->get_second_select_field_options_by_field($field_id, $option_value_id, true);           
        }
        break;
    default:
        exit;
        break;
}