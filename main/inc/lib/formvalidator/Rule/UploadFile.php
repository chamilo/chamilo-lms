<?php
/* For licensing terms, see /license.txt */

/** @author Julio Montoya */

/**
 * Class HTML_QuickForm_Rule_UploadFile.
 */
class HTML_QuickForm_Rule_UploadFile extends HTML_QuickForm_Rule
{
    /**
     * Checks if the given element contains an uploaded file of the filename regex.
     *
     * @param array     Uploaded file info (from $_FILES)
     * @param     string    Regular expression
     *
     * @return bool true if name matches regex, false otherwise
     */
    public function validate($elementValue, $regex)
    {
        if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
            (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')) {
            return is_uploaded_file($elementValue['tmp_name']);
        } else {
            return false;
        }
    }

    // end func _ruleCheckFileName
}
