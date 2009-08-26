<?php
/**
 * ==============================================================================
 * File: internationalization_internal.lib.php
 * Main API extension library for Dokeos 1.8.6+ LMS,
 * contains functions for internal use only.
 * License: GNU/GPL version 2 or later (Free Software Foundation)
 * @author: Ivan Tcholakov, ivantcholakov@gmail.com, 2009
 * @package dokeos.library
 * ==============================================================================
 *
 * Note: All functions and data structures here are not to be used directly.
 * See the file internationalization.lib.php which contains the "public" API.
 */


/**
 * ----------------------------------------------------------------------------
 * Appendix to "Language support"
 * ----------------------------------------------------------------------------
 */

/**
 * Upgrades the function get_lang() with the following logic:
 * 1. Checks whether the retrieved human language string is UTF-8 valid or not.
 * 2. If the system encoding is UTF-8 and the string is not UTF-8, the function
 * performs conversion from supposed non UTF-8 encodeng.
 * 3. If the system encoding is non UTF-8 but the string is valid UTF-8, then
 * conversion from UTF-8 is performed.
 * 4. At the end the string is purified from HTML entities.
 * @param string $string	This is the retrieved human language string.
 * @param string $language	A language identiticator.
 * @return string			Returns the human language string, checked for proper encoding and purified.
 */
function & _get_lang_purify(& $string, & $language) {
	$system_encoding = api_get_system_encoding();
	if (api_is_utf8($system_encoding)) {
		if (!api_is_valid_utf8($string)) {
			$string = api_utf8_encode($string, api_get_non_utf8_encoding($language));
		}
	} else {
		if (api_is_valid_utf8($string)) {
			$string = api_utf8_decode($string, $system_encoding);
		}
	}
	return api_html_entity_decode($string, ENT_QUOTES, $system_encoding);
}


/**
 * ----------------------------------------------------------------------------
 * Appendix to "Date and time formats"
 * ----------------------------------------------------------------------------
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
 * ----------------------------------------------------------------------------
 * Appendix to "Name order conventions"
 * ----------------------------------------------------------------------------
 */

/**
 * Returns an array of conventions (patterns) of writting personal names for all "known" languages.
 * @return array	Returns the array in the following form: attay('language1 => 'pattern1', ...).
 * @link http://en.wikipedia.org/wiki/Personal_name#Naming_convention
 */
function &_get_name_conventions() {
	static $conventions;
	if (!isset($conventions)) {
		$file = dirname(__FILE__) . '/internationalization_database/name_order_conventions.php';
		if (file_exists($file)) {
			$conventions = include ($file);
		} else {
			$conventions = array('english' => 'first_name last_name');
		}
		$search = array('first_name', 'last_name');
		$replacement = array('%f', '%l');
		foreach ($conventions as $key => &$value) {
			$value = str_ireplace($search, $replacement, $value);
		}
	}
	return $conventions;
}

/**
 * Checks whether the input namin convention (a pattern) is valid or not.
 * @param string $convention	The input convention to be verified. 
 * @return bool					Returns TRUE if the pattern is valid, FALSE othewise.
 */
function _api_is_valid_name_convention($convention) {
	static $cache = array();
	if (!isset($cache[$convention])) {
		$cache[$convention] = !empty($convention) && strpos($convention, '%f') !== false && strpos($convention, '%l') !== false;
	}
	return $cache[$convention];
}
