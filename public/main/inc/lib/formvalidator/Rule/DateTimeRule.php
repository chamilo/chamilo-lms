<?php
/* For licensing terms, see /license.txt */

/**
 * Class DateTimeRule.
 *
 * @author Julio Montoya
 */
class DateTimeRule extends HTML_QuickForm_Rule
{
    /**
     * Check a date.
     *
     * @param string $date    example 2014-04-30 18:00
     * @param array  $options
     *
     * @return bool True if date is valid
     *
     * @see HTML_QuickForm_Rule
     */
    public function validate($date, $options)
    {
        return api_is_valid_date($date, 'Y-m-d H:i');
    }
}
