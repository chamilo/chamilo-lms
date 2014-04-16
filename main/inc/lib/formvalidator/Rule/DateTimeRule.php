<?php
/* For licensing terms, see /license.txt */
/** @author Bart Mollet, Julio Montoya */
require_once 'HTML/QuickForm/Rule.php';

/**
 * Class HTML_QuickForm_Rule_Date
 */
class DateTimeRule extends HTML_QuickForm_Rule
{
	/**
	 * Check a date
	 * @see HTML_QuickForm_Rule
	 * @param string $date example 2014-04-30
     * @param array $options
     *
	 * @return boolean True if date is valid
	 */
	public function validate($date, $options)
	{
        return api_is_valid_date($date, 'Y-m-d h:i');
	}
}
