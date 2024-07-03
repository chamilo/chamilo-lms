<?php
/* For licensing terms, see /license.txt */

/** @author Julio Montoya */

/**
 * Class HTML_QuickForm_Rule_MaxFileSize.
 */
class HTML_QuickForm_Rule_MaxFileSize extends HTML_QuickForm_Rule
{
    /**
     * @param array $elementValue Uploaded file info (from $_FILES)
     * @param int   $maxSize
     *
     * @return bool
     */
    public function validate($elementValue, $maxSize = 0)
    {
        if (!empty($elementValue['error']) &&
            (UPLOAD_ERR_FORM_SIZE == $elementValue['error'] || UPLOAD_ERR_INI_SIZE == $elementValue['error'])
        ) {
            return false;
        }
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
            return true;
        }

        return $maxSize >= @filesize($elementValue['tmp_name']);
    }
}
