<?php

// File: multibyte_string_functions.lib.php
// Main API extension for Dokeos 1.8.6 LMS
// A common purpose library for supporting multibyte string aware functions.
// This library requires PHP mbstring extension to be activated.
// When encodings to be used are not supported by mbstring, this library
// is able to exploit the PHP iconv extesion, which in this case should
// activated too.
// License: GNU/GPL version 2 or later (Free Software Foundation)
// Author: Ivan Tcholakov, ivantcholakov@gmail.com
// October 2008.
// May 2009 - refactoring and minor fixes have been implemented.


//----------------------------------------------------------------------------
// Multibyte string conversion functions
//----------------------------------------------------------------------------


// Converts character encoding of a given string.
// See http://php.net/manual/en/function.mb-convert-encoding
function api_convert_encoding($string, $to_encoding, $from_encoding) {
	if (api_equal_encodings($to_encoding, $from_encoding)) {
		return $string;
	}
	if (api_mb_supports($to_encoding) && api_mb_supports($from_encoding)) {
		return @mb_convert_encoding($string, $to_encoding, $from_encoding);
	}
	elseif (api_iconv_supports($to_encoding) && api_iconv_supports($from_encoding)) {
		return @iconv($from_encoding, $to_encoding, $string);
	} 
	return $string;
}

// Converts a given string into UTF-8 encoded string.
// See http://php.net/manual/en/function.utf8-encode
function api_utf8_encode($string, $from_encoding = null) {
	if (empty($from_encoding)) {
		$from_encoding = api_mb_internal_encoding();
	}
	if (api_is_utf8($from_encoding)) {
		return $string;
	}
	if (api_mb_supports($from_encoding)) {
		return @mb_convert_encoding($string, 'UTF-8', $from_encoding);
	}
	elseif (api_iconv_supports($from_encoding)) {
		return @iconv($from_encoding, 'UTF-8', $string);
	}
	return $string;
}

// Converts a given string, from UTF-8 encoding to a specified encoding.
// See http://php.net/manual/en/function.utf8-decode
function api_utf8_decode($string, $to_encoding = null) {
	if (empty($to_encoding)) {
		$to_encoding = api_mb_internal_encoding();
	}
	if (api_is_utf8($to_encoding)){
		return $string;
	}
	if (api_mb_supports($to_encoding)) {
		return @mb_convert_encoding($string, $to_encoding, 'UTF-8');
	}
	elseif (api_iconv_supports($to_encoding)) {
		return @iconv('UTF-8', $to_encoding, $string);
	}
	return $string;
}

// Encodes a given string into the system ecoding if this conversion has been detected as necessary.
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

// Converts all applicable characters to HTML entities.
// See http://php.net/manual/en/function.htmlentities
function api_htmlentities($string, $quote_style = ENT_COMPAT, $encoding = 'ISO-8859-15') {
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

// Decodes HTML entities into normal characters.
// See http://php.net/html_entity_decode
function api_html_entity_decode($string, $quote_style = ENT_COMPAT, $encoding = 'ISO-8859-15') {
	if (!api_is_utf8($encoding) && api_html_entity_supports($encoding)) {
		return html_entity_decode($string, $quote_style, $encoding);
	}
	if (!api_is_encoding_supported($encoding)) {
		return $string;
	}
	return api_utf8_decode(html_entity_decode(api_convert_encoding($string, 'UTF-8', $encoding), $quote_style, 'UTF-8'), $encoding);
}


//----------------------------------------------------------------------------
//               Common multibyte string functions
//----------------------------------------------------------------------------

// Regular expression match with multibyte support.
// See http://php.net/manual/en/function.mb-ereg
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
			$regs = api_array_utf8_decode($regs, $encoding);
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

// Replace regular expression with multibyte support.
// See http://php.net/manual/en/function.mb-ereg-replace
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
function api_array_utf8_decode($variable, $encoding) {
	if (is_array($variable)) {
		return array_map('api_array_utf8_decode', $variable, $encoding);
	}
    if (is_string($var)) {
    	return api_utf8_decode($variable, $encoding);
    }
    return $variable;
}

// Regular expression match ignoring case with multibyte support.
// See http://php.net/manual/en/function.mb-eregi
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
			$regs = api_array_utf8_decode($regs, $encoding);
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

// Replace regular expression with multibyte support ignoring case.
// See http://php.net/manual/en/function.mb-eregi-replace
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

// This function returns a selected by position character of a string.
function api_get_character($string, $position, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	return api_substr($string, $position, 1, $encoding);
}

// This function returns an array containing all characters of a string.
function api_get_characters($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	return api_str_split($string, 1, $encoding);
}

// Makes a string's first character lowercase.
// See http://php.net/manual/en/function.lcfirst
function api_lcfirst($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
    return api_strtolower(api_substr($string, 0, 1, $encoding), $encoding) . api_substr($string, 1, api_strlen($string, $encoding), $encoding);
}

// Puts a prefix into a string.
// The input variables could be arrays too.
function api_prefix($string, $prefix) {
	if (is_array($string)) {
		if (is_array($prefix)) {
			return array_map('api_prefix', $string, $prefix);
		} else {
			return array_map('api_prefix', $string, array_fill(0 , count($string) , $prefix));
		}
	}
	if (is_array($prefix)) {
		$prefix = implode('', $prefix);
	}
	return $prefix.$string;
}

// Splits string into array by regular expression.
// See http://php.net/manual/en/function.mb-split
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
		$result = api_array_utf8_decode($result, $encoding);
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

// This is a multibyte replacement of str_ireplace().
// See http://php.net/manual/en/function.str-ireplace
// TODO: To be revised an to be checked.
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

// This is a multibyte replacement of str_split().
// See http://php.net/str_split
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

// This is a multibyte replacement of strcasecmp().
// See http://php.net/manual/en/function.strcasecmp
function api_strcasecmp($str1, $str2, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	return strcmp(api_strtolower($str1, $encoding), api_strtolower($str2, $encoding));
}

// This is a multibyte replacement of stripos().
// See http://php.net/manual/en/function.mb-stripos
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

// This is a multibyte replacement of stristr().
// See http://php.net/manual/en/function.mb-stristr
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

// Returns length of the input string.
// See http://php.net/manual/en/function.mb-strlen
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

// This is a multibyte replacement of strpos().
// See http://php.net/manual/en/function.mb-strpos
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

// This is a multibyte replacement of strrchr().
// See http://php.net/manual/en/function.mb-strrchr
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

// This is a multibyte replacement of strrev().
// See http://php.net/manual/en/function.strrev
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

// This is a multibyte replacement of strrpos().
// See http://php.net/manual/en/function.mb-strrpos
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

// This is a multibyte replacement of strstr().
// See http://php.net/manual/en/function.mb-strstr
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

// Makes a string lowercase.
// See http://php.net/manual/en/function.mb-strtolower
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

// Makes a string uppercase.
// See http://php.net/manual/en/function.mb-strtoupper
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

// Translates certain characters.
// See http://php.net/manual/en/function.strtr
// TODO: To be revised and tested.
// It would be good tihs function to be removed. I hesitate to do it right now.
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
		$arr_from = api_get_characters($from, $encoding);
		$arr_to = api_get_characters($to, $encoding);
		$n = count($arr_from);
		$n2 = count($arr_to);
		if ($n > $n2) $n = $n2;
		for ($i = 0; $i < $n; $i++) {
			$translator[$arr_from[$i]] = $arr_to[$i];
		}
	}
	$arr_string = api_get_characters($string, $encoding);
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

// Performs a multi-byte safe substr() operation based on number of characters.
// See http://bg.php.net/manual/en/function.mb-substr
function api_substr($string, $start, $length = null, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
	// Passing null as $length will mean 0. This behaviour have to be corrected.
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

// Puts a suffix into a string.
// The input variables could be arrays too.
function api_suffix($string, $suffix) {
	if (is_array($string)) {
		if (is_array($suffix)) {
			return array_map('api_suffix', $string, $suffix);
		} else {
			return array_map('api_suffix', $string, array_fill(0 , count($string) , $suffix));
		}
	}
	if (is_array($suffix)) {
		$suffix = implode('', $suffix);
	}
	return $string.$suffix;
}

// This is a multibyte replacement of substr_replace().
// See http://php.net/manual/function.substr-replace
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
			api_substr($string, 0, $start, $encoding) .$replacement .
			api_substr($string, $start + $length, api_strlen($string, $encoding), $encoding);
	}
}

// Returns a string with the first character capitalized, if that character is alphabetic.
// See http://php.net/manual/en/function.ucfirst
function api_ucfirst($string, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_mb_internal_encoding();
	}
   	return api_strtoupper(api_substr($string, 0, 1, $encoding), $encoding) . api_substr($string, 1, api_strlen($string, $encoding), $encoding);
}

// Uppercases the first character of each word in a string.
// See http://php.net/manual/en/function.ucwords
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

// This function adds a unicode modifier to a
// Perl-compatible regular expression when it is necessary.
function api_add_pcre_unicode_modifier($pcre, $encoding = null) {
	if (empty($encoding)){
		$encoding = api_get_system_encoding();
	}
	return api_is_utf8($encoding) ? $pcre.'u' : $pcre;
}


//----------------------------------------------------------------------------
// Encoding management functions
//----------------------------------------------------------------------------


// Returns the most-probably used non-UTF-8 encoding for the given language.
// The $language parameter must be cleaned by api_refine_language_id() if it
// is necessary.
// If the returned value is not as you expect, you may do the following:
// In the table $encodings below, correct the order of the encodings for your
// language, or if it is necessary - insert at the first place a new encoding.
function api_get_non_utf8_encoding($language = null) {
	if (empty($language)) {
		$language = api_refine_language_id(api_get_interface_language());
	}
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

// Returns a two-dimensional array of non-UTF-8 encodings for all system languages.
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
bosnian: WINDOWS-1250;
brazilian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
bulgarian: WINDOWS-1251;
catalan: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
croatian: WINDOWS-1250;
czech: WINDOWS-1250, ISO-8859-2;
danish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
dutch: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
english: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
esperanto: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
finnish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
french: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
galician: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
german: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
greek: ISO-8859-7, WINDOWS-1253;
hebrew: ISO-8859-8, WINDOWS-1255;
hungarian: ISO-8859-2;
indonesian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
italian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
japanese: EUC-JP, ISO-2022-JP;
korean: EUC-KR, CP949, ISO-2022-KR;
malay: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
norwegian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
persian: WINDOWS-1252;
polish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
portuguese: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
russian: WINDOWS-1251, KOI8-R;
serbian: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
simpl_chinese: GB2312, WINDOWS-936;
slovak: WINDOWS-1250;
slovenian: WINDOWS-1250;
spanish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
swahili: ISO-8859-1;
swedish: ISO-8859-15, WINDOWS-1252, ISO-8859-1;
thai: ISO-8859-11, WINDOWS-874;
trad_chinese: BIG-5, EUC-TW;
turkce: ISO-8859-9, WINDOWS-1254;
ukrainian: KOI8-U;
vietnamese: WINDOWS-1258;
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

// This function unifies internally the encoding identificators.
// It is to be adjusted in case of id comparison problems.
function api_refine_encoding_id($encoding) {
	return strtoupper($encoding);
}

// This function checks whether two $encoding are equal (same, equvalent).
function api_equal_encodings($encoding_1, $encoding_2) {
	// We have to deal with aliases. This function alone does not solve
	// the problem entirely. And there is no time for this kind of research.
	// At the momemnt, the quick proposition could be:
	return strcmp(api_refine_encoding_id($encoding_1), api_refine_encoding_id($encoding_2)) == 0 ? true : false;
}

// Returns true if the given encoding id means UTF-8, otherwise returns false.
function api_is_utf8($encoding) {
	return api_equal_encodings($encoding, 'UTF-8');
}

// Returns the encoding currently used by the system.
function api_get_system_encoding() {
	$system_encoding = api_get_setting('platform_charset');
	if (!empty($system_encoding)) {
		return $system_encoding;
	}
	global $charset;
	return empty($charset) ? 'ISO-8859-15' : $charset;
}

// Sets/Gets internal character encoding.
// See http://php.net/manual/en/function.mb-internal-encoding
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

// Sets/Gets current encoding for multibyte regex.
// See http://php.net/manual/en/function.mb-regex-encoding
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

// Retrieves internal configuration variables of iconv extension.
// The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
// See http://php.net/manual/en/function.iconv-get-encoding
function api_iconv_get_encoding($type) {
	return api_iconv_set_encoding($type);
}

// Sets current setting for character encoding conversion.
// The parameter $type could be: 'iconv_internal_encoding', 'iconv_input_encoding', or 'iconv_output_encoding'.
function api_iconv_set_encoding($type, $encoding = null) {
	static $iconv_internal_encoding = null;
	static $iconv_input_encoding = null;
	static $iconv_output_encoding = null;
	if (!api_iconv_present()) {
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

// Checks whether the specified encoding is supported by this API.
function api_is_encoding_supported($encoding) {
	return api_mb_supports($encoding) || api_iconv_supports($encoding);
}

// Checks whether the specified encoding is supported by mbstring library.
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

// Checks whether the specified non-UTF-8 encoding is supported by iconv library.
function api_iconv_supports($encoding) {
	static $supported = array();
	$encoding = api_refine_encoding_id($encoding);
	if (!isset($supported[$encoding])) {
		if (api_iconv_present()) {
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

// Checks whether the iconv library is installed and works.
function api_iconv_present() {
	static $iconv_present = null;
	if (!is_null($iconv_present)) {
		return $iconv_present;
	}
	// I don't want to spoil the code with ugly strings.
	$test_string = '';
	for ($i = 32; $i < 128; $i++) {
		$test_string .= chr($i);
	}
	$iconv_present = (function_exists('iconv') &&
		@iconv('UTF-16LE', 'ISO-8859-1',
			@iconv('ISO-8859-1', 'UTF-16LE', $test_string)) == $test_string) ? true : false;
	return $iconv_present;
}

// Checks whether the specified encoding is supported by html-entities operations.
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


// Returns true if the specified string is a valid UTF-8 one and false otherwise.
function api_is_valid_utf8($string) {

	//return @mb_detect_encoding($string, 'UTF-8', true) == 'UTF-8' ? true : false;
	// Ivan Tcholakov, 05-OCT-2008: I do not trust mb_detect_encoding(). I have
	// found a string with a single cyrillic letter (single byte), that is
	// wrongly detected as UTF-8. Possibly, there would be problems with other
	// languages too.
	//
	// To understand the following algorithm see http://en.wikipedia.org/wiki/UTF-8

	$len = strlen($string);
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

// Checks whether a string contains 7bit ASCII characters only.
function api_is_valid_ascii($string) {
	return @mb_detect_encoding($string, 'ASCII', true) == 'ASCII' ? true : false;
}


//----------------------------------------------------------------------------
// Language management functions
//----------------------------------------------------------------------------


// Returns a pure language id, without possible suffixes
// that will disturb language identification in certain cases.
function api_refine_language_id($language) {
	return (
		str_replace('_unicode', '', strtolower(
		str_replace('_latin', '',
		str_replace('_corporate', '',
		str_replace('_org', '',
		str_replace('_KM', '', $language)))))));
}


//----------------------------------------------------------------------------
// Array functions
//----------------------------------------------------------------------------


// A case insensitive version of in_array() function.
// See http://php.net/manual/en/function.in-array.php
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
		if (api_strtolower($item, $encoding) == $needle) {
			return true;
		}
	}
	return false;
}


//----------------------------------------------------------------------------
// Multibyte string functions designed to upgrade the PHP5 mbstring extension
//----------------------------------------------------------------------------


// ---------- Multibyte string functions implemented in PHP 5.2.0+ -----------

// This is a multibyte replacement of strchr().
// This function exists in PHP 5 >= 5.2.0
// See http://php.net/manual/en/function.mb-strrchr
if (!function_exists('mb_strchr')) {
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
if (!function_exists('mb_stripos')) {
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
if (!function_exists('mb_stristr')) {
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
if (!function_exists('mb_strrchr')) {
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
if (!function_exists('mb_strstr')) {
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
