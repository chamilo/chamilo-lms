<?php
/* For licensing terms, see /license.txt */

/** @author Julio Montoya */

/**
 * Class HTML_QuickForm_Rule_FileName.
 */
class HTML_QuickForm_Rule_FileName extends HTML_QuickForm_Rule
{
    /**
     * @param $value array     Uploaded file info (from $_FILES)
     * @param null $options
     *
     * @return bool
     */
    public function validate($value, $options = null)
    {
        if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
            (!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')) {
            return is_uploaded_file($elementValue['tmp_name']);
        } else {
            return false;
        }
    }
}
