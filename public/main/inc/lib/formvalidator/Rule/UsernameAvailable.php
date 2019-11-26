<?php
/* For licensing terms, see /license.txt */

/**
 * QuickForm rule to check if a username is available.
 */
class HTML_QuickForm_Rule_UsernameAvailable extends HTML_QuickForm_Rule
{
    /**
     * Function to check if a username is available.
     *
     * @see HTML_QuickForm_Rule
     *
     * @param string $username         Wanted username
     * @param string $current_username
     *
     * @return bool True if username is available
     */
    public function validate($username, $current_username = null)
    {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $username = Database::escape_string($username);
        $current_username = Database::escape_string($current_username);

        $sql = "SELECT * FROM $user_table WHERE username = '$username'";
        if (!is_null($current_username)) {
            $sql .= " AND username != '$current_username'";
        }
        $res = Database::query($sql);
        $number = Database::num_rows($res);

        return $number == 0;
    }
}
