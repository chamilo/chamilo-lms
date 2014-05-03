<?php
/* For licensing terms, see /license.txt */

/**
* Required elements validation
* @version     1.0
*/
class HTML_QuickForm_Rule_MultipleRequired extends HTML_QuickForm_Rule
{
    /**
     * Checks if all the elements are empty
     *
     * @param     string    $value      Value to check (can be an array)
     * @param     mixed     $options    Not used yet
     * @access    public
     * @return    boolean   true if value is not empty
     */
    function validate($value, $options = null)
    {
    	if(is_array($value))
    	{
    		$value = implode(null,$value);
    	}
        if ((string)$value == '') {
            return false;
        }
        return true;
    } // end func validate


    function getValidationScript($options = null)
    {
        return array('', "{jsVar} == ''");
    } // end func getValidationScript

} // end class HTML_QuickForm_Rule_MultipleRequired
