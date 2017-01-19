<?php

class TestCalendar extends UnitTestCase
{
	public function TestCalendar(){
		$this->UnitTestCase('Admin calendar library - main/admin/calendar.inc.test.php');
	}
	public function testToJavascript(){
		$res = to_javascript();
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
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

 	public function testCalculateStartEndOfWeek(){
 		$week_number=4;
 		$year=2011;
 		$res = calculate_start_end_of_week($week_number, $year);
 		$this->assertTrue(is_array($res));
 		$this->assertTrue($res);
 		//var_dump($res);
 	}


 	public function testGetDayAgendaitems() {
 		$courses_dbs=array();
 		$month=01;
 		$year=2010;
 		$day='1';
 		$res = get_day_agendaitems($courses_dbs, $month, $year, $day);
 		$this->assertTrue(is_array($res));
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

 	public function testShowUserFilterForm(){
		ob_start();
 		$res = show_user_filter_form();
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 	}

 	public function testIsRepeatedEvent() {
	//This is deprecated or not used
 	}
}
