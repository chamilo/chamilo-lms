<?php
/* For licensing terms, see /license.txt */

/**
 * QuickForm rule to compare 2 dates.
 */
class HTML_QuickForm_Rule_CompareDateTimeText extends HTML_QuickForm_Rule_Compare
{
    /**
     * Validate 2 dates.
     *
     * @param string $operator The operator to use (default '==')
     *
     * @return bool True if the 2 given dates match the operator
     */
    public function validate($values, $operator = null)
    {
        $datetime1 = api_strtotime($values[0]);
        $datetime2 = api_strtotime($values[1]);

        if (strpos($operator, 'allow_empty') !== false) {
            $operator = str_replace('allow_empty', '', $operator);
            if (!$datetime2 || empty($datetime2)) {
                return true;
            }
        }
        $result = parent::validate([$datetime1, $datetime2], $operator);

        return $result;
    }
}
