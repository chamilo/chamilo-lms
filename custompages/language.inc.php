<?php
/* For licensing terms, see /license.txt */
/**
 * CustomPages : Browser language detection
 * Include this file in your custom page if you want to set the language variable of the Chamilo session
 * to the best pick according to the visitor's browser's options.
 * 2011, Jean-Karim Bockstael, CBlue <jeankarim@cblue.be>
 * This requires the Chamilo system to be initialized
 * (note that it's easier to do the following include in the parent page).
 *
 * @package chamilo.custompages
 */
/**
 * Returns the best match between available languages and visitor preferences.
 *
 * @return string the best match as 2-chars code, null when none match
 */
function get_preferred_language($available_langs)
{
    // Parsing the Accept-languages HTTP header
    $langs = [];
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $httplang) {
        $rawlang = explode(';q=', $httplang);
        if (strpos($rawlang[0], '-') !== false) {
            // We ignore the locale part, as in en-GB vs en-US
            $rawlang[0] = substr($rawlang[0], 0, strpos($rawlang[0], '-'));
        }
        if (count($rawlang) == 1) {
            $rawlang[1] = 1.0; // The absence of weighting means a weight of 1 (max)
        }
        $langs[$rawlang[1]][] = $rawlang[0];
    }
    krsort($langs, SORT_NUMERIC);
    // Choosing the best match
    foreach ($langs as $weight => $codes) {
        foreach ($codes as $code) {
            if (in_array($code, $available_langs)) {
                return $code;
            }
        }
    }

    // No match
    return null;
}

/**
 * Get a language variable in a specific language.
 */
function custompages_get_lang($variable)
{
    return get_lang($variable, null, $_SESSION['user_language_choice']);
}
