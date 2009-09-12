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
 * Returns returns person name convention for a given language.
 * @param string $language	The input language.
 * @param string $type		The type of the requested convention. It may be 'format' for name order convention or 'sort_by' for name sorting convention.
 * @return mixed			Depending of the requested type, the returned result may be string or boolean; null is returned on error;
 */
function _api_get_person_name_convention($language, $type) {
	static $conventions;
	$language = api_refine_language_id($language);
	if (!isset($conventions)) {
		$file = dirname(__FILE__) . '/internationalization_database/name_order_conventions.php';
		if (file_exists($file)) {
			$conventions = include ($file);
		} else {
			$conventions = array('english' => array('format' => 'title first_name last_name', 'sort_by' => 'first_name'));
		}
		$search = array('first_name', 'last_name', 'title');
		$replacement = array('%f', '%l', '%t');
		foreach (array_keys($conventions) as $key) {
			$conventions[$key]['format'] = _api_validate_person_name_format(_api_clean_person_name(str_replace('%', ' %', str_ireplace($search, $replacement, $conventions[$key]['format']))));
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
	if (empty($format) || strpos($format, '%f') === false || strpos($format, '%l') === false) {
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
