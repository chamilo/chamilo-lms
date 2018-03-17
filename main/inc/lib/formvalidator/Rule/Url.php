<?php

/**
 * Abstract base class for QuickForm validation rules.
 */

/**
 * Validate urls.
 */
class HTML_QuickForm_Rule_Url extends HTML_QuickForm_Rule
{
    /**
     * Validates url.
     *
     * @param string $url
     *
     * @return bool returns true if valid, false otherwise
     */
    public function validate($url, $options)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
}
