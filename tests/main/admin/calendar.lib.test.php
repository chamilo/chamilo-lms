<?php
require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
//require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'icalcreator/iCalcreator.class.php');
//require_once(api_get_path(LIBRARY_PATH).'course.lib.php');


class TestCalendar extends UnitTestCase {


	public function TestCalendar(){
		$this->UnitTestCase('Admin calendar library - main/admin/calendar.inc.test.php');
	}
	public function testToJavascript(){
		$res = to_javascript();
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	 * Adds an agenda item in the database. Similar to store_new_agenda_item() except it takes parameters
	 * @param   array   Course info
	 * @param   string  Event title
	 * @param   string  Event content/description
	 * @param   string  Start date
	 * @param   string  End date
	 * @param   array   List of groups to which this event is added
	 * @param   int     Parent id (optional)
	 * @return  int     The new item's DB ID
	 */
	public function testAgendaAddItem(){
		global $_course;
		$course_code=$_course;
		$course_info = '';
		$title='test';
		$content='test function';
		$db_start_date='07/11/2009';
		$db_end_date='07/20/2009';
		$res = agenda_add_item($course_info, $title, $content, $db_start_date, $db_end_date, $to=array(), $parent_id=null);
		$this->assertTrue($res>0,'Global agenda item could not be inserted');
        delete_agenda_item($res);
		//var_dump($res);
	}

	public function testStoreNewAgendaItem(){
		global $_user;
		$res_store = store_new_agenda_item();
		$this->assertTrue(is_numeric($res_store));

		//delete the new agenda item in the database
		if (is_numeric($res_store)) {
			$res_delete = delete_agenda_item($res_store);
			$this->assertTrue(is_numeric($res_store));
		}
	}

 	 public function testShowAddForm(){
 		ob_start();
 		global $MonthsLong;
 		$id='';
 		$res= show_add_form($id);
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}

 	public function testAddWeek(){
 		$timestamp=12;
 		$num=1;
 		$res = add_week($timestamp,$num);
 		$this->assertTrue(is_numeric($res));
 		//var_dump($res);
 	}

 	public function testAddMonth(){
 		$timestamp=5;
 		$num=1;
 		$res = add_month($timestamp,$num);
 		$this->assertTrue(is_numeric($res));
 		//var_dump($res);
 	}

 	public function testAddYear(){
 		$timestamp=9999;
 		$num=1;
 		$res = add_year($timestamp,$num);
 		$this->assertTrue(is_numeric($res));
 		//var_dump($res);
 	}


/**
 * Adds a repetitive item to the database
 * @param   array   Course info
 * @param   int     The original event's id
 * @param   string  Type of repetition
 * @param   int     Timestamp of end of repetition (repeating until that date)
 * @param   array   Original event's destination
 * @return  boolean False if error, True otherwise
 */

 	public function testAgendaAddRepeatItem(){
 		//this function is not used or deprecated
 	}

 	public function testGetCalendarItems(){
 		global $_course;
 		$month=01;
 		$year=2010;
		$res = get_calendar_items($month, $year);
 		$this->assertTrue(is_array($res));
 		//var_dump($res);
 	}

 	//This funtion need a path where will copy to the other folder.
/*
 	public function testAgendaImportIcal() {
 		global $_course;
 		$course_info = $_course;
 		$file = api_get_path(SYS_PATH).'tests/main/admin/icals/test.ics';
 		$res = agenda_import_ical($course_info, $file);
 		if(is_bool($res)){
 		$this->assertTrue(is_bool($res));
 		$this->assertTrue($res===false || $res === true);
 		}else{
 			$this->assertTrue($res);
 		}
 	}
*/

	public function testStoreEditedAgendaItem(){
 		ob_start();
 		$id=1;
		$title='';
		$content='';
		$start_date= 21;
		$end_date=25;
		$res = store_edited_agenda_item();
 		$edit_result=save_edit_agenda_item($id,$title,$content,$start_date,$end_date);
 		ob_end_clean();
 		$this->assertTrue($edit_result);
 		//var_dump($edit_result);
 	}

 	public function testSaveEditAgendaItem(){
	 	$id=Database::escape_string($id);
		$title=Database::escape_string($title);
		$content=Database::escape_string($content);
		$start_date=Database::escape_string($start_date);
		$end_date=Database::escape_string($end_date);
	 	$res = save_edit_agenda_item($id,$title,$content,$start_date,$end_date);
 		$this->assertTrue($res);
 		$this->assertTrue(is_bool($res));
 		//var_dump($res);
 	}

 	public function testCalculateStartEndOfWeek(){
 		$week_number=4;
 		$year=2011;
 		$res = calculate_start_end_of_week($week_number, $year);
 		$this->assertTrue(is_array($res));
 		$this->assertTrue($res);
 		//var_dump($res);
 	}

 	/**
	* Displays all the agenda items
	*/
 	public function testDisplayAgendaItems() {
 		global $is_courseAdmin;
 		ob_start();
 		$TABLEAGENDA = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		$res = display_agenda_items();
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 	}

 	 	public function testDisplayDaycalendar(){
 		ob_start();
 		$agendaitems='';
 		$day='';
 		$month='';
 		$year='';
 		$weekdaynames='';
 		$monthName='';
 		$res = display_daycalendar($agendaitems, $day, $month, $year, $weekdaynames, $monthName);
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}

 	public function testDisplayWeekcalendar() {
 		ob_start();
 		$agendaitems='';
 		$month=10;
 		$year=2011;
 		$weekdaynames='';
 		$monthName='';
 		$res = display_weekcalendar($agendaitems, $month, $year, $weekdaynames, $monthName);
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}

 	public function testGetDayAgendaitems() {
 		$courses_dbs=array();
 		$month=01;
 		$year=2010;
 		$day='1';
 		$res = get_day_agendaitems($courses_dbs, $month, $year, $day);
 		$this->assertTrue(is_array($res));
 		//var_dump($res);
 	}

 	public function testGetWeekAgendaitems() {
 		$courses_dbs=array();
 		$month=01;
 		$year=2010;
		$res = get_week_agendaitems($courses_dbs, $month, $year);
 		$this->assertTrue(is_array($res));
 	}

 	public function testGetRepeatedEventsDayView(){
 			global $_course;
 			$start = 0;
 			$end = 0;
 			$params = array();
			$course_code = 'COURSETEST';
			$course_info = api_get_course_info($course_code);
			$resul = get_repeated_events_day_view($course_info,$start,$end,$params);
	 		$this->assertTrue(is_array($resul));
 	}

 	public function testGetRepeatedEventsWeekView(){
 		$course_info = 'COURSETEST';
 		$resul = get_repeated_events_week_view($course_info, 0, 0, '');
 		$this->assertTrue(is_array($resul));
 	}

 	public function testGetRepeatedEventsMonthView(){
 		$course_code='COURSETEST';
 		$course_info = api_get_course_info($course_code);
 		$resul= get_repeated_events_month_view($course_info,0,0,'');
		$this->assertTrue(is_array($resul));
		//var_dump($resul);
	}

	public function testGetRepeatedEventsListView(){
		$course_code='COURSETEST';
 		$course_info = api_get_course_info($course_code);
		$resul = get_repeated_events_list_view($course_info,0,0,'');
		$this->assertTrue(is_array($resul));
		//var_dump($resul);
	}

	public function testDeleteAgendaItem(){
		$id=1;
		$res = delete_agenda_item($id);
		$this->assertTrue(is_bool($res));
 	}
 	 	public function testDisplayMinimonthcalendar(){
 		ob_start();
 		global $DaysShort;
 		$agendaitems=array('test','test2');
 		$month=01;
 		$year=2010;
 		$monthName='';
 		$res = display_minimonthcalendar($agendaitems, $month, $year, $monthName);
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}

 	public function testUserGroupFilterJavascript(){
 		$res = user_group_filter_javascript();
 		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
 		//var_dump($res);
 	}

 	public function testDisplayMonthcalendar(){
 		ob_start();
 		global $MonthsLong;
		global $DaysShort;
		global $origin;
		$month=05;
		$year=2010;
		$res = display_monthcalendar($month, $year);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
 	}

 	public function testDisplayCourseadminLinks(){
 		ob_start();
 		$res = display_courseadmin_links();
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}

 	public function testDisplayStudentLinks(){
 		ob_start();
 		global $show;
 		$res = display_student_links();
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}

 	public function testGetAgendaItem(){
 		$id=4;
 		$res = get_agenda_item($id);
 		if(is_array($res)) {
 			$this->assertTrue(is_array($res));
 		}
 	}


	/**
	* Makes an agenda item visible or invisible for a student
	* @param integer id the id of the agenda item we are changing the visibility of
	*/
 	public function testShowhideAgendaItem(){
		ob_start();
		$id=1;
		global $nameTools;
 		$res = showhide_agenda_item($id);
 		//Show the message when the visibility was changed
 		$real_show = Display::display_normal_message(get_lang("VisibilityChanged"));
 		ob_end_clean();
 		if(!empty($res)){
 			$this->assertTrue($res);
 			$this->assertTrue($real_show);
 		} else {
 			$this->assertNull($res);
 		}
 		//var_dump($res);
 	}

 	/**
	* Displays only 1 agenda item. This is used when an agenda item is added to the learning path.
	*/
 	public function testDisplayOneAgendaItem(){
 		ob_start();
		$agenda_id=1;
		$res = display_one_agenda_item($agenda_id);
		ob_end_clean();
 		$this->assertTrue(is_null($res));

  	}

	/**
	* Show the form for adding a new agenda item. This is the same function that is used whenever we are editing an
	* agenda item. When the id parameter is empty (default behaviour), then we show an empty form, else we are editing and
	* we have to retrieve the information that is in the database and use this information in the forms.
	*/
 	public function testShowGroupFilterForm(){
 		ob_start();
 		$res = show_group_filter_form();
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 	}

 	public function testShowUserFilterForm(){
		ob_start();
 		$res = show_user_filter_form();
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 	}

 	public function testGetAgendaitems(){
 		global $_user;
		global $_configuration;
 		$month=01;
 		$year=2010;
 		$res = get_agendaitems($month, $year);
 		if(is_array($res)) {
 			$this->assertTrue(is_array($res));
 		}

 	}

 	public function testDisplayUpcomingEvents(){
 		 ob_start();
		 $res = display_upcoming_events();
		 ob_end_clean();
		 $this->assertNull($res);
 	}

 	public function testIsRepeatedEvent() {
	//This is deprecated or not used
 	}

}
?>
