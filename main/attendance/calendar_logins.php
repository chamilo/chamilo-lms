<?php
/* For licensing terms, see /license.txt */

// See AttendanceController::calendarLogins function
echo '<div class="actions">';
echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_list">'.
    Display::return_icon('back.png', get_lang('AttendanceCalendar'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';
echo $form;
echo $table;
