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
define('NOTIFY_MESSAGE_NO',			'0');

//mail_notify_invitation ("At once", "Daily", "No")

define('NOTIFY_INVITATION_AT_ONCE',	'1');
define('NOTIFY_INVITATION_DAILY',	'8');
define('NOTIFY_INVITATION_NO',		'0');

// mail_notify_group_message ("At once", "Daily", "No")
define('NOTIFY_GROUP_AT_ONCE',		'1');
define('NOTIFY_GROUP_DAILY',		'8');
define('NOTIFY_GROUP_NO',			'0');

class Notification extends Model {
    
    var $table;
    var $columns = array('id','dest_user_id','dest_mail','title','content','send_freq','created_at','sent_at');
    var $max_content_length = 255;
    var $debug = true;
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_NOTIFICATION);
	}    
   
    public function send($frec = NOTIFY_MESSAGE_DAILY) {
        $notifications = $this->find('all',array('where'=>array('sent_at IS NULL AND send_freq = ?'=>$frec)));
        if (!empty($notifications)) {
            foreach($notifications as $item_to_send) {
                //Sending email
                api_send_mail($item_to_send['dest_mail'], $item_to_send['title'], $item_to_send['content']);
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
     * 
     */
    public function save_message_notifications($user_list, $title, $content) {
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
                            api_send_mail($user_info['mail'], $title, cut($content, $this->max_content_length));
                        }
                        $params['sent_at']       = api_get_utc_datetime();
                    default:    			        
                        $extra_data              = UserManager::get_extra_user_data($user_id);
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
    public function save_invitation_notifications($user_list, $title, $content) {
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
                            api_send_mail($user_info['mail'], $title, cut($content, $this->max_content_length));
                        }
                        $params['sent_at']       = api_get_utc_datetime();    
                    default:    			        
                        $extra_data              = UserManager::get_extra_user_data($user_id);
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
    public function save_group_notifications($user_list, $title, $content) {
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
                            api_send_mail($user_info['mail'], $subject, cut($content,150));
                        }
                        $params['sent_at']       = api_get_utc_datetime();
                    default:    			        
                        $extra_data              = UserManager::get_extra_user_data($user_id);
                        $user_info               = api_get_user_info($user_id);			    
                	    $params['dest_user_id']  = $user_id;
                	    $params['dest_mail']     = $user_info['mail'];
                	    $params['title']         = $subject;
                	    $params['content']       = cut($content,150);
                	    $params['send_freq']     = $extra_data['mail_notify_group_message'];                    	    			 
                	    $this->save($params);
                	    break;	   
                }                   
            }
        }
    }    
}