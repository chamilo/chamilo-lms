<?php
/**
 * ==============================================================================
 * File: multibyte_string_functions_internal.lib.php
 * Main API extension library for Dokeos 1.8.6+ LMS,
 * contains functions for internal use only.
 * License: GNU/GPL version 2 or later (Free Software Foundation)
 * @author: Ivan Tcholakov, ivantcholakov@gmail.com, 2009
 * @package dokeos.library
 * ==============================================================================
 */


/**
 * ----------------------------------------------------------------------------
 * Appendix to "Common multibyte string functions"
 * ----------------------------------------------------------------------------
 */

/**
 * Takes an UTF-8 string and returns an array of ints representing the 
 * Unicode characters. Astral planes are supported ie. the ints in the
 * output can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
 * are not allowed.
 * @param string $string				The UTF-8 encoded string.
 * @param string $unknown (optional)	A US-ASCII character to represent invalid bytes.
 * @return array						Returns an array of unicode code points.
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, 2009, modifications for the Dokeos LMS.
*/
function _api_utf8_to_unicode($string, $unknown = '?') {
	if (!empty($unknown)) {
		$unknown = ord($unknown[0]);
	}
	$state = 0;			// cached expected number of octets after the current octet
						// until the beginning of the next UTF8 character sequence
	$codepoint  = 0;	// cached Unicode character
	$bytes = 1;			// cached expected number of octets in the current sequence
	$result = array();
	$len = api_byte_count($string);
	for ($i = 0; $i < $len; $i++) {
		$byte = ord($string[$i]);
		if ($state == 0) {
			// When state is zero we expect either a US-ASCII character or a
			// multi-octet sequence.
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
				// Current octet is neither in the US-ASCII range nor a legal first
				// octet of a multi-octet sequence.
				$state = 0;
				$codepoint = 0;
				$bytes = 1;
				if (!empty($unknown)) {
					$result[] = $unknown;
				}
				continue ;
			}
		} else {
			// When state is non-zero, we expect a continuation of the multi-octet
			// sequence
			if (0x80 == (0xC0 & ($byte))) {
				// Legal continuation.
				$shift = ($state - 1) * 6;
				$tmp = $byte;
				$tmp = ($tmp & 0x0000003F) << $shift;
				$codepoint |= $tmp;
				// End of the multi-octet sequence. $codepoint now contains the final
				// Unicode codepoint to be output
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
						if (!empty($unknown)) {
							$result[] = $unknown;
						}
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
				if (!empty($unknown)) {
					$result[] = $unknown;
				}
			}
		}
	}
	return $result;
}

/**
 * Takes an array of ints representing the Unicode characters and returns 
 * a UTF-8 string. Astral planes are supported ie. the ints in the
 * input can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
 * are not allowed.
 * @param array $array					An array of unicode code points representing a string.
 * @param string $unknown (optional)	A US-ASCII character to represent invalid bytes.
 * @return string						Returns a UTF-8 string constructed using the given code points.
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * @author Ivan Tcholakov, 2009, modifications for the Dokeos LMS.
 * @see _api_utf8_from_unicodepoint()
*/
function _api_utf8_from_unicode($array, $unknown = '?') {
	foreach ($array as $i => &$codepoint) {
		$codepoint = _api_utf8_from_unicodepoint($codepoint, $unknown);
	}
	return implode($array);
}

/**
 * Takes an integer value and returns its correspondent representing the Unicode character.
 * @param array $array					An array of unicode code points representing a string
 * @param string $unknown (optional)	A US-ASCII character to represent invalid bytes.
 * @return string						Returns the corresponding  UTF-8 character.
 * @see _api_utf8_from_unicode()
 */
function _api_utf8_from_unicodepoint($codepoint, $unknown = '?') {
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
		$result = $unknown;
	// 3 byte sequence
	} else if ($codepoint <= 0xffff) {
		$result = chr(0xe0 | ($codepoint >> 12)) . chr(0x80 | (($codepoint >> 6) & 0x003f)) . chr(0x80 | ($codepoint & 0x003f));
	// 4 byte sequence
	} else if ($codepoint <= 0x10ffff) {
		$result = chr(0xf0 | ($codepoint >> 18)) . chr(0x80 | (($codepoint >> 12) & 0x3f)) . chr(0x80 | (($codepoint >> 6) & 0x3f)) . chr(0x80 | ($codepoint & 0x3f));
	} else {
 		// out of range
		$result = $unknown;
	}
	return $result;
}

// Reads case folding properties about a given character from a file-based "database".
function _api_utf8_get_letter_case_properties($codepoint, $type = 'lower') {
	static $config = array();
	static $range = array();

	if (!isset($range[$codepoint])) {
		if ($codepoint > 128 && $codepoint < 256)  {
			$range[$codepoint] = '0080_00ff'; // Latin-1 Supplement
		} elseif ($codepoint < 384) {
			$range[$codepoint] = '0100_017f'; // Latin Extended-A
		} elseif ($codepoint < 592) {
			$range[$codepoint] = '0180_024F'; // Latin Extended-B
		} elseif ($codepoint < 688) {
			$range[$codepoint] = '0250_02af'; // IPA Extensions
		} elseif ($codepoint >= 880 && $codepoint < 1024) {
			$range[$codepoint] = '0370_03ff'; // Greek and Coptic
		} elseif ($codepoint < 1280) {
			$range[$codepoint] = '0400_04ff'; // Cyrillic
		} elseif ($codepoint < 1328) {
			$range[$codepoint] = '0500_052f'; // Cyrillic Supplement
		} elseif ($codepoint < 1424) {
			$range[$codepoint] = '0530_058f'; // Armenian
		} elseif ($codepoint >= 7680 && $codepoint < 7936) {
			$range[$codepoint] = '1e00_1eff'; // Latin Extended Additional
		} elseif ($codepoint < 8192) {
			$range[$codepoint] = '1f00_1fff'; // Greek Extended
		} elseif ($codepoint >= 8448 && $codepoint < 8528) {
			$range[$codepoint] = '2100_214f'; // Letterlike Symbols
		} elseif ($codepoint < 8592) {
			$range[$codepoint] = '2150_218f'; // Number Forms
		} elseif ($codepoint >= 9312 && $codepoint < 9472) {
			$range[$codepoint] = '2460_24ff'; // Enclosed Alphanumerics
		} elseif ($codepoint >= 11264 && $codepoint < 11360) {
			$range[$codepoint] = '2c00_2c5f'; // Glagolitic
		} elseif ($codepoint < 11392) {
			$range[$codepoint] = '2c60_2c7f'; // Latin Extended-C
		} elseif ($codepoint < 11520) {
			$range[$codepoint] = '2c80_2cff'; // Coptic
		} elseif ($codepoint >= 65280 && $codepoint < 65520) {
			$range[$codepoint] = 'ff00_ffef'; // Halfwidth and Fullwidth Forms
		} else {
			$range[$codepoint] = false;
		}

		if ($range[$codepoint] === false) {
			return null;
		}
		if (!isset($config[$range[$codepoint]])) {
			$file = dirname(__FILE__) . '/multibyte_string_database/casefolding/' . $range[$codepoint] . '.php';
			if (file_exists($file)) {
				include $file;
			}
		}
	}

	if ($range[$codepoint] === false || !isset($config[$range[$codepoint]])) {
		return null;
	}

	$result = array();
	$count = count($config[$range[$codepoint]]);

	for ($i = 0; $i < $count; $i++) {
		if ($type === 'lower' && $config[$range[$codepoint]][$i][$type][0] === $codepoint) {
			$result[] = $config[$range[$codepoint]][$i];
		} elseif ($type === 'upper' && $config[$range[$codepoint]][$i][$type] === $codepoint) {
			$result[] = $config[$range[$codepoint]][$i];
		}
	}
	return $result;
}


/**
 * ----------------------------------------------------------------------------
 * Appendix to "Common sting operations with arrays"
 * ----------------------------------------------------------------------------
 */

// This (callback) function convers from UTF-8 to other encoding.
// It works with arrays of strings too.
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
 * ----------------------------------------------------------------------------
 * Appendix to "String comparison"
 * ----------------------------------------------------------------------------
 */

// Global variables used by the sorting functions.
$_api_collator = null;
$_api_encoding = null;

// A string comparison function that serves sorting functions.
function _api_cmp($string1, $string2) {
	global $_api_collator, $_api_encoding;
	$result = collator_compare($_api_collator, api_utf8_encode($string1, $_api_encoding), api_utf8_encode($string2, $_api_encoding));
	return $result === false ? 0 : $result;
}

// A reverse string comparison function that serves sorting functions.
function _api_rcmp($string1, $string2) {
	global $_api_collator, $_api_encoding;
	$result = collator_compare($_api_collator, api_utf8_encode($string2, $_api_encoding), api_utf8_encode($string1, $_api_encoding));
	return $result === false ? 0 : $result;
}

// A case-insensitive string comparison function that serves sorting functions.
function _api_casecmp($string1, $string2) {
	global $_api_collator, $_api_encoding;
	$result = collator_compare($_api_collator, api_strtolower(api_utf8_encode($string1, $_api_encoding), 'UTF-8'), api_strtolower(api_utf8_encode($string2, $_api_encoding), 'UTF-8'));
	return $result === false ? 0 : $result;
}

// A reverse case-insensitive string comparison function that serves sorting functions.
function _api_casercmp($string1, $string2) {
	global $_api_collator, $_api_encoding;
	$result = collator_compare($_api_collator, api_strtolower(api_utf8_encode($string2, $_api_encoding), 'UTF-8'), api_strtolower(api_utf8_encode($string1, $_api_encoding), 'UTF-8'));
	return $result === false ? 0 : $result;
}

// A reverse function from strnatcmp().
function _api_strnatrcmp($string1, $string2) {
	return strnatcmp($string2, $string1);
}

// A reverse function from strnatcasecmp().
function _api_strnatcasercmp($string1, $string2) {
	return strnatcasecmp($string2, $string1);
}

// A fuction that translates sorting flag constants from php core to correspondent constants from intl extension.
function _api_get_collator_sort_flag($sort_flag = SORT_REGULAR) {
	switch ($sort_flag) {
		case SORT_STRING:
		case SORT_SORT_LOCALE_STRING:
			return Collator::SORT_STRING;
		case SORT_NUMERIC:
			return Collator::SORT_NUMERIC;
	}
	return Collator::SORT_REGULAR;
}


/**
 * ----------------------------------------------------------------------------
 * Upgrading the PHP5 mbstring extension
 * ----------------------------------------------------------------------------
 */

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