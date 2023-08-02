<?php

/**
 * Abstract base class for QuickForm validation rules.
 */

/**
 * Validate internal urls (URLs without the domain).
 */
class HTML_QuickForm_Rule_InternalUrl extends HTML_QuickForm_Rule
{
    /**
     * Validates internal url.
     * We cheat a little by using the adding the domain as prefix to use the domain validation process of filter_var().
     *
     * @param string $url
     *
     * @return bool returns true if valid, false otherwise
     */
    public function validate($url, $options)
    {
        return (bool) filter_var(api_get_path(WEB_PATH).$url, FILTER_VALIDATE_URL);
    }
}
