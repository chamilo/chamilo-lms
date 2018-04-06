<?php
/* For licensing terms, see /license.txt */

/**
 * QuickForm rule to check if a filetype.
 */
class HTML_QuickForm_Rule_Filetype extends HTML_QuickForm_Rule
{
    /**
     * Function to check if a filetype is allowed.
     *
     * @see HTML_QuickForm_Rule
     *
     * @param array $file       Uploaded file
     * @param array $extensions Allowed extensions
     *
     * @return bool True if filetype is allowed
     */
    public function validate($file, $extensions = [])
    {
        $parts = explode('.', $file['name']);
        if (count($parts) < 2) {
            return false;
        }

        $ext = $parts[count($parts) - 1];
        $extensions = array_map('strtolower', $extensions);

        return in_array(api_strtolower($ext), $extensions);
    }
}
