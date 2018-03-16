<?php
/* For licensing terms, see /license.txt */
/** @author Bart Mollet, Julio Montoya */

/**
 * Class HTML_QuickForm_Rule_Date.
 */
class HTML_QuickForm_Rule_Date extends HTML_QuickForm_Rule
{
    /**
     * Check a date.
     *
     * @see HTML_QuickForm_Rule
     *
     * @param string $date    example 2014-04-30
     * @param array  $options
     *
     * @return bool True if date is valid
     */
    public function validate($date, $options)
    {
        return api_is_valid_date($date, 'Y-m-d');
    }
}
