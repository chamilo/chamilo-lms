<?php
/* For licensing terms, see /license.txt */

/**
 * QuickForm rule to check if a username is of the correct format.
 */
class HTML_QuickForm_Rule_Username extends HTML_QuickForm_Rule
{
    /**
     * Function to check if a username is of the correct format.
     *
     * @param string $username Wanted username
     * @param array  $options
     *
     * @return bool True if username is of the correct format
     *
     * @author Modified by Ivan Tcholakov, 15-SEP-2009.
     *
     * @see HTML_QuickForm_Rule
     * The validation rule is served by the UserManager class as of this moment.
     */
    public function validate($username, $options)
    {
        if (api_get_setting('login_is_email') == 'true') {
            return api_valid_email($username);
        } else {
            return UserManager::is_username_valid($username);
        }
    }
}
