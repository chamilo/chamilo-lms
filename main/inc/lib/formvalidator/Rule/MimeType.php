<?php
/* For licensing terms, see /license.txt */

/** @author Julio Montoya */

/**
 * Class HTML_QuickForm_Rule_MimeType.
 */
class HTML_QuickForm_Rule_MimeType extends HTML_QuickForm_Rule
{
    /**
     * Checks if the given element contains an uploaded file of the right mime type.
     *
     * @param array     Uploaded file info (from $_FILES)
     * @param     mixed     Mime Type (can be an array of allowed types)
     *
     * @return bool true if mimetype is correct, false otherwise
     */
    public function validate($elementValue, $mimeType)
    {
        if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
            return true;
        }
        if (is_array($mimeType)) {
            return in_array($elementValue['type'], $mimeType);
        }

        return $elementValue['type'] == $mimeType;
    }

    // end func _ruleCheckMimeType
}
