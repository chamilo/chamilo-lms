<?php 

class Agenda {
	
	function __construct() {
		$this->tbl_global_agenda 	= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);  
		$this->tbl_personal_agenda 	= Database::get_user_personal_table(TABLE_PERSONAL_AGENDA);
		
		$this->course = null;
		$this->tbl_course_agenda = null;
		
		$course_info = api_get_course_info();
		if (!empty($course_info)) {
			$this->course = $course_info;
			$this->tbl_course_agenda 	= Database::get_course_table(TABLE_AGENDA, $course_info['db_name']);
		}
		
		$this->events				= array();
		
		$this->event_platform_color = 'red';//red
		$this->event_course_color 	= '#458B00'; //green
		$this->event_group_color 	= '#A0522D'; //siena
		$this->event_session_color 	= '#000080'; // blue
		$this->event_personal_color = 'steel blue'; //steel blue
		$this->type 				= 'personal';
		
	}
	
	/**
	 * 
	 * Add an event
	 * @param 	int		start tms
	 * @param 	int		end tms
	 * @param 	string	agendaDay, agendaWeek, month
	 * @param	string	personal, course or global (only works for personal by now) 
	 */
	function add_event($start, $end, $all_day, $view, $title, $content) {
		
		$start 		= date('Y-m-d H:i:s', $start);
		$end 		= date('Y-m-d H:i:s', $end);			
		$start 		= api_get_utc_datetime($start);
		$end 		= api_get_utc_datetime($end);				
		$all_day 	= isset($all_day) && $all_day == 'true' ? 1:0;
		
		$attributes = array();
		$id = null;
		switch($this->type) {
			case 'personal':
				$attributes['user'] 	= api_get_user_id();
				$attributes['title'] 	= $title;
				$attributes['text'] 	= $content;
				$attributes['date'] 	= $start;
				$attributes['enddate'] 	= $end;
				$attributes['all_day'] 	= $all_day;
				$id = Database::insert($this->tbl_personal_agenda, $attributes);
				break;
			case 'course':
				//$attributes['user'] 		= api_get_user_id();
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				$attributes['all_day'] 		= $all_day;
				$attributes['session_id'] 	= api_get_session_id();
				$attributes['c_id'] 		= $this->course['real_id'];
				
				//simple course event
				$id = Database::insert($this->tbl_course_agenda, $attributes);				
				api_item_property_update($this->course, TOOL_CALENDAR_EVENT, $id,"AgendaAdded", api_get_user_id(), '','',$start, $end);
			
				break;
			case 'admin':				
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				$attributes['all_day'] 		= $all_day;
				$attributes['access_url_id'] 	= api_get_current_access_url_id();
				$id = Database::insert($this->tbl_global_agenda, $attributes);
				break;
		}				
		return $id;				
	}
	
	function edit_event($id, $start, $end, $all_day, $view, $title, $content) {
		
		$start 		= date('Y-m-d H:i:s', $start);
		$start 		= api_get_utc_datetime($start);
		
		if ($all_day == '0') {
			$end 		= date('Y-m-d H:i:s', $end);		
			$end 		= api_get_utc_datetime($end);			
		}
		$all_day 	= isset($all_day) && $all_day == '1' ? 1:0;
		
		$attributes = array();
		
		switch($this->type) {
			case 'personal':
				$attributes['title'] 	= $title;
				$attributes['text'] 	= $content;
				$attributes['date'] 	= $start;
				$attributes['enddate'] 	= $end;
				Database::update($this->tbl_personal_agenda, $attributes, array('id = ?' => $id));
				break;
			case 'course':
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				Database::update($this->tbl_course_agenda, $attributes, array('id = ?' => $id));
				break;
			case 'admin':
				$attributes['title'] 		= $title;
				$attributes['content'] 		= $content;
				$attributes['start_date'] 	= $start;
				$attributes['end_date'] 	= $end;
				Database::update($this->tbl_global_agenda, $attributes, array('id = ?' => $id));
				break;
				break;
		}
	}
	
	function delete_event($id) {
		switch($this->type) {
			case 'personal':
				Database::delete($this->tbl_personal_agenda, array('id = ?' =>$id));
				break;
			case 'course':
				Database::delete($this->tbl_course_agenda, array('id = ?' =>$id));
				break;
			case 'admin':
				Database::delete($this->tbl_global_agenda, array('id = ?' =>$id));
				break;
		}
	}
	
	/**
	 * 
	 * Get agenda events
	 * @param	int		start tms
	 * @param	int		end tms
	 * @param 	string	agenda type (personal, admin or course)
	 * @param	int		user id
	 * @param	int		course id *integer* not the course code 
	 * 
	 */
	function get_events($start, $end, $user_id, $course_id = null) {	
					
		switch ($this->type) {
			case 'admin':
				$this->get_platform_events($start, $end);
				break;
			case 'course':
				
				$course_info = api_get_course_info_by_id($course_id);				
				$this->get_course_events($start, $end, $course_info);
				break;
			case 'personal':
			default:
				$this->get_personal_events($start, $end);
				$this->get_platform_events($start, $end);
				$my_course_list = array();
				if (!api_is_anonymous()) {
					$my_course_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(), true);
				}				
				if (!empty($my_course_list)) {
					foreach($my_course_list as $course_info_item) {
						if (isset($course_id) && !empty($course_id)) {
							if ($course_info_item['course_id'] == $course_id) {
								$this->get_course_events($start, $end, $course_info_item);
							}
						} else {
							$this->get_course_events($start, $end, $course_info_item);
						}
					}				
				}
				break;
		}		
		if (!empty($this->events)) {
			return json_encode($this->events);
		}
		return '';	
	}
	
	function move_event($id, $day_delta, $minute_delta) {		
		// we convert the hour delta into minutes and add the minute delta
		$delta = ($day_delta * 60 * 24) + $minute_delta;
		$delta = intval($delta);
		
		$event = $this->get_event($id);
		
		if (!empty($event)) {
			switch($this->type) {
				case 'personal':
					$sql = "UPDATE $this->tbl_personal_agenda SET date = DATE_ADD(date, INTERVAL $delta MINUTE), enddate = DATE_ADD(enddate, INTERVAL $delta MINUTE) 
							WHERE id=".intval($id);					
					$result = Database::query($sql);				
					break;
				case 'course':
					$sql = "UPDATE $this->tbl_course_agenda SET start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
					$result = Database::query($sql);					
					break;
				case 'admin':
					$sql = "UPDATE $this->tbl_global_agenda SET start_date = DATE_ADD(start_date,INTERVAL $delta MINUTE), end_date = DATE_ADD(end_date, INTERVAL $delta MINUTE)
							WHERE id=".intval($id);
					$result = Database::query($sql);
					break;
			}
		}	
		return 1;
	}
	
	/**
	 * Gets a single personal event	 
	 * @param int event id
	 */
	function get_event($id) {
		// make sure events of the personal agenda can only be seen by the user himself
		$id = intval($id);
		$event = null;
		switch ($this->type) {
			case 'personal':				
				$sql = " SELECT * FROM ".$this->tbl_personal_agenda." WHERE id=".$id." AND user = ".api_get_user_id();
				$result = Database::query($sql);				
				if (Database::num_rows($result)) {
					$event = Database::fetch_array($result, 'ASSOC');
				}
				break;
			case 'course':
				$sql = " SELECT * FROM ".$this->tbl_course_agenda." WHERE id=".$id;
				$result = Database::query($sql);
				if (Database::num_rows($result)) {
					$event = Database::fetch_array($result, 'ASSOC');
				}
				break;
			case 'admin':
				$sql = " SELECT * FROM ".$this->tbl_global_agenda." WHERE id=".$id;
				$result = Database::query($sql);
				if (Database::num_rows($result)) {
					$event = Database::fetch_array($result, 'ASSOC');
				}
			break;
		}
		return $event;
	}
	
	/**
	 * 
	 * Gets personal events
	 * @param int 	start date tms
	 * @param int	end   date tms
	 */
	function get_personal_events($start, $end) {
		$start 	= intval($start);
		$end	= intval($end);		
		$start  = api_get_utc_datetime($start);	
		$end  	= api_get_utc_datetime($end);
		$user_id = api_get_user_id();
		$sql 	= "SELECT * FROM ".$this->tbl_personal_agenda."
				   WHERE date >= '".$start."' AND (enddate <='".$end."' OR enddate IS NULL) AND user = $user_id";
		
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
				$event['description'] = $row['text']; 
				$event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;
				$my_events[] = $event;
				$this->events[]= $event;
			}
		}		
		return $my_events;
	}
	
	function get_course_events($start, $end, $course_info, $group_id = 0) {
		$course_id = $course_info['real_id'];
		$group_memberships 	= GroupManager::get_group_ids($course_id, api_get_user_id());
	
		$tlb_course_agenda	= Database::get_course_table(TABLE_AGENDA);
		$tbl_property 		= Database::get_course_table(TABLE_ITEM_PROPERTY);
	
		$user_id = api_get_user_id();
	
		if (is_array($group_memberships) && count($group_memberships)>0) {
			$sql = "SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
					FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
	                WHERE 	agenda.id 		= ip.ref  AND 
	                		ip.tool			='".TOOL_CALENDAR_EVENT."' AND 
	                		( ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) AND 
	                		ip.visibility	= '1' AND
	                		agenda.c_id = $course_id AND
	                		ip.c_id = $course_id
	                		
	            	";
		} else {
			if (api_get_user_id()) {
				$sql="SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
	                		FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
	            			WHERE agenda.id = ip.ref 
	            				AND ip.tool='".TOOL_CALENDAR_EVENT."'
	            				AND ( ip.to_user_id=$user_id OR ip.to_group_id='0')
	                            AND ip.visibility='1' AND 
	                            agenda.c_id = $course_id AND
	                			ip.c_id = $course_id
	                        ";
			} else {
				$sql="SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
	                                FROM ".$tlb_course_agenda." agenda, ".$tbl_property." ip
	                                WHERE agenda.id = ip.ref
	                                AND ip.tool='".TOOL_CALENDAR_EVENT."'
	                                AND ip.to_group_id='0'
	                                AND ip.visibility='1' AND 
	                                agenda.c_id = $course_id AND
	                				ip.c_id = $course_id
	                                ";
			}
		}
		
		$result = Database::query($sql);
		$events = array();
		if (Database::num_rows($result)) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				//Only show events from the session
				if (api_get_course_int_id()) {
					if ($row['session_id'] != api_get_session_id()) {
						continue;
					}
				}
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
				if (api_is_allowed_to_edit() && $this->type == 'course') {
					$event['editable'] 		= true;
				}	
	
				if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['start_date']);
				}
				if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['end_date']);
				}	
				$event['description'] = $row['content'];
				
				$event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;					
	
				$my_events[] = $event;	
	
				$this->events[] = $event;
			}
		}
		return $events;
	}
	
	function get_platform_events($start, $end) {
		$start 	= intval($start);
		$end	= intval($end);
		
		$start  = api_get_utc_datetime($start);	
		$end  	= api_get_utc_datetime($end);
				
		$access_url_id 	= api_get_current_access_url_id();
		
		$sql 	= "SELECT * FROM ".$this->tbl_global_agenda."
				   WHERE start_date >= '".$start."' AND end_date <= '".$end."' AND access_url_id = $access_url_id ";
		
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
				
				if (api_is_platform_admin() && $this->type == 'admin') {
					$event['editable'] 		= true;
				}
				
				if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
					$event['start'] = $this->format_event_date($row['start_date']);					
				}			
				if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
					$event['end'] = $this->format_event_date($row['end_date']);				
				}
				
				$event['description'] = $row['content'];								
				$event['allDay'] = isset($row['all_day']) && $row['all_day'] == 1 ? $row['all_day'] : 0;
				
				$my_events[] = $event;
				$this->events[]= $event;
			}
		}
		return $my_events;
		
	}
	
	
	
	function format_event_date($utc_time) {		
		return date('c', api_strtotime(api_get_local_time($utc_time)));
	}
	
}