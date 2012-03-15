<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls for thematic 
 */
 
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'thematic.lib.php';

api_protect_course_script(true);

$action = $_GET['a'];
$thematic = new Thematic();

switch ($action) {		
	case 'save_thematic_plan':
		$title_list         = $_REQUEST['title'];
		$description_list   = $_REQUEST['desc'];
		//$description_list   = $_REQUEST['description'];
		$description_type   = $_REQUEST['description_type'];
		if (api_is_allowed_to_edit(null, true)) {
			for($i=1;$i<count($title_list)+1; $i++) {
				$thematic->set_thematic_plan_attributes($_REQUEST['thematic_id'], $title_list[$i], $description_list[$i], $description_type[$i]);
				$affected_rows = $thematic->thematic_plan_save();
			}
		}	
		$thematic_plan_data = $thematic->get_thematic_plan_data();
		$return = $thematic->get_thematic_plan_div($thematic_plan_data);
		echo $return[$_REQUEST['thematic_id']];
		break;		
        
	case 'save_thematic_advance':		
		if (!api_is_allowed_to_edit(null, true)) {
			echo '';
			exit;
        }
        
		if (($_REQUEST['start_date_type'] == 1 && empty($_REQUEST['start_date_by_attendance'])) || (!empty($_REQUEST['duration_in_hours']) && !is_numeric($_REQUEST['duration_in_hours'])) ) {	    			
			if ($_REQUEST['start_date_type'] == 1 && empty($_REQUEST['start_date_by_attendance'])) {
        		$start_date_error = true;
    			$data['start_date_error'] = $start_date_error;	
			} 
        			
        	if (!empty($_REQUEST['duration_in_hours']) && !is_numeric($_REQUEST['duration_in_hours'])) {
        		$duration_error = true;
    			$data['duration_error'] = $duration_error;	
        	}
    
    		$data['action'] = $_REQUEST['action'];	
    		$data['thematic_id'] = $_REQUEST['thematic_id'];				
    		$data['attendance_select'] = $attendance_select;					
    		if (isset($_REQUEST['thematic_advance_id'])) {
    			$data['thematic_advance_id'] = $_REQUEST['thematic_advance_id'];
    			$thematic_advance_data = $thematic->get_thematic_advance_list($_REQUEST['thematic_advance_id']);
    			$data['thematic_advance_data'] = $thematic_advance_data;		
    		}	    			
		} else {   				
			if ($_REQUEST['thematic_advance_token'] == $_SESSION['thematic_advance_token'] && api_is_allowed_to_edit(null, true)) {    	    			
    	    	$thematic_advance_id 	= $_REQUEST['thematic_advance_id'];
    	    	$thematic_id 			= $_REQUEST['thematic_id'];	    			
    	    	$content 				= $_REQUEST['real_content'];
    	    	$duration				= $_REQUEST['duration_in_hours'];
    	    	if (isset($_REQUEST['start_date_type']) && $_REQUEST['start_date_type'] == 2) {
    	    		$start_date 	= $thematic->build_datetime_from_array($_REQUEST['custom_start_date']);
    	    		$attendance_id 	= 0;	
    	    	} else {
    	    		$start_date 	= $_REQUEST['start_date_by_attendance'];
    	    		$attendance_id 	= $_REQUEST['attendance_select'];
    	    	}		    			
    	    	$thematic->set_thematic_advance_attributes($thematic_advance_id, $thematic_id,  $attendance_id, $content, $start_date, $duration);	    				    								
				$affected_rows = $thematic->thematic_advance_save();			        	
    		    if ($affected_rows) {
    		    	// get last done thematic advance before move thematic list
    				$last_done_thematic_advance = $thematic->get_last_done_thematic_advance();
    				// update done advances with de current thematic list
    				if (!empty($last_done_thematic_advance)) {
    					$update_done_advances = $thematic->update_done_thematic_advances($last_done_thematic_advance);
    				}
				}
			}
		}
		$thematic_advance_data = $thematic->get_thematic_advance_list(null, null, true);        
		$return = $thematic->get_thematic_advance_div($thematic_advance_data);
		
		echo $return[$_REQUEST['thematic_id']][$_REQUEST['thematic_advance_id']];
        break;		
	case 'get_datetime_by_attendance':							
		$attendance_id = intval($_POST['attendance_id']);
        
		$thematic_advance_id = intval($_POST['thematic_advance_id']);
		
		$label = '';
		$input_select = '';			
		if (!empty($attendance_id)) {
			$attendance = new Attendance();										
			$thematic   = new Thematic();                
            $thematic_list = $thematic->get_thematic_list();
            
            $my_list = $thematic_list_temp = array();
            foreach($thematic_list as $item) {                    	
                $my_list = $thematic->get_thematic_advance_by_thematic_id($item['id']);                    
                $thematic_list_temp = array_merge($my_list, $thematic_list_temp);
            }     
			$new_thematic_list = array();
		
			foreach($thematic_list_temp as $item) {
				if (!empty($item['attendance_id']) ) {
					$new_thematic_list[$item['id']] = array('attendance_id' =>$item['attendance_id'], 'start_date'=>$item['start_date']);
				}
			}      
			          
			$attendance_calendar = $attendance->get_attendance_calendar($attendance_id);		
			$calendar_select = array();
			$label = get_lang('StartDate');
			if (!empty($attendance_calendar)) {
				$input_select .= '<select id="start_date_select_calendar" name="start_date_by_attendance" UNIQUE size="5">';				
				foreach ($attendance_calendar as $calendar) {
					$insert = true;
					//checking if was already taken						
					foreach($new_thematic_list as $thematic_item) {
						//if ($calendar['db_date_time'] == $thematic_item['start_date'] && $calendar['attendance_id'] == $thematic_item['attendance_id'] ) {
                        if ($calendar['db_date_time'] == $thematic_item['start_date'] ) {
							$insert = false;
							break;	
						}						
					}
					if ($insert == true) {
						$input_select .= '<option value="'.$calendar['date_time'].'">'.$calendar['date_time'].'</option>';
					}
				}
				$input_select .= '</select>';
			} else {
				$input_select .= '<em>'.get_lang('ThereAreNoRegisteredDatetimeYet').'</em>';
			}
		} 
		?>			
		<div class="control-group">
			<label class="control-label"><?php echo $label ?></label>
			<div class="controls"><?php echo $input_select ?></div>
		</div>			
		<?php				
	    break;	    
	case 'update_done_thematic_advance':	
		$thematic_advance_id = intval($_GET['thematic_advance_id']);
		$total_average = 0;			
		if (!empty($thematic_advance_id)) {				
			$thematic = new Thematic();				
			$affected_rows  = $thematic->update_done_thematic_advances($thematic_advance_id);			
			//if ($affected_rows) {
			$total_average  = $thematic->get_total_average_of_thematic_advances(api_get_course_id(), api_get_session_id());
			//}			
		}
		echo $total_average;
		break;
	default:
		echo '';
}
exit;