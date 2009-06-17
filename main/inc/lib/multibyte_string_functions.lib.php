<?php
/**
 * ==============================================================================
 * File: multibyte_string_functions.lib.php
 * Main API extension library for Dokeos 1.8.6+ LMS
 * A common purpose library for supporting multibyte string aware functions.
 * License: GNU/GPL version 2 or later (Free Software Foundation)
 * @author: Ivan Tcholakov, ivantcholakov@gmail.com
 * October 2008 - initial implementation.
 * May 2009 - refactoring and minor corrections have been implemented.
 * @package dokeos.library
 * ==============================================================================
 */

/**
 * Notes:
 *
 * 1. For all the functions from this library witn optional encoding
 * parameters, the system's encoding is assumed by default, i.e. the
 * value that is returned by api_get_setting('platform_charset') or 
 * the value of the global variable $charset.
 *
 * 2. In other aspects, most of the functions in this library try to copy
 * behaviour of some core PHP functions and some functions from the
 * mbstring extension. Mostly they have similar names prefixed with "api_".
 * For your convenience, links have been given to the documentation of the
 * original PHP functions. Thus, you may exploit on your previous habits.
 *
 * 3. Why these function have been introduced? Because they are able to
 * support more encodings than the original ones. And which is more
 * important - they are UTF-8 aware. So, they should be used for strings
 * in natural language. For internal system identificators of file names
 * which are supposed to contain only English letters you may use the
 * original PHP string functions.
 *
 * 4. This library requires PHP mbstring extension to be activated.
 * When encodings to be used are not supported by mbstring, this library
 * is able to exploit the PHP iconv extesion, which in this case should
 * be activated too.
 */

/**
 * ----------------------------------------------------------------------------
 * Constants
 * ----------------------------------------------------------------------------
 */

define('INTL_INSTALLED', function_exists('intl_get_error_code'));	// intl extension (from PECL), it is installed by default as of PHP 5.3.0
define('ICONV_INSTALLED', function_exists('iconv'));				// iconv extension, for PHP5 on Windows it is installed by default.
define('MBSTRING_INSTALLED', function_exists('mb_strlen'));			// mbstring extension.

/**
 * ----------------------------------------------------------------------------
 * A safe way to calculate binary lenght of a string (as number of bytes)
 * ----------------------------------------------------------------------------
 */

/**
 * Calculates binary lenght of a string, as number of bytes, regardless the php-setting mbstring.func_overload.
 * This function should work for all multi-byte related changes of PHP5 configuration.
 * @param string $string	The input string.
 * @return int				Returns the length of the input string (or binary data) as number of bytes.
 */
function api_byte_count($string) {
	static $use_mb_strlen;
	if (!isset($use_mb_strlen)) {
		$use_mb_strlen = MBSTRING_INSTALLED && ((int) ini_get('mbstring.func_overload') & 2);
	}
	if ($use_mb_strlen) {
		return mb_strlen($string, '8bit');
	}
	return strlen($string);

	// For PHP6 this function probably will contain:
	//return strlen((binary)$string);
}

/**
 * ----------------------------------------------------------------------------
 * Multibyte string conversion functions
 * ----------------------------------------------------------------------------
 */

/**
 * Converts character encoding of a given string.
 * @param string $string					The string being converted.
 * @param string $to_encoding				The encoding that $string is being converted to.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 * This function is aimed at replacing the function mb_convert_encoding() for human-language strings.
 * @link http://php.net/manual/en/function.mb-convert-encoding
 */
function api_convert_encoding($string, $to_encoding, $from_encoding = null) {
	if (empty($from_encoding)) {
		$from_encoding = api_mb_internal_encoding();
	}
	if (api_equal_encodings($to_encoding, $from_encoding)) {
		// When conversion is not needed, the string is returned directly, without validation.
		return $string;
	}
	if (api_mb_supports($to_encoding) && api_mb_supports($from_encoding)) {
		return @mb_convert_encoding($string, $to_encoding, $from_encoding);
	}
	elseif (api_iconv_supports($to_encoding) && api_iconv_supports($from_encoding)) {
		return @iconv($from_encoding, $to_encoding, $string);
	} 
	// Here the function gives up.
	return $string;
}

/**
 * Converts a given string into UTF-8 encoded string.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 * This function is aimed at replacing the function utf8_encode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-encode
 */
function api_utf8_encode($string, $from_encoding = null) {
	if (empty($from_encoding)) {
		$from_encoding = api_mb_internal_encoding();
	}
	if (api_is_utf8($from_encoding)) {
		// When conversion is not needed, the string is returned directly, without validation.
		return $string;
	}
	if (api_mb_supports($from_encoding)) {
		return @mb_convert_encoding($string, 'UTF-8', $from_encoding);
	}
	elseif (api_iconv_supports($from_encoding)) {
		return @iconv($from_encoding, 'UTF-8', $string);
	}
	// Here the function gives up.
	return $string;
}

/**
 * Converts a given string from UTF-8 encoding to a specified encoding.
 * @param string $string					The string being converted.
 * @param string $to_encoding (optional)	The encoding that $string is being converted to. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 * This function is aimed at replacing the function utf8_decode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-decode
 */
function api_utf8_decode($string, $to_encoding = null) {
	if (empty($to_encoding)) {
		$to_encoding = api_mb_internal_encoding();
	}
	if (api_is_utf8($to_encoding)) {
		// When conversion is not needed, the string is returned directly, without validation.
		return $string;
	}
	if (api_mb_supports($to_encoding)) {
		return @mb_convert_encoding($string, $to_encoding, 'UTF-8');
	}
	elseif (api_iconv_supports($to_encoding)) {
		return @iconv('UTF-8', $to_encoding, $string);
	}
	// Here the function gives up.
	return $string;
}

/**
 * Converts a given string into the system ecoding (or platform character set).
 * When $from encoding is omited on UTF-8 platforms then language dependent encoding
 * is guessed/assumed. On non-UTF-8 platforms omited $from encoding is assumed as UTF-8.
 * When the parameter $check_utf8_validity is true the function checks string's
 * UTF-8 validity and decides whether to try to convert it or not.
 * This function is useful for problem detection or making workarounds.
 * @param string $string						The string being converted.
 * @param string $from_encoding (optional)		The encoding that $string is being converted from. It is guessed when it is omited.
 * @param bool $check_utf8_validity (optional)	A flag for UTF-8 validity check as condition for making conversion.
 * @return string								Returns the converted string.
 */
function api_to_system_encoding($string, $from_encoding = null, $check_utf8_validity = false) {
	$charset = api_get_system_encoding();
	if (empty($from_encoding)) {
		if (api_is_utf8($charset)) {
			$from_encoding = api_get_non_utf8_encoding();
		} else {
			$from_encoding = 'UTF-8';
		}
	}
	if (api_equal_encodings($charset, $from_encoding)) {
		return $string;
	}
	if ($check_utf8_validity) {
		if (api_is_utf8($charset)) {
			if (api_is_valid_utf8($string)) {
				return $string;
			}
		}
		elseif (api_is_utf8($from_encoding)) {
			if (!api_is_valid_utf8($string)) {
				return $string;
			}
		}
	}
	return api_convert_encoding($string, $charset, $from_encoding);
}

/**
 * Converts all applicable characters to HTML entities.
 * @param string $string				The input string.
 * @param int $quote_style (optional)	The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)	The encoding (of the input string) used in conversion. If it is omited, the platform character set is assumed.
 * @return string						Returns the converted string.
 * This function is aimed at replacing the function htmlentities() for human-language strings.
 * @link http://php.net/manual/en/function.htmlentities
 */
function api_htmlentities($string, $quote_style = ENT_COMPAT, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (!api_is_utf8($encoding) && api_html_entity_supports($encoding)) {
		return htmlentities($string, $quote_style, $encoding);
	}
	if (!api_is_encoding_supported($encoding)) {
		return $string;
	}
	$string = api_convert_encoding(api_utf8_encode($string, $encoding), 'HTML-ENTITIES', 'UTF-8');
	switch($quote_style) {
		case ENT_COMPAT:
			$string = str_replace("'", '&quot;', $string);
			break;
		case ENT_QUOTES:
			$string = str_replace("'", '&#039;', str_replace('"', '&quot;', $string));
			break;
	}
	return $string;
}

/**
 * Convers HTML entities into normal characters.
 * @param string $string				The input string.
 * @param int $quote_style (optional)	The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)	The encoding (of the result) used in conversion. If it is omited, the platform character set is assumed.
 * @return string						Returns the converted string.
 * This function is aimed at replacing the function html_entity_decode() for human-language strings.
 * @link http://php.net/html_entity_decode
 */
function api_html_entity_decode($string, $quote_style = ENT_COMPAT, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (!api_is_utf8($encoding) && api_html_entity_supports($encoding)) {
		return html_entity_decode($string, $quote_style, $encoding);
	}
	if (!api_is_encoding_supported($encoding)) {
		return $string;
	}
	return api_utf8_decode(html_entity_decode(api_convert_encoding($string, 'UTF-8', $encoding), $quote_style, 'UTF-8'), $encoding);
}

/**
 * This function encodes (conditionally) a given string to UTF-8 if XmlHttp-request has been detected.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_xml_http_response_encode($string, $from_encoding = null) {
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		return api_convert_encoding($string, 'UTF-8', $from_encoding);
	}
	return $string;
}

/**
 * This function converts a given string to the encoding that filesystem uses for representing file/folder names.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_file_system_encode($string, $from_encoding = null) {
	if (empty($from_encoding)) {
		$from_encoding = api_mb_internal_encoding();
	}
	return api_convert_encoding($string, api_get_file_system_encoding(), $from_encoding);
}

/**
 * This function converts a given string from the encoding that filesystem uses for representing file/folder names.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_file_system_decode($string, $to_encoding = null) {
	if (empty($to_encoding)) {
		$to_encoding = api_mb_internal_encoding();
	}
	return api_convert_encoding($string, $to_encoding, api_get_file_system_encoding());
}

/**
 * ----------------------------------------------------------------------------
 * Common multibyte string functions
 * ----------------------------------------------------------------------------
 */

/**
 * Executes a regular expression match with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern			The regular expression pattern.
 * @param string $string			The searched string.
 * @param array $regs (optional)	If specified, by this passed by reference parameter an array containing found match and its substrings is returned.
 * @return mixed					1 if match is found, FALSE if not. If $regs has been specified, byte-length of the found match is returned, or FALSE if no match has been found.
 * This function is aimed at replacing the functions ereg() and mb_ereg() for human-language strings.
 * @link http://php.net/manual/en/function.ereg
 * @link http://php.net/manual/en/function.mb-ereg
 */
function api_ereg($pattern, $string, & $regs = null) {
	$count = func_num_args();
	$encoding = api_mb_regex_encoding();
	if (api_mb_supports($encoding)) {
		if ($count < 3) {
			return @mb_ereg($pattern, $string);
		} else {
			$result = @mb_ereg($pattern, $string, $regs);
			return $result;
		}
	}
	elseif (api_iconv_supports($encoding)) {
		api_mb_regex_encoding('UTF-8');
		if ($count < 3) {
			$result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
		} else {
			$result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
			$regs = _api_array_utf8_decode($regs, $encoding);
		}
		api_mb_regex_encoding($encoding);
		return $result;
	} else {
		if ($count < 3) {
			return ereg($pattern, $string);
		} else {
			return ereg($pattern, $string, $regs);
		}
	}
}

/**
 * Scans string for matches to pattern, then replaces the matched text with replacement, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern				The regular expression pattern.
 * @param string $replacement			The replacement text.
 * @param string $string				The searched string.
 * @param string $option (optional)		Matching condition.
 * If i is specified for the matching condition parameter, the case will be ignored.
 * If x is specified, white space will be ignored.
 * If m is specified, match will be executed in multiline mode and line break will be included in '.'.
 * If p is specified, match will be executed in POSIX mode, line break will be considered as normal character.
 * If e is specified, replacement string will be evaluated as PHP expression.
 * @return mixed						The modified string is returned. If no matches are found within the string, then it will be returned unchanged. FALSE will be returned on error.
 * This function is aimed at replacing the functions ereg_replace() and mb_ereg_replace() for human-language strings.
 * @link http://php.net/manual/en/function.ereg-replace
 * @link http://php.net/manual/en/function.mb-ereg-replace
 */
function api_ereg_replace($pattern, $replacement, $string, $option = null) {
	$encoding = api_mb_regex_encoding();
	if (api_mb_supports($encoding)) {
		if (is_null($option)) {
			return @mb_ereg_replace($pattern, $replacement, $string);
		} else {
			return @mb_ereg_replace($pattern, $replacement, $string, $option);
		}
	}
	elseif (api_iconv_supports($encoding)) {
		api_mb_regex_encoding('UTF-8');

		if (is_null($option)) {
			$result = api_utf8_decode(@mb_ereg_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding)), $encoding);
		} else {
			$result = api_utf8_decode(@mb_ereg_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding), $option), $encoding);
		}
		api_mb_regex_encoding($encoding);
		return $result;
	} else {
		return ereg_replace($pattern, $replacement, $string);
	}
}

// This is a helper callback function for internal purposes.
function _api_array_utf8_decode($variable, $encoding) {
	if (is_array($variable)) {
		return array_map('_api_array_utf8_decode', $variable, $encoding);
	}
    if (is_string($var)) {
    	return api_utf8_decode($variable, $encoding);
    }
    return $variable;
}

/**
 * Executes a regular expression match, ignoring case, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern			The regular expression pattern.
 * @param string $string			The searched string.
 * @param array $regs (optional)	If specified, by this passed by reference parameter an array containing found match and its substrings is returned.
 * @return mixed					1 if match is found, FALSE if not. If $regs has been specified, byte-length of the found match is returned, or FALSE if no match has been found.
 * This function is aimed at replacing the functions eregi() and mb_eregi() for human-language strings.
 * @link http://php.net/manual/en/function.eregi
 * @link http://php.net/manual/en/function.mb-eregi
 */
function api_eregi($pattern, $string, & $regs = null) {
	$count = func_num_args();
	$encoding = api_mb_regex_encoding();
	if (api_mb_supports($encoding)) {
		if ($count < 3) {
			return @mb_eregi($pattern, $string);
		} else {
			return @mb_eregi($pattern, $string, $regs);
		}
	}
	elseif (api_iconv_supports($encoding)) {
		api_mb_regex_encoding('UTF-8');

		if ($count < 3) {
			$result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
		} else {
			$result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
			$regs = _api_array_utf8_decode($regs, $encoding);
		}
		api_mb_regex_encoding($encoding);
		return $result;
	} else {
		if ($count < 3) {
			return eregi($pattern, $string);
		} else {
			return eregi($pattern, $string, $regs);
		}
	}
}

/**
 * Scans string for matches to pattern, then replaces the matched text with replacement, ignoring case, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern				The regular expression pattern.
 * @param string $replacement			The replacement text.
 * @param string $string				The searched string.
 * @param string $option (optional)		Matching condition.
 * If i is specified for the matching condition parameter, the case will be ignored.
 * If x is specified, white space will be ignored.
 * If m is specified, match will be executed in multiline mode and line break will be included in '.'.
 * If p is specified, match will be executed in POSIX mode, line break will be considered as normal character.
 * If e is specified, replacement string will be evaluated as PHP expression.
 * @return mixed						The modified string is returned. If no matches are found within the string, then it will be returned unchanged. FALSE will be returned on error.
 * This function is aimed at replacing the functions eregi_replace() and mb_eregi_replace() for human-language strings.
 * @link http://php.net/manual/en/function.eregi-replace
 * @link http://php.net/manual/en/function.mb-eregi-replace
 */
function api_eregi_replace($pattern, $replacement, $string, $option = null) {
	$encoding = api_mb_regex_encoding();
	if (api_mb_supports($encoding)) {
		if (is_null($option)) {
			return @mb_eregi_replace($pattern, $replacement, $string);
		} else {
			return @mb_eregi_replace($pattern, $replacement, $string, $option);
		}
	}
	elseif (api_iconv_supports($encoding)) {
		api_mb_regex_encoding('UTF-8');
		if (is_null($option)) {
			$result = api_utf8_decode(@mb_eregi_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding)), $encoding);
		} else {
			$result = api_utf8_decode(@mb_eregi_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding), $option), $encoding);
		}
		api_mb_regex_encoding($encoding);
		return $result;
	} else {
		return eregi_replace($pattern, $replacement, $string);
	}
}

/**
 * Splits a multibyte string using regular expression pattern and returns the result as an array.
 * By default this function uses the platform character set.
 * @param string $pattern			The regular expression pattern.
 * @param string $string			The string being split.
 * @param int $limit (optional)		If this optional parameter $limit is specified, the string will be split in $limit elements as maximum.
 * @return array					The result as an array.
 * This function is aimed at replacing the functions split() and mb_split() for human-language strings.
 * @link http://php.net/manual/en/function.split
 * @link http://php.net/manual/en/function.mb-split
 */
function api_split($pattern, $string, $limit = null) {
	$encoding = api_mb_regex_encoding();
	if (api_mb_supports($encoding)) {
		if (is_null($limit)) {
			return @mb_split($pattern, $string);
		} else {
			return @mb_split($pattern, $string, $limit);
		}
	}
	elseif (api_iconv_supports($encoding)) {
		api_mb_regex_encoding('UTF-8');
		if (is_null($limit)) {
			$result = @mb_split(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
		} else {
			$result = @mb_split(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $limit);
		}
		$result = _api_array_utf8_decode($result, $encoding);
		api_mb_regex_encoding($encoding);
		return $result;
	} else {
		if (is_null($limit)) {
			return split($pattern, $string);
		} else {
			return split($pattern, $string, $limit);
		}
	}
}

/**
 * This function returns a string or an array with all occurrences of search in subject (ignoring case) replaced with the given replace value.
 * @param mixed $search					String or array of strings to be found.
 * @param mixed $replace				String or array of strings used for replacement.
 * @param mixed $subject				String or array of strings being searced.
 * @param int $count (optional)			The number of matched and replaced needles will be returned in count, which is passed by reference. 
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						String or array as a result.
 * Notes:
 * If $subject is an array, then the search and replace is performed with every entry of subject, the return value is an array.
 * If $search and $replace are arrays, then the function takes a value from each array and uses it to do search and replace on subject.
 * If $replace has fewer values than search, then an empty string is used for the rest of replacement values.
 * If $search is an array and $replace is a string, then this replacement string is used for every value of search.
 * This function is aimed at replacing the function str_ireplace() for human-language strings.
 * @link http://php.net/manual/en/function.str-ireplace
 * TODO: To be revised and to be checked.
 */
function api_str_ireplace($search, $replace, $subject, & $count = null, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (is_array($subject)) {
		foreach ($subject as $key => $val) {
			$subject[$key] = api_str_ireplace($search, $replace, $val, $count, $encoding);
		}
		return $subject;
	}
	if (is_array($search)) {
		foreach (array_keys($search) as $key) {
			if (is_array($replace)) {
				if (array_key_exists($key, $replace)) {
					$subject = api_str_ireplace($search[$key], $replace[$key], $subject, $count, $encoding);
				} else {
					$subject = api_str_ireplace($search[$key], '', $subject, $count, $encoding);
				}
			} else {
				$subject = api_str_ireplace($search[$key], $replace, $subject, $count, $encoding);
			}
		}
		return $subject;
	}
	$search = api_strtolower($search, $encoding);
	$subject_lower = api_strtolower($subject, $encoding);
	$total_matched_strlen = 0;
	$i = 0;
	while (preg_match(api_add_pcre_unicode_modifier('/(.*?)'.preg_quote($search, '/').'/s', $encoding), $subject_lower, $matches)) {
		$matched_strlen = api_strlen($matches[0], $encoding);
		$subject_lower = api_substr($subject_lower, $matched_strlen, api_strlen($subject_lower, $encoding), $encoding);
		$offset = $total_matched_strlen + api_strlen($matches[1], $encoding) + ($i * (api_strlen($replace, $encoding) - 1));
		$subject = api_substr_replace($subject, $replace, $offset, api_strlen($search), $encoding);
		$total_matched_strlen += $matched_strlen;
		$i++;
	}
	$count += $i;
	return $subject;
}

/**
 * Converts a string to an array.
 * @param string $string				The input string.
 * @param int $split_length				Maximum character-length of the chunk, one character by default.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array						The result array of chunks with the spcified length.
 * Notes:
 * If the optional split_length parameter is specified, the returned array will be broken down into chunks
 * with each being split_length in length, otherwise each chunk will be one character in length.
 * FALSE is returned if split_length is less than 1.
 * If the split_length length exceeds the length of string, the entire string is returned as the first (and only) array element.
 * This function is aimed at replacing the function str_split() for human-language strings.
 * @link http://php.net/str_split
 */
function api_str_split($string, $split_length = 1, $encoding = null) {
	if ($split_length < 1) {
		return false;
	}
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	$result = array();
	if (api_mb_supports($encoding)) {
		for ($i = 0, $length = @mb_strlen($string, $encoding); $i < $length; $i += $split_length) {
			$result[] = @mb_substr($string, $i, $split_length, $encoding);
		}
	}
	elseif (api_iconv_supports($encoding)) {
		for ($i = 0, $length = api_strlen($string, $encoding); $i < $length; $i += $split_length) {
			$result[] = api_substr($string, $i, $split_length, $encoding);
		}
	} else {
		for ($i = 0, $length = strlen($string); $i < $length; $i += $split_length) {
			$result[] = substr($string, $i, $split_length);
		}
	}
	return $result;
}

/**
 * Case-insensitive string comparison wuth extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal. 
 * This function is aimed at replacing the function strcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcasecmp
 */
function api_strcasecmp($string1, $string2, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	return strcmp(api_strtolower($string1, $encoding), api_strtolower($string2, $encoding));
}

/**
 * Finds position of first occurrence of a string within another, case insensitive.
 * @param string $haystack				The string from which to get the position of the first occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset					The position in $haystack to start searching from. If it is omitted, searching starts from the beginning.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on. 
 * This function is aimed at replacing the functions stripos() and mb_stripos() for human-language strings.
 * @link http://php.net/manual/en/function.stripos
 * @link http://php.net/manual/en/function.mb-stripos
 */
function api_stripos($haystack, $needle, $offset = 0, $encoding = null) {
	if (empty($encoding)){
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_stripos($haystack, $needle, $offset, $encoding);
	} elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_stripos(api_utf8_encode($haystack, $encoding), api_utf8_encode($needle, $encoding), $offset, 'UTF-8'), $encoding);
	}
	return stripos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another, case insensitive.
 * @param string $haystack				The string from which to get the first occurrence.
 * @param string @needle				The string to be found.
 * @param bool $part (optional)			Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $part is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $part is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions stristr() and mb_stristr() for human-language strings.
 * @link http://php.net/manual/en/function.stristr
 * @link http://php.net/manual/en/function.mb-stristr
 */
function api_stristr($haystack, $needle, $part = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_stristr($haystack, $needle, $part, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_stristr(api_utf8_encode($haystack, $encoding), api_utf8_encode($needle, $encoding), $part, 'UTF-8'));
	}
	return stristr($haystack, $needle, $part);
}

/**
 * Returns length of the input string.
 * @param string $string				The string which length is to be calculated.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns the number of characters within the string. A multi-byte character is counted as 1.
 * This function is aimed at replacing the functions strlen() and mb_strlen() for human-language strings.
 * @link http://php.net/manual/en/function.strlen
 * @link http://php.net/manual/en/function.mb-strlen
 * Note: When you use strlen() to test for an empty string, you needn't change it to api_strlen().
 * For example, in lines like the following:
 * if (strlen($string) > 0)
 * if (strlen($string) != 0)
 * there is no need the original function strlen() to be changed, it works correctly and faster for these cases.
 */
function api_strlen($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strlen($string, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return @iconv_strlen($string, $encoding);
	}
	return strlen($string);
}

/**
 * Finds position of first occurrence of a string within another.
 * @param string $haystack				The string from which to get the position of the first occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset (optional)		The position in $haystack to start searching from. If it is omitted, searching starts from the beginning.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on. 
 * This function is aimed at replacing the functions strpos() and mb_strpos() for human-language strings.
 * @link http://php.net/manual/en/function.strpos
 * @link http://php.net/manual/en/function.mb-strpos
 */
function api_strpos($haystack, $needle, $offset = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strpos($haystack, $needle, $offset, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_strpos(api_utf8_encode($haystack, $encoding), api_utf8_encode($needle, $encoding), $offset, 'UTF-8'), $encoding);
	}
	return strpos($haystack, $needle, $offset);
}

/**
 * Finds the last occurrence of a character in a string.
 * @param string $haystack				The string from which to get the last occurrence.
 * @param string $needle				The string which first character is to be found.
 * @param bool $part (optional)			Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the portion of $haystack, or FALSE if the first character from $needle is not found.
 * Notes:
 * If $part is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence.
 * If $part is set to FALSE, the function returns all of $haystack from the first occurrence to the end.
 * This function is aimed at replacing the functions strrchr() and mb_strrchr() for human-language strings.
 * @link http://php.net/manual/en/function.strrchr
 * @link http://php.net/manual/en/function.mb-strrchr
 */
function api_strrchr($haystack, $needle, $part = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strrchr($haystack, $needle, $part, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_strrchr(api_utf8_encode($haystack, $encoding), api_utf8_encode($needle, $encoding), $part, 'UTF-8'), $encoding);
	}
	return strrchr($haystack, $needle);
}

/**
 * Reverses a string.
 * @param string $string				The string to be reversed.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the reversed string.
 * This function is aimed at replacing the function strrev() for human-language strings.
 * @link http://php.net/manual/en/function.strrev
 */
function api_strrev($string, $encoding = null) {
	if (empty($string)) {
		return '';
	}
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	$result = '';
	for ($i = api_strlen($string, $encoding) - 1; $i > -1; $i--) {
		$result .= api_substr($string, $i, 1, $encoding);
	}
	return $result;
}

/**
 * Finds the position of last occurrence of a string in a string.
 * @param string $haystack				The string from which to get the position of the last occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset (optional)		$offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on. 
 * This function is aimed at replacing the functions strrpos() and mb_strrpos() for human-language strings.
 * @link http://php.net/manual/en/function.strrpos
 * @link http://php.net/manual/en/function.mb-strrpos
 */
function api_strrpos($haystack, $needle, $offset = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strrpos($haystack, $needle, $offset, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_strrpos(api_utf8_encode($haystack, $encoding), api_utf8_encode($needle, $encoding), $offset, 'UTF-8'), $encoding);
	}
	return strrpos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another.
 * @param string $haystack				The string from which to get the first occurrence.
 * @param string @needle				The string to be found.
 * @param bool $part (optional)			Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $part is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $part is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions strstr() and mb_strstr() for human-language strings.
 * @link http://php.net/manual/en/function.strstr
 * @link http://php.net/manual/en/function.mb-strstr
 */
function api_strstr($haystack, $needle, $part = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strstr($haystack, $needle, $part, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_strstr(api_utf8_encode($haystack, $encoding), api_utf8_encode($needle, $encoding), $part, 'UTF-8'), $encoding);
	}
	return strstr($haystack, $needle, $part);
}

/**
 * Makes a string lowercase.
 * @param string $string				The string being lowercased.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the string with all alphabetic characters converted to lowercase.
 * This function is aimed at replacing the functions strtolower() and mb_strtolower() for human-language strings.
 * @link http://php.net/manual/en/function.strtolower
 * @link http://php.net/manual/en/function.mb-strtolower
 */
function api_strtolower($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strtolower($string, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_strtolower(api_utf8_encode($string, $encoding), 'UTF-8'), $encoding);
	}
	return strtolower($string);
}

/**
 * Makes a string uppercase.
 * @param string $string				The string being uppercased.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the string with all alphabetic characters converted to uppercase.
 * This function is aimed at replacing the functions strtoupper() and mb_strtoupper() for human-language strings.
 * @link http://php.net/manual/en/function.strtoupper
 * @link http://php.net/manual/en/function.mb-strtoupper
 */
function api_strtoupper($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if (api_mb_supports($encoding)) {
		return @mb_strtoupper($string, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_strtoupper(api_utf8_encode($string, $encoding), 'UTF-8'), $encoding);
	}
	return strtoupper($string);
}

/**
 * Translates certain characters.
 * @param string $string				The string being translated.
 * @param mixed $from					A string that contains the character to be replaced. This parameter can be also an array with pairs of characters 'from' => 'to'.
 * @param string $to (optional)			A string that contains the replacing characters.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns a copy of $string, translating all occurrences of each character in $from to the corresponding character in $to.
 * This function is aimed at replacing the function strtr() for human-language strings.
 * @link http://php.net/manual/en/function.strtr
 * TODO: To be revised and tested. Probably this function will be not needed.
 */
function api_strtr($string, $from, $to = null, $encoding = null) {
	if (empty($string)) {
		return '';
	}
	if (is_array($from)) {
		if (empty($from)) {
			return $string;
		}
		$encoding = $to;
		if (empty($encoding)){
			$encoding = api_mb_internal_encoding();
		}
		$translator = $from;
	} else {
		if (empty($from) || empty($to)) {
			return $string;
		}
		if (empty($encoding)) {
			$encoding = api_mb_internal_encoding();
		}
		$translator = array();
		$arr_from = api_str_split($from, 1, $encoding);
		$arr_to = api_str_split($to, 1, $encoding);
		$n = count($arr_from);
		$n2 = count($arr_to);
		if ($n > $n2) $n = $n2;
		for ($i = 0; $i < $n; $i++) {
			$translator[$arr_from[$i]] = $arr_to[$i];
		}
	}
	$arr_string = api_str_split($string, 1, $encoding);
	$n = count($arr_string);
	$result = '';
	for ($i = 0; $i < $n; $i++) {
		if (is_set($translator[$arr_string[$i]])) {
			$result .= $translator[$arr_string[$i]];
		} else {
			$result .= $arr_string[$i];
		}
	}
	return $result;
}

/**
// Gets part of a string.
 * @param string $string				The input string.
 * @param int $start					The first position from which the extracted part begins.
 * @param int $length					The length in character of the extracted part.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the part of the string specified by the start and length parameters.
 * Note: First character's position is 0. Second character position is 1, and so on.
 * This function is aimed at replacing the functions substr() and mb_substr() for human-language strings.
 * @link http://php.net/manual/en/function.substr
 * @link http://php.net/manual/en/function.mb-substr
 */
function api_substr($string, $start, $length = null, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	// Passing null as $length would mean 0. This behaviour has been corrected here.
	if (is_null($length)) {
		$length = api_strlen($string, $encoding);
	}
	if (api_mb_supports($encoding)) {
		return @mb_substr($string, $start, $length, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_substr(api_utf8_encode($string, $encoding), $start, $length, 'UTF-8'), $encoding);
	}
	return substr($string, $start, $length);
}

/**
 * Replaces text within a portion of a string.
 * @param string $string				The input string.
 * @param string $replacement			The replacement string.
 * @param int $start					The position from which replacing will begin.
 * Notes:
 * If $start is positive, the replacing will begin at the $start'th offset into the string.
 * If $start is negative, the replacing will begin at the $start'th character from the end of the string.
 * @param int $length (optional)		The position where replacing will end.
 * Notes:
 * If given and is positive, it represents the length of the portion of the string which is to be replaced.
 * If it is negative, it represents the number of characters from the end of string at which to stop replacing.
 * If it is not given, then it will default to api_strlen($string); i.e. end the replacing at the end of string.
 * If $length is zero, then this function will have the effect of inserting replacement into the string at the given start offset. 
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						The result string is returned.
 * This function is aimed at replacing the function substr_replace() for human-language strings.
 * @link http://php.net/manual/function.substr-replace
 */
function api_substr_replace($string, $replacement, $start, $length = null, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	if ($length == null) {
		return api_substr($string, 0, $start, $encoding) . $replacement;
	} else {
		if ($length < 0) {
			$length = api_strlen($string, $encoding) - $start + $length;
		}
		return
			api_substr($string, 0, $start, $encoding) . $replacement .
			api_substr($string, $start + $length, api_strlen($string, $encoding), $encoding);
	}
}

/**
 * Makes a string's first character uppercase.
 * @param string $string				The input string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns a string with the first character capitalized, if that character is alphabetic.
 * This function is aimed at replacing the function ucfirst() for human-language strings.
 * @link http://php.net/manual/en/function.ucfirst
 */
function api_ucfirst($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
   	return api_strtoupper(api_substr($string, 0, 1, $encoding), $encoding) . api_substr($string, 1, api_strlen($string, $encoding), $encoding);
}

/**
 * Uppercases the first character of each word in a string.
 * @param string $string				The input string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the modified string.
 * This function is aimed at replacing the function ucwords() for human-language strings.
 * @link http://php.net/manual/en/function.ucwords
 */
function api_ucwords($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
    if (api_mb_supports($encoding)) {
		return @mb_convert_case($string, MB_CASE_TITLE, $encoding);
	}
	elseif (api_iconv_supports($encoding)) {
		return api_utf8_decode(@mb_convert_case(api_utf8_encode($string, $encoding), MB_CASE_TITLE, 'UTF-8'), $encoding);
	}
	return ucwords($string);
}

/**
 * This function adds a unicode modifier (u suffix) to a Perl-compatible regular expression depending on the specified encoding.
 * @param string $pcre					The Perl-compatible regular expression.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string						Returns the same regular expression wit a suffix 'u' if $encoding is 'UTF-8'.
 */
function api_add_pcre_unicode_modifier($pcre, $encoding = null) {
	if (empty($encoding)){
		$encoding = api_get_system_encoding();
	}
	return api_is_utf8($encoding) ? $pcre.'u' : $pcre;
}

/**
 * ----------------------------------------------------------------------------
 * Functions for string comparison
 * ----------------------------------------------------------------------------
 */

/**
 * Performs string comparison, case insensitive, language sensitive, replacement of strcmp().
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @link http://php.net/manual/en/function.strcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strcmp($string1, $string2, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		$collator = api_get_collator($language);
		if (is_object($collator)) {
			$result = collator_compare($collator, api_utf8_encode($string1, $encoding), api_utf8_encode($string2, $encoding));
			return $result === false ? 0 : $result;
		}
	}
	return strcmp($string1, $string2);
}

/**
 * Returns an instance of Collator class (ICU) created for a specified language.
 * @param string $language (optional)	The specified language. If it is omitted, then the interface language is assumed.
 * @return object						The corresponding instance of Collator class.
 * @link http://php.net/manual/en/collator.create.php
 */
function api_get_collator($language = null) {
	static $collator = array();
	if (INTL_INSTALLED) {
		if (!isset($collator[$language])) {
			$locale = api_get_locale_from_language($language);
			$collator[$language] = collator_create($locale);
		}
		return $collator[$language];
	}
	return null;
}

/**
 * ----------------------------------------------------------------------------
 * Encoding management functions
 * ----------------------------------------------------------------------------
 */

/**
 * Returns the most-probably used non-UTF-8 encoding for the given language.
 * @param string $language (optional)	The specified language, the default value is the user intrface language.
 * @return string						The correspondent encoding to the specified language.
 */
function api_get_non_utf8_encoding($language = null) {
	if (empty($language)) {
		$language = api_get_interface_language();
	}
	$language = api_refine_language_id($language);
	$encodings = & api_non_utf8_encodings();
	if (is_array($encodings[$language])) {
		if (!empty($encodings[$language][0])) {
			return $encodings[$language][0];
		} else {
			return 'ISO-8859-15';
		}
	} else {
		return 'ISO-8859-15';
	}
}

/**
 * Returns a table with non-UTF-8 encodings for all system languages.
 * @return array		Returns an array in the form array('language1' => array('encoding1', encoding2', ...), ...)
 * Note: The function api_get_non_utf8_encoding() returns the first encoding from this array that is correspondent to the given language. 
 */
function & api_non_utf8_encodings() {
	// The following list may have some inconsistencies.
	// Place the most used for your language encoding at the first place.
	// If you are adding an encoding, check whether it is supported either by
	// mbstring library, either by iconv library.
	// If you modify this list, please, follow the given syntax exactly.
	// The language names must be stripped of any suffixes, such as _unicode, _corporate, _org, etc.
	static $encodings =
'
arabic: WINDOWS-1256, ISO-8859-6;
asturian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
bosnian: WINDOWS-1250;
brazilian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
bulgarian: WINDOWS-1251;
catalan: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
croatian: WINDOWS-1250;
czech: WINDOWS-1250, ISO-8859-2;
danish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
dari: WINDOWS-1256;
dutch: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
english: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
euskera:  ISO-8859-15, WINDOWS-1252, ISO-8859-1;
esperanto: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
finnish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
french: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
friulian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
galician: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
georgian: GEORGIAN-ACADEMY, GEORGIAN-PS;
german: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
greek: WINDOWS-1253, ISO-8859-7;
hebrew: ISO-8859-8, WINDOWS-1255;
hungarian: WINDOWS-1250, ISO-8859-2;
indonesian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
italian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
japanese: EUC-JP, ISO-2022-JP, Shift-JIS;
korean: EUC-KR, ISO-2022-KR, CP949;
latvian: WINDOWS-1257, ISO-8859-13;
lithuanian: WINDOWS-1257, ISO-8859-13;
macedonian: WINDOWS-1251;
malay: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
norwegian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
occitan: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
pashto: WINDOWS-1256;
persian: WINDOWS-1256;
polish: WINDOWS-1250, ISO-8859-2;
portuguese: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
quechua_cusco: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
romanian: WINDOWS-1250, ISO-8859-2;
russian: KOI8-R, WINDOWS-1251;
serbian: ISO-8859-15, WINDOWS-1252, ISO-8859-1, WINDOWS-1251;
simpl_chinese: GB2312, WINDOWS-936;
slovak: WINDOWS-1250, ISO-8859-2;
slovenian: WINDOWS-1250, ISO-8859-2;
spanish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
swahili: ISO-8859-1;
swedish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
thai: WINDOWS-874, ISO-8859-11;
trad_chinese: BIG-5, EUC-TW;
turkce: WINDOWS-1254, ISO-8859-9;
ukrainian: KOI8-U;
vietnamese: WINDOWS-1258, VISCII, TCVN;
yoruba: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
';

	if (!is_array($encodings)) {
		$table = explode(';', str_replace(' ', '', $encodings));
		$encodings = array();
		foreach ($table as & $row) {
			$row = trim($row);
			if (!empty($row)) {
				$row = explode(':', $row);
				$encodings[$row[0]] = explode(',', strtoupper($row[1]));
			}
		}
	}
	return $encodings;
}

/**
 * This function unifies the encoding identificators, so they could be compared.
 * @param string $encoding	The specified encoding.
 * @return string			Returns the encoding identificator modified in suitable for comparison way.
 */
function api_refine_encoding_id($encoding) {
	return strtoupper($encoding);
}

/**
 * This function checks whether two $encoding are equal (same, equvalent).
 * @param string $encoding1		The first encoding
 * @param string $encoding2		The second encoding
 * @return bool					Returns TRUE if the encodings are equal, FALSE otherwise.
 */
function api_equal_encodings($encoding1, $encoding2) {
	// We have to deal with aliases. This function alone does not solve
	// the problem entirely. And there is no time for this kind of research.
	// At the momemnt, the quick proposition could be:
	return strcmp(api_refine_encoding_id($encoding1), api_refine_encoding_id($encoding2)) == 0 ? true : false;
}

/**
 * This function checks whether a given encoding is UTF-8.
 * @param string $encoding		The tested encoding.
 * @return bool					Returns TRUE if the given encoding id means UTF-8, otherwise returns false.
 */
function api_is_utf8($encoding) {
	static $result = array();
	if (!isset($result[$encoding])) {
		$result[$encoding] = api_equal_encodings($encoding, 'UTF-8');
	}
	return $result[$encoding];
}

/**
 * This function checks whether a given encoding represents (is an alias of) ISO Latin 1 character set.
 * @param string $encoding		The tested encoding.
 * @return bool					Returns TRUE if the given encoding id means Latin 1 character set, otherwise returns false.
 */
function api_is_latin1($encoding) {
	static $latin1_encodings = array('ISO-8859-15', 'ISO-8859-1', 'WINDOWS-1252', 'CP1252', 'ISO8859-15', 'ISO8859-1', 'WIN-1252', '1252');
	return in_array(api_refine_encoding_id($encoding), $latin1_encodings);
}

/**
 * This function returns the encoding, currently used by the system.
 * @return string	The system's encoding.
 * Note: The value of api_get_setting('platform_charset') is tried to be returned first,
 * on the second place the global variable $charset is tried to be returned. If for some
 * reason both attempts fail, 'ISO-8859-15' will be returned.
 */
function api_get_system_encoding() {
	$system_encoding = api_get_setting('platform_charset');
	if (!empty($system_encoding)) {
		return $system_encoding;
	}
	global $charset;
	return empty($charset) ? 'ISO-8859-15' : $charset;
}

/**
 * This function returns the encoding, currently used by the file system.
 * @return string	The file system's encoding, it depends on the locale that OS currently uses.
 * @link http://php.net/manual/en/function.setlocale.php
 * Note: For Linux systems, to see all installed locales type in a terminal  locale -a
 */
function api_get_file_system_encoding() {
	static $file_system_encoding;
	if (!isset($file_system_encoding)) {
		$locale = setlocale(LC_CTYPE, '0');
		$seek_pos = strpos($locale, '.');
		if ($seek_pos !== false) {
			$file_system_encoding = substr($locale, $seek_pos + 1);
			if (IS_WINDOWS_OS) {
				$file_system_encoding = 'CP'.$file_system_encoding;
			}
		}
		// Dealing with some aliases.
		$file_system_encoding = str_ireplace('utf8', 'UTF-8', $file_system_encoding);
		$file_system_encoding = preg_replace('/^CP65001$/', 'UTF-8', $file_system_encoding);
		$file_system_encoding = preg_replace('/^CP(125[0-9])$/', 'WINDOWS-\1', $file_system_encoding);
		$file_system_encoding = str_replace('WINDOWS-1252', 'ISO-8859-15', $file_system_encoding);
		if (empty($file_system_encoding)) {
			if (IS_WINDOWS_OS) {
				// Not expected for Windows, this assignment is here just in case.
				$file_system_encoding = api_get_system_encoding();
			} else {
				// For Ububntu and other UTF-8 enabled Linux systems this fits with the default settings.
				$file_system_encoding = 'UTF-8';
			}
		}
	}
	return $file_system_encoding;
}

/**
 * Sets all internal default encodings of the multi-byte functions to the given value.
 * @param string $encoding		The specified default encoding.
 * @return void
 * Note: This function should be called once the initialization. Please, avoid further manipulation of the internal default encodings.
 */
function api_set_default_encoding($encoding) {
	api_mb_internal_encoding($encoding);
	api_mb_regex_encoding($encoding);
	api_iconv_set_encoding('iconv_internal_encoding', $encoding);
}

/**
 * Sets/Gets internal character encoding of the common string functions within the PHP mbstring extension.
 * @param string $encoding (optional)	When this parameter is given, the function sets the internal encoding.
 * @return string						When $encoding parameter is not given, the function returns the internal encoding.
 * Note: This function is used in the global initialization script for setting the internal encoding to the platform's character set.
 * @link http://php.net/manual/en/function.mb-internal-encoding
 */
function api_mb_internal_encoding($encoding = null) {
	static $mb_internal_encoding = null;
	if (empty($encoding)) {
		if (is_null($mb_internal_encoding)) {
			$mb_internal_encoding = @mb_internal_encoding();
		}
		return $mb_internal_encoding;
	}
	$mb_internal_encoding = $encoding;
	if (api_mb_supports($encoding)) {
		return @mb_internal_encoding($encoding);
	}
	return false;
}

/**
 * Sets/Gets internal character encoding of the regular expression functions (ereg-like) within the PHP mbstring extension.
 * @param string $encoding (optional)	When this parameter is given, the function sets the internal encoding.
 * @return string						When $encoding parameter is not given, the function returns the internal encoding.
 * Note: This function is used in the global initialization script for setting the internal encoding to the platform's character set.
 * @link http://php.net/manual/en/function.mb-regex-encoding
 */
function api_mb_regex_encoding($encoding = null) {
	static $mb_regex_encoding = null;
	if (empty($encoding)) {
		if (is_null($mb_regex_encoding)) {
			$mb_regex_encoding = @mb_regex_encoding();
		}
		return $mb_regex_encoding;
	}
	$mb_regex_encoding = $encoding;
	if (api_mb_supports($encoding)) {
		return @mb_regex_encoding($encoding);
	}
	return false;
}

/**
 * Retrieves specified internal encoding configuration variable within the PHP iconv extension.
 * @param string $type	The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
 * @return mixed		The function returns the requested encoding or FALSE on error.
 * @link http://php.net/manual/en/function.iconv-get-encoding
 */
function api_iconv_get_encoding($type) {
	return api_iconv_set_encoding($type);
}

/**
 * Sets specified internal encoding configuration variables within the PHP iconv extension.
 * @param string $type					The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
 * @param string $encoding (optional)	The desired encoding to be set.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: This function is used in the global initialization script for setting these three internal encodings to the platform's character set.
 * @link http://php.net/manual/en/function.iconv-set-encoding
 */
// Sets current setting for character encoding conversion.
// The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
function api_iconv_set_encoding($type, $encoding = null) {
	static $iconv_internal_encoding = null;
	static $iconv_input_encoding = null;
	static $iconv_output_encoding = null;
	if (!ICONV_INSTALLED) {
		return false;
	}
	switch ($type) {
		case 'iconv_internal_encoding':
			if (empty($encoding)) {
				if (is_null($iconv_internal_encoding)) {
					$iconv_internal_encoding = @iconv_get_encoding($type);
				}
				return $iconv_internal_encoding;
			}
			if (api_iconv_supports($encoding)) {
				if(@iconv_set_encoding($type, $encoding)) {
					$iconv_internal_encoding = $encoding;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
			break;
		case 'iconv_input_encoding':
			if (empty($encoding)) {
				if (is_null($iconv_input_encoding)) {
					$iconv_input_encoding = @iconv_get_encoding($type);
				}
				return $iconv_input_encoding;
			}
			if (api_iconv_supports($encoding)) {
				if(@iconv_set_encoding($type, $encoding)) {
					$iconv_input_encoding = $encoding;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
			break;
		case 'iconv_output_encoding':
			if (empty($encoding)) {
				if (is_null($iconv_output_encoding)) {
					$iconv_output_encoding = @iconv_get_encoding($type);
				}
				return $iconv_output_encoding;
			}
			if (api_iconv_supports($encoding)) {
				if(@iconv_set_encoding($type, $encoding)) {
					$iconv_output_encoding = $encoding;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
			break;
		default:
			return false;
	}
}

/**
 * Checks whether a specified encoding is supported by this API.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_is_encoding_supported($encoding) {
	return api_mb_supports($encoding) || api_iconv_supports($encoding);
}

/**
 * Checks whether the specified encoding is supported by the PHP mbstring extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_mb_supports($encoding) {
	static $supported = array();
	$encoding = api_refine_encoding_id($encoding);
	if (!isset($supported[$encoding])) {
		$mb_encodings = mb_list_encodings();
		$mb_encodings = array_map('api_refine_encoding_id', $mb_encodings);
		$supported[$encoding] = in_array($encoding, $mb_encodings);
	}
	return $supported[$encoding] ? true : false;
}

/**
 * Checks whether the specified encoding is supported by the PHP iconv extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_iconv_supports($encoding) {
	static $supported = array();
	$encoding = api_refine_encoding_id($encoding);
	if (!isset($supported[$encoding])) {
		if (ICONV_INSTALLED) {
			$test_string = '';
			for ($i = 32; $i < 128; $i++) {
				$test_string .= chr($i);
			}
			$supported[$encoding] = (@iconv_strlen($test_string, $encoding)) ? true : false;
		} else {
			$supported[$encoding] = false;
		}
	}

	return $supported[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the html-entitiy related functions.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_html_entity_supports($encoding) {
	static $supported = array();
	$encoding = api_refine_encoding_id($encoding);
	if (!isset($supported[$encoding])) {
		// See http://php.net/manual/en/function.htmlentities.php
		$html_entity_encodings = array(explode(',',
'
ISO-8859-1, ISO8859-1,
ISO-8859-15, ISO8859-15,
UTF-8,
cp866, ibm866, 866,
cp1251, Windows-1251, win-1251, 1251,
cp1252, Windows-1252, 1252,
KOI8-R, koi8-ru, koi8r,
BIG5, 950,
GB2312, 936,
BIG5-HKSCS,
Shift_JIS, SJIS, 932,
EUC-JP, EUCJP
'));
		$html_entity_encodings = array_map('trim', $html_entity_encodings);
		$html_entity_encodings = array_map('api_refine_encoding_id', $html_entity_encodings);
		$supported[$encoding] = in_array($encoding, $html_entity_encodings);
	}
	return $supported[$encoding] ? true : false;
}

/**
 * ----------------------------------------------------------------------------
 * String validation functions concerning some encodings
 * ----------------------------------------------------------------------------
 */

/**
 * Checks a string for UTF-8 validity.
 * @param string $string	The string to be tested/validated.
 * @return bool				Returns TRUE when the tested string is valid UTF-8 one, FALSE othewise.
 * @link http://en.wikipedia.org/wiki/UTF-8
 */
function api_is_valid_utf8($string) {

	//return @mb_detect_encoding($string, 'UTF-8', true) == 'UTF-8' ? true : false;
	// Ivan Tcholakov, 05-OCT-2008: I do not trust mb_detect_encoding(). I have
	// found a string with a single cyrillic letter (single byte), that is
	// wrongly detected as UTF-8. Possibly, there would be problems with other
	// languages too. An alternative implementation will be used.

	//$len = strlen($string); // mbstring.func_overload might change behaviour of the function strlen().
	// Ivan Tcholakov, 11-JUN-2009: Let us calculate for sure the length in bytes.
	static $use_mb_strlen;
	if (!isset($use_mb_strlen)) {
		$use_mb_strlen = MBSTRING_INSTALLED && ((int) ini_get('mbstring.func_overload') & 2);
	}
	if ($use_mb_strlen) {
		$len = mb_strlen($string, '8bit');
	} else {
		$len = strlen($string);
	}
	// For PHP6 all this fragment probably will contain:
	//$len = strlen((binary)$string);

	$i = 0;

	while ($i < $len) {
		$byte1 = ord($string[$i++]);		// Here the current character begins. Its size is
											// determined by the senior bits in the first byte.

		if (($byte1 & 0x80) == 0x00) {		// 0xxxxxxx
											//    &
											// 10000000
											// --------
											// 00000000
											// This is s valid character and it contains a single byte.
		}

		elseif (($byte1 & 0xE0) == 0xC0) {	// 110xxxxx 10xxxxxx
											//    &        &
											// 11100000 11000000
											// -------- --------
											// 11000000 10000000
											// The character contains two bytes.
			if ($i == $len)
				return false;				// Here the string ends unexpectedly.

			if (!((ord($string[$i++]) & 0xC0) == 0x80))
				return false;				// Invalid second byte, invalid string.
		}

		elseif(($byte1 & 0xF0) == 0xE0) {	// 1110xxxx 10xxxxxx 10xxxxxx
											//    &        &        &
											// 11110000 11000000 11000000
											// -------- -------- --------
											// 11100000 10000000 10000000
											// This is a character of three bytes.
			if ($i == $len) {
				return false;				// Unexpected end of the string.
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;				// Invalid second byte.
			}

			if ($i == $len) {
				return false;				// Unexpected end of the string.
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;				// Invalid third byte, invalid string.
			}
		}

		elseif(($byte1 & 0xF8) == 0xF0) {	// 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
											//    &        &        &        &
											// 11111000 11000000 11000000 11000000
											// -------- -------- -------- --------
											// 11110000 10000000 10000000 10000000
											// This is a character of four bytes.
			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}
		}

		elseif(($byte1 & 0xFC) == 0xF8) {	// 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
											//    &        &        &        &        &
											// 11111100 11000000 11000000 11000000 11000000
											// -------- -------- -------- -------- --------
											// 11111000 10000000 10000000 10000000 10000000
											// This is a character of five bytes.
			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}
		}

		elseif(($byte1 & 0xFE) == 0xFC) {	// 1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
											//    &        &        &        &        &        &
											// 11111110 11000000 11000000 11000000 11000000 11000000
											// -------- -------- -------- -------- -------- --------
											// 11111100 10000000 10000000 10000000 10000000 10000000
											// This is a character of six bytes.
			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}

			if ($i == $len) {
				return false;
			}

			if (!((ord($string[$i++]) & 0xC0) == 0x80)) {
				return false;
			}
		}

		else {
			return false;					// In any other case the character is invalid.
		}
											// Here the current character is valid, it
											// matches to some of the cases above.
				 							// The next character is to be examinated.
	}

	return true;							// Empty strings are valid too.
}

/**
 * Checks whether a string contains 7-bit ASCII characters only.
 * @param string $string	The string to be tested/validated.
 * @return bool				Returns TRUE when the tested string contains 7-bit ASCII characters only, FALSE othewise.
 */
function api_is_valid_ascii($string) {
	return @mb_detect_encoding($string, 'ASCII', true) == 'ASCII' ? true : false;
}

/**
 * ----------------------------------------------------------------------------
 * Language management functions
 * ----------------------------------------------------------------------------
 */

/**
 * Returns a purified language id, without possible suffixes that will disturb language identification in certain cases.
 * @param string $language	The input language identificator, for example 'french_unicode'.
 * @param string			The same purified or filtered language identificator, for example 'french'.
 */
function api_refine_language_id($language) {
	static $search = array('_unicode', '_latin', '_corporate', '_org', '_KM');
	return strtolower(str_replace($search, '', $language));
}

/**
 * This function check whether a given language can use Latin 1 encoding.
 * @param string $language	The checked language.
 * @return bool				TRUE if the given language can use Latin 1 encoding (ISO-8859-15, ISO-8859-1, WINDOWS-1252, ...), FALSE otherwise.
 */
function api_is_latin1_compatible($language) {
	static $latin1_languages;
	if (!isset($latin1_languages)) {
		$latin1_languages = api_get_latin1_compatible_languages();
	}
	$language = api_refine_language_id($language);
	return in_array($language, $latin1_languages);
}

/**
 * This function returns an array of those languages that can use Latin 1 encoding.
 * @return array	The array of languages that can use Latin 1 encoding (ISO-8859-15, ISO-8859-1, WINDOWS-1252, ...).
 * Note: The returned language identificators are purified, without suffixes.
 */
function api_get_latin1_compatible_languages() {
	static $latin1_languages;
	if (!isset($latin1_languages)) {
		$latin1_languages = array();
		$encodings = & api_non_utf8_encodings();
		foreach ($encodings as $key => $value) {
			if (api_is_latin1($value[0])) {
				$latin1_languages[] = $key;
			}
		}
	}
	return $latin1_languages;
}

/**
 * ----------------------------------------------------------------------------
 * ICU locales (accessible through intl extension).
 * ----------------------------------------------------------------------------
 */

/**
 * Returns isocode (see api_get_language_isocode()) which is purified accordingly to
 * be used by the php intl extension (ICU library).
 * @param string $language (optional)	This is the name of the folder containing translations for the corresponding language.
 * If $language is omitted, interface language is assumed then.
 * @return string						The found language locale id or null on error. Examples: bg, en, pt_BR, ...
 */
function api_get_locale_from_language($language = null) {
	static $locale = array();
	if (!isset($locale[$language])) {
		$locale[$language] = Database::get_language_isocode($language);
		if (!is_null($locale[$language])) {
			$locale[$language] = str_replace('-', '_', $locale[$language]);
		}
	}
	return $locale[$language];
}

/**
 * Sets/gets the default internal value of the locale id (for the intl extension, ICU).
 * @param string $locale (optional)	The locale id to be set. When it is omitted, the function returns (gets, reads) the default internal value.
 * @return mixed						When the function sets the default value, it returns TRUE on success or FALSE on error. Otherwise the function returns as string the current default value.
 */
function api_set_default_locale($locale = null) {
	static $default_locale = 'en';
	if (!empty($language)) {
		$default_locale = $locale;
		if (INTL_INSTALLED) {
			return @locale_set_default($locale);
		}
		return true;
	} else {
		if (INTL_INSTALLED) {
			$default_locale = @locale_get_default();
		}
	}
	return $default_locale;
}

/**
 * Gets the default internal value of the locale id (for the intl extension, ICU).
 * @return string		Returns as string the current default value.
 */
function api_get_default_locale() {
	return api_set_default_locale();
}

/**
 * ----------------------------------------------------------------------------
 * Array functions
 * ----------------------------------------------------------------------------
 */

/**
 * Checks if a value exists in an array, a case insensitive version of in_array() function with extended multibyte support.
 * @param mixed $needle					The searched value. If needle is a string, the comparison is done in a case-insensitive manner.
 * @param array $haystack				The array.
 * @param bool $strict (optional)		If is set to TRUE then the function will also check the types of the $needle in the $haystack. The default value if FALSE.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE if $needle is found in the array, FALSE otherwise.
 * @link http://php.net/manual/en/function.in-array.php
 */
function api_in_array_nocase($needle, $haystack, $strict = false, $encoding = null) {
	if (is_array($needle)) {
		foreach ($needle as $item) {
			if (api_in_array_nocase($item, $haystack, $strict, $encoding)) return true;
		}
		return false;
	}
	if (!is_string($needle)) {
		return in_array($needle, $haystack, $strict);
	}
	$needle = api_strtolower($needle, $encoding);
	foreach ($haystack as $item) {
		if ($strict && !is_string($item)) {
			continue;
		}
		if (api_strtolower($item, $encoding) == $needle) {
			return true;
		}
	}
	return false;
}


//----------------------------------------------------------------------------
// Transliteration, converting ANSI and UTF-8 strings to ASCII strings
//----------------------------------------------------------------------------


/**
 * Transliterates a string to a plain ASCII string.
 *
 * Example:
 * echo api_transliterate(api_html_entity_decode(
 * 	'&#1060;&#1105;&#1076;&#1086;&#1088; '.
 * 	'&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
 * 	'&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
 * 	ENT_QUOTES, 'UTF-8'), 'UTF-8');
 * The output should be: Fyodor Mihaylovich Dostoevkiy
 *
 * @param string $string					The input string.
 * @param string $unknown (optional)		Replacement character for unknown characters and illegal UTF-8 sequences.
 * @param string $from_encoding (optional)	The encoding of the input string. If it is omited, the platform character set is assumed.
 * @return string							Plain ASCII output.
 *
 * Based on Drupal's module "Transliteration", version 6.x-2.1, 09-JUN-2009:
 * @author Stefan M. Kudwien (smk-ka)
 * @author Daniel F. Kudwien (sun)
 * @link http://drupal.org/project/transliteration
 *
 * See also MediaWiki's UtfNormal.php and CPAN's Text::Unidecode library
 * @link http://www.mediawiki.org
 * @link http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm).
 *
 * Adaptation for the Dokeos 1.8.6.1, 12-JUN-2009:
 * @author Ivan Tcholakov
 */
function api_transliterate($string, $unknown = '?', $from_encoding = null) {
	static $map = array();

	$string = api_utf8_encode($string, $from_encoding);

	// Screen out some characters that eg won't be allowed in XML.
	$string = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $unknown, $string);

	// ASCII is always valid NFC!
	// If we're only ever given plain ASCII, we can avoid the overhead
	// of initializing the decomposition tables by skipping out early.
	if (!preg_match('/[\x80-\xff]/', $string)) {
		return $string;
	}

	static $tail_bytes;

	if (!isset($tail_bytes)) {
		// Each UTF-8 head byte is followed by a certain
		// number of tail bytes.
		$tail_bytes = array();
		for ($n = 0; $n < 256; $n++) {
			if ($n < 0xc0) {
				$remaining = 0;
			}
			elseif ($n < 0xe0) {
				$remaining = 1;
			}
			elseif ($n < 0xf0) {
				$remaining = 2;
			}
			elseif ($n < 0xf8) {
				$remaining = 3;
			}
			elseif ($n < 0xfc) {
				$remaining = 4;
			}
			elseif ($n < 0xfe) {
				$remaining = 5;
			} else {
				$remaining = 0;
			}
			$tail_bytes[chr($n)] = $remaining;
		}
	}

	// Chop the text into pure-ASCII and non-ASCII areas;
	// large ASCII parts can be handled much more quickly.
	// Don't chop up Unicode areas for punctuation, though,
	// that wastes energy.
	preg_match_all('/[\x00-\x7f]+|[\x80-\xff][\x00-\x40\x5b-\x5f\x7b-\xff]*/', $string, $matches);

	$result = '';
	foreach ($matches[0] as $str) {
		if ($str{0} < "\x80") {
			// ASCII chunk: guaranteed to be valid UTF-8
			// and in normal form C, so skip over it.
			$result .= $str;
			continue;
		}

		// We'll have to examine the chunk byte by byte to ensure
		// that it consists of valid UTF-8 sequences, and to see
		// if any of them might not be normalized.
		//
		// Since PHP is not the fastest language on earth, some of
		// this code is a little ugly with inner loop optimizations.

		$head = '';
		$chunk = api_byte_count($str);
		// Counting down is faster. I'm *so* sorry.
		$len = $chunk + 1;

		for ($i = -1; --$len; ) {
			$c = $str{++$i};
			if ($remaining = $tail_bytes[$c]) {
				// UTF-8 head byte!
				$sequence = $head = $c;
				do {
					// Look for the defined number of tail bytes...
					if (--$len && ($c = $str{++$i}) >= "\x80" && $c < "\xc0") {
					// Legal tail bytes are nice.
					$sequence .= $c;
					} else {
						if ($len == 0) {
							// Premature end of string!
							// Drop a replacement character into output to
							// represent the invalid UTF-8 sequence.
							$result .= $unknown;
							break 2;
						} else {
							// Illegal tail byte; abandon the sequence.
							$result .= $unknown;
							// Back up and reprocess this byte; it may itself
							// be a legal ASCII or UTF-8 sequence head.
							--$i;
							++$len;
							continue 2;
						}
					}
				} while (--$remaining);

				$n = ord($head);
				if ($n <= 0xdf) {
					$ord = ($n - 192) * 64 + (ord($sequence{1}) - 128);
				}
				else if ($n <= 0xef) {
					$ord = ($n - 224) * 4096 + (ord($sequence{1}) - 128) * 64 + (ord($sequence{2}) - 128);
				}
				else if ($n <= 0xf7) {
					$ord = ($n - 240) * 262144 + (ord($sequence{1}) - 128) * 4096 + (ord($sequence{2}) - 128) * 64 + (ord($sequence{3}) - 128);
				}
				else if ($n <= 0xfb) {
					$ord = ($n - 248) * 16777216 + (ord($sequence{1}) - 128) * 262144 + (ord($sequence{2}) - 128) * 4096 + (ord($sequence{3}) - 128) * 64 + (ord($sequence{4}) - 128);
				}
				else if ($n <= 0xfd) {
					$ord = ($n - 252) * 1073741824 + (ord($sequence{1}) - 128) * 16777216 + (ord($sequence{2}) - 128) * 262144 + (ord($sequence{3}) - 128) * 4096 + (ord($sequence{4}) - 128) * 64 + (ord($sequence{5}) - 128);
				}

				// Lookup and replace a character from the transliteration database.
				$bank = $ord >> 8;
				// Check if we need to load a new bank
				if (!isset($map[$bank])) {
					if (api_get_path(LIBRARY_PATH) == '/lib/') {
						// Include the bank when we are running the installer script.
						$file = 'transliteration_database/' . sprintf('x%02x', $bank) . '.php';
					} else {
						// Include the bank when the system has been already installed, this is the usual way.
						$file = api_get_path(LIBRARY_PATH).'transliteration_database/' . sprintf('x%02x', $bank) . '.php';
					}
					if (file_exists($file)) {
						$map[$bank] = include ($file);
					} else {
						$map[$bank] = array('en' => array());
					}
				}
				$ord = $ord & 255;
				$result .= isset($map[$bank]['en'][$ord]) ? $map[$bank]['en'][$ord] : $unknown;
				
				$head = '';
			}
			elseif ($c < "\x80") {
				// ASCII byte.
				$result .= $c;
				$head = '';
			}
			elseif ($c < "\xc0") {
				// Illegal tail bytes.
				if ($head == '') {
					$result .= $unknown;
				}
			} else {
				// Miscellaneous freaks.
				$result .= $unknown;
				$head = '';
			}
		}
	}
	return $result;
}


//----------------------------------------------------------------------------
// Multibyte string functions designed to upgrade the PHP5 mbstring extension
//----------------------------------------------------------------------------


// ---------- Multibyte string functions implemented in PHP 5.2.0+ -----------

// This is a multibyte replacement of strchr().
// This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strrchr
if (MBSTRING_INSTALLED && !function_exists('mb_strchr')) {
	function mb_strchr($haystack, $needle, $part = false, $encoding = null) {
		if (empty($encoding)) {
			$encoding = mb_internal_encoding();
		}
		return mb_strstr($haystack, $needle, $part, $encoding);
	}
}

// This is a multibyte replacement of stripos().
// This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-stripos
if (MBSTRING_INSTALLED && !function_exists('mb_stripos')) {
	function mb_stripos($haystack, $needle, $offset = 0, $encoding = null) {
		if (empty($encoding)) {
			$encoding = mb_internal_encoding();
		}
		return mb_strpos(mb_strtolower($haystack, $encoding), mb_strtolower($needle, $encoding), $offset, $encoding);
	}
}

// This is a multibyte replacement of stristr().
// This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-stristr
if (MBSTRING_INSTALLED && !function_exists('mb_stristr')) {
	function mb_stristr($haystack, $needle, $part = false, $encoding = null) {
		if (empty($encoding)) {
			$encoding = mb_internal_encoding();
		}
		$pos = mb_strpos(mb_strtolower($haystack, $encoding), mb_strtolower($needle, $encoding), 0, $encoding);
		if ($pos === false) {
			return false;
		}
		elseif($part == true) {
			return mb_substr($haystack, 0, $pos + 1, $encoding);
		} else {
			return mb_substr($haystack, $pos, mb_strlen($haystack, $encoding), $encoding);
		}
	}
}

// This is a multibyte replacement of strrchr().
// This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strrchr
if (MBSTRING_INSTALLED && !function_exists('mb_strrchr')) {
	function mb_strrchr($haystack, $needle, $part = false, $encoding = null) {
		if (empty($encoding)) {
			$encoding = mb_internal_encoding();
		}
		$needle = mb_substr($needle, 0, 1, $encoding);
		$pos = mb_strrpos($haystack, $needle, mb_strlen($haystack, $encoding) - 1, $encoding);
		if ($pos === false) {
			return false;
		} elseif($part == true) {
			return mb_substr($haystack, 0, $pos + 1, $encoding);
		} else {
			return mb_substr($haystack, $pos, mb_strlen($haystack, $encoding), $encoding);
		}
	}
}

// This is a multibyte replacement of strstr().
// This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strstr
if (MBSTRING_INSTALLED && !function_exists('mb_strstr')) {
	function mb_strstr($haystack, $needle, $part = false, $encoding = null) {
		if (empty($encoding)) {
			$encoding = mb_internal_encoding();
		}
		$pos = mb_strpos($haystack, $needle, 0, $encoding);
		if ($pos === false) {
			return false;
		} elseif($part == true) {
			return mb_substr($haystack, 0, $pos + 1, $encoding);
		} else {
			return mb_substr($haystack, $pos, mb_strlen($haystack, $encoding), $encoding);
		}
	}
}

?>
