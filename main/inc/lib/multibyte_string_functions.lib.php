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
 * August 2009 - PCRE-related functions have been added,
 *               dependancy on mbstring extension has been removed.
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
 *
 * 5. For improved sorting of multibyte strings the library uses the intl
 * php-extension if it is installed.
 */


/**
 * ----------------------------------------------------------------------------
 * Initialization
 * ----------------------------------------------------------------------------
 */

/**
 * Initialization of some internal default valies of the multibyte string library.
 * @return void
 * Note: This function should be called once in the initialization script.
 */
function api_initialize_string_library() {
	if (MBSTRING_INSTALLED) {
		@ini_set('mbstring.func_overload', 0);
		@ini_set('mbstring.encoding_translation', 0);
		@ini_set('mbstring.http_input', 'pass');
		@ini_set('mbstring.http_output', 'pass');
		@ini_set('mbstring.language', 'neutral');
	}
	api_set_string_library_default_encoding('ISO-8859-15');
}

/**
 * Sets the internal default encoding for the multi-byte string functions.
 * @param string $encoding		The specified default encoding.
 * @return string				Returns the old value of the default encoding.
 */
function api_set_string_library_default_encoding($encoding) {
	$encoding = api_refine_encoding_id($encoding);
	$result = _api_mb_internal_encoding();
	_api_mb_internal_encoding($encoding);
	_api_mb_regex_encoding($encoding);
	_api_iconv_set_encoding('iconv_internal_encoding', $encoding);
	return $result;
}


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
		$from_encoding = _api_mb_internal_encoding();
	}
	if (api_equal_encodings($to_encoding, $from_encoding)) {
		return $string; // When conversion is not needed, the string is returned directly, without validation.
	}
	if (_api_mb_supports($to_encoding) && _api_mb_supports($from_encoding)) {
		return @mb_convert_encoding($string, $to_encoding, $from_encoding);
	}
	if (_api_iconv_supports($to_encoding) && _api_iconv_supports($from_encoding)) {
		return @iconv($from_encoding, $to_encoding, $string);
	}
	if (api_is_utf8($to_encoding) && api_is_latin1($from_encoding, true)) {
		return utf8_encode($string);
	}
	if (api_is_latin1($to_encoding, true) && api_is_utf8($from_encoding)) {
		return utf8_decode($string);
	}
	if (_api_convert_encoding_supports($to_encoding) && _api_convert_encoding_supports($from_encoding)) {
		return _api_convert_encoding($string, $to_encoding, $from_encoding);
	}
	return $string; // Here the function gives up.
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
		$from_encoding = _api_mb_internal_encoding();
	}
	if (api_is_utf8($from_encoding)) {
		return $string; // When conversion is not needed, the string is returned directly, without validation.
	}
	if (_api_mb_supports($from_encoding)) {
		return @mb_convert_encoding($string, 'UTF-8', $from_encoding);
	}
	if (_api_iconv_supports($from_encoding)) {
		return @iconv($from_encoding, 'UTF-8', $string);
	}
	if (api_is_latin1($from_encoding, true)) {
		return utf8_encode($string);
	}
	if (_api_convert_encoding_supports($from_encoding)) {
		return _api_convert_encoding($string, 'UTF-8', $from_encoding);
	}
	return $string; // Here the function gives up.
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
		$to_encoding = _api_mb_internal_encoding();
	}
	if (api_is_utf8($to_encoding)) {
		return $string; // When conversion is not needed, the string is returned directly, without validation.
	}
	if (_api_mb_supports($to_encoding)) {
		return @mb_convert_encoding($string, $to_encoding, 'UTF-8');
	}
	if (_api_iconv_supports($to_encoding)) {
		return @iconv('UTF-8', $to_encoding, $string);
	}
	if (api_is_latin1($to_encoding, true)) {
		return utf8_decode($string);
	}
	if (_api_convert_encoding_supports($to_encoding)) {
		return _api_convert_encoding($string, $to_encoding, 'UTF-8');
	}
	return $string; // Here the function gives up.
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
	$system_encoding = api_get_system_encoding();
	if (empty($from_encoding)) {
		if (api_is_utf8($system_encoding)) {
			$from_encoding = api_get_non_utf8_encoding();
		} else {
			$from_encoding = 'UTF-8';
		}
	}
	if (api_equal_encodings($system_encoding, $from_encoding)) {
		return $string;
	}
	if ($check_utf8_validity) {
		if (api_is_utf8($system_encoding)) {
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
	return api_convert_encoding($string, $system_encoding, $from_encoding);
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
		$encoding = _api_mb_internal_encoding();
	}
	if (!api_is_utf8($encoding) && _api_html_entity_supports($encoding)) {
		return htmlentities($string, $quote_style, $encoding);
	}
	if (_api_mb_supports($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		$string = @mb_convert_encoding(api_utf8_encode($string, $encoding), 'HTML-ENTITIES', 'UTF-8');
		if (!api_is_utf8($encoding)) { // Just in case.
			$string = api_utf8_decode($string, $encoding);
		}
	}
	elseif (_api_convert_encoding_supports($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = _api_convert_encoding($string, 'UTF-8', $encoding);
		}
		$string = implode(array_map('_api_html_entity_from_unicode', _api_utf8_to_unicode($string)));
		if (!api_is_utf8($encoding)) { // Just in case.
			$string = _api_convert_encoding($string, $encoding, 'UTF-8');
		}
	}
	else {
		// Here the function gives up.
		return $string;
	}
	switch($quote_style) {
		case ENT_COMPAT:
			$string = str_replace('"', '&quot;', $string);
			break;
		case ENT_QUOTES:
			$string = str_replace(array('\'', '"'), array('&#039;', '&quot;'), $string);
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
		$encoding = _api_mb_internal_encoding();
	}
	if (_api_html_entity_supports($encoding)) {
		return html_entity_decode($string, $quote_style, $encoding);
	}
	if (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		$string = html_entity_decode($string, $quote_style, 'UTF-8');
		if (!api_is_utf8($encoding)) {
			return api_utf8_decode($string, $encoding);
		}
		return $string;
	}
	return $string; // Here the function guves up.
}

/**
 * This function encodes (conditionally) a given string to UTF-8 if XmlHttp-request has been detected.
 * @param string $string					The string being converted.
 * @param string $from_encoding (optional)	The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string							Returns the converted string.
 */
function api_xml_http_response_encode($string, $from_encoding = null) {
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		if (empty($from_encoding)) {
			$from_encoding = _api_mb_internal_encoding();
		}
		if (!api_is_utf8($from_encoding)) {
			return api_utf8_encode($string, $from_encoding);
		}
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
		$from_encoding = _api_mb_internal_encoding();
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
		$to_encoding = _api_mb_internal_encoding();
	}
	return api_convert_encoding($string, $to_encoding, api_get_file_system_encoding());
}

/**
 * Transliterates a string with arbitrary encoding into a plain ASCII string.
 *
 * Example:
 * echo api_transliterate(api_html_entity_decode(
 * 	'&#1060;&#1105;&#1076;&#1086;&#1088; '.
 * 	'&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
 * 	'&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
 * 	ENT_QUOTES, 'UTF-8'), 'X', 'UTF-8');
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
	if (api_is_valid_ascii($string)) {
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
					$file = dirname(__FILE__) . '/internationalization_database/transliteration/' . sprintf('x%02x', $bank) . '.php';
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


/**
 * ----------------------------------------------------------------------------
 * Common multibyte string functions
 * ----------------------------------------------------------------------------
 */

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
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, August 2009, adaptation for the Dokeos LMS.
 */
function api_str_ireplace($search, $replace, $subject, & $count = null, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (api_is_encoding_supported($encoding)) {
		if (!is_array($search) && !is_array($replace)) {
			if (!api_is_utf8($encoding)) {
				$search = api_utf8_encode($search, $encoding);
			}
			$slen = api_byte_count($search);
			if ( $slen == 0 ) {
				return $subject;
			}
			if (!api_is_utf8($encoding)) {
				$replace = api_utf8_encode($replace, $encoding);
				$subject = api_utf8_encode($subject, $encoding);
			}
			$lendif = api_byte_count($replace) - api_byte_count($search);
			$search = api_strtolower($search, 'UTF-8');
			$search = preg_quote($search);
			$lstr = api_strtolower($subject, 'UTF-8');
			$i = 0;
			$matched = 0;
			while (preg_match('/(.*)'.$search.'/Us', $lstr, $matches) ) {
				if ($i === $count) {
					break;
				}
				$mlen = api_byte_count($matches[0]);
				$lstr = substr($lstr, $mlen);
				$subject = substr_replace($subject, $replace, $matched + api_byte_count($matches[1]), $slen);
				$matched += $mlen + $lendif;
				$i++;
			}
			if (!api_is_utf8($encoding)) {
				$subject = api_utf8_decode($subject, $encoding);
			}
			return $subject;
		} else {
			foreach (array_keys($search) as $k) {
				if (is_array($replace)) {
					if (array_key_exists($k, $replace)) {
						$subject = api_str_ireplace($search[$k], $replace[$k], $subject, $count);
					} else {
						$subject = api_str_ireplace($search[$k], '', $subject, $count);
					}
				} else {
					$subject = api_str_ireplace($search[$k], $replace, $subject, $count);
				}
			}
			return $subject;
		}
	}
	if (is_null($count)) {
		return str_ireplace($search, $replace, $subject);
	}
	return str_ireplace($search, $replace, $subject, $count);
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
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (empty($string)) {
		return array();
	}
	if ($split_length < 1) {
		return false;
	}
	if (_api_is_single_byte_encoding($encoding)) {
		return str_split($string, $split_length);
	}
	if (api_is_encoding_supported($encoding)) {
		$len = api_strlen($string);
		if ($len <= $split_length) {
			return array($string);
		}
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		if (preg_match_all('/.{'.$split_length.'}|[^\x00]{1,'.$split_length.'}$/us', $string, $result) === false) {
			return array();
		}
		if (!api_is_utf8($encoding)) {
			global $_api_encoding;
			$_api_encoding = $encoding;
			$result = _api_array_utf8_decode($result[0]);
		}
		return $result[0];
	}
	return str_split($string, $split_length);
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
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (!is_string($needle)) {
		$needle = (int)$needle;
		if (api_is_utf8($encoding)) {
			$needle = _api_utf8_chr($needle);
		} else {
			$needle = chr($needle);
		}
	}
	if ($needle == '') {
		return false;
	}
	if (_api_mb_supports($encoding)) {
		return @mb_stripos($haystack, $needle, $offset, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (MBSTRING_INSTALLED) {
			if (!api_is_utf8($encoding)) {
				$haystack = api_utf8_encode($haystack, $encoding);
				$needle = api_utf8_encode($needle, $encoding);
			}
			return @mb_stripos($haystack, $needle, $offset, 'UTF-8');
		}
		return api_strpos(api_strtolower($haystack, $encoding), api_strtolower($needle, $encoding), $offset, $encoding);
	}
	return stripos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another, case insensitive.
 * @param string $haystack					The string from which to get the first occurrence.
 * @param mixed $needle						The string to be found.
 * @param bool $before_needle (optional)	Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)		The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed							Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions stristr() and mb_stristr() for human-language strings.
 * @link http://php.net/manual/en/function.stristr
 * @link http://php.net/manual/en/function.mb-stristr
 */
function api_stristr($haystack, $needle, $before_needle = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (!is_string($needle)) {
		$needle = (int)$needle;
		if (api_is_utf8($encoding)) {
			$needle = _api_utf8_chr($needle);
		} else {
			$needle = chr($needle);
		}
	}
	if ($needle == '') {
		return false;
	}
	if (_api_mb_supports($encoding)) {
		return @mb_stristr($haystack, $needle, $before_needle, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (MBSTRING_INSTALLED) {
			if (!api_is_utf8($encoding)) {
				$haystack = api_utf8_encode($haystack, $encoding);
				$needle = api_utf8_encode($needle, $encoding);
			}
			$result = @mb_stristr($haystack, $needle, $before_needle, 'UTF-8');
			if ($result === false) {
				return false;
			}
			if (!api_is_utf8($encoding)) {
				return api_utf8_decode($result, $encoding);
			}
			return $result;
		}
		$result = api_strstr(api_strtolower($haystack, $encoding), api_strtolower($needle, $encoding), $before_needle, $encoding);
		if ($result === false) {
			return false;
		}
		if ($before_needle) {
			return api_substr($haystack, 0, api_strlen($result, $encoding), $encoding);
		}
		return api_substr($haystack, api_strlen($haystack, $encoding) - api_strlen($result, $encoding), null, $encoding);
	}
	if (!IS_PHP_53) {
		return stristr($haystack, $needle);
	}
	return stristr($haystack, $needle, $before_needle);
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
		$encoding = _api_mb_internal_encoding();
	}
	if (_api_is_single_byte_encoding($encoding)) {
		return strlen($string);
	}
	if (_api_mb_supports($encoding)) {
		return @mb_strlen($string, $encoding);
	}
	if (_api_iconv_supports($encoding)) {
		return @iconv_strlen($string, $encoding);
	}
	if (api_is_utf8($encoding)) {
    	return api_byte_count(preg_replace("/[\x80-\xBF]/", '', $string));
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
		$encoding = _api_mb_internal_encoding();
	}
	if (!is_string($needle)) {
		$needle = (int)$needle;
		if (api_is_utf8($encoding)) {
			$needle = _api_utf8_chr($needle);
		} else {
			$needle = chr($needle);
		}
	}
	if ($needle == '') {
		return false;
	}
	if (_api_is_single_byte_encoding($encoding)) {
		return strpos($haystack, $needle, $offset);
	}
	elseif (_api_mb_supports($encoding)) {
		return @mb_strpos($haystack, $needle, $offset, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$haystack = api_utf8_encode($haystack, $encoding);
			$needle = api_utf8_encode($needle, $encoding);
		}
		if (MBSTRING_INSTALLED) {
			return @mb_strpos($haystack, $needle, $offset, 'UTF-8');
		}
		if (empty($offset)) {
			$haystack = explode($needle, $haystack, 2);
			if (count($haystack) > 1) {
				return api_strlen($haystack[0]);
			}
			return false;
		}
		$haystack = api_substr($haystack, $offset);
		if (($pos = api_strpos($haystack, $needle)) !== false ) {
			return $pos + $offset;
		}
		return false;
	}
	return strpos($haystack, $needle, $offset);
}

/**
 * Finds the last occurrence of a character in a string.
 * @param string $haystack					The string from which to get the last occurrence.
 * @param mixed $needle						The string which first character is to be found.
 * @param bool $before_needle (optional)	Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)		The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed							Returns the portion of $haystack, or FALSE if the first character from $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence to the end.
 * This function is aimed at replacing the functions strrchr() and mb_strrchr() for human-language strings.
 * @link http://php.net/manual/en/function.strrchr
 * @link http://php.net/manual/en/function.mb-strrchr
 */
function api_strrchr($haystack, $needle, $before_needle = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (!is_string($needle)) {
		$needle = (int)$needle;
		if (api_is_utf8($encoding)) {
			$needle = _api_utf8_chr($needle);
		} else {
			$needle = chr($needle);
		}
	}
	if ($needle == '') {
		return false;
	}
	if (_api_is_single_byte_encoding($encoding)) {
		if (!$before_needle) {
			return strrchr($haystack, $needle);
		}
		$result = strrchr($haystack, $needle);
		if ($result === false) {
			return false;
		}
		return api_substr($haystack, 0, api_strlen($haystack, $encoding) - api_strlen($result, $encoding), $encoding);
	}
	elseif (_api_mb_supports($encoding)) {
		return @mb_strrchr($haystack, $needle, $before_needle, $encoding);
	}
	elseif (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$haystack = api_utf8_encode($haystack, $encoding);
			$needle = api_utf8_encode($needle, $encoding);
		}
		$result = @mb_strrchr($haystack, $needle, $before_needle, 'UTF-8');
		if ($result === false) {
			return false;
		}
		if (!api_is_utf8($encoding)) {
			return api_utf8_decode($result, $encoding);
		}
		return $result;
	}
	if (!$before_needle) {
		return strrchr($haystack, $needle);
	}
	$result = strrchr($haystack, $needle);
	if ($result === false) {
		return false;
	}
	return api_substr($haystack, 0, api_strlen($haystack, $encoding) - api_strlen($result, $encoding), $encoding);
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
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (empty($string)) {
		return '';
	}
	if (_api_is_single_byte_encoding($encoding)) {
		return strrev($string);
	}
	if (api_is_encoding_supported($encoding)) {
		return implode(array_reverse(api_str_split($string, 1, $encoding)));
	}
	return strrev($string);
}

/**
 * Finds the position of last occurrence (case insensitive) of a string in a string.
 * @param string $haystack				The string from which to get the position of the last occurrence.
 * @param string $needle				The string to be found.
 * @param int $offset (optional)		$offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed						Returns the numeric position of the first occurrence (case insensitive) of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strripos() and mb_strripos() for human-language strings.
 * @link http://php.net/manual/en/function.strripos
 * @link http://php.net/manual/en/function.mb-strripos
 */
function api_strripos($haystack, $needle, $offset = 0, $encoding = null) {
	return api_strrpos(api_strtolower($haystack, $encoding), api_strtolower($needle, $encoding), $offset, $encoding);
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
		$encoding = _api_mb_internal_encoding();
	}
	if (!is_string($needle)) {
		$needle = (int)$needle;
		if (api_is_utf8($encoding)) {
			$needle = _api_utf8_chr($needle);
		} else {
			$needle = chr($needle);
		}
	}
	if ($needle == '') {
		return false;
	}
	if (_api_is_single_byte_encoding($encoding)) {
		return strrpos($haystack, $needle, $offset);
	}
	if (_api_mb_supports($encoding) && IS_PHP_52) {
		return @mb_strrpos($haystack, $needle, $offset, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$haystack = api_utf8_encode($haystack, $encoding);
			$needle = api_utf8_encode($needle, $encoding);
		}
		if (MBSTRING_INSTALLED && IS_PHP_52) {
			return @mb_strrpos($haystack, $needle, $offset, 'UTF-8');
		}
		// This branch (this fragment of code) is an adaptation from the CakePHP(tm) Project, http://www.cakefoundation.org
		$found = false;
		$haystack = _api_utf8_to_unicode($haystack);
		$haystack_count = count($haystack);
		$matches = array_count_values($haystack);
		$needle = _api_utf8_to_unicode($needle);
		$needle_count = count($needle);
		$position = $offset;
		while (($found === false) && ($position < $haystack_count)) {
			if (isset($needle[0]) && $needle[0] === $haystack[$position]) {
				for ($i = 1; $i < $needle_count; $i++) {
					if ($needle[$i] !== $haystack[$position + $i]) {
						if ($needle[$i] === $haystack[($position + $i) -1]) {
							$position--;
							$found = true;
							continue;
						}
					}
				}
				if (!$offset && isset($matches[$needle[0]]) && $matches[$needle[0]] > 1) {
					$matches[$needle[0]] = $matches[$needle[0]] - 1;
				} elseif ($i === $needle_count) {
					$found = true;
					$position--;
				}
			}
			$position++;
		}
		return ($found) ? $position : false;
	}
	return strrpos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another.
 * @param string $haystack					The string from which to get the first occurrence.
 * @param mixed $needle						The string to be found.
 * @param bool $before_needle (optional)	Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional)		The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed							Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions strstr() and mb_strstr() for human-language strings.
 * @link http://php.net/manual/en/function.strstr
 * @link http://php.net/manual/en/function.mb-strstr
 */
function api_strstr($haystack, $needle, $before_needle = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (!is_string($needle)) {
		$needle = (int)$needle;
		if (api_is_utf8($encoding)) {
			$needle = _api_utf8_chr($needle);
		} else {
			$needle = chr($needle);
		}
	}
	if ($needle == '') {
		return false;
	}
	if (_api_is_single_byte_encoding($encoding)) {
		// Adding the missing parameter $before_needle to the original function strstr(), PHP_VERSION < 5.3
		if (!$before_needle) {
			return strstr($haystack, $needle);
		}
		if (!IS_PHP_53) {
			$result = explode($needle, $haystack, 2);
			if ($result === false || count($result) < 2) {
				return false;
			}
			return $result[0];
		}
		return strstr($haystack, $needle, $before_needle);
	}
	if (_api_mb_supports($encoding)) {
		return @mb_strstr($haystack, $needle, $before_needle, $encoding);
	}
	elseif (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$haystack = api_utf8_encode($haystack, $encoding);
			$needle = api_utf8_encode($needle, $encoding);
		}
		$result = @mb_strstr($haystack, $needle, $before_needle, 'UTF-8');
		if ($result !== false) {
			if (!api_is_utf8($encoding)) {
				return api_utf8_decode($result, $encoding);
			}
			return $result;
		}
		return false;
	}
	// Adding the missing parameter $before_needle to the original function strstr(), PHP_VERSION < 5.3
	if (!$before_needle) {
		return strstr($haystack, $needle);
	}
	if (!IS_PHP_53) {
		$result = explode($needle, $haystack, 2);
		if ($result === false || count($result) < 2) {
			return false;
		}
		return $result[0];
	}
	return strstr($haystack, $needle, $before_needle);
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
		$encoding = _api_mb_internal_encoding();
	}
	if (_api_mb_supports($encoding)) {
		return @mb_strtolower($string, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		if (MBSTRING_INSTALLED) {
			$string = @mb_strtolower($string, 'UTF-8');
		} else {
			// This branch (this fragment of code) is an adaptation from the CakePHP(tm) Project, http://www.cakefoundation.org
			$codepoints = _api_utf8_to_unicode($string);
			$length = count($codepoints);
			$matched = false;
			$result = array();
			for ($i = 0 ; $i < $length; $i++) {
				$codepoint = $codepoints[$i];
				if ($codepoint < 128) {
					$str = strtolower(chr($codepoint));
					$strlen = api_byte_count($str);
					for ($ii = 0 ; $ii < $strlen; $ii++) {
						$lower = ord($str[$ii]);
					}
					$result[] = $lower;
					$matched = true;
				} else {
					$matched = false;
					$properties = &_api_utf8_get_letter_case_properties($codepoint, 'upper');
					if (!empty($properties)) {
						foreach ($properties as $key => $value) {
							if ($properties[$key]['upper'] == $codepoint && count($properties[$key]['lower'][0]) === 1) {
								$result[] = $properties[$key]['lower'][0];
								$matched = true;
								break 1;
							}
						}
					}
				}
				if ($matched === false) {
					$result[] = $codepoint;
				}
			}
			$string = _api_utf8_from_unicode($result);
		}
		if (!api_is_utf8($encoding)) {
			return api_utf8_decode($string, $encoding);
		}
		return $string;
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
		$encoding = _api_mb_internal_encoding();
	}
	if (_api_mb_supports($encoding)) {
		return @mb_strtoupper($string, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		if (MBSTRING_INSTALLED) {
			$string = @mb_strtoupper($string, 'UTF-8');
		} else {
			// This branch (this fragment of code) is an adaptation from the CakePHP(tm) Project, http://www.cakefoundation.org
			$codepoints = _api_utf8_to_unicode($string);
			$length = count($codepoints);
			$matched = false;
			$replaced = array();
			$result = array();
			for ($i = 0 ; $i < $length; $i++) {
				$codepoint = $codepoints[$i];
				if ($codepoint < 128) {
					$str = strtoupper(chr($codepoint));
					$strlen = api_byte_count($str);
					for ($ii = 0 ; $ii < $strlen; $ii++) {
						$lower = ord($str[$ii]);
					}
					$result[] = $lower;
					$matched = true;
				} else {
					$matched = false;
					$properties = &_api_utf8_get_letter_case_properties($codepoint);
					$property_count = count($properties);
					if (!empty($properties)) {
						foreach ($properties as $key => $value) {
							$matched = false;
							$replace = 0;
							if ($length > 1 && count($properties[$key]['lower']) > 1) {
								$j = 0;
								for ($ii = 0; $ii < count($properties[$key]['lower']); $ii++) {
									$next_codepoint = $next_codepoints[$i + $ii];
									if (isset($next_codepoint) && ($next_codepoint == $properties[$key]['lower'][$j + $ii])) {
										$replace++;
									}
								}
								if ($replace == count($properties[$key]['lower'])) {
									$result[] = $properties[$key]['upper'];
									$replaced = array_merge($replaced, array_values($properties[$key]['lower']));
									$matched = true;
									break 1;
								}
							} elseif ($length > 1 && $property_count > 1) {
								$j = 0;
								for ($ii = 1; $ii < $property_count; $ii++) {
									$next_codepoint = $next_codepoints[$i + $ii - 1];
									if (in_array($next_codepoint, $properties[$ii]['lower'])) {
										for ($jj = 0; $jj < count($properties[$ii]['lower']); $jj++) {
											$next_codepoint = $next_codepoints[$i + $jj];
											if (isset($next_codepoint) && ($next_codepoint == $properties[$ii]['lower'][$j + $jj])) {
												$replace++;
											}
										}
										if ($replace == count($properties[$ii]['lower'])) {
											$result[] = $properties[$ii]['upper'];
											$replaced = array_merge($replaced, array_values($properties[$ii]['lower']));
											$matched = true;
											break 2;
										}
									}
								}
							}
							if ($properties[$key]['lower'][0] == $codepoint) {
								$result[] = $properties[$key]['upper'];
								$matched = true;
								break 1;
							}
						}
					}
				}
				if ($matched === false && !in_array($codepoint, $replaced, true)) {
					$result[] = $codepoint;
				}
			}
			$string = _api_utf8_from_unicode($result);
		}
		if (!api_is_utf8($encoding)) {
			return api_utf8_decode($string, $encoding);
		}
		return $string;
	}
	return strtoupper($string);
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
		$encoding = _api_mb_internal_encoding();
	}
	// Passing null as $length would mean 0. This behaviour has been corrected here.
	if (is_null($length)) {
		$length = api_strlen($string, $encoding);
	}
	if (_api_is_single_byte_encoding($encoding)) {
		return substr($string, $start, $length);
	}
	if (_api_mb_supports($encoding)) {
		return @mb_substr($string, $start, $length, $encoding);
	}
	elseif (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		if (MBSTRING_INSTALLED) {
			$string = @mb_substr($string, $start, $length, 'UTF-8');
		} else {
			// The following branch of code is from the Drupal CMS, see the function drupal_substr().
			$strlen = api_byte_count($string);
			// Find the starting byte offset
			$bytes = 0;
			if ($start > 0) {
				// Count all the continuation bytes from the start until we have found
				// $start characters
				$bytes = -1; $chars = -1;
				while ($bytes < $strlen && $chars < $start) {
					$bytes++;
					$c = ord($string[$bytes]);
					if ($c < 0x80 || $c >= 0xC0) {
						$chars++;
					}
				}
			}
			else if ($start < 0) {
				// Count all the continuation bytes from the end until we have found
				// abs($start) characters
				$start = abs($start);
				$bytes = $strlen; $chars = 0;
				while ($bytes > 0 && $chars < $start) {
					$bytes--;
					$c = ord($string[$bytes]);
					if ($c < 0x80 || $c >= 0xC0) {
						$chars++;
					}
				}
			}
			$istart = $bytes;
			// Find the ending byte offset
			if ($length === NULL) {
				$bytes = $strlen - 1;
			}
			else if ($length > 0) {
				// Count all the continuation bytes from the starting index until we have
				// found $length + 1 characters. Then backtrack one byte.
				$bytes = $istart; $chars = 0;
				while ($bytes < $strlen && $chars < $length) {
					$bytes++;
					$c = ord($string[$bytes]);
					if ($c < 0x80 || $c >= 0xC0) {
						$chars++;
					}
				}
				$bytes--;
			}
			else if ($length < 0) {
				// Count all the continuation bytes from the end until we have found
				// abs($length) characters
				$length = abs($length);
				$bytes = $strlen - 1; $chars = 0;
				while ($bytes >= 0 && $chars < $length) {
					$c = ord($string[$bytes]);
					if ($c < 0x80 || $c >= 0xC0) {
						$chars++;
					}
					$bytes--;
				}
			}
			$iend = $bytes;
			$string = substr($string, $istart, max(0, $iend - $istart + 1));
		}
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_decode($string, $encoding);
		}
		return $string;
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
		$encoding = _api_mb_internal_encoding();
	}
	if (_api_is_single_byte_encoding($encoding)) {
		if (is_null($length)) {
			return substr_replace($string, $replacement, $start);
		}
		return substr_replace($string, $replacement, $start, $length);
	}
	if (api_is_encoding_supported($encoding)) {
		if (is_null($length)) {
			$length = api_strlen($string);
		}
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
			$replacement = api_utf8_encode($replacement, $encoding);
		}
		$string = _api_utf8_to_unicode($string);
		array_splice($string, $start, $length, _api_utf8_to_unicode($replacement));
		$string = _api_utf8_from_unicode($string);
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_decode($string, $encoding);
		}
		return $string;
	}
	if (is_null($length)) {
		return substr_replace($string, $replacement, $start);
	}
	return substr_replace($string, $replacement, $start, $length);
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
		$encoding = _api_mb_internal_encoding();
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
		$encoding = _api_mb_internal_encoding();
	}
    if (_api_mb_supports($encoding)) {
		return @mb_convert_case($string, MB_CASE_TITLE, $encoding);
	}
	if (api_is_encoding_supported($encoding)) {
		if (!api_is_utf8($encoding)) {
			$string = api_utf8_encode($string, $encoding);
		}
		if (MBSTRING_INSTALLED) {
			$string = @mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
		} else {
			// The following fragment (branch) of code is based on the function utf8_ucwords() by Harry Fuecks
			// See http://dev.splitbrain.org/view/darcs/dokuwiki/inc/utf8.php
			// Note: [\x0c\x09\x0b\x0a\x0d\x20] matches - form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns.
			// This corresponds to the definition of a "word" defined at http://www.php.net/ucwords
			$pattern = '/(^|([\x0c\x09\x0b\x0a\x0d\x20]+))([^\x0c\x09\x0b\x0a\x0d\x20]{1})[^\x0c\x09\x0b\x0a\x0d\x20]*/u';
			$string = preg_replace_callback($pattern, '_api_utf8_ucwords_callback', $string);
		}
		if (!api_is_utf8($encoding)) {
			return api_utf8_decode($string, $encoding);
		}
		return $string;
	}
	return ucwords($string);
}


/**
 * ----------------------------------------------------------------------------
 * String operations using regular expressions
 * ----------------------------------------------------------------------------
 */

/**
 * Performs a regular expression match, UTF-8 aware when it is applicable.
 * @param string $pattern				The pattern to search for, as a string.
 * @param string $subject				The input string.
 * @param array &$matches (optional)	If matches is provided, then it is filled with the results of search (as an array).
 * 										$matches[0] will contain the text that matched the full pattern, $matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.
 * @param int $flags (optional)			Could be PREG_OFFSET_CAPTURE. If this flag is passed, for every occurring match the appendant string offset will also be returned.
 * 										Note that this changes the return value in an array where every element is an array consisting of the matched string at index 0 and its string offset into subject at index 1.
 * @param int $offset (optional)		Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int|boolean					Returns the number of times pattern matches or FALSE if an error occurred.
 * @link http://php.net/preg_match
 */
function api_preg_match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	return preg_match(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $matches, $flags, $offset);
}

/**
 * Performs a global regular expression match, UTF-8 aware when it is applicable.
 * @param string $pattern				The pattern to search for, as a string.
 * @param string $subject				The input string.
 * @param array &$matches (optional)	Array of all matches in multi-dimensional array ordered according to $flags.
 * @param int $flags (optional)			Can be a combination of the following flags (note that it doesn't make sense to use PREG_PATTERN_ORDER together with PREG_SET_ORDER):
 * PREG_PATTERN_ORDER - orders results so that $matches[0] is an array of full pattern matches, $matches[1] is an array of strings matched by the first parenthesized subpattern, and so on;
 * PREG_SET_ORDER - orders results so that $matches[0] is an array of first set of matches, $matches[1] is an array of second set of matches, and so on;
 * PREG_OFFSET_CAPTURE - If this flag is passed, for every occurring match the appendant string offset will also be returned. Note that this changes the value of matches
 * in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
 * If no order flag is given, PREG_PATTERN_ORDER is assumed.
 * @param int $offset (optional)		Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int|boolean					Returns the number of full pattern matches (which might be zero), or FALSE if an error occurred.
 * @link http://php.net/preg_match_all
 */
function api_preg_match_all($pattern, $subject, &$matches, $flags = PREG_PATTERN_ORDER, $offset = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (is_null($flags)) {
		$flags = PREG_PATTERN_ORDER;
	}
	return preg_match_all(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $matches, $flags, $offset);
}

/**
 * Performs a regular expression search and replace, UTF-8 aware when it is applicable.
 * @param string|array $pattern			The pattern to search for. It can be either a string or an array with strings.
 * @param string|array $replacement		The string or an array with strings to replace.
 * @param string|array $subject			The string or an array with strings to search and replace.
 * @param int $limit					The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @param int &$count					If specified, this variable will be filled with the number of replacements done.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array|string|null			returns an array if the subject parameter is an array, or a string otherwise.
 * If matches are found, the new subject will be returned, otherwise subject will be returned unchanged or NULL if an error occurred.
 * @link http://php.net/preg_replace
 */
function api_preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	$is_utf8 = api_is_utf8($encoding);
	if (is_array($pattern)) {
		foreach ($pattern as &$p) {
			$p = $is_utf8 ? $p.'u' : $p;
		}
	} else {
		$pattern = $is_utf8 ? $pattern.'u' : $pattern;
	}
	return preg_replace($pattern, $replacement, $subject, $limit, $count);
}

/**
 * Performs a regular expression search and replace using a callback function, UTF-8 aware when it is applicable.
 * @param string|array $pattern			The pattern to search for. It can be either a string or an array with strings.
 * @param function $callback			A callback that will be called and passed an array of matched elements in the $subject string. The callback should return the replacement string.
 * @param string|array $subject			The string or an array with strings to search and replace.
 * @param int $limit (optional)			The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @param int &$count (optional)		If specified, this variable will be filled with the number of replacements done.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array|string					Returns an array if the subject parameter is an array, or a string otherwise.
 * @link http://php.net/preg_replace_callback
 */
function api_preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	if (is_array($pattern)) {
		foreach ($pattern as &$p) {
			$p = api_is_utf8($encoding) ? $p.'u' : $p;
		}
	} else {
		$pattern = api_is_utf8($encoding) ? $pattern.'u' : $pattern;
	}
	return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
}

/**
 * Splits a string by a regular expression, UTF-8 aware when it is applicable.
 * @param string $pattern				The pattern to search for, as a string.
 * @param string $subject				The input string.
 * @param int $limit (optional)			If specified, then only substrings up to $limit are returned with the rest of the string being placed in the last substring. A limit of -1, 0 or null means "no limit" and, as is standard across PHP.
 * @param int $flags (optional)			$flags can be any combination of the following flags (combined with bitwise | operator):
 * PREG_SPLIT_NO_EMPTY - if this flag is set, only non-empty pieces will be returned;
 * PREG_SPLIT_DELIM_CAPTURE - if this flag is set, parenthesized expression in the delimiter pattern will be captured and returned as well;
 * PREG_SPLIT_OFFSET_CAPTURE - If this flag is set, for every occurring match the appendant string offset will also be returned.
 * Note that this changes the return value in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array						Returns an array containing substrings of $subject split along boundaries matched by $pattern.
 * @link http://php.net/preg_split
 */
function api_preg_split($pattern, $subject, $limit = -1, $flags = 0, $encoding = null) {
	if (empty($encoding)) {
		$encoding = _api_mb_internal_encoding();
	}
	return preg_split(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $limit, $flags);
}


/**
 * ----------------------------------------------------------------------------
 * Obsolete string operations using regular expressions, to be deprecated
 * ----------------------------------------------------------------------------
 */

/**
 * Note: Try to avoid using this function. Use api_preg_match() with Perl-compatible regular expression syntax.
 *
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
	$encoding = _api_mb_regex_encoding();
	if (_api_mb_supports($encoding)) {
		if ($count < 3) {
			return @mb_ereg($pattern, $string);
		}
		return @mb_ereg($pattern, $string, $regs);
	}
	if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		global $_api_encoding;
		$_api_encoding = $encoding;
		_api_mb_regex_encoding('UTF-8');
		if ($count < 3) {
			$result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
		} else {
			$result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
			$regs = _api_array_utf8_decode($regs);
		}
		_api_mb_regex_encoding($encoding);
		return $result;
	}
	if ($count < 3) {
		return ereg($pattern, $string);
	}
	return ereg($pattern, $string, $regs);
}

/**
 * Note: Try to avoid using this function. Use api_preg_replace() with Perl-compatible regular expression syntax.
 *
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
	$encoding = _api_mb_regex_encoding();
	if (_api_mb_supports($encoding)) {
		if (is_null($option)) {
			return @mb_ereg_replace($pattern, $replacement, $string);
		}
		return @mb_ereg_replace($pattern, $replacement, $string, $option);
	}
	if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		_api_mb_regex_encoding('UTF-8');
		if (is_null($option)) {
			$result = api_utf8_decode(@mb_ereg_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding)), $encoding);
		} else {
			$result = api_utf8_decode(@mb_ereg_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding), $option), $encoding);
		}
		_api_mb_regex_encoding($encoding);
		return $result;
	}
	return ereg_replace($pattern, $replacement, $string);
}

/**
 * Note: Try to avoid using this function. Use api_preg_match() with Perl-compatible regular expression syntax.
 *
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
	$encoding = _api_mb_regex_encoding();
	if (_api_mb_supports($encoding)) {
		if ($count < 3) {
			return @mb_eregi($pattern, $string);
		}
		return @mb_eregi($pattern, $string, $regs);
	}
	if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		global $_api_encoding;
		$_api_encoding = $encoding;
		_api_mb_regex_encoding('UTF-8');
		if ($count < 3) {
			$result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
		} else {
			$result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
			$regs = _api_array_utf8_decode($regs);
		}
		_api_mb_regex_encoding($encoding);
		return $result;
	}
	if ($count < 3) {
		return eregi($pattern, $string);
	}
	return eregi($pattern, $string, $regs);
}

/**
 * Note: Try to avoid using this function. Use api_preg_replace() with Perl-compatible regular expression syntax.
 *
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
	$encoding = _api_mb_regex_encoding();
	if (_api_mb_supports($encoding)) {
		if (is_null($option)) {
			return @mb_eregi_replace($pattern, $replacement, $string);
		}
		return @mb_eregi_replace($pattern, $replacement, $string, $option);
	}
	if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		_api_mb_regex_encoding('UTF-8');
		if (is_null($option)) {
			$result = api_utf8_decode(@mb_eregi_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding)), $encoding);
		} else {
			$result = api_utf8_decode(@mb_eregi_replace(api_utf8_encode($pattern, $encoding), api_utf8_encode($replacement, $encoding), api_utf8_encode($string, $encoding), $option), $encoding);
		}
		_api_mb_regex_encoding($encoding);
		return $result;
	}
	return eregi_replace($pattern, $replacement, $string);
}

/**
 * Note: Try to avoid using this function. Use api_preg_split() with Perl-compatible regular expression syntax.
 *
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
	$encoding = _api_mb_regex_encoding();
	if (_api_mb_supports($encoding)) {
		if (is_null($limit)) {
			return @mb_split($pattern, $string);
		}
		return @mb_split($pattern, $string, $limit);
	}
	if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
		global $_api_encoding;
		$_api_encoding = $encoding;
		_api_mb_regex_encoding('UTF-8');
		if (is_null($limit)) {
			$result = @mb_split(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
		} else {
			$result = @mb_split(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $limit);
		}
		$result = _api_array_utf8_decode($result);
		_api_mb_regex_encoding($encoding);
		return $result;
	}
	if (is_null($limit)) {
		return split($pattern, $string);
	}
	return split($pattern, $string, $limit);
}


/**
 * ----------------------------------------------------------------------------
 * String comparison
 * ----------------------------------------------------------------------------
 */

/**
 * Performs string comparison, case insensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcasecmp
 */
function api_strcasecmp($string1, $string2, $language = null, $encoding = null) {
	return api_strcmp(api_strtolower($string1, $encoding), api_strtolower($string2, $encoding), $language, $encoding);
}

/**
 * Performs string comparison, case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strcmp($string1, $string2, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			$result = collator_compare($collator, api_utf8_encode($string1, $encoding), api_utf8_encode($string2, $encoding));
			return $result === false ? 0 : $result;
		}
	}
	return strcmp($string1, $string2);
}

/**
 * Performs string comparison in so called "natural order", case insensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcasecmp
 */
function api_strnatcasecmp($string1, $string2, $language = null, $encoding = null) {
	return api_strnatcmp(api_strtolower($string1, $encoding), api_strtolower($string2, $encoding), $language, $encoding);
}

/**
 * Performs string comparison in so called "natural order", case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strnatcmp($string1, $string2, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			$result = collator_compare($collator, api_utf8_encode($string1, $encoding), api_utf8_encode($string2, $encoding));
			return $result === false ? 0 : $result;
		}
	}
	return strnatcmp($string1, $string2);
}


/**
 * ----------------------------------------------------------------------------
 * Sorting arrays
 * ----------------------------------------------------------------------------
 */

/**
 * Sorts an array with maintaining index association, elements will be arranged from the lowest to the highest.
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function asort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.asort.php
 * @link http://php.net/manual/en/collator.asort.php
 */
function api_asort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			if (api_is_utf8($encoding)) {
				$sort_flag = ($sort_flag == SORT_LOCALE_STRING) ? SORT_STRING : $sort_flag;
				return collator_asort($collator, $array, _api_get_collator_sort_flag($sort_flag));
			}
			elseif ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
				global $_api_collator, $_api_encoding;
				$_api_collator = $collator;
				$_api_encoding = $encoding;
				return uasort($array, '_api_cmp');
			}
		}
	}
	return asort($array, $sort_flag);
}

/**
 * Sorts an array with maintaining index association, elements will be arranged from the highest to the lowest (in reverse order).
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function arsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.arsort.php
 */
function api_arsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
				global $_api_collator, $_api_encoding;
				$_api_collator = $collator;
				$_api_encoding = $encoding;
				return uasort($array, '_api_rcmp');
			}
		}
	}
	return arsort($array, $sort_flag);
}

/**
 * Sorts an array using natural order algorithm.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * This function is aimed at replacing the function natsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.natsort.php
 */
function api_natsort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uasort($array, '_api_cmp');
		}
	}
	return natsort($array);
}

/**
 * Sorts an array using natural order algorithm in reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_natrsort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uasort($array, '_api_rcmp');
		}
	}
	return uasort($array, '_api_strnatrcmp');
}

/**
 * Sorts an array using natural order algorithm, case-insensitive.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * This function is aimed at replacing the function natcasesort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.natcasesort.php
 */
function api_natcasesort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uasort($array, '_api_casecmp');
		}
	}
	return natcasesort($array);
}

/**
 * Sorts an array using natural order algorithm, case-insensitive, reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_natcasersort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uasort($array, '_api_casercmp');
		}
	}
	return uasort($array, '_api_strnatcasercmp');
}

/**
 * Sorts an array by keys, elements will be arranged from the lowest key to the highest key.
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how keys of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - keys will be compared as numbers;
 * SORT_STRING - keys will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - keys will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function ksort() for sorting human-language key strings.
 * @link http://php.net/manual/en/function.ksort.php
 */
function api_ksort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
				global $_api_collator, $_api_encoding;
				$_api_collator = $collator;
				$_api_encoding = $encoding;
				return uksort($array, '_api_cmp');
			}
		}
	}
	return ksort($array, $sort_flag);
}

/**
 * Sorts an array by keys, elements will be arranged from the highest key to the lowest key (in reverse order).
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how keys of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - keys will be compared as numbers;
 * SORT_STRING - keys will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - keys will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function krsort() for sorting human-language key strings.
 * @link http://php.net/manual/en/function.krsort.php
 */
function api_krsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
				global $_api_collator, $_api_encoding;
				$_api_collator = $collator;
				$_api_encoding = $encoding;
				return uksort($array, '_api_rcmp');
			}
		}
	}
	return krsort($array, $sort_flag);
}

/**
 * Sorts an array by keys using natural order algorithm.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatsort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uksort($array, '_api_cmp');
		}
	}
	return uksort($array, 'strnatcmp');
}

/**
 * Sorts an array by keys using natural order algorithm in reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatrsort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uksort($array, '_api_rcmp');
		}
	}
	return uksort($array, '_api_strnatrcmp');
}

/**
 * Sorts an array by keys using natural order algorithm, case insensitive.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatcasesort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uksort($array, '_api_casecmp');
		}
	}
	return uksort($array, 'strnatcasecmp');
}

/**
 * Sorts an array by keys using natural order algorithm, case insensitive, reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_knatcasersort(&$array, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_alpha_numerical_collator($language);
		if (is_object($collator)) {
			global $_api_collator, $_api_encoding;
			$_api_collator = $collator;
			$_api_encoding = $encoding;
			return uksort($array, '_api_casercmp');
		}
	}
	return uksort($array, '_api_strnatcasercmp');
}

/**
 * Sorts an array, elements will be arranged from the lowest to the highest.
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function sort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.sort.php
 * @link http://php.net/manual/en/collator.sort.php
 */
function api_sort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			if (api_is_utf8($encoding)) {
				$sort_flag = ($sort_flag == SORT_LOCALE_STRING) ? SORT_STRING : $sort_flag;
				return collator_sort($collator, $array, _api_get_collator_sort_flag($sort_flag));
			}
			elseif ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
				global $_api_collator, $_api_encoding;
				$_api_collator = $collator;
				$_api_encoding = $encoding;
				return usort($array, '_api_cmp');
			}
		}
	}
	return sort($array, $sort_flag);
}

/**
 * Sorts an array, elements will be arranged from the highest to the lowest (in reverse order).
 * @param array $array					The input array.
 * @param int $sort_flag (optional)		Shows how elements of the array to be compared.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function rsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.rsort.php
 */
function api_rsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null) {
	if (INTL_INSTALLED) {
		if (empty($encoding)) {
			$encoding = _api_mb_internal_encoding();
		}
		$collator = _api_get_collator($language);
		if (is_object($collator)) {
			if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
				global $_api_collator, $_api_encoding;
				$_api_collator = $collator;
				$_api_encoding = $encoding;
				return usort($array, '_api_rcmp');
			}
		}
	}
	return rsort($array, $sort_flag);
}


/**
 * ----------------------------------------------------------------------------
 * Common sting operations with arrays
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
	if (!is_array($haystack)) {
		return false;
	}
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


/**
 * ----------------------------------------------------------------------------
 * Encoding management functions
 * ----------------------------------------------------------------------------
 */

/**
 * This function unifies the encoding identificators, so they could be compared.
 * @param string/array $encoding	The specified encoding.
 * @return string					Returns the encoding identificator modified in suitable for comparison way.
 */
function api_refine_encoding_id($encoding) {
	if (is_array($encoding)){
		return array_map('strtoupper', $encoding);
	}
	return strtoupper($encoding);
}

/**
 * This function checks whether two $encoding are equal (same, equvalent).
 * @param string/array $encoding1		The first encoding
 * @param string/array $encoding2		The second encoding
 * @return bool							Returns TRUE if the encodings are equal, FALSE otherwise.
 */
function api_equal_encodings($encoding1, $encoding2) {
	static $equal_encodings = array();
	if (is_array($encoding1)) {
		foreach ($encoding1 as $encoding) {
			if (api_equal_encodings($encoding, $encoding2)) {
				return true;
			}
		}
		return false;
	}
	elseif (is_array($encoding2)) {
		foreach ($encoding2 as $encoding) {
			if (api_equal_encodings($encoding1, $encoding)) {
				return true;
			}
		}
		return false;
	}
	if (!isset($equal_encodings[$encoding1][$encoding2])) {
		$encoding_1 = api_refine_encoding_id($encoding1);
		$encoding_2 = api_refine_encoding_id($encoding2);
		if ($encoding_1 == $encoding_2) {
			$result = true;
		} else {
			$alias1 = _api_get_character_map_name($encoding_1);
			$alias2 = _api_get_character_map_name($encoding_2);
			$result = !empty($alias1) && !empty($alias2) && $alias1 == $alias2;
		}
		$equal_encodings[$encoding1][$encoding2] = $result;
	}
	return $equal_encodings[$encoding1][$encoding2];
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
 * @param string/array $encoding		The tested encoding.
 * @param bool $strict					Flag for check precision. ISO-8859-1 is always Latin 1. When $strict is false, ISO-8859-15 is assumed as Latin 1 too.
 * @return bool							Returns TRUE if the given encoding id means Latin 1 character set, otherwise returns false.
 */
function api_is_latin1($encoding, $strict = false) {
	static $latin1 = array();
	static $latin1_strict = array();
	if ($strict) {
		if (!isset($latin1_strict[$encoding])) {
			$latin1_strict[$encoding] = api_equal_encodings($encoding, array('ISO-8859-1', 'ISO8859-1', 'CP819', 'LATIN1'));
		}
		return $latin1_strict[$encoding];
	}
	if (!isset($latin1[$encoding])) {
		$latin1[$encoding] = api_equal_encodings($encoding, array(
			'ISO-8859-1', 'ISO8859-1', 'CP819', 'LATIN1',
			'ISO-8859-15', 'ISO8859-15', 'CP923', 'LATIN0', 'LATIN-9',
			'WINDOWS-1252', 'CP1252', 'WIN-1252', 'WIN1252'
		));
	}
	return $latin1[$encoding];
}

/**
 * This function returns the encoding, currently used by the system.
 * @return string	The system's encoding.
 * Note: The value of api_get_setting('platform_charset') is tried to be returned first,
 * on the second place the global variable $charset is tried to be returned. If for some
 * reason both attempts fail, then the libraly's internal value will be returned.
 */
function api_get_system_encoding() {
	$system_encoding = api_get_setting('platform_charset');
	if (empty($system_encoding)) {
		global $charset;
		if (empty($charset)) {
			return _api_mb_internal_encoding();
		}
		return $charset;
	}
	return $system_encoding;
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
 * Checks whether a specified encoding is supported by this API.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_is_encoding_supported($encoding) {
	static $supported = array();
	if (!isset($supported[$encoding])) {
		$supported[$encoding] = _api_mb_supports($encoding) || _api_iconv_supports($encoding) || _api_convert_encoding_supports($encoding);
	}
	return $supported[$encoding];
}

/**
 * Returns in an array the most-probably used non-UTF-8 encoding for the given language.
 * The first (leading) value is actually used by the system at the moment.
 * @param string $language (optional)	The specified language, the default value is the user intrface language.
 * @return string						The correspondent encoding to the specified language.
 * Note: See the file dokeos/main/inc/lib/internationalization_database/non_utf8_encodings.php
 * if you wish to revise the leading non-UTF-8 encoding for your language.
 */
function api_get_non_utf8_encoding($language = null) {
	if (empty($language)) {
		$language = api_get_interface_language();
	}
	$language = api_refine_language_id($language);
	$encodings = & _api_non_utf8_encodings();
	if (is_array($encodings[$language])) {
		if (!empty($encodings[$language][0])) {
			return $encodings[$language][0];
		}
		return 'ISO-8859-15';
	}
	return 'ISO-8859-15';
}

/**
 * Detects encoding of xml-formatted text.
 * @param string $string				The input xml-formatted text.
 * @param string $default_encoding		This is the default encoding to be returned if there is no way the xml-text's encoding to be detected. If it not spesified, the system encoding is assumed then.
 * @return string						Returns the detected encoding.
 * Note: The regular expression string has been published by Steve Minutillo.
 * @link http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss/
 */
function api_detect_xml_encoding(&$string, $default_encoding = null) {
	if (preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m', $string, $matches)) {
		return api_refine_encoding_id($matches[1]);
	}
	if (api_is_valid_utf8($string)) {
		return 'UTF-8';
	}
	if (empty($default_encoding)) {
		$default_encoding = _api_mb_internal_encoding();
	}
	return api_refine_encoding_id($default_encoding);
}


/**
 * ----------------------------------------------------------------------------
 * String validation functions concerning certain encodings
 * ----------------------------------------------------------------------------
 */

/**
 * Checks a string for UTF-8 validity.
 * @param string $string	The string to be tested/validated.
 * @return bool				Returns TRUE when the tested string is valid UTF-8 one, FALSE othewise.
 * @link http://en.wikipedia.org/wiki/UTF-8
 */
function api_is_valid_utf8(&$string) {

	//return @mb_detect_encoding($string, 'UTF-8', true) == 'UTF-8' ? true : false;
	// Ivan Tcholakov, 05-OCT-2008: I do not trust mb_detect_encoding(). I have
	// found a string with a single cyrillic letter (single byte), that is
	// wrongly detected as UTF-8. Possibly, there would be problems with other
	// languages too. An alternative implementation will be used.

	$len = api_byte_count($string);
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
			if ($i == $len) {
				return false;				// Here the string ends unexpectedly.
			}

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
function api_is_valid_ascii(&$string) {
	if (MBSTRING_INSTALLED) {
		return @mb_detect_encoding($string, 'ASCII', true) == 'ASCII' ? true : false;
	}
	return !preg_match('/[^\x00-\x7F]/S', $string);
}


/**
 * ----------------------------------------------------------------------------
 * Language management functions
 * ----------------------------------------------------------------------------
 */

/**
 * Checks whether a given language identificator represents supported by the system language.
 * @param string $language		The language identificator to be checked ('english', 'french', 'spanish', ...).
 * @return bool $language		TRUE if the language is supported, FALSE otherwise.
 */
function api_is_language_supported($language) {
	static $supported = array();
	if (!isset($supported[$language])) {
		$supported[$language] = in_array(api_refine_language_id($language), array_keys(_api_non_utf8_encodings()));
	}
	return $supported[$language];
}

/**
 * Validates the input language identificator in order always to return a language that is supported by the system.
 * @param string $language		The language identificator to be validated.
 * @param bool $purify			A modifier to the returne result. If it is TRUE, then the returned language identificator is purified.
 * @return string				Returns the input language identificator, purified, if it was demanded. If the input language is not supported, 'english' is returned then.
 */
function api_validate_language($language, $purify = false) {
	if (!api_is_language_supported($language)) {
		return 'english';
	}
	if ($purify) {
		return api_refine_language_id($language);
	}
	return str_replace('_km', '_KM', strtolower($language));
}

/**
 * Returns a purified language id, without possible suffixes that will disturb language identification in certain cases.
 * @param string $language	The input language identificator, for example 'french_unicode'.
 * @param string			The same purified or filtered language identificator, for example 'french'.
 */
function api_refine_language_id($language) {
	static $purified = array();
	if (!isset($purified[$language])) {
		$purified[$language] = str_replace(array('_unicode', '_latin', '_corporate', '_org', '_km'), '', strtolower($language));
	}
	return $purified[$language];
}

/**
 * This function check whether a given language can use Latin 1 encoding.
 * @param string $language	The checked language.
 * @return bool				TRUE if the given language can use Latin 1 encoding (ISO-8859-15, ISO-8859-1, WINDOWS-1252, ...), FALSE otherwise.
 */
function api_is_latin1_compatible($language) {
	static $latin1_languages;
	if (!isset($latin1_languages)) {
		$latin1_languages = _api_get_latin1_compatible_languages();
	}
	$language = api_refine_language_id($language);
	return in_array($language, $latin1_languages);
}


/**
 * ----------------------------------------------------------------------------
 * Functions for internal use behind this API.
 * ----------------------------------------------------------------------------
 */

require_once dirname(__FILE__).'/multibyte_string_functions_internal.lib.php';
