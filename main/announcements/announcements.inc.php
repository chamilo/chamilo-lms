<?php
/* For licensing terms, see /license.txt */
/**
* Include file with functions for the announcements module.
* @package chamilo.announcements
* @todo use OOP
*/

/**
 * @author jmontoya
 *
 */
class AnnouncementManager  {
    
    
	public function __construct() {
	}	
	
	public function get_tags() {
	    return array('((user_name))','((teacher_name))','((teacher_email))','((course_title))', '((course_link))');
	}
	
	public function parse_content($content, $course_code) {
    	$reader_info  = api_get_user_info(api_get_user_id());
		$course_info  = api_get_course_info($course_code);
	    $teacher_list = Coursemanager::get_teacher_list_from_course_code($course_info['code']);
	    
	    $teacher_name = '';
	    if (!empty($teacher_list)) {
	        foreach($teacher_list as $teacher_data) {    	  
	            $teacher_name  = api_get_person_name($teacher_data['firstname'], $teacher_data['lastname']);
	            $teacher_email = $teacher_data['email'];
	            break;
	        }
	    }    	    
		$course_link = api_get_course_url();
		
		$data['username']        = $reader_info['username'];    		
		$data['teacher_name']    = $teacher_name;
		$data['teacher_email']   = $teacher_email;    		
		$data['course_title']    = $course_info['name'];
		$data['course_link']     = Display::url($course_link, $course_link);
	
        $content = str_replace(self::get_tags(), $data, $content);
        return $content;	    
	}
		
	/**
	 * Gets all announcements from a course 
	 * @param	string course db
	 * @param	int session id
	 * @return	array html with the content and count of announcements or false otherwise
	 */
	public static function get_all_annoucement_by_course($course_db, $session_id = 0) {
		if (empty($course_db)) {
			return false;
		}
		$session_id = intval($session_id);
		
		$tbl_announcement	= Database::get_course_table(TABLE_ANNOUNCEMENT, $course_db['db_name']);
		$tbl_item_property  = Database::get_course_table(TABLE_ITEM_PROPERTY, $course_db['db_name']);
		/*
		if (empty($group_id)) {
			$group_condition = "AND (toolitemproperties.to_group_id='0' OR toolitemproperties.to_group_id is null)";
		} else {
			$group_condition = "AND (toolitemproperties.to_group_id='$group_id')";
		}
		$group_condition
		*/
			
		$sql="SELECT DISTINCT announcement.id, announcement.title, announcement.content
				FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
				WHERE announcement.id = toolitemproperties.ref
				AND toolitemproperties.tool='announcement'				
				AND announcement.session_id  = '$session_id'
				ORDER BY display_order DESC";
		$rs = Database::query($sql);
		$num_rows = Database::num_rows($rs);
		$result = array();
		if ($num_rows>0) {
			$list = array();
			while ($row = Database::fetch_array($rs)) {
				$list[] = $row;		
			}		
			return $list;
		}		
		return false;
	}
		
		
	/**
	* This functions swithes the visibility a course resource
	* using the visibility field in 'item_property'
	* @param    array	the course array
	* @param    int     ID of the element of the corresponding type
	* @return   bool    False on failure, True on success
	*/
	public static function change_visibility_announcement($_course, $id) {
		$item_visibility = api_get_item_visibility($_course, TOOL_ANNOUNCEMENT, $id);
		if ($item_visibility == '1') {
			api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'invisible', api_get_user_id());
		} else {
			api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'visible', api_get_user_id());
		}	
	    return true;
	}
		
	/**
	 * Deletes an announcement
	 * @param array the course array
	 * @param int 	the announcement id
	 */	
	public static function delete_announcement($_course, $id) {	
		api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, 'delete', api_get_user_id());
	}
	
	/**
	 * Deletes all announcements by course
	 * @param array the course array
	 */
	public static function delete_all_announcements($_course) {	
		$announcements = self::get_all_annoucement_by_course($_course, api_get_session_id());
		
		foreach ($announcements  as $annon) {
			api_item_property_update($_course, TOOL_ANNOUNCEMENT, $annon['id'], 'delete', api_get_user_id());	
		}	
	}
	
	/**
	* Displays one specific announcement
	* @param $announcement_id, the id of the announcement you want to display
	*/
	public static function display_announcement($announcement_id) {	
		if ($announcement_id != strval(intval($announcement_id))) { return false; } // potencial sql injection
        global $charset;
		$tbl_announcement 	= Database::get_course_table(TABLE_ANNOUNCEMENT);
		$tbl_item_property	= Database::get_course_table(TABLE_ITEM_PROPERTY);
	    
		if (api_is_allowed_to_edit(false,true) || (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
            $sql_query = "  SELECT announcement.*, toolitemproperties.*
                            FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
                            WHERE announcement.id = toolitemproperties.ref
                            AND announcement.id = '$announcement_id'
                            AND toolitemproperties.tool='announcement'                                                        
                            ORDER BY display_order DESC";   
		} else {
    		if (api_get_user_id() != 0) {
    			$sql_query = "	SELECT announcement.*, toolitemproperties.*
    							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
    							WHERE announcement.id = toolitemproperties.ref
    							AND announcement.id = '$announcement_id'
    							AND toolitemproperties.tool='announcement'
    							AND (toolitemproperties.to_user_id='".api_get_user_id()."' OR toolitemproperties.to_group_id='0')
    							AND toolitemproperties.visibility='1'
    							ORDER BY display_order DESC";
    	
    		} else {
    			$sql_query = "	SELECT announcement.*, toolitemproperties.*
    							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
    							WHERE announcement.id = toolitemproperties.ref
    							AND announcement.id = '$announcement_id'
    							AND toolitemproperties.tool='announcement'
    							AND toolitemproperties.to_group_id='0'
    							AND toolitemproperties.visibility='1'";
    		}
		}		
		$sql_result = Database::query($sql_query);
		if (Database::num_rows($sql_result) > 0 ) {
    		$result		= Database::fetch_array($sql_result, 'ASSOC');    		
    	
			$title		 = $result['title'];
			$content	 = $result['content'];
			$content     = make_clickable($content);
			$content     = text_filter($content);			    			
		    	
    		echo "<table height=\"100\" width=\"100%\" cellpadding=\"5\" cellspacing=\"0\" class=\"data_table\">";
    		echo "<tr><td><h2>".$title."</h2></td></tr>";   		
    		
    		
    		if (api_is_allowed_to_edit(false,true) || (api_get_course_setting('allow_user_edit_announcement') && !api_is_anonymous())) {
    		    $modify_icons = "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=modify&id=".$announcement_id."\">".Display::return_icon('edit.png', get_lang('Edit'),'',22)."</a>";
                if ($result['visibility'] == 1) {
                    $image_visibility = "visible";
                    $alt_visibility = get_lang('Hide');
                } else {
                    $image_visibility="invisible";
                    $alt_visibility = get_lang('Visible');
                }
                global $stok;
                
    		    $modify_icons .=  "<a href=\"".api_get_self()."?".api_get_cidreq()."&origin=".(!empty($_GET['origin'])?Security::remove_XSS($_GET['origin']):'')."&action=showhide&id=".$announcement_id."&sec_token=".$stok."\">".
                            Display::return_icon($image_visibility.'.png', $alt_visibility,'',22)."</a>";    		    		
                    
                if (api_is_allowed_to_edit(false,true)) {
                    $modify_icons .= "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=delete&id=".$announcement_id."&sec_token=".$stok."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">".
                        Display::return_icon('delete.png', get_lang('Delete'),'',22).
                        "</a>";
                }                            
                echo "<tr><th style='text-align:right'>$modify_icons</th></tr>";
    		}
    		    		
    		$content = self::parse_content($content, api_get_course_id());
    		
    		echo "<tr><td>$content</td></tr>";       		

            echo "<tr><td class=\"announcements_datum\">" . get_lang('LastUpdateDate') . " : " .api_convert_and_format_date($result['insert_date'], DATE_TIME_FORMAT_LONG). "</td></tr>";
            
            // User or group icon
            $sent_to_icon = '';            
            if ($result['to_group_id']!== '0' and $result['to_group_id']!== 'NULL') {
                $sent_to_icon = Display::return_icon('group.gif', get_lang('AnnounceSentToUserSelection'));
            }            
            $sent_to        = self::sent_to('announcement', $announcement_id);            
            $sent_to_form   = self::sent_to_form($sent_to);
            echo Display::tag('td', get_lang('SentTo').' : '.$sent_to_form, array('class'=>'announcements_datum'));     
                		
    	    $attachment_list = self::get_attachment($announcement_id);
        
            if (count($attachment_list)>0) {
                echo "<tr><td>";
                $realname = $attachment_list['path'];
                $user_filename = $attachment_list['filename'];
                $full_file_name = 'download.php?file='.$realname;
                echo '<br/>';                
                echo Display::return_icon('attachment.gif',get_lang('Attachment'));
                echo '<a href="'.$full_file_name.' "> '.$user_filename.' </a>';
                echo '<span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
                echo '</td></tr>';
            }           
    		echo "</table>";
		} else {
		    api_not_allowed();
		}
	}	
	
		
	/**
	 * Store an announcement in the database (including its attached file if any)
	 * @param string    Announcement title (pure text)
	 * @param string    Content of the announcement (can be HTML)
	 * @param int       Display order in the list of announcements
	 * @param array     Array of users and groups to send the announcement to
	 * @param array	    uploaded file $_FILES
	 * @param string    Comment describing the attachment
	 * @return int      false on failure, ID of the announcement on success
	 */
	public static function add_announcement($emailTitle, $newContent, $order, $to, $file = array(), $file_comment='') {
		global $_course;	
	
		$tbl_announcement  = Database::get_course_table(TABLE_ANNOUNCEMENT);
		$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
	
		// filter data
		$emailTitle = Database::escape_string($emailTitle);
		$newContent = Database::escape_string($newContent);
		$order = intval($order);
	    $now = api_get_utc_datetime();
		// store in the table announcement
		$sql = "INSERT INTO $tbl_announcement SET content = '$newContent', title = '$emailTitle', end_date = '$now', display_order ='$order', session_id=".api_get_session_id();
		$result = Database::query($sql);
		if ($result === false) {
			return false;
		} else {
			//Store the attach file
			$last_id = Database::insert_id();
			if (!empty($file)) {
				$save_attachment = self::add_announcement_attachment_file($last_id, $file_comment, $_FILES['user_upload']);
			}
		
			// store in item_property (first the groups, then the users
			
			if (!is_null($to)) {
				 // !is_null($to): when no user is selected we send it to everyone			 
				$send_to = self::separate_users_groups($to);
				// storing the selected groups
				if (is_array($send_to['groups'])) {
					foreach ($send_to['groups'] as $group) {
						api_item_property_update($_course, TOOL_ANNOUNCEMENT, $last_id, "AnnouncementAdded", api_get_user_id(), $group);
					}
				}
		
				// storing the selected users
				if (is_array($send_to['users'])) {
					foreach ($send_to['users'] as $user) {
						api_item_property_update($_course, TOOL_ANNOUNCEMENT, $last_id, "AnnouncementAdded", api_get_user_id(), '', $user);
					}
				}
			} else {
				// the message is sent to everyone, so we set the group to 0
				api_item_property_update($_course, TOOL_ANNOUNCEMENT, $last_id, "AnnouncementAdded", api_get_user_id(), '0');
			}
			return $last_id;
		}
	}
	
	/*
		   STORE ANNOUNCEMENT  GROUP ITEM
	*/
	public static function add_group_announcement($emailTitle,$newContent, $order, $to, $to_users, $file = array(), $file_comment='') {
		global $_course;	
	
		// database definitions
		$tbl_announcement  = Database::get_course_table(TABLE_ANNOUNCEMENT);
		$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
		
		$emailTitle = Database::escape_string($emailTitle);
		$newContent = Database::escape_string($newContent);
		$order = intval($order);
		
		$now = api_get_utc_datetime();
	
		// store in the table announcement
		$sql = "INSERT INTO $tbl_announcement SET content = '$newContent', title = '$emailTitle', end_date = '$now', display_order ='$order', session_id=".api_get_session_id();
		$result = Database::query($sql);
		if ($result === false) {
			return false;
		}
	
		//store the attach file
		$last_id = Database::insert_id();
		if (empty($file)) {
			$save_attachment = self::add_announcement_attachment_file($last_id, $file_comment, $file);
		}
	
		// store in item_property (first the groups, then the users
		if (!isset($to_users)) // !isset($to): when no user is selected we send it to everyone
		{
			$send_to=self::separate_users_groups($to);
			// storing the selected groups
			if (is_array($send_to['groups'])) {
				foreach ($send_to['groups'] as $group) {
					api_item_property_update($_course, TOOL_ANNOUNCEMENT, $last_id, "AnnouncementAdded", api_get_user_id(), $group);
				}
			}
		}
		else // the message is sent to everyone, so we set the group to 0
		{
			// storing the selected users
			if (is_array($to_users)) {
				foreach ($to_users as $user) {
					api_item_property_update($_course, TOOL_ANNOUNCEMENT, $last_id, "AnnouncementAdded", api_get_user_id(), '', $user);
				}
			}
		}
		return $last_id;
	}
	
	
	/*
		EDIT ANNOUNCEMENT 
	*/
	/**
	* This function stores the announcement item in the announcement table 
	* and updates the item_property table
	* 
	* @param int 	id of the announcement
	* @param string email 
	* @param string content
	* @param array 	users that will receive the announcement
	* @param mixed 	attachment 
	* @param string file comment
	* 
	*/
	public static function edit_announcement($id, $emailTitle, $newContent, $to, $file = array(), $file_comment='') {	
		global $_course;	
		$tbl_item_property  = Database::get_course_table(TABLE_ITEM_PROPERTY);	
		$tbl_announcement 	= Database::get_course_table(TABLE_ANNOUNCEMENT);
	
		$emailTitle = Database::escape_string($emailTitle);
		$newContent = Database::escape_string($newContent);
		$id = intval($id);
		
		// store the modifications in the table announcement
	 	$sql = "UPDATE $tbl_announcement SET content='$newContent', title = '$emailTitle' WHERE id='$id'";
		$result = Database::query($sql);
	
		// save attachment file
		$row_attach = self::get_attachment($id);
		$id_attach = intval($row_attach['id']);
	
		if (!empty($file)) {
			if (empty($id_attach)) {
				self::add_announcement_attachment_file($id,$file_comment,$file);
			} else {
				self::edit_announcement_attachment_file($id_attach,$file,$file_comment);
			}
		}
	
		// we remove everything from item_property for this
		$sql_delete="DELETE FROM $tbl_item_property WHERE ref='$id' AND tool='announcement'";
		$result = Database::query($sql_delete);
	
		// store in item_property (first the groups, then the users
	
		if (!is_null($to)) {
			// !is_null($to): when no user is selected we send it to everyone
			
			$send_to = self::separate_users_groups($to);
	
			// storing the selected groups
			if (is_array($send_to['groups'])) {
				foreach ($send_to['groups'] as $group) {
					api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, "AnnouncementUpdated", api_get_user_id(), $group);
				}
			}
			// storing the selected users
			if (is_array($send_to['users'])) {
				foreach ($send_to['users'] as $user) {
					api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, "AnnouncementUpdated", api_get_user_id(), 0, $user);
				}
			}
		} else {
			// the message is sent to everyone, so we set the group to 0
			api_item_property_update($_course, TOOL_ANNOUNCEMENT, $id, "AnnouncementUpdated", api_get_user_id(), '0');
		}
	}
	/*
			MAIL FUNCTIONS
	*/
	
	/**
	* Sends an announcement by email to a list of users.
	* Emails are sent one by one to try to avoid antispam.
	* @todo seems not used in Chamilo 1.8.7 RC2
	*/
	public static function send_announcement_email($user_list, $course_code, $_course, $mail_title, $mail_content) {
		global $_user;
		foreach ($user_list as $this_user) {
			
			$mail_subject = get_lang('professorMessage').' - '.$_course['official_code'].' - '.$mail_title;
			$mail_body = '['.$_course['official_code'].'] - ['.$_course['name']."]\n";
			$mail_body .= api_get_person_name($this_user['firstname'], $this_user['lastname'], null, PERSON_NAME_EMAIL_ADDRESS).' <'.$this_user["email"]."> \n\n".stripslashes($mail_title)."\n\n".trim(stripslashes(api_html_entity_decode(strip_tags(str_replace(array('<p>','</p>','<br />'),array('',"\n","\n"),$mail_content)), ENT_QUOTES, api_get_system_encoding())))." \n\n-- \n";
			$mail_body .= api_get_person_name($_user['firstname'], $_user['lastname'], null, PERSON_NAME_EMAIL_ADDRESS).' ';
			$mail_body .= '<'.$_user['mail'].">\n";
			$mail_body .= $_course['official_code'].' '.$_course['name'];
	
			@api_mail(api_get_person_name($this_user['firstname'], $this_user['lastname'], null, PERSON_NAME_EMAIL_ADDRESS), $this_user['email'], $mail_subject, $mail_body, api_get_person_name($_SESSION['_user']['firstname'], $_SESSION['_user']['lastname'], null, PERSON_NAME_EMAIL_ADDRESS), $_SESSION['_user']['mail']);
		}
	}
	
	public static function update_mail_sent($insert_id) {
		$tbl_announcement = Database::get_course_table(TABLE_ANNOUNCEMENT);
		if ($insert_id != strval(intval($insert_id))) { return false; }
		$insert_id = Database::escape_string($insert_id);
		// store the modifications in the table tbl_annoucement
		$sql = "UPDATE $tbl_announcement SET email_sent='1' WHERE id='$insert_id'";
		Database::query($sql);
	}
	
	/**
	 * Gets all announcements from a user by course
	 * @param	string course db
	 * @param	int user id
	 * @return	array html with the content and count of announcements or false otherwise
	 */
	public static function get_all_annoucement_by_user_course($course_db, $user_id) {
		if (empty($course_db) || empty($user_id)) {
			return false;
		}
		$tbl_announcement		= Database::get_course_table(TABLE_ANNOUNCEMENT, $course_db);
		$tbl_item_property  	= Database::get_course_table(TABLE_ITEM_PROPERTY, $course_db);
		if (!empty($user_id) && is_numeric($user_id)) {
			$user_id = intval($user_id);
			$sql="SELECT DISTINCT announcement.title, announcement.content
							FROM $tbl_announcement announcement, $tbl_item_property toolitemproperties
							WHERE announcement.id = toolitemproperties.ref
							AND toolitemproperties.tool='announcement'
							AND (toolitemproperties.insert_user_id='$user_id' AND (toolitemproperties.to_group_id='0' OR toolitemproperties.to_group_id is null))
							AND toolitemproperties.visibility='1'
							AND announcement.session_id  = 0
							ORDER BY display_order DESC";
			$rs = Database::query($sql);
			$num_rows = Database::num_rows($rs);
			$content = '';
			$i=0;
			$result = array();
			if ($num_rows>0) {
				while ($myrow = Database::fetch_array($rs)) {
						$content.= '<strong>'.$myrow['title'].'</strong><br /><br />';
						$content.= $myrow['content'];
					$i++;
				}
				$result['content'] = $content;
				$result['count'] = $i;
				return $result;
			}
			return false;
		}
		return false;
	}
	
	/*
		SHOW_TO_FORM
	*/
	/**
	* this function shows the form for sending a message to a specific group or user.
	*/
	public static function show_to_form($to_already_selected) {	
		
		$user_list	= self::get_course_users();
		$group_list = self::get_course_groups();
	
		if ($to_already_selected == '' || $to_already_selected == 'everyone')  {
			$to_already_selected = array();
		}
	
		echo "<table id=\"recipient_list\" style=\"display: none;\">";
		echo '<tr>';
	
		// the form containing all the groups and all the users of the course
		echo '<td>';
		echo "<strong>".get_lang('Users')."</strong><br />";
		self::construct_not_selected_select_form($group_list,$user_list,$to_already_selected);
		echo "</td>";
	
		// the buttons for adding or removing groups/users
		echo '<td valign="middle">';
			echo '<button class="arrowr" type="button" onClick="javascript: move(this.form.elements[0], this.form.elements[3])" onClick="javascript: move(this.form.elements[0], this.form.elements[3])"></button>';
			echo '<br /> <br />';
			echo '<button class="arrowl" type="button" onClick="javascript: move(this.form.elements[3], this.form.elements[0])" onClick="javascript: move(this.form.elements[3], this.form.elements[0])"></button>';		
		echo "</td>";
		
		echo "<td>";
	
		// the form containing the selected groups and users
		echo "<strong>".get_lang('DestinationUsers')."</strong><br />";
		self::construct_selected_select_form($group_list,$user_list,$to_already_selected);
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	
	
	
	/**
	* this function shows the form for sending a message to a specific group or user.
	*/
	public static function show_to_form_group($group_id) {
		echo "<table id=\"recipient_list\" style=\"display: none;\">";
		echo "<tr>";
		echo "<td>";
		echo "<select name=\"not_selected_form[]\" size=5 style=\"width:200px\" multiple>";
		$group_users = GroupManager::get_subscribed_users($group_id);
		foreach ($group_users as $user){
			echo '<option value="'.$user['user_id'].'">'.api_get_person_name($user['firstname'], $user['lastname']).'</option>';
		}
		echo '</select>';
	
		echo "</td>";
	
		// the buttons for adding or removing groups/users
		echo "<td valign=\"middle\">";	
			echo '<button class="arrowr" type="button" onClick="javascript: move(this.form.elements[1], this.form.elements[4])" onClick="javascript: move(this.form.elements[1], this.form.elements[4])"></button>';
			echo '<br /> <br />';
			echo '<button class="arrowl" type="button" onClick="javascript: move(this.form.elements[4], this.form.elements[1])" onClick="javascript: move(this.form.elements[4], this.form.elements[1])"></button>';	
		echo "</td>";
		echo "<td>";
	
		echo "<select name=\"selectedform[]\" size=5 style=\"width:200px\" multiple>";
		echo '</select>';
	
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	
	/*
		  CONSTRUCT_NOT_SELECT_SELECT_FORM
	*/
	/**
	* this function shows the form for sending a message to a specific group or user.
	*/
	public static function construct_not_selected_select_form($group_list=null, $user_list=null,$to_already_selected) {
	
		echo "<select name=\"not_selected_form[]\" size=5 style=\"width:200px\" multiple>";
		// adding the groups to the select form
		if ($group_list){
			foreach($group_list as $this_group) {
				if (is_array($to_already_selected)) {
					if (!in_array("GROUP:".$this_group['id'],$to_already_selected)) // $to_already_selected is the array containing the groups (and users) that are already selected
					{
						echo	"<option value=\"GROUP:".$this_group['id']."\">",
						"G: ",$this_group['name']," - " . $this_group['userNb'] . " " . get_lang('Users') .
						"</option>";
					}
				}
			}
			// a divider
			echo	"<option value=\"\">---------------------------------------------------------</option>";
		}
		// adding the individual users to the select form
		if ($user_list) {
			foreach($user_list as $this_user) {
				if (is_array($to_already_selected)) {
					if (!in_array("USER:".$this_user['user_id'],$to_already_selected)) // $to_already_selected is the array containing the users (and groups) that are already selected
					{
						echo "<option value=\"USER:".$this_user['user_id']."\">",
							"", api_get_person_name($this_user['firstname'], $this_user['lastname']),
							"</option>";
					}
				}
			}
		}
		echo "</select>";
	}
	
	
	/*
		   CONSTRUCT_SELECTED_SELECT_FORM
	*/
	/**
	* this function shows the form for sending a message to a specific group or user.
	*/
	public static function construct_selected_select_form($group_list=null, $user_list=null,$to_already_selected) {
		// we separate the $to_already_selected array (containing groups AND users into
		// two separate arrays
		$groupuser = array();
		if (is_array($to_already_selected)) {
			$groupuser	= self::separate_users_groups($to_already_selected);
		}
		$groups_to_already_selected=$groupuser['groups'];
		$users_to_already_selected=$groupuser['users'];
	
		// we load all the groups and all the users into a reference array that we use to search the name of the group / user
		$ref_array_groups	= self::get_course_groups();
		$ref_array_users	= self::get_course_users();
	
		// we construct the form of the already selected groups / users
		echo "<select name=\"selectedform[]\" size=\"5\" multiple style=\"width:200px\" width=\"200px\">";
		if (is_array($to_already_selected)) {
			foreach($to_already_selected as $groupuser) {
				list($type,$id)=explode(":",$groupuser);
				if ($type=="GROUP") {
					echo "<option value=\"".$groupuser."\">G: ".$ref_array_groups[$id]['name']."</option>";
				} else {
					foreach($ref_array_users as $key=>$value){
						if($value['user_id']==$id){
							echo "<option value=\"".$groupuser."\">".api_get_person_name($value['firstname'], $value['lastname'])."</option>";
							break;
						}
					}
				}
			}
		} else {
			if ($to_already_selected=='everyone') {
				// adding the groups to the select form
				if (is_array($ref_array_groups)) {
					foreach($ref_array_groups as $this_group) {
						//api_display_normal_message("group " . $thisGroup[id] . $thisGroup[name]);
						if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'],$to_already_selected)) // $to_already_selected is the array containing the groups (and users) that are already selected
						{
							echo	"<option value=\"GROUP:".$this_group['id']."\">",
								"G: ",$this_group['name']," &ndash; " . $this_group['userNb'] . " " . get_lang('Users') .
								"</option>";
						}
					}
				}
				// adding the individual users to the select form
				foreach($ref_array_users as $this_user) {
					if (!is_array($to_already_selected) || !in_array("USER:".$this_user['user_id'],$to_already_selected)) // $to_already_selected is the array containing the users (and groups) that are already selected
					{
						echo	"<option value=\"USER:",$this_user['user_id'],"\">",
							"", api_get_person_name($this_user['firstname'], $this_user['lastname']),
							"</option>";
					}
				}
			}
		}
		echo "</select>";
	}
	
	/*
			DATA FUNCTIONS
	*/
	
	/**
	* this function gets all the users of the course,
	* including users from linked courses
	*/
	public static function get_course_users() {
		//this would return only the users from real courses:
		//$user_list = CourseManager::get_user_list_from_course_code(api_get_course_id());
		$session_id = api_get_session_id();
		
		if ($session_id != 0) {
			$user_list = CourseManager::get_real_and_linked_user_list(api_get_course_id(), true, $session_id);
		} else {
			$user_list = CourseManager::get_real_and_linked_user_list(api_get_course_id(), false, 0);
		}
	
		return $user_list;
	}
	
	/**
	* this function gets all the groups of the course,
	* not including linked courses
	*/
	public static function get_course_groups() {	
		$session_id = api_get_session_id();	
		if ($session_id != 0) {
			$new_group_list = CourseManager::get_group_list_of_course(api_get_course_id(), intval($session_id));
		} else {	
			$new_group_list = CourseManager::get_group_list_of_course(api_get_course_id(), 0);
		}
		return $new_group_list;
	}
	
	/*
	 * 
		          LOAD_EDIT_USERS
	*/
	/**
	* This tools loads all the users and all the groups who have received
	* a specific item (in this case an announcement item)
	*/
	public static function load_edit_users($tool, $id) {
		$tbl_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);	
		$tool 	= Database::escape_string($tool);
		$id		= Database::escape_string($id);
	
		$sql = "SELECT * FROM $tbl_item_property WHERE tool='$tool' AND ref='$id'";
		$result = Database::query($sql);
		while ($row = Database::fetch_array($result)) {
			$to_group=$row['to_group_id'];
			switch ($to_group) {
				// it was send to one specific user
				case null:
					$to[]="USER:".$row['to_user_id'];
					break;
				// it was sent to everyone
				case 0:
					 return "everyone";
					 exit;
					 break;
				default:
					$to[]="GROUP:".$row['to_group_id'];
			}
		}
		return $to;
	}
	
	
	
	/*
		 USER_GROUP_FILTER_JAVASCRIPT
	*/
	/**
	* returns the javascript for setting a filter
	* this goes into the $htmlHeadXtra[] array
	*/
	public static function user_group_filter_javascript() {
		return "<script language=\"JavaScript\" type=\"text/JavaScript\">
		<!--
		function jumpMenu(targ,selObj,restore)
		{
		  eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
		  if (restore) selObj.selectedIndex=0;
		}
		//-->
		</script>";
	}
	
	
	/*
		         TO_JAVASCRIPT
	*/
	/**
	* returns all the javascript that is required for easily
	* setting the target people/groups
	* this goes into the $htmlHeadXtra[] array
	*/
	public static function to_javascript() {
		return "<script type=\"text/javascript\" language=\"JavaScript\">
	
		<!-- Begin javascript menu swapper
	
		function move(fbox,	tbox)
		{
			var	arrFbox	= new Array();
			var	arrTbox	= new Array();
			var	arrLookup =	new	Array();
	
			var	i;
			for	(i = 0;	i <	tbox.options.length; i++)
			{
				arrLookup[tbox.options[i].text]	= tbox.options[i].value;
				arrTbox[i] = tbox.options[i].text;
			}
	
			var	fLength	= 0;
			var	tLength	= arrTbox.length;
	
			for(i =	0; i < fbox.options.length;	i++)
			{
				arrLookup[fbox.options[i].text]	= fbox.options[i].value;
	
				if (fbox.options[i].selected &&	fbox.options[i].value != \"\")
				{
					arrTbox[tLength] = fbox.options[i].text;
					tLength++;
				}
				else
				{
					arrFbox[fLength] = fbox.options[i].text;
					fLength++;
				}
			}
	
			arrFbox.sort();
			arrTbox.sort();
	
			var arrFboxGroup = new Array();
			var arrFboxUser = new Array();
			var prefix_x;
	
			for (x = 0; x < arrFbox.length; x++) {
				prefix_x = arrFbox[x].substring(0,2);
				if (prefix_x == 'G:') {
					arrFboxGroup.push(arrFbox[x]);
				} else {
					arrFboxUser.push(arrFbox[x]);
				}
			}
	
			arrFboxGroup.sort();
			arrFboxUser.sort();
			arrFbox = arrFboxGroup.concat(arrFboxUser);
	
			var arrTboxGroup = new Array();
			var arrTboxUser = new Array();
			var prefix_y;
	
			for (y = 0; y < arrTbox.length; y++) {
				prefix_y = arrTbox[y].substring(0,2);
				if (prefix_y == 'G:') {
					arrTboxGroup.push(arrTbox[y]);
				} else {
					arrTboxUser.push(arrTbox[y]);
				}
			}
	
			arrTboxGroup.sort();
			arrTboxUser.sort();
			arrTbox = arrTboxGroup.concat(arrTboxUser);
	
			fbox.length	= 0;
			tbox.length	= 0;
	
			var	c;
			for(c =	0; c < arrFbox.length; c++)
			{
				var	no = new Option();
				no.value = arrLookup[arrFbox[c]];
				no.text	= arrFbox[c];
				fbox[c]	= no;
			}
			for(c =	0; c < arrTbox.length; c++)
			{
				var	no = new Option();
				no.value = arrLookup[arrTbox[c]];
				no.text	= arrTbox[c];
				tbox[c]	= no;
			}
		}
	
		function validate()
		{
			var	f =	document.new_calendar_item;
			f.submit();
			return true;
		}
	
	
		function selectAll(cbList, bSelect, showwarning) {
	
			if (document.getElementById('emailTitle').value==''){
				document.getElementById('msg_error').innerHTML='".get_lang('FieldRequired')."';
				document.getElementById('msg_error').style.display='block';
				document.getElementById('emailTitle').focus();
			} else {			
				if (cbList.length <	1) {
					if (!confirm(\"".get_lang('Send2All')."\")) {
						return false;
					}
				}				
				for	(var i=0; i<cbList.length; i++)
				cbList[i].selected = cbList[i].checked = bSelect;				
				document.f1.submit();
			}	
		}
	
		function reverseAll(cbList)
		{
			for	(var i=0; i<cbList.length; i++)
			{
				cbList[i].checked  = !(cbList[i].checked)
				cbList[i].selected = !(cbList[i].selected)
			}
		}
	
	
		function plus_attachment() {
			if (document.getElementById('options').style.display == 'none') {
				document.getElementById('options').style.display = 'block';
				document.getElementById('plus').innerHTML='&nbsp;<img style=\"vertical-align:middle;\" src=\"../img/div_hide.gif\" alt=\"\" />&nbsp;".get_lang('AddAnAttachment')."';
			} else {
				document.getElementById('options').style.display = 'none';
				document.getElementById('plus').innerHTML='&nbsp;<img style=\"vertical-align:middle;\" src=\"../img/div_show.gif\" alt=\"\" />&nbsp;".get_lang('AddAnAttachment')."';
			}
		}
		
	
	
	
		//	End	-->
		</script>";
	}
	
	
	/*
				SENT_TO_FORM
	*/
	/**
	* constructs the form to display all the groups and users the message has been sent to
	* input: 	$sent_to_array is a 2 dimensional array containing the groups and the users
	*			the first level is a distinction between groups and users:
	*			$sent_to_array['groups'] * and $sent_to_array['users']
	*			$sent_to_array['groups'] (resp. $sent_to_array['users']) is also an array
	*			containing all the id's of the groups (resp. users) who have received this message.
	* @author Patrick Cool <patrick.cool@>
	*/
	public static function sent_to_form($sent_to_array) {
		// we find all the names of the groups
		$group_names = self::get_course_groups();		
	
		// we count the number of users and the number of groups
		if (isset($sent_to_array['users'])) {
			$number_users=count($sent_to_array['users']);
		} else {
			$number_users=0;
		}
		if (isset($sent_to_array['groups'])) {
			$number_groups=count($sent_to_array['groups']);
		} else {
				$number_groups=0;
		}
		$total_numbers = $number_users + $number_groups;
	
		// starting the form if there is more than one user/group
		$output = array();
		if ($total_numbers > 1 ) {			
			//$output.="<option>".get_lang("SentTo")."</option>";
			// outputting the name of the groups
			if (is_array($sent_to_array['groups'])) {
				foreach ($sent_to_array['groups'] as $group_id) {
					$output[] = $group_names[$group_id]['name'];
				}
			}
	
			if (isset($sent_to_array['users'])) {
				if (is_array($sent_to_array['users'])) {
					foreach ($sent_to_array['users'] as $user_id) {
						$user_info = api_get_user_info($user_id);
						$output[] = api_get_person_name($user_info['firstname'], $user_info['lastname']);
					}
				}
			}			
		} else {
			// there is only one user/group
			if (isset($sent_to_array['users']) and is_array($sent_to_array['users'])) {
				$user_info = api_get_user_info($sent_to_array['users'][0]);
				$output[]= api_get_person_name($user_info['firstname'], $user_info['lastname']);
			}
			if (isset($sent_to_array['groups']) and is_array($sent_to_array['groups']) and isset($sent_to_array['groups'][0]) and $sent_to_array['groups'][0]!==0) {
				$group_id=$sent_to_array['groups'][0];
				$output[]= "&nbsp;".$group_names[$group_id]['name'];
			}
			if (empty($sent_to_array['groups']) and empty($sent_to_array['users'])) {
				$output[]= "&nbsp;".get_lang('Everybody');
			}
		}
		
	    if (!empty($output)) {        
            $output = array_filter($output);        
            if (count($output) > 0) {
                $output = implode(', ', $output);
            }
            return $output;
        }   
	}
	
	
	/*
				SEPARATE_USERS_GROUPS
	*/
	/**
	* This function separates the users from the groups
	* users have a value USER:XXX (with XXX the dokeos id
	* groups have a value GROUP:YYY (with YYY the group id)
	* @param    array   Array of strings that define the type and id of each destination
	* @return   array   Array of groups and users (each an array of IDs)
	*/
	public static function separate_users_groups($to) {
		foreach($to as $to_item) {
			list($type, $id) = explode(':', $to_item);
			switch($type) {
				case 'GROUP':
					$grouplist[] = intval($id);
					break;
				case 'USER':
					$userlist[] = intval($id);
					break;
			}
		}
	
		$send_to['groups']=$grouplist;
		$send_to['users']=$userlist;
		return $send_to;
	}
	
	
	
	/*
		 SENT_TO()
	*/
	/**
	* Returns all the users and all the groups a specific announcement item
	* has been sent to
	* @param    string  The tool (announcement, agenda, ...)
	* @param    int     ID of the element of the corresponding type
	* @return   array   Array of users and groups to whom the element has been sent
	*/
	public static function sent_to($tool, $id) {
		global $_course;
		global $tbl_item_property;
	
		$tool 	= Database::escape_string($tool);
		$id 	= intval($id);
	
		$sent_to_group = array();
		$sent_to = array();
	
		$sql="SELECT to_group_id, to_user_id FROM $tbl_item_property WHERE tool = '$tool' AND ref=".$id;
		$result = Database::query($sql);	
	
		while ($row = Database::fetch_array($result)) {
			// if to_group_id is null then it is sent to a specific user
			// if to_group_id = 0 then it is sent to everybody
			if ($row['to_group_id'] != 0) {
				$sent_to_group[]=$row['to_group_id'];
			}
			// if to_user_id <> 0 then it is sent to a specific user
			if ($row['to_user_id'] <> 0) {
				$sent_to_user[]=$row['to_user_id'];
			}
		}
		if (isset($sent_to_group)) {
			$sent_to['groups']=$sent_to_group;
		}
		if (isset($sent_to_user)) {
			$sent_to['users']=$sent_to_user;
		}
		return $sent_to;
	}
	
	
	/*		ATTACHMENT FUNCTIONS	*/
	
	/**
	 * Show a list with all the attachments according to the post's id
	 * @param int announcement id
	 * @return array with the post info
	 * @author Arthur Portugal
	 * @version November 2009, dokeos 1.8.6.2
	 */
	
	public static function get_attachment($announcement_id) {
		$tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
		$announcement_id= intval($announcement_id);
		$row=array();
		$sql = 'SELECT id,path, filename,comment FROM '. $tbl_announcement_attachment.' WHERE announcement_id = '.$announcement_id.'';
		$result=Database::query($sql);
		if (Database::num_rows($result)!=0) {
			$row = Database::fetch_array($result,'ASSOC');
		}
		return $row;
	}
	
	/**
	 * This function add a attachment file into announcement
	 * @param int  announcement id
	 * @param string file comment
	 * @param array  uploaded file $_FILES
	 * @return int  -1 if failed, 0 if unknown (should not happen), 1 if success
	 */
	
	public static function add_announcement_attachment_file($announcement_id, $file_comment, $file) {
		global $_course;	
		$tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
		$return = 0;
		$announcement_id = intval($announcement_id);
	
		if (is_array($file) && $file['error'] == 0 ) {
			$courseDir   = $_course['path'].'/upload/announcements'; // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;
	
			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
			// user's file name
			$file_name = $file['name'];
	
			if (!filter_extension($new_file_name))  {
				$return = -1;
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
			} else {
				$new_file_name = uniqid('');
				$new_path           = $updir.'/'.$new_file_name;
				$result             = @move_uploaded_file($file['tmp_name'], $new_path);
				$safe_file_comment  = Database::escape_string($file_comment);
				$safe_file_name     = Database::escape_string($file_name);
				$safe_new_file_name = Database::escape_string($new_file_name);
				// Storing the attachments if any
				$sql = 'INSERT INTO '.$tbl_announcement_attachment.'(filename, comment, path, announcement_id, size) '.
					   "VALUES ( '$safe_file_name', '$file_comment', '$safe_new_file_name' , '$announcement_id', '".intval($file['size'])."' )";
				$result = Database::query($sql);
	            $return = 1;
			}
		}
		return $return;
	}
	
	/**
	 * This function edit a attachment file into announcement
	 * @param int attach id
	 * @param array uploaded file $_FILES
	 * @param string file comment
	 * @return int
	 */
	public static function edit_announcement_attachment_file($id_attach, $file, $file_comment) {
		global $_course;
		$tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
	    $return = 0;
	
		if (is_array($file) && $file['error'] == 0 ) {
			$courseDir   = $_course['path'].'/upload/announcements'; // TODO: This path is obsolete. The new document repository scheme should be kept in mind here.
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;
	
			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($file['name']), $file['type']);
			// user's file name
			$file_name =$file ['name'];
	
			if (!filter_extension($new_file_name)) {
				$return -1;
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
			} else {
				$new_file_name = uniqid('');
				$new_path = $updir.'/'.$new_file_name;
				$result = @move_uploaded_file($file['tmp_name'], $new_path);
				$safe_file_comment 	= Database::escape_string($file_comment);
				$safe_file_name 	= Database::escape_string($file_name);
				$safe_new_file_name = Database::escape_string($new_file_name);
				$id_attach = intval($id_attach);
				$sql = "UPDATE $tbl_announcement_attachment SET filename = '$safe_file_name', comment = '$safe_file_comment', path = '$safe_new_file_name', size ='".intval($file['size'])."'
					 	WHERE id = '$id_attach'";
				$result = Database::query($sql);
				if ($result === false) {
					$return = -1;
	                Display :: display_error_message(get_lang('UplUnableToSaveFile'));
				} else {
	                $return = 1;
				}
			}
		}
		return $return;
	}
	
	/**
	 * This function delete a attachment file by id
	 * @param integer attachment file Id
	 *
	 */
	public static function delete_announcement_attachment_file($id) {
		global $_course;
		$tbl_announcement_attachment = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
		$id = intval($id);
		$sql = "DELETE FROM $tbl_announcement_attachment WHERE id = $id";
		$result = Database::query($sql);
		// update item_property
		//api_item_property_update($_course, 'announcement_attachment',  $id,'AnnouncementAttachmentDeleted', api_get_user_id());
	}
} //end class