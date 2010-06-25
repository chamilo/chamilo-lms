<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls for thematic 
 */
 
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'thematic.lib.php';

$action = $_GET['a'];

switch ($action) {	
	case 'get_datetime_by_attendance':							
			$attendance_id = intval($_POST['attendance_id']);
			$label = '';
			$input_select = '';			
			if (!empty($attendance_id)) {
				$attendance = new Attendance();						
				$attendance_calendar = $attendance->get_attendance_calendar($attendance_id);				
				$calendar_select = array();
				$label = get_lang('StartDate');
				if (!empty($attendance_calendar)) {
					$input_select .= '<select name="start_date_by_attendance" UNIQUE size="5">';				
					foreach ($attendance_calendar as $calendar) {
						$input_select .= '<option value="'.$calendar['date_time'].'">'.$calendar['date_time'].'</option>';	
					}
					$input_select .= '</select>';
				} else {
					$input_select .= '<em>'.get_lang('ThereAreNoRegisteredDatetimeYet').'</em>';
				}
			} 
			?>			
				<div class="row">
					<div class="label"><?php echo $label ?></div>
					<div class="formw"><?php echo $input_select ?></div>
				</div>			
			<?php				
			break;
	case 'update_done_thematic_advance':	
			$thematic_advance_id = intval($_GET['thematic_advance_id']);
			$total_avererage = 0;			
			if (!empty($thematic_advance_id)) {				
				$thematic = new Thematic();				
				$affected_rows = $thematic->update_done_thematic_advances($thematic_advance_id);
				$total_avererage = $thematic->get_total_average_of_thematic_advances();				
			}
			echo $total_avererage;
			break;
	default:
		echo '';
}
exit;

?>
