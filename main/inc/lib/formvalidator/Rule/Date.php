<?php
/* For licensing terms, see /license.txt */
require_once ('HTML/QuickForm/Rule.php');
/**
 * QuickForm rule to check a date
 */
class HTML_QuickForm_Rule_Date extends HTML_QuickForm_Rule
{
	/**
	 * Function to check a date
	 * @see HTML_QuickForm_Rule
	 * @param array $date An array with keys F (month), d (day) and Y (year)
	 * @return boolean True if date is valid
	 */
	function validate($date, $options)
	{
		return checkdate($date['F'], $date['d'], $date['Y']);
	}
}