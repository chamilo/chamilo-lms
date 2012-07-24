<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * Initialization
 */

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables 
require_once dirname(__FILE__).'/config.php';
$tool_name = get_lang('Videoconference');
$tpl = new Template($tool_name);
$bbb = new bbb();

if ($bbb->plugin_enabled) {
    if ($bbb->is_server_running()) {
        
        if (isset($_GET['launch']) && $_GET['launch'] == 1) {
        
            $meeting_params = array();
            $meeting_params['meeting_name'] = api_get_course_id();

            if ($bbb->is_meeting_exist($meeting_params['meeting_name'])) {
                $url = $bbb->join_meeting($meeting_params['meeting_name']);        
                if ($url) {
                    header('location: '.$url);
                    exit;
                } else {
                    $url = $bbb->create_meeting($meeting_params);
                    header('location: '.$url);
                    exit;
                }        
            } else {
                $url = $bbb->create_meeting($meeting_params);
                header('location: '.$url);
                exit;
            }
        } else {
            $url = 'listing.php';
            header('location: '.$url);
            exit;
        }
    } else {        
        $message = Display::return_message(get_lang('ServerIsNotRunning'), 'warning');        
    }
} else {    
    $message = Display::return_message(get_lang('ServerIsNotConfigured'), 'warning');    
}
$tpl->assign('message', $message);
$tpl->display_one_col_template();