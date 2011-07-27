<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like library, provides functions for thematic option inside attendance tool. It's also used like model to thematic_controller (MVC pattern)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> SQL fixes, 
 * By the way there are 3 tables that are mixed,
 * there should be 3 different classes Thematic, ThematicAdvanced and Thematic plan and a controller
 * @package chamilo.course_progress
 */

/**
 * Thematic class can be used to instanciate objects or as a library for thematic control
 * @package chamilo.course_progress
 */
class Thematic
{	
	private $session_id;
	private $thematic_id;
	private $thematic_title;
	private $thematic_content;
	private $thematic_plan_id;
	private $thematic_plan_title;
	private $thematic_plan_description;
	private $thematic_plan_description_type;	
	private $thematic_advance_id;
	private $attendance_id;
	private $thematic_advance_content;
	private	$start_date;
	private $duration;

	public function __construct() {}
	
	
	/**
	 * Get the total number of thematic inside current course and current session
	 * @see SortableTable#get_total_number_of_items()
	 */
	public function get_number_of_thematics() {
		$tbl_thematic = Database :: get_course_table(TABLE_THEMATIC);
		$condition_session = '';
        if (!api_get_session_id()) {
			$condition_session = api_get_session_condition(0);
        }
		$sql = "SELECT COUNT(id) AS total_number_of_items FROM $tbl_thematic WHERE active = 1 $condition_session ";
		$res = Database::query($sql);
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	}


	/**
	 * Get the thematics to display on the current page (fill the sortable-table)
	 * @param   int     offset of first user to recover
	 * @param   int     Number of users to get
	 * @param   int     Column to sort on
	 * @param   string  Order (ASC,DESC)
	 * @see SortableTable#get_table_data($from)
	 */
	public function get_thematic_data($from, $number_of_items, $column, $direction) {
		$tbl_thematic = Database :: get_course_table(TABLE_THEMATIC);
        $condition_session = '';
        if (!api_get_session_id()) {    
    	    $condition_session = api_get_session_condition(0);
        }        
	    $column = intval($column);
	    $from   = intval($from);
	    $number_of_items = intval($number_of_items);
        
		if (!in_array($direction, array('ASC','DESC'))) {
	    	$direction = 'ASC';
	    }
                
		$sql = "SELECT id AS col0, title AS col1, display_order AS col2, session_id  FROM $tbl_thematic
				WHERE active = 1 $condition_session
				ORDER BY col2 LIMIT $from,$number_of_items ";
		$res = Database::query($sql);
		
		$thematics = array ();

		$param_gradebook = '';
		if (isset($_SESSION['gradebook'])) {
			$param_gradebook = '&gradebook='.$_SESSION['gradebook'];
		}
        $user_info = api_get_user_info(api_get_user_id());
        
		while ($thematic = Database::fetch_row($res)) {
		    $session_star = '';
            if (api_get_session_id() == $thematic[3]) {
                $session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
            }  
			$thematic[1] = '<a href="index.php?'.api_get_cidreq().'&action=thematic_details&thematic_id='.$thematic[0].$param_gradebook.'">'.Security::remove_XSS($thematic[1], STUDENT).$session_star.'</a>';			
			if (api_is_allowed_to_edit(null, true)) {
				$actions  = '';
				
			    if (api_get_session_id()) {                  
                    if (api_get_session_id() == $thematic[3]) {
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('lesson_plan.png',get_lang('ThematicPlan'),'','22').'</a>&nbsp;';
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('lesson_plan_calendar.png',get_lang('ThematicAdvance'),'','22').'</a>&nbsp;';
                                    
                        $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('edit.png',get_lang('Edit'),'',22).'</a>';                        
                        $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('delete.png',get_lang('Delete'),'',22).'</a>';	
                    } else {
                        $actions .= Display::return_icon('lesson_plan_na.png',get_lang('ThematicPlan'),'',22).'&nbsp;';
                        $actions .= Display::return_icon('lesson_plan_calendar_na.png',get_lang('ThematicAdvance'),'',22).'&nbsp;';
                        $actions .= Display::return_icon('edit_na.png',get_lang('Edit'),'',22);
                        $actions .= Display::return_icon('delete_na.png',get_lang('Delete'),'',22).'&nbsp;';
                        $actions .= Display::url(Display::return_icon('cd.gif', get_lang('Copy')), 'index.php?'.api_get_cidreq().'&action=thematic_copy&thematic_id='.$thematic[0].$param_gradebook);    
                    }                    
                } else {	
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_plan_list&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('lesson_plan.png',get_lang('ThematicPlan'),'','22').'</a>&nbsp;';
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_list&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('lesson_plan_calendar.png',get_lang('ThematicAdvance'),'','22').'</a>&nbsp;';
                                               
					if ($thematic[2] > 1) {
						$actions .= '<a href="'.api_get_self().'?action=moveup&'.api_get_cidreq().'&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('up.png', get_lang('Up'),'',22).'</a>';
					} else {
						$actions .= Display::return_icon('up_na.png','&nbsp;','',22);
					}
					if ($thematic[2] < self::get_max_thematic_item()) {
						$actions .= '<a href="'.api_get_self().'?action=movedown&a'.api_get_cidreq().'&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('down.png',get_lang('Down'),'',22).'</a>';
					} else {
						$actions .= Display::return_icon('down_na.png','&nbsp;','',22);
					}
					$actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_edit&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('edit.png',get_lang('Edit'),'',22).'</a>';
					$actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_delete&thematic_id='.$thematic[0].$param_gradebook.'">'.Display::return_icon('delete.png',get_lang('Delete'),'',22).'</a>';
					
				
				}
				$thematics[] = array($thematic[0], $thematic[1], $actions);
			}
		}
		return $thematics;
	}

	/**
	 * Get the maximum display order of the thematic item
	 * @return int	Maximum display order
	 */
	public function get_max_thematic_item($use_session = true) {
		// Database table definition
		$tbl_thematic   = Database :: get_course_table(TABLE_THEMATIC);
		$session_id     = api_get_session_id();
		if ($use_session) {
		    $condition_session = api_get_session_condition($session_id);
		} else {
		    $condition_session = '';
		}		
		$sql = "SELECT MAX(display_order) FROM $tbl_thematic WHERE active = 1 $condition_session";
		$rs = Database::query($sql);		
		$row = Database::fetch_array($rs);
		return $row[0];
	}

	/**
	 * Move a thematic
	 *
	 * @param string	Direction (up, down)
	 * @param int		Thematic id
	 */
	public function move_thematic($direction, $thematic_id) {
		// Database table definition
		$tbl_thematic = Database :: get_course_table(TABLE_THEMATIC);

		// sort direction
		if ($direction == 'up') {
			$sortorder = 'DESC';
		} else {
			$sortorder = 'ASC';
		}

		$session_id = api_get_session_id();
		$condition_session = api_get_session_condition($session_id);

		$sql = "SELECT id, display_order FROM $tbl_thematic WHERE active = 1 $condition_session ORDER BY display_order $sortorder";
		$res = Database::query($sql);
		$found = false;

		//Variable definition
		$current_id = 0;
		$next_id 	= 0;

		while ($row = Database::fetch_array($res)) {
			if ($found && empty($next_id)) {
				$next_id = intval($row['id']);
				$next_display_order = intval($row['display_order']);
			}

			if ($row['id'] == $thematic_id) {
				$current_id = intval($thematic_id);
				$current_display_order = intval($row['display_order']);
				$found = true;
			}
		}

		// get last done thematic advance before move thematic list
		$last_done_thematic_advance = $this->get_last_done_thematic_advance();

		if (!empty($next_display_order) && !empty($current_id)) {
			$sql = "UPDATE $tbl_thematic SET display_order = $next_display_order WHERE id = $current_id ";
			Database::query($sql);
		}
		if (!empty($current_display_order) && !empty($next_id)) {
			$sql = "UPDATE $tbl_thematic SET display_order = $current_display_order WHERE id = $next_id ";
			Database::query($sql);
		}

		// update done advances with de current thematic list
		$update_done_advances = $this->update_done_thematic_advances($last_done_thematic_advance);

	}

	/**
	 * get thematic list
	 * @param	int		Thematic id (optional), get list by id
	 * @return	array	Thematic data
	 */
	 public function get_thematic_list($thematic_id = null, $course_code = null, $session_id = null) {

	 	// set current course and session
	 	if (isset($course_code)) {
	 		$course_info = api_get_course_info($course_code);
	 		$tbl_thematic = Database :: get_course_table(TABLE_THEMATIC, $course_info['dbName']);
	 	} else {
	 		$tbl_thematic = Database :: get_course_table(TABLE_THEMATIC);
	 	}
	 	
	 	if (isset($session_id)) {
	 		$session_id = intval($session_id);	 		
	 	} else {
	 		$session_id = api_get_session_id();
	 	}

	    $data = array();
	    $condition = '';
	    if (isset($thematic_id)) {
	    	$thematic_id = intval($thematic_id);
	    	$condition = " WHERE id = $thematic_id AND active = 1 ";
	    } else {
	        $condition_session = '';
	        if (empty($session_id)) {
                $condition_session = api_get_session_condition(0);
	        }
	    	$condition = " WHERE active = 1 $condition_session ";
	    }
		$sql = "SELECT * FROM $tbl_thematic $condition ORDER BY display_order ";
		
		$res = Database::query($sql);
		if (Database::num_rows($res) > 0) {
			if (!empty($thematic_id)) {
				$data = Database::fetch_array($res,'ASSOC');
			} else {
				while ($row = Database::fetch_array($res,'ASSOC')) {
					$data[$row['id']] = $row;
				}
			}
		}
		return $data;
	 }

	/**
	 * insert or update a thematic
	 * @return int last thematic id
	 */
	public function thematic_save() {
		global $_course;
		// definition database table
		$tbl_thematic = Database::get_course_table(TABLE_THEMATIC);

		// protect data
		$id = intval($this->thematic_id);
		$title = Database::escape_string($this->thematic_title);
		$content = Database::escape_string($this->thematic_content);
		$session_id = intval($this->session_id);
		$user_id = api_get_user_id();

		// get the maximum display order of all the glossary items
		$max_thematic_item = $this->get_max_thematic_item(false);
		
		if (empty($id)) {
			// insert
			$sql = "INSERT INTO $tbl_thematic(title, content, active, display_order, session_id) VALUES ('$title', '$content', 1, ".(intval($max_thematic_item)+1).", $session_id) ";
			Database::query($sql);
			$last_id = Database::insert_id();
			if (Database::affected_rows()) {
				// save inside item property table
				$last_id = Database::insert_id();
				api_item_property_update($_course, 'thematic', $last_id,"ThematicAdded", $user_id);
			}
		} else {
			// update
			$sql = "UPDATE $tbl_thematic SET title = '$title', content = '$content', session_id = $session_id WHERE id = $id ";
			Database::query($sql);
			$last_id = $id;
			if (Database::affected_rows()) {
				// save inside item property table
				api_item_property_update($_course, 'thematic', $last_id,"ThematicUpdated", $user_id);
			}
		}
		return $last_id;
	}

	/**
	 * Delete logically (set active field to 0) a thematic
	 * @param	int|array	One or many thematic ids
	 * @return	int			Affected rows
	 */
	public function thematic_destroy($thematic_id) {
		global $_course;
		$tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
		$affected_rows = 0;
		$user_id = api_get_user_id();
		if (is_array($thematic_id)) {
			foreach ($thematic_id as $id) {
				$id	= intval($id);
				$sql = "UPDATE $tbl_thematic SET active = 0 WHERE id = $id";
				Database::query($sql);
				$affected_rows += Database::affected_rows();
				if (!empty($affected_rows)) {
					// update row item property table
                                        api_item_property_update($_course, 'thematic', $id,"ThematicDeleted", $user_id);
					//api_item_property_update($_course, TOOL_COURSE_PROGRESS, $id,"delete", $user_id);
				}
			}
		} else  {
			$thematic_id	= intval($thematic_id);
			$sql = "UPDATE $tbl_thematic SET active = 0 WHERE id = $thematic_id";
			Database::query($sql);
			$affected_rows = Database::affected_rows();
			if (!empty($affected_rows)) {
				// update row item property table
				//api_item_property_update($_course, TOOL_COURSE_PROGRESS, $thematic_id,"delete", $user_id);
                                api_item_property_update($_course, 'thematic', $thematic_id,"ThematicDeleted", $user_id);
			}
		}
		return $affected_rows;
	}
	
	public function copy($thematic_id) {
	    $thematic = self::get_thematic_list($thematic_id, api_get_course_id(), 0);	    
	    $thematic_copy = new Thematic();
	    $thematic_copy->set_thematic_attributes('', $thematic['title'].' - '.get_lang('Copy'), $thematic['content'], api_get_session_id());
	    
	    $new_thematic_id = $thematic_copy->thematic_save();
	    if (!empty($new_thematic_id)) {
            $thematic_advanced = self::get_thematic_advance_by_thematic_id($thematic_id);
            if(!empty($thematic_advanced)) {
                foreach($thematic_advanced as $item) {    
                    $thematic = new Thematic();    
                    $thematic->set_thematic_advance_attributes(0, $new_thematic_id,  0, $item['content'], $item['start_date'], $item['duration']);                                                          
                    $thematic->thematic_advance_save();        
                }
            }
            $thematic_plan = self::get_thematic_plan_data($thematic_id);            
            if (!empty($thematic_plan)) {
                foreach ($thematic_plan as $item) {                    
                    $thematic = new Thematic();    
                    $thematic->set_thematic_plan_attributes($new_thematic_id, $item['title'], $item['description'], $item['description_type']);                                                          
                    $thematic->thematic_plan_save();                
                }
            }
	    }	    
	}

	/**
	 * Get the total number of thematic advance inside current course
	 * @see SortableTable#get_total_number_of_items()
	 */
	public function get_number_of_thematic_advances() {
		global $thematic_id;
		$tbl_thematic_advance = Database :: get_course_table(TABLE_THEMATIC_ADVANCE);			
		$sql = "SELECT COUNT(id) AS total_number_of_items FROM $tbl_thematic_advance WHERE thematic_id = $thematic_id ";
		$res = Database::query($sql);
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	}


	/**
	 * Get the thematic advances to display on the current page (fill the sortable-table)
	 * @param   int     offset of first user to recover
	 * @param   int     Number of users to get
	 * @param   int     Column to sort on
	 * @param   string  Order (ASC,DESC)
	 * @see SortableTable#get_table_data($from)
	 */
	public function get_thematic_advance_data($from, $number_of_items, $column, $direction) {
		global $thematic_id;
		$tbl_thematic_advance = Database :: get_course_table(TABLE_THEMATIC_ADVANCE);
		$thematic_data = self::get_thematic_list($thematic_id);
	    $column = intval($column);
	    $from   = intval($from);
	    $number_of_items = intval($number_of_items);
		if (!in_array($direction, array('ASC','DESC'))) {
	    	$direction = 'ASC';
	    }
		$data = array();
		
		if (api_is_allowed_to_edit(null, true)) {
		    
    		$sql = "SELECT id AS col0, start_date AS col1, duration AS col2, content AS col3 FROM $tbl_thematic_advance
    				WHERE thematic_id = $thematic_id
    				ORDER BY col$column $direction LIMIT $from,$number_of_items ";
    		
    		$list = api_get_item_property_by_tool('thematic_advance', api_get_course_id(), api_get_session_id());
    		
    		$elements = array();
    		foreach ($list as $value) {
    		    $elements[] = $value['ref'];
    		}		
    				
    		$res = Database::query($sql);    		
    		$i = 1;
    		while ($thematic_advance = Database::fetch_row($res)) {
    		    
    		    if(in_array($thematic_advance[0], $elements)) {
        			$thematic_advance[1] = api_get_local_time($thematic_advance[1]);
        			$thematic_advance[1] = api_format_date($thematic_advance[1], DATE_TIME_FORMAT_LONG);			
        				$actions  = '';
        				$actions .= '<a href="index.php?'.api_get_cidreq().'&action=thematic_advance_edit&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.Display::return_icon('edit.png',get_lang('Edit'),'',22).'</a>';
        				$actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=thematic_advance_delete&thematic_id='.$thematic_id.'&thematic_advance_id='.$thematic_advance[0].'">'.Display::return_icon('delete.png',get_lang('Delete'),'',22).'</a></center>';
        				$data[] = array($i, $thematic_advance[1], $thematic_advance[2], $thematic_advance[3], $actions);			
        			$i++;
    		    }
    		}
		}
		return $data;
	}

	/**
	 * get thematic advance data by tematic id
	 * @param	int		Thematic id
	 * @param	string	Course code (optional)
	 * @return	array	data
	 */
	 public function get_thematic_advance_by_thematic_id($thematic_id, $course_code = null) {
        
	    $course_info = api_get_course_info($course_code);
	    
	 	// set current course
	 	if (isset($course_code)) {	 		
	 		$tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE, $course_info['dbName']);
	 	} else {
	 		$tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
	 	}

	    $thematic_id = intval($thematic_id);
	    $data = array();	    
		$sql = "SELECT * FROM $tbl_thematic_advance WHERE thematic_id = $thematic_id ";
		
		$elements = array();
		$list = api_get_item_property_by_tool('thematic_advance', $course_info['code'], api_get_session_id());
	    foreach($list as $value) {
            $elements[] = $value['ref'];
	    }
		
		$res = Database::query($sql);
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_array($res, 'ASSOC')) {
			    if (in_array($row['id'], $elements)) {
				    $data[] = $row;
			    }
			}
		}
		return $data;
	 }

	 
	 public function get_thematic_plan_div($data, $id = false) {
	 	$final_return = array();
	
		foreach ($data as $thematic_id => $thematic_plan_data) {
			$new_thematic_plan_data = array();
			foreach($thematic_plan_data as $thematic_item) {
				$thematic_simple_list[] = $thematic_item['description_type'];
				$new_thematic_plan_data[$thematic_item['description_type']] = $thematic_item;
			}			
				 
			$new_id = ADD_THEMATIC_PLAN;			
			if (!empty($thematic_simple_list)) {
				foreach($thematic_simple_list as $item) {				
					//if ($item >= ADD_THEMATIC_PLAN) {
			 			//$new_id = $item + 1;
			 			$default_thematic_plan_title[$item] = $new_thematic_plan_data[$item]['title'];
			 		//}
				}
			}
			
			$no_data = true;
			$session_star = '';		 
			$return = '<div id="thematic_plan_'.$thematic_id.'">';
			if (!empty($default_thematic_plan_title)) {
				foreach ($default_thematic_plan_title as $id=>$title) {
					$thematic_plan_data_item = '';
			 		//avoid others
			 		if ($title == 'Others' && empty($data[$thematic_id][$id]['description'])) {
				 		continue; 
				 	}     
					if (!empty($data[$thematic_id][$id]['title']) && !empty($data[$thematic_id][$id]['description'])) {
						if (api_is_allowed_to_edit(null, true)) {
							if ($data[$thematic_id][$id]['session_id'] !=0) {
								$session_star = api_get_session_image(api_get_session_id(), $user_info['status']);
							}
						}
				 		$return  .= Display::tag('h3', Security::remove_XSS($data[$thematic_id][$id]['title'], STUDENT).$session_star);
				 		$return  .= Security::remove_XSS($data[$thematic_id][$id]['description'], STUDENT);
				 	 	$no_data  = false;
					}
				}
			}							
			if ($no_data) {
				$return .= '<div><em>'.get_lang('StillDoNotHaveAThematicPlan').'</em></div>';
			}
			$return  .= '</div>';
			$final_return[$thematic_id] = $return;
		}
		return $final_return;
	}
	 	
	 /**
	 * get thematic advance list
	 * @param	int		Thematic advance id (optional), get data by thematic advance list
	 * @param	string	Course code (optional)
	 * @return	array	data
	 */
	 public function get_thematic_advance_list($thematic_advance_id = null, $course_code = null, $force_session_id = false) {	 	// set current course
	    $course_info = api_get_course_info($course_code);
	 	if (isset($course_code)) {	 		
	 		$tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE, $course_info['dbName']);
	 		$tbl_thematic = Database::get_course_table(TABLE_THEMATIC,$course_info['dbName']);
	 	} else {
	 		$tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);
	 		$tbl_thematic = Database::get_course_table(TABLE_THEMATIC);
	 	}

	 	$data = array();

	 	$condition = '';
	 	if (isset($thematic_advance_id)) {
	 		$thematic_advance_id = intval($thematic_advance_id);
	 		$condition = " AND a.id = $thematic_advance_id ";
	 	}
	 	
	 	/*if ($force_session_id) {
            $sql = "SELECT a.* FROM $tbl_thematic_advance a INNER JOIN $tbl_thematic t ON t.id = a.thematic_id WHERE 1 $condition AND t.session_id = ".api_get_session_id()." ORDER BY start_date ";
	 	} else {		
		    $sql = "SELECT * FROM $tbl_thematic_advance a WHERE 1 $condition  ORDER BY start_date ";
	 	}*/
	 	
	 	$sql = "SELECT * FROM $tbl_thematic_advance a WHERE 1 $condition  ORDER BY start_date ";
	 	
        $elements =  array();
	 	if ($force_session_id) {	 	    
    	 	$list = api_get_item_property_by_tool('thematic_advance', $course_info['code'], api_get_session_id());
    	 	foreach($list as $value) {
    	 	    $elements[$value['ref']]= $value;    	 	    
    	 	}
	 	}
	 
        $res = Database::query($sql);
		if (Database::num_rows($res) > 0) {			
			if (!empty($thematic_advance_id)) {		
				$data = Database::fetch_array($res);
			} else {
				// group all data group by thematic id
				$tmp = array();
				while ($row = Database::fetch_array($res, 'ASSOC')) {				    				    
				    
					$tmp[] = $row['thematic_id'];
					if (in_array($row['thematic_id'], $tmp)) {
					    if ($force_session_id) {
					        if (in_array($row['id'], array_keys($elements))) {
					            $row['session_id'] = $elements[$row['id']]['id_session'];
						        $data[$row['thematic_id']][$row['id']] = $row;					        
					        }
					    } else {
					        $data[$row['thematic_id']][$row['id']] = $row;
					    }
					}
				    
				}
			}
		}
		return $data;
	 }

	/**
	 * insert or update a thematic advance
	 * @todo problem
	 * @return int last thematic advance id
	 */
	public function thematic_advance_save() {
	    global $_course;

		// definition database table
		$tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

		// protect data
		$id               = intval($this->thematic_advance_id);
		$tematic_id       = intval($this->thematic_id);
		$attendance_id    = intval($this->attendance_id);
		$content          = Database::escape_string($this->thematic_advance_content);
		$start_date       = Database::escape_string($this->start_date);
		$duration	      = intval($this->duration);
        $user_id          = api_get_user_id();

		if (empty($id)) {			
			// Insert
			$sql = "INSERT INTO $tbl_thematic_advance (thematic_id, attendance_id, content, start_date, duration) VALUES ($tematic_id, $attendance_id, '$content', '".api_get_utc_datetime($start_date)."', '$duration') ";
			Database::query($sql);
			$last_id = Database::insert_id();
            if (Database::affected_rows()) {
                api_item_property_update($_course, 'thematic_advance', $last_id,"ThematicAdvanceAdded", $user_id);
            }
		} else {
			// update
			$sql = "UPDATE $tbl_thematic_advance SET thematic_id = '$tematic_id', attendance_id = '$attendance_id', content = '$content', start_date = '".api_get_utc_datetime($start_date)."', duration = '$duration' WHERE id = $id ";
			Database::query($sql);			
            if (Database::affected_rows()) {
                api_item_property_update($_course, 'thematic_advance', $id, "ThematicAdvanceUpdated", $user_id);
            }
		}
		return $last_id;
	}

	/**
	 * delete  thematic advance
	 * @param	int		Thematic advance id
	 * @return	int		Affected rows
	 */
	public function thematic_advance_destroy($thematic_advance_id) {
		global $_course;

		// definition database table
		$tbl_thematic_advance = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

		// protect data
		$thematic_advance_id = intval($thematic_advance_id);
                $user_id = api_get_user_id();

		$sql = "DELETE FROM $tbl_thematic_advance WHERE id = $thematic_advance_id ";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
        if ($affected_rows) {
            api_item_property_update($_course, 'thematic_advance', $thematic_advance_id,"ThematicAdvanceDeleted", $user_id);
        }

		return $affected_rows;
	}

	/**
	 * get thematic plan data
	 * @param	int		Thematic id (optional), get data by thematic id
	 * @param	int		Thematic plan description type (optional), get data by description type
	 * @return 	array	Thematic plan data
	 */
	public function get_thematic_plan_data($thematic_id = null, $description_type = null) {

		// definition database table
		$tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);
		$tbl_thematic      = Database::get_course_table(TABLE_THEMATIC);
		 
		$data = array();
		$condition = '';
		if (isset($thematic_id)) {
			$thematic_id = intval($thematic_id);
			$condition .= " AND thematic_id = $thematic_id ";
		}
		if (isset($description_type)) {
			$description_type = intval($description_type);
			$condition .= " AND description_type = $description_type ";
		}
		
		$session_condition = '';
		if (!api_get_session_id()) {
		    $session_condition .= ' AND t.session_id = 0'; 
		}
		
		$sql = "SELECT tp.id, thematic_id, tp.title, description, description_type, t.session_id 
		        FROM $tbl_thematic_plan tp INNER JOIN $tbl_thematic t ON (t.id=tp.thematic_id) 
		        WHERE 1 $condition  $session_condition ";
		$items = $elements_to_show = $elements_to_show_values = array();
		
	    $items = api_get_item_property_by_tool('thematic_plan', api_get_course_id(), api_get_session_id());
	 	$elements_to_show = array();
		foreach($items as $value) {
		    $elements_to_show[$value['ref']] = $value;
		}
		$elements_to_show_values = array_keys($elements_to_show);
		
		$rs	 = Database::query($sql);
		if (Database::num_rows($rs) > 0) {			
			if (!isset($thematic_id) && !isset($description_type)) {		
				// group all data group by thematic id
				$tmp = array();
				while ($row = Database::fetch_array($rs,'ASSOC')) {	
				    if (!in_array($row['id'], $elements_to_show_values) && !api_get_session_id()) {
				        continue;
				    }			    		
                    $tmp[] = $row['thematic_id'];
                    if (in_array($row['thematic_id'], $tmp)) {                        
                        $row['session_id'] =isset($elements_to_show[$row['id']]) ? $elements_to_show[$row['id']]['id_session']: 0;                        
                        $data[$row['thematic_id']][$row['description_type']] = $row;
                    }					
				}				
			} else {                
				while ($row = Database::fetch_array($rs,'ASSOC')) {
				    if (!in_array($row['id'], $elements_to_show_values) && !api_get_session_id()) {
				        continue;
				    }
				    $row['session_id'] =isset($elements_to_show[$row['id']]) ? $elements_to_show[$row['id']]['id_session']: 0;
				    
					$data[] = $row;
				}	
			}
		}

		return $data;
	}

	/**
	 * insert or update a thematic plan
	 * @return int affected rows
	 */
	public function thematic_plan_save() {
		global $_course;
		// definition database table		
		$tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

		// protect data
		$thematic_id = intval($this->thematic_id);
		$title 		 = Database::escape_string($this->thematic_plan_title);
		$description = Database::escape_string($this->thematic_plan_description);
		$description_type = intval($this->thematic_plan_description_type);		
		$user_id     = api_get_user_id();
        $session_id  = api_get_session_id();

	
		$list        = api_get_item_property_by_tool('thematic_plan', api_get_course_id(), api_get_session_id());
		
		$elements_to_show = array();
		foreach($list as $value) {
		    $elements_to_show[]= $value['ref'];
		}
		$condition = '';
		if (!empty($elements_to_show)) {
		    $condition = "AND id IN (".implode(',', $elements_to_show).") ";
		}
		// check thematic plan type already exists
		$sql = "SELECT id FROM $tbl_thematic_plan WHERE thematic_id = $thematic_id AND description_type = $description_type ";
		
		$rs	 = Database::query($sql);
		
		$affected_rows = 0;
		if (Database::num_rows($rs) > 0) {
		    //if (!empty($thematic_plan_data)) {
            $row_thematic_plan = Database::fetch_array($rs);
            $thematic_plan_id = $row_thematic_plan['id'];
            
            //Checking the session
            $thematic_plan_data = api_get_item_property_info(api_get_course_int_id(), 'thematic_plan', $thematic_plan_id);
            
            $update = false;            
            if (in_array($thematic_plan_id, $elements_to_show)) {
                $update = true;
            }
           
            
            if ($update) {
    			// update
    			$upd = "UPDATE $tbl_thematic_plan SET title = '$title', description = '$description' WHERE id = $thematic_plan_id";
    			Database::query($upd);
    			$affected_rows = Database::affected_rows();
                if ($affected_rows) {
                    api_item_property_update($_course, 'thematic_plan', $thematic_plan_id, "ThematicPlanUpdated", $user_id);
                }                
            } else {            
                // insert
    			$ins = "INSERT INTO $tbl_thematic_plan(thematic_id, title, description, description_type) VALUES($thematic_id, '$title', '$description', $description_type) ";    		
    			Database::query($ins);
                $last_id = Database::insert_id();
    			$affected_rows = Database::affected_rows();
                if ($affected_rows) {
                    api_item_property_update($_course, 'thematic_plan', $last_id,"ThematicPlanAdded", $user_id);
                }                     
            }            
		} else {		    
			// insert
			$ins = "INSERT INTO $tbl_thematic_plan(thematic_id, title, description, description_type) VALUES($thematic_id, '$title', '$description', $description_type) ";			
			Database::query($ins);
            $last_id = Database::insert_id();
			$affected_rows = Database::affected_rows();
            if ($affected_rows) {
                api_item_property_update($_course, 'thematic_plan', $last_id,"ThematicPlanAdded", $user_id);
            }
		}
		return $affected_rows;
	}

	/**
	 * delete a thematic plan description
	 * @param	int		Thematic id
	 * @param	int		Description type
	 * @return	int		Affected rows
	 */
	public function thematic_plan_destroy($thematic_id, $description_type) {
        global $_course;
		// definition database table
		$tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

		// protect data
		$thematic_id = intval($thematic_id);
		$description_type = intval($description_type);
                $user_id = api_get_user_id();

                // get thematic plan id
                $thematic_plan_data = $this->get_thematic_plan_data($thematic_id, $description_type);
                $thematic_plan_id = $thematic_plan_data[0]['id'];

                // delete
		$sql = "DELETE FROM $tbl_thematic_plan WHERE thematic_id = $thematic_id AND description_type = $description_type ";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
                if ($affected_rows) {
                    api_item_property_update($_course, 'thematic_plan', $thematic_plan_id,"ThematicPlanDeleted", $user_id);
                }

		return $affected_rows;
	}

	/**
	 * Get next description type for a new thematic plan description (option 'others')
	 * @param	int		Thematic id
	 * @return 	int		New Description type
	 */
	public function get_next_description_type($thematic_id) {

		// definition database table
		$tbl_thematic_plan = Database::get_course_table(TABLE_THEMATIC_PLAN);

		// protect data
		$thematic_id = intval($thematic_id);
		$description_type = intval($description_type);
		$next_description_type = 0;

		$sql = "SELECT MAX(description_type) as max FROM $tbl_thematic_plan WHERE thematic_id = $thematic_id AND description_type >= ".ADD_THEMATIC_PLAN." ";
		$rs = Database::query($sql);
		$row = Database::fetch_array($rs);
		$last_description_type = $row['max'];

		if (isset($last_description_type)) {
			$row = Database::fetch_array($rs);

			$next_description_type = $last_description_type + 1;
		} else {
			$next_description_type = ADD_THEMATIC_PLAN;
		}

		return $next_description_type;
	}


	/**
	 * update done thematic advances from thematic details interface
	 * @param 	int		Thematic id
	 * @return	int		Affected rows
	 */
	public function update_done_thematic_advances($thematic_advance_id) {
		global $_course;
		$thematic_data         = $this->get_thematic_list(null, api_get_course_id());
		$thematic_advance_data = $this->get_thematic_advance_list(null, api_get_course_id(), true);
		$tbl_thematic_advance  = Database::get_course_table(TABLE_THEMATIC_ADVANCE);

		$affected_rows = 0;
        $user_id       = api_get_user_id();
        
        $all = array();
        //var_dump($thematic_advance_data);
        if (!empty($thematic_data)) {
            foreach ($thematic_data as $thematic) {
                $thematic_id = $thematic['id'];
                if (!empty($thematic_advance_data[$thematic['id']])) {
                    foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
                        $all[] = $thematic_advance['id'];
                    }
                }
            }
        }           
        $error = null;                    
		$a_thematic_advance_ids = array();
		if (!empty($thematic_data)) {
			foreach ($thematic_data as $thematic) {
			    $my_affected_rows = 0;
				$thematic_id = $thematic['id'];
				if (!empty($thematic_advance_data[$thematic['id']])) {
					foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
						
						$item_info = api_get_item_property_info(api_get_course_int_id(), 'thematic_advance', $thematic_advance['id']);
						//var_dump($item_info );
						
												
						if ($item_info['id_session'] == api_get_session_id()) {
    						
    						$a_thematic_advance_ids[] = $thematic_advance['id'];
    						
    						// update done thematic for previous advances ((done_advance = 1))
    						$upd = "UPDATE $tbl_thematic_advance set done_advance = 1 WHERE id = ".$thematic_advance['id']." ";
    						Database::query($upd);
    						$my_affected_rows = Database::affected_rows();    						
    						$affected_rows += $my_affected_rows;
                            //if ($my_affected_rows) {
                                api_item_property_update($_course, 'thematic_advance', $thematic_advance['id'], "ThematicAdvanceDone", $user_id);
                            //}
    						if ($thematic_advance['id'] == $thematic_advance_id) {
    							break 2;
    						}
						}
					}
				}
			}
		}
		
		
		// Update done thematic for others advances (done_advance = 0)
		if (!empty($a_thematic_advance_ids) && count($a_thematic_advance_ids) > 0) {
		    $diff = array_diff($all, $a_thematic_advance_ids);		    
	        if (!empty($diff)) {	            
    			$upd = "UPDATE $tbl_thematic_advance set done_advance = 0 WHERE id IN(".implode(',',$diff).") ";
                Database::query($upd);
	        }
	        
            // update item_property
            $tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
            $session_id = api_get_session_id();
            
            // get all thematic advance done
            $rs_thematic_done = Database::query("SELECT ref FROM $tbl_item_property WHERE tool='thematic_advance' AND lastedit_type='ThematicAdvanceDone' AND id_session = $session_id ");
            if (Database::num_rows($rs_thematic_done) > 0) {
                while ($row_thematic_done = Database::fetch_array($rs_thematic_done)) {
                    $ref = $row_thematic_done['ref'];
                    if (in_array($ref, $a_thematic_advance_ids)) { continue; }
                    // update items
                    Database::query("UPDATE $tbl_item_property SET lastedit_date='".api_get_utc_datetime()."', lastedit_type='ThematicAdvanceUpdated', lastedit_user_id = $user_id WHERE tool='thematic_advance' AND ref=$ref AND id_session = $session_id  ");
                }
            }
		}
		return $affected_rows;	     
	}

	/**
	 * Get last done thematic advance from thematic details interface
	 * @return	int		Last done thematic advance id
	 */
	public function get_last_done_thematic_advance() {
		$thematic_data          = $this->get_thematic_list();		
		$thematic_advance_data  = $this->get_thematic_advance_list(null, api_get_course_id(), true);
		
		
		$a_thematic_advance_ids = array();
		$last_done_advance_id = 0;
		if (!empty($thematic_data)) {
			foreach ($thematic_data as $thematic) {
				$thematic_id = $thematic['id'];
				if (!empty($thematic_advance_data[$thematic['id']])) {
					foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
						if ($thematic_advance['done_advance'] == 1) {
							$a_thematic_advance_ids[] = $thematic_advance['id'];
						}
					}
				}
			}
		}
		if (!empty($a_thematic_advance_ids)) {
			$last_done_advance_id = array_pop($a_thematic_advance_ids);
			$last_done_advance_id = intval($last_done_advance_id);
		}		
		return $last_done_advance_id;
	}

	/**
	 * Get next thematic advance not done from thematic details interface
	 * @return	int		next thematic advance not done
	 */
	public function get_next_thematic_advance_not_done() {

		$thematic_data = $this->get_thematic_list();
		$thematic_advance_data = $this->get_thematic_advance_list();
		$a_thematic_advance_ids = array();
		$next_advance_not_done = 0;
		if (!empty($thematic_data)) {
			foreach ($thematic_data as $thematic) {
				$thematic_id = $thematic['id'];
				if (!empty($thematic_advance_data[$thematic['id']])) {
					foreach ($thematic_advance_data[$thematic['id']] as $thematic_advance) {
						if ($thematic_advance['done_advance'] == 0) {
							$a_thematic_advance_ids[] = $thematic_advance['id'];
						}
					}
				}
			}
		}

		if (!empty($a_thematic_advance_ids)) {
			$next_advance_not_done = array_shift($a_thematic_advance_ids);
			$next_advance_not_done = intval($next_advance_not_done);
		}

		return $next_advance_not_done;
	}

	/**
	 * Get total average of thematic advances
	 * @param	string	Course code (optional)
	 * @param	int		Session id	(optional)
	 * @return 	float	Average of thematic advances
	 */
	public function get_total_average_of_thematic_advances($course_code = null, $session_id = null) {
        
	    if (api_get_session_id()) {
		    $thematic_data = $this->get_thematic_list(null, api_get_course_id());
	    } else {
	        $thematic_data = $this->get_thematic_list(null, api_get_course_id(), 0);	        
	    }	    
		$new_thematic_data = array();
		if (!empty($thematic_data)) {
    		foreach ($thematic_data as $item) {    		    
                $new_thematic_data[] = $item;    		    
    		}
    		$thematic_data = $new_thematic_data;
		}
		
		$thematic_advance_data = $this->get_thematic_advance_list(null, $course_code, true);
		$a_average_of_advances_by_thematic = array();
		$total_average = 0;
		if (!empty($thematic_data)) {
			foreach ($thematic_data as $thematic) {
				$thematic_id = $thematic['id'];
				$a_average_of_advances_by_thematic[$thematic_id] = $this->get_average_of_advances_by_thematic($thematic_id, $course_code);
			}
		}

		// calculate total average
		if (!empty($a_average_of_advances_by_thematic)) {
			$count_tematics = count($thematic_data);
			$score          = array_sum($a_average_of_advances_by_thematic);
			$total_average  = round(($score*100)/($count_tematics*100));
		}		
		return $total_average;
	}


	/**
	 * Get average of advances by thematic
	 * @param	int		Thematic id
	 * @param	string	Course code (optional)
	 * @return 	float	Average of thematic advances
	 */
	public function get_average_of_advances_by_thematic($thematic_id, $course_code = null) {

		$thematic_advance_data = $this->get_thematic_advance_by_thematic_id($thematic_id, $course_code);
		$average = 0;
		if (!empty($thematic_advance_data)) {
			// get all done advances by thematic
			$advances = array();
			$count_done_advances = 0;
			$average = 0;			
			foreach ($thematic_advance_data as $thematic_advance) {
				if ($thematic_advance['done_advance'] == 1) {
					$count_done_advances++;					
				}
				$advances[] = $thematic_advance['done_advance'];
			}
			// calculate average by thematic
			$count_total_advances = count($advances);			
			$average = round(($count_done_advances*100)/$count_total_advances);			
		}

		return $average;

	}

	/**
	 * set attributes for fields of thematic table
	 * @param	int		Thematic id
	 * @param	string	Thematic title
	 * @param	string	Thematic content
	 * @param	int		Session id
	 * @return void
	 */
	 public function set_thematic_attributes($id = null, $title = '', $content = '', $session_id = 0) {
	 	$this->thematic_id         = $id;
	 	$this->thematic_title      = $title;
	 	$this->thematic_content    = $content;
	 	$this->session_id          = $session_id;
	 }

	/**
	 * set attributes for fields of thematic_plan table
	 * @param	int		Thematic id
	 * @param	string	Thematic plan title
	 * @param	string	Thematic plan description
	 * @param	int		Thematic plan description type
	 * @return void
	 */
	 public function set_thematic_plan_attributes($thematic_id = 0, $title = '', $description = '', $description_type = 0) {
	 	$this->thematic_id = $thematic_id;
	 	$this->thematic_plan_title = $title;
	 	$this->thematic_plan_description = $description;
	 	$this->thematic_plan_description_type = $description_type;
	 }

	 /**
	 * set attributes for fields of thematic_advance table
	 * @param	int		Thematic advance id
	 * @param	int		Thematic id
	 * @param	int		Attendance id
	 * @param	string	Content
	 * @param	string	Date and time
	 * @param	int		Duration in hours
	 * @return void
	 */
	 public function set_thematic_advance_attributes($id = null, $thematic_id = 0,  $attendance_id = 0, $content = '', $start_date = '0000-00-00 00:00:00', $duration = 0) {
	 	$this->thematic_advance_id = $id;
	 	$this->thematic_id = $thematic_id;
	 	$this->attendance_id = $attendance_id;
	 	$this->thematic_advance_content = $content;
	 	$this->start_date = $start_date;
	 	$this->duration = $duration;
	 }

	 /**
	  * set thematic id
	  * @param	int	 Thematic id
	  * @return void
	  */
	 public function set_thematic_id($thematic_id) {
	 	$this->thematic_id = $thematic_id;
	 }

	 /**
	  * get thematic id
	  * @return void
	  */
	 public function get_thematic_id() {
	 	return $this->thematic_id;
	 }

	/**
	 * Get thematic plan titles by default
	 * @return array
	 */
	public function get_default_thematic_plan_title() {
		$default_thematic_plan_titles = array();
		$default_thematic_plan_titles[1]= get_lang('Objectives');
		$default_thematic_plan_titles[2]= get_lang('SkillToAcquire');
		$default_thematic_plan_titles[3]= get_lang('Methodology');
		$default_thematic_plan_titles[4]= get_lang('Infrastructure');		
		$default_thematic_plan_titles[5]= get_lang('Assessment');
		$default_thematic_plan_titles[6]= get_lang('Others');
		return $default_thematic_plan_titles;
	}

	/**
	 * Get thematic plan icons by default
	 * @return array
	 */
	public function get_default_thematic_plan_icon() {
		$default_thematic_plan_icon = array();
		$default_thematic_plan_icon[1]= 'icons/32/objective.png';
		$default_thematic_plan_icon[2]= 'icons/32/skills.png';
		$default_thematic_plan_icon[3]= 'icons/32/strategy.png';
		$default_thematic_plan_icon[4]= 'icons/32/laptop.png';
		$default_thematic_plan_icon[5]= 'icons/32/assessment.png';
		$default_thematic_plan_icon[6]= 'icons/32/wizard.png';
		return $default_thematic_plan_icon;
	}

	/**
	 * Get questions by default for help
	 * @return array
	 */
	public function get_default_question() {
		$question = array();
		$question[1]= get_lang('ObjectivesQuestions');
		$question[2]= get_lang('SkillToAcquireQuestions');
		$question[3]= get_lang('MethodologyQuestions');
		$question[4]= get_lang('InfrastructureQuestions');
		$question[5]= get_lang('AssessmentQuestions');
		return $question;
	}

	/**
	 * buid a string datetime from array
	 * @param	array	array containing data e.g: $array('Y'=>'2010',  'F' => '02', 'd' => '10', 'H' => '12', 'i' => '30')
	 * @return	string	date and time e.g: '2010-02-10 12:30:00'
	 */
	public function build_datetime_from_array($array) {
		$year	 = '0000';
		$month = $day = $hours = $minutes = $seconds = '00';
		if (isset($array['Y']) && isset($array['F']) && isset($array['d']) && isset($array['H']) && isset($array['i'])) {
			$year = $array['Y'];
			$month = $array['F'];
			if (intval($month) < 10 ) $month = '0'.$month;
			$day = $array['d'];
			if (intval($day) < 10 ) $day = '0'.$day;
			$hours = $array['H'];
			if (intval($hours) < 10 ) $hours = '0'.$hours;
			$minutes = $array['i'];
			if (intval($minutes) < 10 ) $minutes = '0'.$minutes;
		}
		if (checkdate($month,$day,$year)) {
			$datetime = $year.'-'.$month.'-'.$day.' '.$hours.':'.$minutes.':'.$seconds;
		}
		return $datetime;
	}

}