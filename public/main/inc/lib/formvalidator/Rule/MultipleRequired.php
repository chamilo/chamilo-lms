<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | Copy of the existing rule "required" to check if at least one element|
// | has been filled. Then $value can be an array						  |
// +----------------------------------------------------------------------+
// | Authors: Eric Marguin <e.marguin@elixir-interactive.com>             |
// +----------------------------------------------------------------------+

/**
 * Required elements validation.
 *
 * @version     1.0
 */
class HTML_QuickForm_Rule_MultipleRequired extends HTML_QuickForm_Rule
{
    /**
     * Checks if all the elements are empty.
     *
     * @param string $value   Value to check (can be an array)
     * @param mixed  $options Not used yet
     *
     * @return bool true if value is not empty
     */
    public function validate($value, $options = null)
    {
        if (is_array($value)) {
            $value = implode(null, $value);
        }
        if ((string) $value == '') {
            return false;
        }

        return true;
    }

    // end func validate

    public function getValidationScript($options = null)
    {
        return ['', "{jsVar} == ''"];
    }
}
