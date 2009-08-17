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

// Reads case folding properties about a given character from a file-based "database".
// For internal use in this API only.
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