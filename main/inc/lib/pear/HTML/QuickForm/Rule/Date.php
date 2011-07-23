<?php
/* For licensing terms, see /license.txt */
/**
 * QuickForm rule to check a date
 * @package chamilo.include
 */
/**
 * Code
 */
require_once ('HTML/QuickForm/Rule.php');
/**
 * QuickForm rule to check a date
 * @package chamilo.include
 */
class Html_Quickform_Rule_Date extends HTML_QuickForm_Rule
{
	/**
	 * Function to check a date
	 * @see HTML_QuickForm_Rule
	 * @param array $date An array with keys F (month), d (day) and Y (year)
	 * @return boolean True if date is valid
	 */
	function validate($date)
	{
		$compareDate = create_function('$a', 'return checkdate($a[\'M\'],$a[\'d\'],$a[\'Y\']);');
        return $compareDate($date);
	}
}
?>
