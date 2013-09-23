<?php
class OpenMeetings {

	private $_securitySalt;
	private $_omServerBaseUrl;

	/* ___________ General Methods for the BigBlueButton Class __________ */

	function __construct() {
	/*
	Establish just our basic elements in the constructor:
	*/
		// BASE CONFIGS - set these for your BBB server in config.php and they will
		// simply flow in here via the constants:
		$this->_securitySalt 		= CONFIG_SECURITY_SALT;
		$this->_omServerBaseUrl 	= CONFIG_SERVER_BASE_URL;
	}

}
