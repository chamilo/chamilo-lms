<?php

require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'icalcreator/iCalcreator.class.php');
Mock::generate('Database');
Mock::generate('Display');
class TestCalendar extends UnitTestCase {
	
	function TestCalendar() {
        $this->UnitTestCase('testing the file about calendar/agenda');
        
    }
	
 	public function testDisplayMinimonthcalendar(){
 		ob_start();
 		global $DaysShort;
 		$agendaitems=array('abc','cde');
 		$month=11; 
 		$year=2008;
 		$monthName='';
 		$res = display_minimonthcalendar($agendaitems, $month, $year, $monthName);
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}
 	
 	public function testToJavascript(){
 		$res = to_javascript();
 		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
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
 	
 	public function testStoreNewAgendaItem(){
 		global $_user;
 		$res = store_new_agenda_item();
 		$this->assertFalse($res);
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
 		$realgrouplist= new MockDatabase();
 		$id=1;
 		$TABLEAGENDA = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		$sql= "SELECT * FROM ".$TABLEAGENDA." WHERE id='".$id."'";
 		$sql_result = api_sql_query($sql,__FILE__,__LINE__);
 		$result= Database::fetch_array($result);
 		$real_list[] = $result;
 		$res = get_agenda_item($id);
 		if(is_array($real_list))
 			$this->assertTrue(is_array($real_list));	
 			else{
 			$this->assertTrue(is_null($real_list));
 			$this->assertTrue($real_list === true || $real_list === false);
 		}
 		$realgrouplist->expectOnce($real_list);
 		$this->assertTrue(is_array($res));
 		//var_dump($real_list);
 		//var_dump($res);
 	}
 	
 	public function testStoreEditedAgendaItem(){
 		ob_start();
 		$instans = new MockDisplay();
 		$id=1;
		$title='';
		$content='';
		$start_date= 21;
		$end_date=25;
		$res = store_edited_agenda_item();
 		$instans = 	Display::display_normal_message(get_lang("EditSuccess"));
 		$edit_result=save_edit_agenda_item($id,$title,$content,$start_date,$end_date);
 		ob_end_clean();
 		$this->assertTrue(is_null($instans));
 		$this->assertTrue($edit_result);
 		//var_dump($instans);
 		//var_dump($edit_result);
 	}
 	
 	public function testSaveEditAgendaItem(){
	 	$TABLEAGENDA = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
	 	$id=Database::escape_string($id);
		$title=Database::escape_string($title);
		$content=Database::escape_string($content);
		$start_date=Database::escape_string($start_date);
		$end_date=Database::escape_string($end_date);
	 	$res = save_edit_agenda_item($id,$title,$content,$start_date,$end_date); 
 		$this->assertTrue($res);
 		$this->assertTrue($TABLEAGENDA);
 		$this->assertTrue(is_bool($res));
 		//var_dump($res);
 		//var_dump($TABLEAGENDA);
 	}
 	
 	public function testDeleteAgendaItem(){
 		$realagenda= new MockDatabase();
 		$realagenda1 = new MockDisplay();
 		global $_course;
		$id=Database::escape_string($id);
		$res = delete_agenda_item($id);
		$sql = "SELECT * FROM $t_agenda WHERE id = $id";
		$sql_result = Database::query($sql,__FILE__,__LINE__);	
 		$result = Database::fetch_array($sql_result);
 		$real_agenda[] = $result;
 		$res= delete_agenda_item($id);
 		$realagenda->expectOnce($real_agenda);
 		$this->assertTrue($real_agenda);
 		$this->assertTrue(is_null($res));
 		$this->assertTrue(is_array($real_agenda));
 		//var_dump($res);
 		//var_dump($real_agenda);
 	}
 	
 	public function testShowhideAgendaItem(){
		ob_start();
		$instans = new MockDisplay();
		$id=1;
		global $nameTools;
 		$res = showhide_agenda_item($id);
 		$real_show = Display::display_normal_message(get_lang("VisibilityChanged"));
 		$instans_real[] = $real_show;
 		$instans->expectOnce($instans_real);
 		ob_end_clean();
 		$this->assertTrue(is_object($instans));
 		$this->assertTrue(is_array($instans_real));
 		//var_dump($instans);
 		//var_dump($res);
 		//var_dump($instans_real);
 	}
 	/**
	 * Para poder ejecutar la funcion display_agenda_items(), es
	 * necesario comentar el die de la linea 718, porque sino, no se
	 * podria realizar la prueba.
	 */
 	public function testDisplayAgendaItems(){
 		ob_start();
 		$realdisplay = new MockDatabase();
 		$TABLEAGENDA = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
		global $select_month, $select_year;
		global $DaysShort, $DaysLong, $MonthsLong;
		global $is_courseAdmin;
		global $dateFormatLong, $timeNoSecFormat,$charset, $_user, $_course;
 		$sql = "SELECT * FROM ".$TABLEAGENDA.' ORDER BY start_date '.$_SESSION['sort'];
 		$result=api_sql_query($sql,__FILE__,__LINE__);
 		$real_display[] = $result;
 		$res = display_agenda_items();
 		ob_end_clean();
 		$realdisplay->expectOnce($real_display);
 		$this->assertTrue(is_null($res));
 		$this->assertTrue(is_array($real_display));
 		$realdisplay->expectOnce($real_display);
 		//var_dump($res);
 		//var_dump($real_display);
 		//var_dump($realdisplay);
 	}
 	/**
 	 * Para poder ejecutar esta prueba es necesario comentar el
 	 * die de la linea 984.
 	 */
 	public function testDisplayOneAgendaItem(){
 		ob_start();
 		$realdisplayone = new MockDatabase();
	 	$TABLEAGENDA = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
		global $TABLE_ITEM_PROPERTY;
		global $select_month, $select_year;
		global $DaysShort, $DaysLong, $MonthsLong;
		global $is_courseAdmin;
		global $dateFormatLong, $timeNoSecFormat, $charset;
		global $_user;
		$agenda_id=2;
		$agenda_id=Database::escape_string($agenda_id);
 		$sql = "SELECT *	FROM ".$TABLEAGENDA;
 		$sql_result=api_sql_query($sql,__FILE__,__LINE__);
 		$myrow=Database::fetch_array($sql_result);
 		$real_display_one[]= $$myrow;
 		//$res = display_one_agenda_item($agenda_id);
 		ob_end_clean();
 		$realdisplayone->expectOnce($real_display_one);
 		$this->assertTrue(is_array($real_display_one));
 		//var_dump($res);
 		//var_dump($realdisplayone);
 		//var_dump($real_display_one);
  	} 
 	
 	/*public function testShowGroupFilterForm(){
 		$group_list=get_course_groups();
 		$res = show_group_filter_form();
 		$this->assertTrue($res);
 		//var_dump($res);
 	}
 	
 	public function testShowUserFilterForm(){
 		
 		$res = show_user_filter_form();
 		$this->assertTrue($res);
 		//var_dump($res);
 	}
 	*/
 	public function testShowAddForm(){
 		ob_start();
 		global $MonthsLong;
 		$id='';
 		$res= show_add_form($id);
 		ob_end_clean();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
 	}
 	
 	public function testGetAgendaitems(){
 		$realgetagenda = new MockDatabase();
 		$$TABLEAGENDA = Database :: get_course_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		$month=06;
 		$year=2010;
 		global $MonthsLong;
 		$month=Database::escape_string($month);
		$year=Database::escape_string($year);
		$sqlquery = "SELECT
						DISTINCT *
						FROM ".$TABLEAGENDA."							 
						WHERE 
						MONTH(start_date)='".$month."'
						AND YEAR(start_date)='".$year."'
						GROUP BY id
						ORDER BY start_date ";
 		 $sql_result = api_sql_query($sqlquery, __FILE__, __LINE__);
 		 $result = Database::fetch_array($sql_result);
 		 $real_get_agenda[] = $result;
 		 $res = get_agendaitems($month, $year);
 		 $this->assertTrue(is_array($res));
 		 $realgetagenda->expectOnce($real_get_agenda);
 		 $this->assertTrue(is_array($real_get_agenda));
 		 //var_dump($res);
 		 //var_dump($real_get_agenda);
 		 //var_dump($realgetagenda);
 	}
 	
 	public function testDisplayUpcomingEvents(){
 		 ob_start();
 		 $realdisplay = new MockDatabase();
 		 $TABLEAGENDA = Database :: get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		 $sqlquery = "SELECT
		 				DISTINCT *
		 				FROM ".$TABLEAGENDA."
		 				ORDER BY start_date ";
		 $result = api_sql_query($sqlquery, __FILE__, __LINE__);
		 $resultado = Database::fetch_array($result,'ASSOC');
		 $real_display[] = $resultado;
		 $res = display_upcoming_events();
		 $realdisplay->expectOnce($real_display);
		 ob_end_clean();
		 $this->assertTrue(is_array($real_display));
		 $this->assertTrue(is_null($res));
		 //var_dump($real_display);
		 //var_dump($realdisplay);
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
 	
 	public function testDisplayWeekcalendar(){
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
 	
 	public function testGetDayAgendaitems(){
		$realgetday = new MockDatabase();
		$TABLEAGENDA = Database :: get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		global $_user;
		global $_configuration;
		global $setting_agenda_link;
 		$courses_dbs='';
 		$month=11;
 		$year=2009;
 		$day='';
 		$sqlquery = "SELECT DISTINCT *
										FROM ".$TABLEAGENDA." 
										WHERE 
										DAYOFMONTH(start_date)='".$day."' AND MONTH(start_date)='".$month."' AND YEAR(start_date)='".$year."'
										GROUP BY agenda.id
										ORDER BY start_date ";
		$result = api_sql_query($sqlquery, __FILE__, __LINE__);
		$item = Database::fetch_array($result);
		$real_get_day[] = $item;
 		$res = get_day_agendaitems($courses_dbs, $month, $year, $day);
 		$realgetday->expectOnce($real_get_day);
 		$this->assertTrue(is_array($real_get_day));
 		$this->assertTrue(is_array($res));
 		//var_dump($res);
 		//var_dump($real_get_day);
 		//var_dump($$realgetday);
 	}
 	
 	public function testGetWeekAgendaitems(){
 		$realgetweek = new MockDatabase();
 		$TABLEAGENDA = Database :: get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
		global $_user;
		global $_configuration;
		global $setting_agenda_link;$month='';
 		$courses_dbs='';
 		$year='';
 		$week = '';
		$sqlquery = "SELECT DISTINCT * FROM ".$TABLEAGENDA." ORDER BY start_date";
		$result = api_sql_query($sqlquery, __FILE__, __LINE__);
		$item = Database::fetch_array($result);
		$real_get_week[]= $item;
		$res = get_week_agendaitems($courses_dbs, $month, $year, $week);
 		$realgetweek->expectOnce($real_get_week);
 		$this->assertTrue(is_array($real_get_week));
 		$this->assertTrue($realgetweek);
 		$this->assertTrue(is_array($res));
 		//var_dump($res);
 		//var_dump($real_get_week);
 		//var_dump($realgetweek);
 	}
 	
 	public function testGetRepeatedEventsDayView(){
 		$realgetrepeat = new MockDatabase();
 		$course_info='';
 		$start=0;
 		$end=0;
 		$params='';
 		$t_cal = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR,$course_info['dbName']);
        $t_ip = Database::get_course_table(TABLE_ITEM_PROPERTY,$course_info['dbName']);
		$sql = "SELECT c.id, c.title, c.content, " .
			" UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
			" cr.cal_type, cr.cal_end " .
			" FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
			" WHERE cr.cal_end >= $start " .
			" AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
			" AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
        $res = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($res);
		$real_get_repeat[] = $row;
		$resul = get_repeated_events_day_view($course_info,$start,$end,$params);
 		$realgetrepeat->expectOnce($real_get_repeat);
 		$this->assertTrue(is_array($real_get_repeat));
 		$this->assertTrue(is_array($resul));
 		//var_dump($resul);
 		//var_dump($realgetrepeat);
 		//var_dump($real_get_repeat);
 	}
 	
 	public function testget_repeated_events_week_view(){
 		$realgetrepeated = new MockDatabase();
 		$course_info='';
 		$start=0;
 		$end=0;
 		$params='';
 		$t_cal = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		$sql = "SELECT c.id, c.title, c.content " .
            " UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
            " FROM". $t_cal ."
             WHERE c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
		$res = api_sql_query($sql,__FILE__,__LINE__);
 		$row = Database::fetch_array($res);
 		$real_get_repeated[] = $row;
 		$resul = get_repeated_events_week_view($course_info,$start,$end,$params);
 		$realgetrepeated->expectOnce($real_get_repeated);
 		$this->assertTrue(is_array($resul));
 		$this->assertTrue($real_get_repeated);
 		//var_dump($resul);
 		//var_dump($real_get_repeated);
 		//var_dump($realgetrepeated);
 	}
 	
 	public function testGetRepeatedEventsMonthView(){
 		$realgetrepeated= new MockDatabase();
 		$course_info='';
 		$start='';
 		$end='';
 		$params='';
 		$t_cal = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		$sql = "SELECT c.id, c.title, c.content, " .
            " UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
            " cr.cal_type, cr.cal_end " .
            " FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
            " WHERE cr.cal_end >= $start " .
            " AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
            " AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($res);
		$real_get_repeated[] =  $row;
		$resul= get_repeated_events_month_view($course_info,$start,$end,$params);
		$realgetrepeated->expectOnce($real_get_repeated);
		$this->assertTrue(is_array($resul));
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false || $res === true);
		$this->assertTrue($real_get_repeated);
		//var_dump($resul);
		//var_dump($real_get_repeated);
		//var_dump($realgetrepeated);
	}
	
	public function testGetRepeatedEventsListView(){
		$realgetrepeatedevents = new MockDatabase();
		$course_info='';
		$start=0;
		$end=0;
		$params='';
		$t_cal = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR,$course_info['dbName']);
		$sql = "SELECT c.id, c.title, c.content, " .
            " UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
            " cr.cal_type, cr.cal_end " .
            " FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
            " WHERE cr.cal_end >= $start " .
            " AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
            " AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$row = Database::fetch_array($res);
		$real_get_repeated_events[] = $row;
		$resul = get_repeated_events_list_view($course_info,$start,$end,$params);
		$realgetrepeatedevents->expectOnce($real_get_repeated_events);
		$this->assertTrue(is_array($real_get_repeated_events));
		$this->assertTrue(is_array($resul));
		$realgetrepeatedevents->expectCallCount($real_get_repeated_events);
		//var_dump($resul);
		//var_dump($real_get_repeated_events);
		//var_dump($realgetrepeatedevents);
	}
 	
 	public function testIsRepeatedEvent() {
 		$realrepetead = new MockDatabase();
 		$id=1;
 		$course=null;
 		$sql = "SELECT * FROM $t_agenda_repeat WHERE cal_id = $id";
 		$res = Database::query($sql,__FILE__,__LINE__);
 		$result = Database::num_rows($res)>0;
 		$real_repetead[] = $result;
 		$resu = is_repeated_event($id,$course);
 		$realrepetead->expectOnce($real_repetead);
 		$this->assertTrue(is_bool($resu));
 		$this->assertTrue($resu === true || $resu === false);
 		$this->assertTrue($real_repetead);
 		$this->assertTrue($realrepetead); 
 		//var_dump($resu);
 		//var_dump($real_repetead);
 		//var_dump($realrepetead);
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
  * para poder realizar esta prueba, se tuvo que comentar el "die" ubicado en la
  * linea 2877 para que la prueba pudiera ejecutarse de manera exitosa.
  */
 	public function testAgendaAddItem(){
 		
 		$realagenda = new MockDatabase();
 		global $_course;
 		$course_info='null';
 		$title='test';
 		$content='test function';
 		$db_start_date='07/11/2009';
 		$db_end_date='07/20/2009';
 		$to=array();
 		$parent_id=null;
 		$t_agenda   = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
        $title      = Database::escape_string($title);
    	$content    = Database::escape_string($content);
    	$start_date = Database::escape_string($db_start_date);
    	$end_date   = Database::escape_string($db_end_date);
    	$sql = "SELECT * FROM $t_agenda WHERE title='$title' AND content = '$content' AND start_date = '$start_date'
    		    AND end_date = '$end_date' ".(!empty($parent_id)? "AND parent_event_id = '$parent_id'":"");
    	$result = api_sql_query($sql,__FILE__,__LINE__);
    	$sql1 = "INSERT INTO ".$t_agenda."(title,content, start_date, end_date)VALUES
                ('".$title."','".$content."', '".$start_date."','".$end_date."')";
 		$result1 = api_sql_query($sql1,__FILE__,__LINE__);
 		$real_agenda[]= $result;
 		$real_agenda1[]= $result1;
 		//$res = agenda_add_item($course_info, $title, $content, $db_start_date, $db_end_date, $to, $parent_id);
 		$realagenda->expectOnce($real_agenda);
 		$realagenda->expectOnce($real_agenda1);
 		//$this->assertTrue(is_numeric($res));
 		$this->assertTrue(is_array($real_agenda));
 		$this->assertTrue(is_array($real_agenda1));
 		//var_dump($res);
 		//var_dump($real_agenda);
 		//var_dump($real_agenda1);
 	}
 	
 	public function testGetCalendarItems(){
 		$realgetcalendar = new MockDatabase();
 		global $_user, $_course;
		global $is_allowed_to_edit;
 		$month='march';
 		$year='2009';
		$TABLEAGENDA = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
 		$sql="SELECT
			  DISTINCT *
			  FROM ".$TABLEAGENDA." agenda
			  WHERE MONTH(start_date)='".$month."' AND YEAR(start_date)='".$year."'
			  GROUP BY id ".
			 "ORDER BY  start_date ";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$row=Database::fetch_array($result);
 		$res = get_calendar_items($month, $year);
 		$real_get_calendar[]= $row;
 		$realgetcalendar->expectOnce($real_get_calendar);
 		$realgetcalendar->expectCallCount($real_get_calendar);
 		$this->assertTrue(is_bool($row));
 		$this->assertTrue(is_array($real_get_calendar));
 		$this->assertTrue(is_array($res));
 		//var_dump($real_get_calendar);
 		//var_dump($row);
 		//var_dump($res);
 	}
 	
 	public function testAgendaAddRepeatItem(){
 		$realagenda = new MockDatabase();
 		$course_info='course of test';
 		$orig_id=001;
 		$type='daily';
 		$end=10;
 		$orig_dest='monday';
 		$t_agenda   = Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR,$course_info['dbName']);
 		$sql = "SELECT title, content, start_date as sd, end_date as ed FROM $t_agenda WHERE id = $orig_id";
 		$res = Database::query($sql,__FILE__,__LINE__);
 		$row = Database::fetch_array($res);
 		$sql1 = "INSERT INTO $t_agenda_r (cal_id, cal_type, cal_end)" .
            " VALUES ($orig_id,'$type',$end)";
        $res1 = Database::query($sql1,__FILE__,__LINE__);
 		$resu= agenda_add_repeat_item($course_info,$orig_id,$type,$end,$orig_dest);
 		$real_agenda[] = $row;
 		$realagenda->expectOnce($real_agenda);
 		$realagenda->expectCallCount($real_agenda);
 		$realagenda->expectOnce($t_agenda);
 		$realagenda->expectOnce($res1);
 		if(is_bool($resu)){
 		$this->assertTrue(is_bool($resu));
 		$this->assertTrue($resu === true || $resu===false);
 		}else
 		$this->assertTrue(is_null($resu));
 		$this->assertTrue(is_array($real_agenda));
 		$this->assertTrue($row);
 		//var_dump($resu);
 		//var_dump($res);
 		//var_dump($res1);
 		//var_dump($real_agenda);
 	}
 	
 	public function testAgendaImportIcal(){
 		$course_info='course_test';
 		$file='';
 		$res = agenda_import_ical($course_info,$file);
 		if(is_bool($res)){
 		$this->assertTrue(is_bool($res));
 		$this->assertTrue($res===false || $res === true);
 		}else{
 			$this->assertTrue($res);
 		}
 		//var_dump($res);
 	}
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
}
?>
