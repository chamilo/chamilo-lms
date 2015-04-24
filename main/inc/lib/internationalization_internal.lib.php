<?php
/* For licensing terms, see /license.txt */

/**
 * File: internationalization_internal.lib.php
 * Main API extension library for Chamilo 1.8.7 LMS,
 * contains functions for internal use only.
 * License: GNU General Public License Version 3 (Free Software Foundation)
 * @author Ivan Tcholakov, <ivantcholakov@gmail.com>, 2009, 2010
 * @author More authors, mentioned in the correpsonding fragments of this source
 *
 * Note: All functions and data structures here are not to be used directly.
 * See the file internationalization.lib.php which contains the "public" API.
 * @package chamilo.library
 */
/**
 * Global variables used by some callback functions
 */
$_api_encoding = null;
$_api_collator = null;

/**
 * Appendix to "Language recognition"
 * Based on the publication:
 * W. B. Cavnar and J. M. Trenkle. N-gram-based text categorization.
 * Proceedings of SDAIR-94, 3rd Annual Symposium on Document Analysis
 * and Information Retrieval, 1994.
 * @link http://citeseer.ist.psu.edu/cache/papers/cs/810/http:zSzzSzwww.info.unicaen.frzSz~giguetzSzclassifzSzcavnar_trenkle_ngram.pdf/n-gram-based-text.pdf
 */
/**
 * Appendix to "Date and time formats"
 */

/**
 * Returns an array of translated week days and months, short and normal names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return array						Returns a multidimensional array with translated week days and months.
 */
function &_api_get_day_month_names($language = null) {
    static $date_parts = array();
    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($date_parts[$language])) {
        $week_day = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $month = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        for ($i = 0; $i < 7; $i++) {
            $date_parts[$language]['days_short'][] = get_lang($week_day[$i].'Short', '', $language);
            $date_parts[$language]['days_long'][] = get_lang($week_day[$i].'Long', '', $language);
        }
        for ($i = 0; $i < 12; $i++) {
            $date_parts[$language]['months_short'][] = get_lang($month[$i].'Short', '', $language);
            $date_parts[$language]['months_long'][] = get_lang($month[$i].'Long', '', $language);
        }
    }
    return $date_parts[$language];
}

/**
 * Returns returns person name convention for a given language.
 * @param string $language	The input language.
 * @param string $type		The type of the requested convention.
 * It may be 'format' for name order convention or 'sort_by' for name sorting convention.
 * @return mixed Depending of the requested type,
 * the returned result may be string or boolean; null is returned on error;
 */
function _api_get_person_name_convention($language, $type)
{
    static $conventions;
    $language = api_purify_language_id($language);
    if (!isset($conventions)) {
        $file = dirname(__FILE__).'/internationalization_database/name_order_conventions.php';
        if (file_exists($file)) {
            $conventions = include ($file);
        } else {
            $conventions = array(
                'english' => array(
                    'format' => 'title first_name last_name',
                    'sort_by' => 'first_name'
                )
            );
        }
        // Overwrite classic conventions
        $customConventions = api_get_configuration_value('name_order_conventions');

        if (!empty($customConventions)) {
            foreach ($customConventions as $key => $data) {
                $conventions[$key] = $data;
            }
        }

        $search1 = array('FIRST_NAME', 'LAST_NAME', 'TITLE');
        $replacement1 = array('%F', '%L', '%T');
        $search2 = array('first_name', 'last_name', 'title');
        $replacement2 = array('%f', '%l', '%t');
        foreach (array_keys($conventions) as $key) {
            $conventions[$key]['format'] = str_replace($search1, $replacement1, $conventions[$key]['format']);
            $conventions[$key]['format'] = _api_validate_person_name_format(_api_clean_person_name(str_replace('%', ' %', str_ireplace($search2, $replacement2, $conventions[$key]['format']))));
            $conventions[$key]['sort_by'] = strtolower($conventions[$key]['sort_by']) != 'last_name' ? true : false;
        }
    }
    switch ($type) {
        case 'format':
            return is_string($conventions[$language]['format']) ? $conventions[$language]['format'] : '%t %f %l';
        case 'sort_by':
            return is_bool($conventions[$language]['sort_by']) ? $conventions[$language]['sort_by'] : true;
    }
    return null;
}

/**
 * Replaces non-valid formats for person names with the default (English) format.
 * @param string $format	The input format to be verified.
 * @return bool				Returns the same format if is is valid, otherwise returns a valid English format.
 */
function _api_validate_person_name_format($format) {
    if (empty($format) || stripos($format, '%f') === false || stripos($format, '%l') === false) {
        return '%t %f %l';
    }
    return $format;
}

/**
 * Removes leading, trailing and duplicate whitespace and/or commas in a full person name.
 * Cleaning is needed for the cases when not all parts of the name are available or when the name is constructed using a "dirty" pattern.
 * @param string $person_name	The input person name.
 * @return string				Returns cleaned person name.
 */
function _api_clean_person_name($person_name) {
    return preg_replace(array('/\s+/', '/, ,/', '/,+/', '/^[ ,]/', '/[ ,]$/'), array(' ', ', ', ',', '', ''), $person_name);
}

/**
 * Appendix to "Multibyte string conversion functions"
 */

/**
 * This is a php-implementation of a function that is similar to mb_convert_encoding() from mbstring extension.
 * The function converts a given string from one to another character encoding.
 * @param string $string					The string being converted.
 * @param string $to_encoding				The encoding that $string is being converted to.
 * @param string $from_encoding				The encoding that $string is being converted from.
 * @return string							Returns the converted string.
 */
function _api_convert_encoding(&$string, $to_encoding, $from_encoding)
{
    return mb_convert_encoding($string, $to_encoding, $from_encoding);
}

/**
 * This function determines the name of corresponding to a given encoding conversion table.
 * It is able to deal with some aliases of the encoding.
 * @param string $encoding		The given encoding identificator, for example 'WINDOWS-1252'.
 * @return string				Returns the name of the corresponding conversion table, for the same example - 'CP1252'.
 */
function _api_get_character_map_name($encoding) {
    static $character_map_selector;
    if (!isset($character_map_selector)) {
        $file = dirname(__FILE__).'/internationalization_database/conversion/character_map_selector.php';
        if (file_exists($file)) {
            $character_map_selector = include ($file);
        } else {
            $character_map_selector = array();
        }
    }
    return isset($character_map_selector[$encoding]) ? $character_map_selector[$encoding] : '';
}

/**
 * Takes an UTF-8 string and returns an array of integer values representing the Unicode characters.
 * Astral planes are supported ie. the ints in the output can be > 0xFFFF. Occurrances of the BOM are ignored.
 * Surrogates are not allowed.
 * @param string $string				The UTF-8 encoded string.
 * @return array						Returns an array of unicode code points.
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, August 2009, adaptation for the Dokeos LMS.
 */
function _api_utf8_to_unicode(&$string) {
    $str = (string)$string;
    $state = 0;			// cached expected number of octets after the current octet
                        // until the beginning of the next UTF8 character sequence
    $codepoint  = 0;	// cached Unicode character
    $bytes = 1;			// cached expected number of octets in the current sequence
    $result = array();
    $len = api_byte_count($str);
    for ($i = 0; $i < $len; $i++) {
        $byte = ord($str[$i]);
        if ($state == 0) {
            // When state is zero we expect either a US-ASCII character or a multi-octet sequence.
            if (0 == (0x80 & ($byte))) {
                // US-ASCII, pass straight through.
                $result[] = $byte;
                $bytes = 1;
            } else if (0xC0 == (0xE0 & ($byte))) {
                // First octet of 2 octet sequence
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x1F) << 6;
                $state = 1;
                $bytes = 2;
            } else if (0xE0 == (0xF0 & ($byte))) {
                // First octet of 3 octet sequence
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x0F) << 12;
                $state = 2;
                $bytes = 3;
            } else if (0xF0 == (0xF8 & ($byte))) {
                // First octet of 4 octet sequence
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x07) << 18;
                $state = 3;
                $bytes = 4;
            } else if (0xF8 == (0xFC & ($byte))) {
                // First octet of 5 octet sequence.
                // This is illegal because the encoded codepoint must be either
                // (a) not the shortest form or
                // (b) outside the Unicode range of 0-0x10FFFF.
                // Rather than trying to resynchronize, we will carry on until the end
                // of the sequence and let the later error handling code catch it.
                $codepoint = ($byte);
                $codepoint = ($codepoint & 0x03) << 24;
                $state = 4;
                $bytes = 5;
            } else if (0xFC == (0xFE & ($byte))) {
                // First octet of 6 octet sequence, see comments for 5 octet sequence.
                $codepoint = ($byte);
                $codepoint = ($codepoint & 1) << 30;
                $state = 5;
                $bytes = 6;
            } else {
                // Current octet is neither in the US-ASCII range nor a legal first octet of a multi-octet sequence.
                $state = 0;
                $codepoint = 0;
                $bytes = 1;
                $result[] = 0xFFFD; // U+FFFD REPLACEMENT CHARACTER is the general substitute character in the Unicode Standard.
                continue ;
            }
        } else {
            // When state is non-zero, we expect a continuation of the multi-octet sequence
            if (0x80 == (0xC0 & ($byte))) {
                // Legal continuation.
                $shift = ($state - 1) * 6;
                $tmp = $byte;
                $tmp = ($tmp & 0x0000003F) << $shift;
                $codepoint |= $tmp;
                // End of the multi-octet sequence. $codepoint now contains the final Unicode codepoint to be output
                if (0 == --$state) {
                    // Check for illegal sequences and codepoints.
                    // From Unicode 3.1, non-shortest form is illegal
                    if (((2 == $bytes) && ($codepoint < 0x0080)) ||
                        ((3 == $bytes) && ($codepoint < 0x0800)) ||
                        ((4 == $bytes) && ($codepoint < 0x10000)) ||
                        (4 < $bytes) ||
                        // From Unicode 3.2, surrogate characters are illegal
                        (($codepoint & 0xFFFFF800) == 0xD800) ||
                        // Codepoints outside the Unicode range are illegal
                        ($codepoint > 0x10FFFF)) {
                        $state = 0;
                        $codepoint = 0;
                        $bytes = 1;
                        $result[] = 0xFFFD;
                        continue ;
                    }
                    if (0xFEFF != $codepoint) {
                        // BOM is legal but we don't want to output it
                        $result[] = $codepoint;
                    }
                    // Initialize UTF8 cache
                    $state = 0;
                    $codepoint = 0;
                    $bytes = 1;
                }
            } else {
                // ((0xC0 & (*in) != 0x80) && (state != 0))
                // Incomplete multi-octet sequence.
                $state = 0;
                $codepoint = 0;
                $bytes = 1;
                $result[] = 0xFFFD;
            }
        }
    }
    return $result;
}

/**
 * Takes an array of Unicode codepoints and returns a UTF-8 string.
 * @param array $codepoints				An array of Unicode codepoints representing a string.
 * @return string						Returns a UTF-8 string constructed using the given codepoints.
 */
function _api_utf8_from_unicode($codepoints) {
    return implode(array_map('_api_utf8_chr', $codepoints));
}

/**
 * Takes a codepoint and returns its correspondent UTF-8 encoded character.
 * Astral planes are supported, ie the intger input can be > 0xFFFF. Occurrances of the BOM are ignored.
 * Surrogates are not allowed.
 * @param int $codepoint				The Unicode codepoint.
 * @return string						Returns the corresponding UTF-8 character.
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, 2009, modifications for the Dokeos LMS.
 * @see _api_utf8_from_unicode()
 * This is a UTF-8 aware version of the function chr().
 * @link http://php.net/manual/en/function.chr.php
 */
function _api_utf8_chr($codepoint) {
    // ASCII range (including control chars)
    if ( ($codepoint >= 0) && ($codepoint <= 0x007f) ) {
        $result = chr($codepoint);
    // 2 byte sequence
    } else if ($codepoint <= 0x07ff) {
        $result = chr(0xc0 | ($codepoint >> 6)) . chr(0x80 | ($codepoint & 0x003f));
    // Byte order mark (skip)
    } else if($codepoint == 0xFEFF) {
        // nop -- zap the BOM
        $result = '';
    // Test for illegal surrogates
    } else if ($codepoint >= 0xD800 && $codepoint <= 0xDFFF) {
        // found a surrogate
        $result = _api_utf8_chr(0xFFFD); // U+FFFD REPLACEMENT CHARACTER is the general substitute character in the Unicode Standard.
    // 3 byte sequence
    } else if ($codepoint <= 0xffff) {
        $result = chr(0xe0 | ($codepoint >> 12)) . chr(0x80 | (($codepoint >> 6) & 0x003f)) . chr(0x80 | ($codepoint & 0x003f));
    // 4 byte sequence
    } else if ($codepoint <= 0x10ffff) {
        $result = chr(0xf0 | ($codepoint >> 18)) . chr(0x80 | (($codepoint >> 12) & 0x3f)) . chr(0x80 | (($codepoint >> 6) & 0x3f)) . chr(0x80 | ($codepoint & 0x3f));
    } else {
         // out of range
        $result = _api_utf8_chr(0xFFFD);
    }
    return $result;
}


/**
 * Appendix to "String comparison"
 */

/**
 * A reverse function from php-core function strnatcmp(), performs string comparison in reverse natural (alpha-numerical) order.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2; >0 if $string1 < $string2; <0 if $string1 > $string2.
 */
function _api_strnatrcmp($string1, $string2) {
    return strnatcmp($string2, $string1);
}

/**
 * ICU locales (accessible through intl extension).
 */

/**
 * Appendix to "Encoding management functions"
 */

/**
 * Sets/Gets internal character encoding of the common string functions within the PHP mbstring extension.
 * @param string $encoding (optional)	When this parameter is given, the function sets the internal encoding.
 * @return string						When $encoding parameter is not given, the function returns the internal encoding.
 * Note: This function is used in the global initialization script for setting the internal encoding to the platform's character set.
 * @link http://php.net/manual/en/function.mb-internal-encoding
 */
function _api_mb_internal_encoding($encoding = null)
{
    return mb_internal_encoding($encoding);
}

/**
 * Checks whether the specified encoding is supported by the PHP mbstring extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_mb_supports($encoding) {
    static $supported = array();
    if (!isset($supported[$encoding])) {
        if (MBSTRING_INSTALLED) {
            $supported[$encoding] = api_equal_encodings($encoding, mb_list_encodings(), true);
        } else {
            $supported[$encoding] = false;
        }
    }
    return $supported[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the PHP iconv extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_iconv_supports($encoding) {
    static $supported = array();
    if (!isset($supported[$encoding])) {
        if (ICONV_INSTALLED) {
            $enc = api_refine_encoding_id($encoding);
            if ($enc != 'HTML-ENTITIES') {
                $test_string = '';
                for ($i = 32; $i < 128; $i++) {
                    $test_string .= chr($i);
                }
                $supported[$encoding] = (@iconv_strlen($test_string, $enc)) ? true : false;
            } else {
                $supported[$encoding] = false;
            }
        } else {
            $supported[$encoding] = false;
        }
    }
    return $supported[$encoding];
}

// This function checks whether the function _api_convert_encoding() (the php-
// implementation) is able to convert from/to a given encoding.
function _api_convert_encoding_supports($encoding) {
    static $supports = array();
    if (!isset($supports[$encoding])) {
        $supports[$encoding] = _api_get_character_map_name(api_refine_encoding_id($encoding)) != '';
    }
    return $supports[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the html-entitiy related functions.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_html_entity_supports($encoding) {
    static $supports = array();
    if (!isset($supports[$encoding])) {
        // See http://php.net/manual/en/function.htmlentities.php
        $html_entity_encodings = array(
            'ISO-8859-1',
            'ISO-8859-15',
            'UTF-8',
            'CP866',
            'CP1251',
            'CP1252',
            'KOI8-R',
            'BIG5', '950',
            'GB2312', '936',
            'BIG5-HKSCS',
            'Shift_JIS', 'SJIS', '932',
            'EUC-JP', 'EUCJP'
        );
        $supports[$encoding] = api_equal_encodings($encoding, $html_entity_encodings);
    }
    return $supports[$encoding];
}
