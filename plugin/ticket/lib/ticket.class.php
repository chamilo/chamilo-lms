<?php
/* For licensing terms, see /license.txt */
/**
 * This class defines the basis of the ticket management system plugin
 * @package chamilo.plugin.ticket
 */
/**
 * Ticket class
 */
class Ticket {

    public $url;
    public $salt;
    public $api;
    public $user_complete_name = null;
    public $protocol = 'http://';
    private $debug = false;
    public $logout_url = null;
    public $plugin_enabled = false;

    /**
     * Constructor (generates a connection to the API and the Chamilo settings
     * required for the connection to the videoconference server)
     */
    function __construct()
    {

        // initialize video server settings from global settings
        $plugin = TicketPlugin::create();
    }
    /**
     * Checks whether a user is teacher in the current course
     * @return bool True if the user can be considered a teacher in this course, false otherwise
     */
    function is_teacher()
    {
        return api_is_course_admin() || api_is_coach() || api_is_platform_admin();
    }

}