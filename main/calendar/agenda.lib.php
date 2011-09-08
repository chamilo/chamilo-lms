<?php 

class Agenda {
	
	function __construct() {
		$this->tbl_global_agenda 	= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);  
		$this->tbl_personal_agenda 	= Database::get_user_personal_table(TABLE_PERSONAL_AGENDA);
		$this->events				= array();
		
		$this->event_platform_color = 'red';//red
		$this->event_course_color 	= '#458B00'; //green
		$this->event_group_color 	= '#A0522D'; //siena
		$this->event_session_color 	= '#000080'; // blue
		$this->event_personal_color = 'steel blue'; //steel blue
		
	}
	
	/**
	 * 
	 * Get agenda events
	 * @param string	$course_code
	 * @param int		$session_id
	 * @param int		$month
	 * @param year		$year
	 * @param day		$day
	 * @param type		all, global (platform), course
	 */
	function get_events($start, $end, $user_id) {
				
		$this->get_personal_events($start, $end);
		$this->get_platform_events($start, $end);
		
		$my_course_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(), true);
		foreach($my_course_list as $course_item) {			
			$this->get_course_events($start, $end, $course_item);
		}		
		
		if (!empty($this->events)) {
			return json_encode($this->events);
		}
		return '';	
	}
	
	function move_event($id, $type, $day_delta, $minute_delta) {
		
		// we convert the hour delta into minutes and add the minute delta
		$delta = ($day_delta * 60 * 24) + $minute_delta;
		
		//$table_agenda = Database::get_course_table ( TABLE_AGENDA );
		switch($type) {
			case 'personal':
				$personal_event = $this->get_personal_event($id);				
				if (!empty($personal_event)) {
					$sql = "UPDATE $this->tbl_personal_agenda SET date = DATE_ADD(date,INTERVAL $delta MINUTE), enddate = DATE_ADD(enddate,INTERVAL $delta MINUTE) 
							WHERE id=".intval($id);
					$result = Database::query($sql);
				}
				break;
		}
		return 1;
	}
	
	function get_personal_event($id) {
		// make sure events of the personal agenda can only be seen by the user himself
		$user = api_get_user_id();
		$sql = " SELECT * FROM ".$this->tbl_personal_agenda." WHERE id=".$id." AND user = ".$user;
		$result = Database::query($sql);
		$item = null;
		if (Database::num_rows($result)==1) {
			$item = Database::fetch_array($result);
		}
		return $item;
	}
	
	function get_personal_events($start, $end) {
		$start 	= intval($start);
		$end	= intval($end);		
		$start  = api_get_utc_datetime($start);	
		$end  	= api_get_utc_datetime($end);
		
		$sql 	= "SELECT * FROM ".$this->tbl_personal_agenda."
				   WHERE date>='".$start."' AND (enddate <='".$end."' OR enddate IS NULL) ";
		
		$result = Database::query($sql);
		if (Database::num_rows($result)) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				$event = array();
				$event['id'] 	  		= 'personal_'.$row['id'];
				$event['title'] 		= $row['title'];
				$event['className'] 	= 'personal';
				$event['borderColor'] 	= $event['backgroundColor'] = $this->event_personal_color;
				$event['editable'] 		= true;
				
				
				if (!empty($row['date']) && $row['date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['date']);
				}
									
				if (!empty($row['enddate']) && $row['enddate'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['enddate']);				
				}
								
				$event['allDay'] = false;
				$my_events[] = $event;
				$this->events[]= $event;
			}
		}		
		return $my_events;
	}
	
	function get_platform_events($start, $end) {
		$start 	= intval($start);
		$end	= intval($end);
		
		$start  = api_get_utc_datetime($start);	
		$end  	= api_get_utc_datetime($end);
				
		$access_url_id 	= api_get_current_access_url_id();
		
		$sql 	= "SELECT * FROM ".$this->tbl_global_agenda."
				   WHERE start_date>='".$start."' AND end_date<='".$end."' AND access_url_id = $access_url_id ";
		
		$result = Database::query($sql);
		$my_events = array();
		if (Database::num_rows($result)) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				$event = array();
				$event['id'] 	  		= 'platform_'.$row['id'];
				$event['title'] 		= $row['title'];
				$event['className'] 	= 'platform';
				$event['allDay'] 	  	= 'false';
				$event['borderColor'] 	= $event['backgroundColor'] = $this->event_platform_color;
				$event['editable'] 		= false;
				
				if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['start_date']);					
				}			
				if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['end_date']);
					$event['allDay'] = false;
				} else {
					$event['allDay'] = true;
				}			
				$my_events[] = $event;
				$this->events[]= $event;
			}
		}
		return $my_events;
		
	}
	
	function get_course_events($start, $end, $course_info, $group_id = 0) {
		$group_memberships 	= GroupManager::get_group_ids($course_info['db_name'], api_get_user_id());
						
		$tlb_course_agenda	= Database::get_course_table(TABLE_AGENDA, $course_info['db_name']);
		$tbl_property 		= Database::get_course_table(TABLE_ITEM_PROPERTY, $course_info['db_name']);
		
		$user_id = api_get_user_id();
		
		if (is_array($group_memberships) && count($group_memberships)>0) {
        	$sql = "SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
					FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
                    WHERE agenda.id = ip.ref   ".$show_all_current."
                    AND ip.tool='".TOOL_CALENDAR_EVENT."'
                    AND ( ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )
                    AND ip.visibility='1'
            	";
		} else {
        	if (api_get_user_id()) {
            	$sql="SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                		FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
            			WHERE agenda.id = ip.ref   ".$show_all_current." 
            				AND ip.tool='".TOOL_CALENDAR_EVENT."'
            				AND ( ip.to_user_id=$user_id OR ip.to_group_id='0')
                            AND ip.visibility='1'
                        ";
			} else {
				$sql="SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                                FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
                                WHERE agenda.id = ip.ref   ".$show_all_current."
                                AND ip.tool='".TOOL_CALENDAR_EVENT."'
                                AND ip.to_group_id='0'
                                AND ip.visibility='1'
                                ";
            	}
		}
		$result = Database::query($sql);
		$events = array();
		if (Database::num_rows($result)) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				
				$event = array();
				$event['id'] 	  		= 'course_'.$row['id'];
				$event['title'] 		= $row['title'];
				$event['className'] 	= 'course';
				$event['allDay'] 	  	= 'false';
			//	var_dump($row);
				$event['borderColor'] 	= $event['backgroundColor'] = $this->event_course_color;
				if (isset($row['session_id']) && !empty($row['session_id'])) {
					$event['borderColor'] 	= $event['backgroundColor'] = $this->event_session_color;
				}
				
				if (isset($row['to_group_id']) && !empty($row['to_group_id'])) {
					$event['borderColor'] 	= $event['backgroundColor'] = $this->event_group_color;
				}
				
				$event['editable'] 		= false;
				
				if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['start_date']);					
				}			
				if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['end_date']);
					$event['allDay'] = false;
				} else {
					$event['allDay'] = true;
				}			
				$my_events[] = $event;
				
		
				$this->events[] = $event;
			}
		}
		
		return $events;
	}
	
	function format_event_date($utc_time) {
		return date('c', api_strtotime(api_get_local_time($utc_time)));
	}
	
}