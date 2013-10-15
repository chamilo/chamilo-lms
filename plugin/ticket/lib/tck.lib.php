<?php
/**
 * This script initiates a videoconference session, calling the BigBlueButton 
 * API
 * @package chamilo.plugin.bigbluebutton
 */
/**
 * BigBlueButton-Chamilo connector class
 */
class Ticket {

    var $url;
    var $salt;
    var $api;
    var $user_complete_name = null;
    var $protocol = 'http://';
    var $debug = false;
    var $logout_url = null;
    var $plugin_enabled = false;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the videoconference server)
     */
    function __construct() {

        // initialize video server settings from global settings
        $plugin = TicketPlugin::create();
/*
        $bbb_plugin = $plugin->get('tool_enable');
        $bbb_host   = $plugin->get('host');
        $bbb_salt   = $plugin->get('salt');

        //$course_code = api_get_course_id();

        $this->logout_url = api_get_path(WEB_PLUGIN_PATH).'bbb/listing.php';
        $this->table = Database::get_main_table('plugin_bbb_meeting');

        if ($bbb_plugin == true) {
            $user_info = api_get_user_info();
            $this->user_complete_name = $user_info['complete_name'];
            $this->salt = $bbb_salt;
            $info = parse_url($bbb_host);
            $this->url = $bbb_host.'/bigbluebutton/';
            if (isset($info['scheme'])) {
                $this->protocol = $info['scheme'].'://';
                $this->url = str_replace($this->protocol, '', $this->url);
            }

            // Setting BBB api
            define('CONFIG_SECURITY_SALT', $this->salt);
            define('CONFIG_SERVER_BASE_URL', $this->url);

            $this->api = new TckBlueButtonBN();
            $this->plugin_enabled = true;
        }*/
    }
    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    function is_teacher() {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }

}