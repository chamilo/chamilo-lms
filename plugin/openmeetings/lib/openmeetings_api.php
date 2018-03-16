<?php
/**
 * OpenMeetings API.
 *
 * @package chamilo.plugin.openmeetings
 */
/**
 * Class OpenMeetingsAPI.
 */
class OpenMeetingsAPI
{
    private $_user;
    private $_pass;
    private $_serverBaseUrl;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_user = CONFIG_OPENMEETINGS_USER;
        $this->_pass = CONFIG_OPENMEETINGS_PASS;
        $this->_serverBaseUrl = CONFIG_OPENMEETINGS_SERVER_URL;
    }
}
