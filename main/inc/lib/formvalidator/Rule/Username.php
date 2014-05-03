<?php
/* For licensing terms, see /license.txt */

/**
 * QuickForm rule to check if a username is of the correct format
 */
class HTML_QuickForm_Rule_Username extends HTML_QuickForm_Rule
{
	/**
	 * Function to check if a username is of the correct format
	 * @see HTML_QuickForm_Rule
	 * @param string $username Wanted username
	 * @return boolean True if username is of the correct format
	 * @author Modified by Ivan Tcholakov, 15-SEP-2009. The validation rule is served by the UserManager class as of this moment.
	 */
	function validate($username, $options)
    {
		return UserManager::is_username_valid($username);
	}
}
