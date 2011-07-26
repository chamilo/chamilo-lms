<?php
/* For licensing terms, see /license.txt */
/**
*	This class provides methods for the Notification management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/
/**
 * Code
 */
require_once 'model.lib.php';
require_once 'usermanager.lib.php';

//@todo put constants in an array

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

define('NOTIFICATION_TYPE_MESSAGE',       1);
define('NOTIFICATION_TYPE_INVITATION',    2);
define('NOTIFICATION_TYPE_GROUP',         3);
/**
 * Notification class
 * @package chamilo.library
 */
class Notification extends Model {
    
    var $table;
    var $columns             = array('id','dest_user_id','dest_mail','title','content','send_freq','created_at','sent_at');
    var $max_content_length  = 254; //Max lenght of the notification.content field
    var $debug               = false;
    
    /* message, invitation, group messages */
    var $type;    
    var $admin_name;
    var $admin_email;
    
	public function __construct() {
        $this->table       = Database::get_main_table(TABLE_NOTIFICATION);
        $this->admin_name  = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS); //api_get_setting('siteName')        
        $this->admin_email = api_get_setting('emailAdministrator');                            
	}  
	  
    /**
     *  Send the notifications
     *  @param int notification frecuency 
     */	
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
                if ($this->debug) { error_log('Updating record : '.print_r($item_to_send,1)); }
            }
        }                
    }
    
    /**
     * Save message notification
     * @param	array	message type NOTIFICATION_TYPE_MESSAGE, NOTIFICATION_TYPE_INVITATION, NOTIFICATION_TYPE_GROUP
     * @param	array	user list of ids
     * @param	string	title
     * @param	string	content of the message
     * @param	array	result of api_get_user_info() or GroupPortalManager:get_group_data()
     */
    public function save_notification($type, $user_list, $title, $content, $sender_info = array()) {
        $this->type = intval($type);
        $content    = $this->format_content($content, $sender_info);
                
        $setting_to_check = '';
        $avoid_my_self  = false;
        
        switch($this->type) {
            case NOTIFICATION_TYPE_MESSAGE;
                $setting_to_check = 'mail_notify_message';
                break;
            case NOTIFICATION_TYPE_INVITATION;
                $setting_to_check = 'mail_notify_invitation';
                break;
            case NOTIFICATION_TYPE_GROUP;
                $setting_to_check = 'mail_notify_group_message';
                $avoid_my_self  = true;
                break;
        }
        
        if (!empty($user_list)) {
            foreach($user_list  as $user_id) {
                $extra_data = UserManager::get_extra_user_data($user_id);              
                $params = array();                
                if ($avoid_my_self) {
                    if ($user_id == api_get_user_id()) {
                        continue;
                    }  
                }
                $user_setting = $extra_data[$setting_to_check];                
                $user_info = api_get_user_info($user_id);
                switch ($user_setting) {
                    //No notifications                   
                    case NOTIFY_MESSAGE_NO:
                    case NOTIFY_INVITATION_NO:
                    case NOTIFY_GROUP_NO:
                        break;
                    //Send notification right now!
                    case NOTIFY_MESSAGE_AT_ONCE:
                    case NOTIFY_INVITATION_AT_ONCE:   
                    case NOTIFY_GROUP_AT_ONCE:                     
                        if (!empty($user_info['mail'])) {
                            $name = api_get_person_name($user_info['firstname'], $user_info['lastname']);                            
                            api_mail_html($name, $user_info['mail'], $title, $content, $this->admin_name, $this->admin_email);
                        }
                        $params['sent_at']       = api_get_utc_datetime();
                    //Saving the notification to be sent some day 
                    default:	    
                	    $params['dest_user_id']  = $user_id;
                	    $params['dest_mail']     = $user_info['mail'];
                	    $params['title']         = $title;
                	    $params['content']       = cut($content, $this->max_content_length);
                	    $params['send_freq']     = $user_setting;    			 
                	    $this->save($params);
                	    break;
                }                   
            }
        }
    }    
   
    /**
     * Formats the content in order to add the welcome message, the notification preference, etc
     * @param	string 	the content
     * @param	array	result of api_get_user_info() or GroupPortalManager:get_group_data()
     * */
    public function format_content($content, $sender_info) {
        $new_message_text = $link_to_new_message = '';        
        
        switch($this->type) {
            case NOTIFICATION_TYPE_MESSAGE:           
                if (!empty($sender_info)) {
                    $sender_name = api_get_person_name($sender_info['firstname'], $sender_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                    //$sender_mail = $sender_info['email'] ;            
                    $new_message_text = sprintf(get_lang('YouHaveANewMessageFromX'), $sender_name);            
                }
                $link_to_new_message = Display::url(get_lang('SeeMessage'), api_get_path(WEB_CODE_PATH).'messages/inbox.php');
                break;
            case NOTIFICATION_TYPE_INVITATION:
                if (!empty($sender_info)) {
                    $sender_name = api_get_person_name($sender_info['firstname'], $sender_info['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
                    //$sender_mail = $sender_info['email'] ;            
                    $new_message_text = sprintf(get_lang('YouHaveANewInvitationFromX'), $sender_name);                
                }                
                $link_to_new_message = Display::url(get_lang('SeeInvitation'), api_get_path(WEB_CODE_PATH).'social/invitations.php');            
                break;
            case NOTIFICATION_TYPE_GROUP:
                if (!empty($sender_info)) {            
                    $sender_name = $sender_info['name'];                    
                    $new_message_text  = sprintf(get_lang('YouHaveReceivedANewMessageInTheGroupX'), $sender_name);                    
                }    
                $link_to_new_message = Display::url(get_lang('SeeMessage'), api_get_path(WEB_CODE_PATH).'social/groups.php?id='.$sender_info['id']);
                break;
        }        
        $preference_url = api_get_path(WEB_CODE_PATH).'auth/profile.php';
        
        // You have received a new message text
        if (!empty($new_message_text)) {
            $content = $new_message_text.'<br /><hr><br />'.$content;
        }     
        
        // See message with link text
        if (!empty($link_to_new_message)) {
            $content = $content.'<br /><br />'.$link_to_new_message;
        }
              
        // You have received this message because you are subscribed text
        $content        = $content.'<br /><hr><i>'.
                          sprintf(get_lang('YouHaveReceivedThisNotificationBecauseYouAreSubscribedOrInvolvedInItToChangeYourNotificationPreferencesPleaseClickHereX'), Display::url($preference_url, $preference_url)).'</i>'; 
        return $content;        
    }
}
