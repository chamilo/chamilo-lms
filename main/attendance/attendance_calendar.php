<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for attendance calendar (list, edit, add)
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.attendance
*/

// protect a course script
api_protect_course_script(true);

$param_gradebook = '';
if (isset($_SESSION['gradebook'])) {
	$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
}
if (!$is_locked_attendance || api_is_platform_admin()) {
	echo '<div class="actions" style="margin-bottom:30px">';	
	if ($action == 'calendar_add') {
		echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('back.png',get_lang('AttendanceCalendar'),'',ICON_SIZE_MEDIUM).'</a>';
	} else {
		echo '<a href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('back.png',get_lang('AttendanceSheet'),'',ICON_SIZE_MEDIUM).'</a>';
		
		echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_add&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('add.png',get_lang('AddDateAndTime'),'',ICON_SIZE_MEDIUM).'</a>';
		echo '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDeleteAllDates').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=calendar_all_delete&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('clean.png',get_lang('CleanCalendar'),'',ICON_SIZE_MEDIUM).'</a>';
	}
	echo '</div>';
}


$message_information = get_lang('AttendanceCalendarDescription');

if (!empty($message_information)) {
	$message = '<strong>'.get_lang('Information').'</strong><br />';
	$message .= $message_information;
	Display::display_normal_message($message, false);
}

if ($error_repeat_date) {
    $message = get_lang('EndDateMustBeMoreThanStartDate');
    Display::display_error_message($message, false);
}

if ($error_checkdate) {
    $message = get_lang('InvalidDate');
    Display::display_error_message($message, false);
}

if (isset($action) && $action == 'calendar_add') {
	// calendar add form
	echo '<div class="attendance-calendar-add">';
	echo '<h4>'.get_lang('AddADateTime').'</h4>';
		$form = new FormValidator('attendance_calendar_add','POST','index.php?action=calendar_add&attendance_id='.$attendance_id.$param_gradebook.'&'.api_get_cidreq(),'');
		$form->addElement('datepicker', 'date_time', '', array('form_name'=>'attendance_calendar_add'), 5);
		$defaults['date_time'] = date('Y-m-d H:i', api_strtotime(api_get_local_time()));

        $form->addElement('html','<br /><br />');
		
        $form->addElement('html', '<div id="repeat_check"><input id="repeat_id" type="checkbox" name="repeat" '.($repeat?'checked="checked"':'').' onclick="javascript: if(this.checked){document.getElementById(\'repeat-date-attendance\').style.display=\'block\';}else{document.getElementById(\'repeat-date-attendance\').style.display=\'none\';}"/> <label for="repeat_id">'. get_lang('RepeatDate').'</label>');
		
        if ($repeat) {
            $form->addElement('html', '<div id="repeat-date-attendance" style="display:block">');
        } else {
            $form->addElement('html', '<div id="repeat-date-attendance" style="display:none">');
        }
        $form->addElement('html', '<table>');
        $a_repeat_type = array('daily'=>get_lang('RepeatDaily'), 'weekly'=>get_lang('RepeatWeekly'), 'monthlyByDate'=>get_lang('RepeatMonthlyByDate'));
        $form->addElement('html', '<tr><td>'.get_lang('RepeatType').'</td><td>');
        $form->addElement('select', 'repeat_type', '', $a_repeat_type);
        $form->addElement('html', '</td></tr>');

        $form->addElement('html', '<tr><td>'.get_lang('RepeatEnd').'</td><td>');
        $form->addElement('datepickerdate', 'end_date_time', '', array('form_name'=>'attendance_calendar_add'));
        $defaults['end_date_time'] = date('Y-m-d 12:00:00');
        $form->addElement('html', '</td></tr>');
        $form->addElement('html', '</table>');
        
        
        $form->addElement('html', '</div>');
        $form->addElement('html', '</div>');
        $form->addElement('html','<br /><br />');

		$form->addElement('html','<div class="clear"></div>');
		$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
		$form->setDefaults($defaults);
		$form->display();
	echo '</div>';
} else {
	// calendar list
	echo '<div class="attendance-calendar-list">';
	echo '<h4>'.get_lang('CalendarList').'</h4>';
	if (!empty($attendance_calendar)) {	
		foreach ($attendance_calendar as $calendar) {	
			echo '<div class="attendance-calendar-row">';				
				if ((isset($action) && $action == 'calendar_edit') && (isset($calendar_id) && $calendar_id == $calendar['id'])) {
					// calendar edit form				
					echo '<div class="attendance-calendar-edit">';				
						$form = new FormValidator('attendance_calendar_edit','POST','index.php?action=calendar_edit&attendance_id='.$attendance_id.'&calendar_id='.$calendar_id.'&'.api_get_cidreq().$param_gradebook,'');					
						$form->addElement('datepicker', 'date_time', '', array('form_name'=>'attendance_calendar_edit'), 5);
						$defaults['date_time'] = $calendar['date_time'];
						$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
						$form->addElement('style_submit_button', 'cancel', get_lang('Cancel'), 'class="cancel"');
						$form->setDefaults($defaults);
						$form->display();
					echo '</div>';						
				} else {
					echo Display::return_icon('lp_calendar_event.png',get_lang('DateTime')).' '.$calendar['date_time'].'&nbsp;';
					if (!$is_locked_attendance || api_is_platform_admin()) {
                        echo '<span style="margin-left:20px;">';
                        echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_edit&calendar_id='.intval($calendar['id']).'&attendance_id='.$attendance_id.$param_gradebook.'">'.Display::return_icon('edit.png', get_lang('Edit'), array('style'=>'vertical-align:middle'), 22).'</a>&nbsp;';
                        echo '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().$param_gradebook.'&action=calendar_delete&calendar_id='.intval($calendar['id']).'&attendance_id='.$attendance_id.'">'.Display::return_icon('delete.png', get_lang('Delete'), array('style'=>'vertical-align:middle'), 22).'</a>';
                        echo '</span>';
                    }
				}	
			echo '</div>';
		}
	} else {
		echo '<div style="padding:5px;">'.get_lang('ThereAreNoRegisteredDatetimeYet').'</div>';
	}
	echo '</div>';			
}