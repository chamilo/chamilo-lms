<?php
/* For licensing terms, see /license.txt */

/**
 * QuickForm rule to check a text field has a minimum of X chars
 * @package chamilo.include
 */
class Html_Quickform_Rule_MinText extends HTML_QuickForm_Rule
{
    /**
     * Function to check a text field has a minimum of X chars
     * @see HTML_QuickForm_Rule
     * @param string $text A text
     * @param int $count The minimum number of characters that the text should contain
     * @return boolean True if text has the minimum number of chars required
     */
    public function validate($text, $count)
    {
        $checkMinText = function($a, $b) {
            return strlen(utf8_decode($a)) >= $b;
        };

        return $checkMinText($text, $count);
    }
}
