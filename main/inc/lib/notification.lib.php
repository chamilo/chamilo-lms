<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the Notification management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/

require_once 'model.lib.php';
require_once 'usermanager.lib.php';

//default values
//mail_notify_message ("At once", "Daily", "No")
define('NOTIFY_MESSAGE_AT_ONCE',	'1');
define('NOTIFY_MESSAGE_DAILY',		'8');
define('NOTIFY_MESSAGE_WEEKLY',    '12');
define('NOTIFY_MESSAGE_NO',			'0');

//mail_notify_invitation ("At once", "Daily", "No")

define('NOTIFY_INVITATION_AT_ONCE',	'1');
define('NOTIFY_INVITATION_DAILY',	'8');
define('NOTIFY_INVITATION_WEEKLY', '12');
define('NOTIFY_INVITATION_NO',		'0');

// mail_notify_group_message ("At once", "Daily", "No")
define('NOTIFY_GROUP_AT_ONCE',		'1');
define('NOTIFY_GROUP_DAILY',		'8');
define('NOTIFY_GROUP_WEEKLY',      '12');
define('NOTIFY_GROUP_NO',			'0');

class Notification extends Model {
    
    var $table;
    var $columns             = array('id','dest_user_id','dest_mail','title','content','send_freq','created_at','sent_at');
    var $max_content_length  = 254; //Max lenght of the notification.content field
    var $debug               = true;
    
    var $admin_name;
    var $admin_email;
    
	public function __construct() {
        $this->table       = Database::get_main_table(TABLE_NOTIFICATION);
        $this->admin_name  = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS); //api_get_setting('siteName')        
        $this->admin_email = api_get_setting('emailAdministrator');            
	}    
   
    public function send($frec = NOTIFY_MESSAGE_DAILY) {
        $notifications = $this->find('all',array('where'=>array('sent_at IS NULL AND send_freq = ?'=>$frec)));
        if (!empty($notifications)) {
            foreach($notifications as $item_to_send) {
                //Sending email
                api_mail_html($item_to_send['dest_mail'], $item_to_send['dest_mail'], $item_to_send['title'], $item_to_send['content'], $this->admin_name, $this->admin_email);                    
                if ($this->debug) { error_log('Sending message to: '.$item_to_send['dest_mail']); }
                //Updating
                $item_to_send['sent_at'] = api_get_utc_datetime();
                $this->update($item_to_send);
                //if ($this->debug) { error_log('Updating record : '.print_r($item_to_send,1)); }
            }
        }                
    }
    
    /**
     * Save message notification
     * @param	array	user list of ids
     * @param	string	title
     * @param	string	content of the message
     * @param	array	sender info (return of the api_get_user_info() function )
     * 
     */
    public function save_message_notifications($user_list, $title, $content, $sender_info = array()) {        
        if (!empty($sender_info)) {
            $sender_name = api_get_person_name($sender_info['firstname'], $sender_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
            $sender_mail = $sender_info['email'] ;            
            $content = sprintf(get_lang('YouHaveANewMessageFromX'), $sender_name).'<br />'.$content;            
        }
        $content = $content.'<br />'.Display::url(get_lang('SeeMessage'), api_get_path(WEB_CODE_PATH).'messages/inbox.php');
        
        if (!empty($user_list)) {            
            foreach($user_list  as $user_id) {
                $extra_data = UserManager::get_extra_user_data($user_id);              
                $params = array();
                switch ($extra_data['mail_notify_message']) {
                    case NOTIFY_MESSAGE_NO:
                        break;
                    case NOTIFY_MESSAGE_AT_ONCE:                        
                        $user_info = api_get_user_info($user_id);
                        if (!empty($user_info['mail'])) {                            
                            $name = api_get_person_name($user_info['firstname'], $user_info['lastname']);
                            api_mail_html($name, $user_info['mail'], $title, $content, $this->admin_name, $this->admin_email);
                        }
                        $params['sent_at']       = api_get_utc_datetime();
                    default:
                        $user_info               = api_get_user_info($user_id);			    
                	    $params['dest_user_id']  = $user_id;
                	    $params['dest_mail']     = $user_info['mail'];
                	    $params['title']         = $title;
                	    $params['content']       = cut($content, $this->max_content_length);
                	    $params['send_freq']     = $extra_data['mail_notify_message'];    			 
                	    $this->save($params);    			        
                	    break;	   
                }                   
            }
        }
    }
    
    /**
     * Save invitation notification
     * @param	array	user list of ids
     * @param	string	title
     * @param	string	content of the message
     * 
     */
    public function save_invitation_notifications($user_list, $title, $content, $sender_info = array()) {
        if (!empty($sender_info)) {
            $sender_name = api_get_person_name($sender_info['firstname'], $sender_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
            $sender_mail = $sender_info['email'] ;            
            $content = sprintf(get_lang('YouHaveANewInvitationFromX'), $sender_name).'<br />'.$content;                
        }        
        
        $content = $content.'<br />'.Display::url(get_lang('SeeInvitation'), api_get_path(WEB_CODE_PATH).'social/invitations.php');
        
        if (!empty($user_list)) {
            foreach($user_list  as $user_id) {                
                $extra_data = UserManager::get_extra_user_data($user_id);   
                $params = array();           
                switch ($extra_data['mail_notify_invitation']) {
                    case NOTIFY_INVITATION_NO:
                        break;
                    case NOTIFY_INVITATION_AT_ONCE:                        
                        $user_info = api_get_user_info($user_id);
                        if (!empty($user_info['mail'])) {
                            $name = api_get_person_name($user_info['firstname'], $user_info['lastname']);                            
                            api_mail_html($name, $user_info['mail'], $title, $content, $this->admin_name, $this->admin_email);
                        }
                        $params['sent_at']       = api_get_utc_datetime();    
                    default:    		
                        $user_info               = api_get_user_info($user_id);			    
                	    $params['dest_user_id']  = $user_id;
                	    $params['dest_mail']     = $user_info['mail'];
                	    $params['title']         = $title;
                	    $params['content']       = cut($content, $this->max_content_length);
                	    $params['send_freq']     = $extra_data['mail_notify_invitation'];                    	    		 
                	    $this->save($params);  			        
                	    break;	   
                }                   
            }
        }
    }
    
  
  	/**
     * Save group notifications
     * @param	array	user list of ids
     * @param	string	title
     * @param	string	content of the message
     * 
     */
    public function save_group_notifications($user_list, $title, $content, $sender_info = array()) {
        if (!empty($sender_info)) {            
            $sender_name = $sender_info['name'];                    
            $content     = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $sender_name).'<br />'.$content;
            $content     = $content.'<br />'.Display::url(get_lang('SeeMessage'), api_get_path(WEB_CODE_PATH).'social/groups.php?id='.$sender_info['id']);
        }        
        if (!empty($user_list)) {            
            foreach($user_list  as $user_id) {
                //Avoiding sending a message to myself    
                if ($user_id == api_get_user_id()) {
                    continue;
                }            
                $extra_data = UserManager::get_extra_user_data($user_id);   
                $params = array();           
                switch ($extra_data['mail_notify_group_message']) {
                    case NOTIFY_GROUP_NO:
                        break;
                    case NOTIFY_GROUP_AT_ONCE:                        
                        $user_info = api_get_user_info($user_id);
                        if (!empty($user_info['mail'])) {
                            $name = api_get_person_name($user_info['firstname'], $user_info['lastname']);                            
                            api_mail_html($name, $user_info['mail'], $title, $content, $this->admin_name, $this->admin_email);
                        }
                        $params['sent_at']       = api_get_utc_datetime();
                    default:
                        $user_info               = api_get_user_info($user_id);			    
                	    $params['dest_user_id']  = $user_id;
                	    $params['dest_mail']     = $user_info['mail'];
                	    $params['title']         = $subject;
                	    $params['content']       = cut($content,$this->max_content_length);
                	    $params['send_freq']     = $extra_data['mail_notify_group_message'];                    	    			 
                	    $this->save($params);
                	    break;	   
                }                   
            }
        }
    }    
}