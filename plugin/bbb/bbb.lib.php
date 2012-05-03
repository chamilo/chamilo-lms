<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

class bbb {
    
    var $url;
    var $salt;
    var $api;
    var $user_complete_name = null;
    var $protocol = 'http://';
    var $debug = true;
    var $logout_url = null;
    
    function __construct() {
        
        // initialize video server settings from global settings
        $settings = api_get_settings('Extra','list',api_get_current_access_url_id());
        $bbb_settings = array();
        foreach ($settings as $setting) {
            if (substr($setting['variable'],0,4)==='bbb_') {
                $bbb_settings[$setting['variable']] = $setting['selected_value'];
            }
        }
        $bbb_plugin = $bbb_settings['bbb_plugin'] === 'true';
        $bbb_host   = $bbb_settings['bbb_plugin_host'];
        $bbb_salt   = $bbb_settings['bbb_plugin_salt'];
        
        $course_code = api_get_course_id();
        
        $this->logout_url = api_get_path(WEB_COURSE_PATH).$course_code;  
        
        if ($bbb_plugin) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];        
            $this->salt = $bbb_salt;
            $this->url  = $bbb_host.'/bigbluebutton/';        
            $this->table = Database::get_main_table('plugin_bbb_meeting');
            return true;
        }
        return false;
    }
    
    function create_meeting($params) {        
        $params['c_id'] = api_get_course_int_id();  
        $course_code = api_get_course_id();
        
        $attende_password = $params['attendee_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : api_get_course_id();
        $moderator_password = $params['moderator_pw'] = isset($params['moderator_pw']) ? $params['moderator_pw'] : api_get_course_id().'mod';
        
        $params['record'] = api_get_course_setting('big_blue_button_record_and_store', $course_code) == 1 ? true : false;
        $max = api_get_course_setting('big_blue_button_max_students_allowed', $course_code);
        
        $max =  isset($max) ? $max : -1;
        $params['status'] = 1;
        
        if ($this->debug) error_log("enter create_meeting ".print_r($params, 1));
        
        $params['created_at'] = api_get_utc_datetime();
        $id = Database::insert($this->table, $params);
        
        if ($id) {
            if ($this->debug) error_log("create_meeting $id ");
            
            $meeting_name       = isset($params['meeting_name']) ? $params['meeting_name'] : api_get_course_id();            
            $welcome_msg        = isset($params['welcome_msg']) ? $params['welcome_msg'] : null;
            $record             = isset($params['record']) && $params['record'] ? 'true' : 'false';
            $duration           = isset($params['duration']) ? intval($params['duration']) : 0;
            

            // ?? 
            $voiceBridge = 0;
            $metadata = array('maxParticipants' => $max);                      
            return $this->protocol.BigBlueButtonBN::createMeetingAndGetJoinURL(
                            $this->user_complete_name, $meeting_name, $id, $welcome_msg, $moderator_password, $attende_password, 
                            $this->salt, $this->url, $this->logout_url, $record, $duration, $voiceBridge, $metadata
            );       
        }
    }
    
    function is_meeting_exist($meeting_name) {
        $course_id = api_get_course_int_id();
        $meeting_data = Database::select('*', $this->table, array('where' => array('c_id = ? AND meeting_name = ? AND status = 1 ' => array($course_id, $meeting_name))), 'first');
        if ($this->debug) error_log("is_meeting_exist ".print_r($meeting_data,1));
        if (empty($meeting_data)) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * @todo implement moderator pass
     */
    function join_meeting($meeting_name) {        
        $pass = $this->get_user_metting_password();
        $meeting_data = Database::select('*', $this->table, array('where' => array('meeting_name = ? AND status = 1 ' => $meeting_name)), 'first');
        if (empty($meeting_data)) {
            if ($this->debug) error_log("meeting does not exist: $meeting_name ");
            return false;
        }        
                
        $meeting_is_running = BigBlueButtonBN::isMeetingRunning($meeting_data['id'], $this->url, $this->salt);
        
        
        $meeting_info = BigBlueButtonBN::getMeetingInfoArray($meeting['id'], $pass, $this->url, $this->salt);
        $meeting_info_exists = false;
        
        if ($meeting_info['returncode'] != 'FAILED') {
            $meeting_info_exists = true;
        }                  
        $url = false;
        if ($this->debug) error_log("meeting is running".$meeting_is_running);
        
        if (isset($meeting_is_running) && $meeting_info_exists) {        
            $url = $this->protocol.BigBlueButtonBN::joinURL($meeting_data['id'], $this->user_complete_name, $pass, $this->salt, $this->url);        
        }
        if ($this->debug) error_log("return url :".$url);
        return $url;
    }    
       
    /**
     * Gets all the course meetings saved in the plugin_bbb_meeting table
     * @return string 
     */
    function get_course_meetings() {
        $pass = $this->get_user_metting_password();
        $meeting_list = Database::select('*', $this->table, array('where' => array('c_id = ? ' => api_get_course_int_id())));                     
        $new_meeting_list = array();
        
        foreach ($meeting_list as $meeting) {
            $item_meeting = $meeting;
            $item_meeting['info'] = BigBlueButtonBN::getMeetingInfoArray($meeting['id'], $pass, $this->url, $this->salt);
            
            if ($meeting['info']['returncode'] == 'FAILED') {     
            } else {
                $item_meeting['end_url']             = api_get_self().'?action=end&id='.$meeting['id'];    
                $item_meeting['add_to_calendar_url'] = api_get_self().'?action=add_to_calendar&id='.$meeting['id'].'&start='.api_strtotime($meeting['created_at']);
            }   
            $record_array = array();
            
            if ($meeting['record'] == 1) {                
                $records =  BigBlueButtonBN::getRecordingsArray($meeting['id'], $this->url, $this->salt);                      
                //var_dump($meeting['id']);
                if (!empty($records)) {
                    $count = 1;
                    foreach ($records as $record) {                        
                        if (is_array($record) && isset($record['recordID']) && isset($record['playbacks'])) {
                            
                            //Fix the bbb timestamp
                            //$record['startTime'] = substr($record['startTime'], 0, strlen($record['startTime']) -3);
                            //$record['endTime']   = substr($record['endTime'], 0, strlen($record['endTime']) -3);
                            //.' - '.api_convert_and_format_date($record['startTime']).' - '.api_convert_and_format_date($record['endTime'])
                           
                            foreach ($record['playbacks'] as $item) {                                                        
                                $url = Display::url(get_lang('ViewRecord').' #'.$count, $item['url'], array('target' => '_blank'));
                                //$url .= Display::url(get_lang('DeleteRecord'), api_get_self().'?action=delete_record&'.$record['recordID']);
                                $url .= Display::url(Display::return_icon('link.gif',get_lang('CopyToLinkTool')), api_get_self().'?action=copy_record_to_link_tool&id='.$meeting['id'].'&record_id='.$record['recordID']);
                                $url .= Display::url(Display::return_icon('agenda.png',get_lang('AddToCalendar')), api_get_self().'?action=add_to_calendar&id='.$meeting['id'].'&start='.api_strtotime($meeting['created_at']).'&url='.$item['url']);
                                
                                //$url .= api_get_self().'?action=publish&id='.$record['recordID'];
                                $count++;
                                $record_array[] = $url;
                            }                            
                        }
                    }
                }
                $item_meeting['show_links']  = implode('<br />', $record_array);
            }
            
            $item_meeting['created_at'] = api_convert_and_format_date($item_meeting['created_at']);            
            //created_at
            
            $item_meeting['publish_url'] = api_get_self().'?action=publish&id='.$meeting['id'];
            $item_meeting['unpublish_url'] = api_get_self().'?action=unpublish&id='.$meeting['id'];    
            
            if ($meeting['status'] == 1) {
                $item_meeting['go_url'] = $this->protocol.BigBlueButtonBN::joinURL($meeting['id'], $this->user_complete_name, $pass, $this->salt, $this->url);
            }
            $new_meeting_list[] = $item_meeting;
        }              
        return $new_meeting_list;
    }
    
    function publish_meeting($id) {
        return BigBlueButtonBN::setPublishRecordings($id, 'true', $this->url, $this->salt);
    }
    
    function unpublish_meeting($id) {
        return BigBlueButtonBN::setPublishRecordings($id, 'false', $this->url, $this->salt);
    }
    
    function end_meeting($id) {
        $pass = $this->get_user_metting_password();
        BigBlueButtonBN::endMeeting($id, $pass, $this->url, $this->salt);
        Database::update($this->table, array('status' => 0), array('id = ? ' => $id));
    }
    
    function get_user_metting_password() {
        $teacher = api_is_course_admin() || api_is_coach() || api_is_platform_admin();
        if ($teacher) {
            return api_get_course_id().'mod';
        } else {
            return api_get_course_id();
        }
    }
    
    /**
     * Get users online in the current course room 
     */
    function get_users_online_in_current_room() {
        $course_id = api_get_course_int_id();
        $meeting_data = Database::select('*', $this->table, array('where' => array('c_id = ? AND status = 1 ' => $course_id)), 'first');                
        if (empty($meeting_data)) {
            return 0;
        }        
        $pass = $this->get_user_metting_password();        
        //$meeting_is_running = BigBlueButtonBN::isMeetingRunning($meeting_data['id'], $this->url, $this->salt);
        $info = BigBlueButtonBN::getMeetingInfoArray($meeting_data['id'], $pass, $this->url, $this->salt);
        var_dump($meeting_data['id']);
        
        if (!empty($info) && isset($info['participantCount'])) {
            return $info['participantCount'];
            
        }
        return 0;
    }   
    
    /**
     * @todo 
     */
    function delete_record($id) {        
    }
    
    function copy_record_to_link_tool($id, $record_id) {
        require_once api_get_path(LIBRARY_PATH).'link.lib.php';        
        $records =  BigBlueButtonBN::getRecordingsArray($id, $this->url, $this->salt);           
        if (!empty($records)) {
            foreach ($records as $record) {
                if ($record['recordID'] == $record_id) {
                    if (is_array($record) && isset($record['recordID']) && isset($record['playbacks'])) {
                        foreach ($record['playbacks'] as $item) {
                            $link = new Link();         
                            $params['url'] = $item['url'];
                            $params['title'] = 'bbb 1';                                                       
                            $id = $link->save($params);
                            return $id;
                        }                      
                    }
                                        
                }
            }
        }
        return false;        
    }
    
    function is_server_running() {
        return BigBlueButtonBN::isServerRunning($this->url);
    }
}