<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for attendance sheet (list, edit, add) 
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.attendance
*/

if (api_is_allowed_to_edit(null, true)) {
$param_gradebook = '';
if (isset($_SESSION['gradebook'])) {
	$param_gradebook = '&gradebook='.$_SESSION['gradebook'];
}
echo '<div class="actions" style="margin-bottom:30px">';
echo '<a href="index?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('calendar_up.gif',get_lang('AttendanceCalendar')).' '.get_lang('AttendanceCalendar').'</a>';	
echo '</div>';

$message_information = get_lang('informationAttendanceSheet');
if (!empty($message_information)) {
	$message = '<strong>'.get_lang('Information').'</strong><br />';
	$message .= $message_information;
	Display::display_normal_message($message, false);
}
?>
<?php 

if (count($users_in_course) > 0) { 
?>
<form method="post" action="index?action=attendance_sheet_add&<?php echo api_get_cidreq().$param_gradebook ?>&attendance_id=<?php echo $attendance_id?>" >
<div class="attendance-sheet-content" style="width:100%;background-color:#E1E1E1;border:1px solid gray;margin-top:20px;">
	
	<div class="attendance-users-table" style="width:450px;float:left;">

		<table class="data_table" width="100%">
			<tr class="row_odd" >	
				<th height="60px" width="35px" ><?php echo get_lang('Order')?></th>
				<th width="45px" ><?php echo get_lang('Photo')?></th>
				<th><?php echo get_lang('LastName')?></th>
				<th><?php echo get_lang('FirstName')?></th>
				<th width="90px"><?php echo get_lang('faults')?></th>
			</tr>
			
			<?php 
			$i = 1;
			$class = '';
			foreach ($users_in_course as $data) {
				$faults = 0;
				if ($i%2 == 0) {$class='row_odd';}
				else {$class='row_even';}  
			?>
				<tr class="<?php echo $class ?>">
				<td height="50px"><center><?php echo $i ?></center></td>
				<td><?php echo $data['photo'] ?></td>
				<td><?php echo $data['lastname'] ?></td>
				<td><?php echo $data['firstname'] ?></td>
				<td><center><div class="attendance-faults-bar" style="background-color:<?php echo (!empty($data['result_color_bar'])?$data['result_color_bar']:'none') ?>"><?php echo $data['attendance_result'] ?></div></center></td>
				</tr>					
			<?php 
				$i++;
			} 
			?> 
		</table>
		
	</div>
	
	<div class="attendance-calendar-table" style="overflow:auto;overflow-y:hidden;">
		<table class="data_table" width="100%">
			<tr class="row_odd">
			<?php			
				if (count($attendant_calendar) > 0 ) {
					foreach ($attendant_calendar as $calendar) {												
						//$datetime = explode(' ',$calendar['date_time']);
						$date = $calendar['date'];
						$time = $calendar['time'];
						$datetime = $date.'<br />'.$time;
						$checked_date = '';						
						if (!empty($calendar['done_attendance'])){
							$datetime = '<font color="blue">'.$date.'<br />'.$time.'</font>';
						}						
						if ($next_attendance_calendar_id == $calendar['id']) {
							$checked_date = 'checked';
						}													
				?>
						<th height="60px" style="padding:5px;"><?php echo '<span style="font-size:10px;">'.$datetime.'<br /><input type="checkbox" id="datetime_column_'.$calendar['id'].'" '.$checked_date.'/></span>' ?></th>
				<?php }					
				} else { ?>
					<th height="60px" style="padding:5px;"><span><a href="index?<?php echo api_get_cidreq() ?>&action=calendar_list&attendance_id=<?php echo $attendance_id.$param_gradebook ?>">
					<?php echo Display::return_icon('calendar_up.gif',get_lang('AttendanceCalendar')).' '.get_lang('GoToAttendanceCalendar') ?></a></span></th>
				<?php } ?>
			</tr>			
			<?php 
			$i = 0;
			foreach ($users_in_course as $user) { 
					$class = '';
					if ($i%2==0) {$class = 'row_even';}
					else {$class = 'row_odd';}
			?>			
				<tr class="<?php echo $class ?>">
				<?php 
					if (count($attendant_calendar) > 0 ) {
						foreach ($attendant_calendar as $calendar) { 
							$checked = 'checked';							
							if (isset($users_presence[$user['user_id']][$calendar['id']]['presence'])) {
								$presence = $users_presence[$user['user_id']][$calendar['id']]['presence'];
								if (intval($presence) == 1) {
									$checked = 'checked';
								} else {
									$checked = '';
								}
							}
							$disabled = 'disabled';
							$style_td = '';
							if ($next_attendance_calendar_id == $calendar['id']) {
								$style_td = 'background-color:#e1e1e1';
								$disabled = '';
							}
				?>
						<td height="50px" style="<?php echo $style_td ?>" class="checkboxes_col_<?php echo $calendar['id'] ?>"><center><input type="checkbox" name="check_presence[]" value="cal_<?php echo $calendar['id'] ?>_user_<?php echo $user['user_id'] ?>"  <?php echo $disabled.' '.$checked ?> /><span class="<?php echo 'anchor_'.$calendar['id'] ?>"></span></center></td>
						
				<?php 	} 
					} else { ?>
						<td height="50px" class="checkboxes_col_<?php echo $calendar['id'] ?>"><center>&nbsp;</center></td>
			  <?php	} ?>
				</tr>
			<?php $i++ ; 			
			} 
			?>
			
		</table>
	</div>
</div>

<div class="clear"></div>
<div style="margin-top:20px;"><button type="submit" class="save"><?php echo get_lang('SaveAttendanceSheet') ?></button></div>
</form>

<?php } else {	
	echo '<div><a href="'.api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'">'.get_lang('ThereAreNoRegisteredLearnersInsidetheCourse').'</a></div>';	
}

} else {
	// View for students
	$user_id = api_get_user_id();
?>	
	<h3><?php echo get_lang('AttendanceSheetReport') ?></h3>
	<div>
		<table width="200px;">
		<tr>
			<td><?php echo get_lang('Faults').': ' ?></td><td><center><div class="attendance-faults-bar" style="background-color:<?php echo (!empty($faults['color_bar'])?$faults['color_bar']:'none') ?>"><?php echo $faults['faults'].'/'.$faults['total'].' ('.$faults['faults_porcent'].'%)' ?></div></center></td>
		</tr>	
		</table>
	</div>
	<div>
	<br />
	<center>
		<table class="data_table">
				<tr class="row_odd" >	
					<th><?php echo get_lang('Calendar')?></th>
					<th><?php echo get_lang('Attendance')?></th>				
				</tr>
				<?php 
				$i = 0;
				foreach($users_presence[$user_id] as $presence) { 
					$class = '';
					if ($i%2==0) {$class = 'row_even';}
					else {$class = 'row_odd';}	
				?>
				<tr class="<?php echo $class ?>"><td><?php echo Display::return_icon('lp_calendar_event.png',get_lang('DateTime')).' '.$presence['date_time'] ?></td><td><center><?php echo $presence['presence']?Display::return_icon('checkbox_on.gif',get_lang('Presence')):Display::return_icon('checkbox_off.gif',get_lang('Presence')) ?></center></td></tr>	
					
				<?php } ?>
		</table>			
	</center>
	</div>
<?php } ?>