<?php
/* For licensing terms, see /license.txt */

/**
 * Validate urls
 *
 */
class HTML_QuickForm_Rule_Url extends HTML_QuickForm_Rule
{

    /**
     * Validates url
     *
     * @param string $url
     * @return boolean  Returns true if valid, false otherwise.
     */
    function validate($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

}

