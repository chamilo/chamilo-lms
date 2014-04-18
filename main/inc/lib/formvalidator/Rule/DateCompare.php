<?php
/* For licensing terms, see /license.txt */
/** @author Bart Mollet, Julio Montoya */
require_once 'HTML/QuickForm/Rule/Compare.php';
/**
 * Class HTML_QuickForm_Rule_DateCompare
 */
class HTML_QuickForm_Rule_DateCompare extends HTML_QuickForm_Rule_Compare
{
    /**
     * Validate 2 dates
     * @param array $values Array with the 2 dates.
     * @param $operator
     *
     * @return boolean true if the 2 given dates match the operator
     */
    function validate($values, $operator = null)
    {
        return api_strtotime($values[0]) < api_strtotime($values[1]);
    }
}
