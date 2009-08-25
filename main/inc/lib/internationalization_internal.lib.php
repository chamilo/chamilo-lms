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
 * Appendix to "Date and time formats"
 * ----------------------------------------------------------------------------
 */

/**
 * Returns an array of translated week days and months, short and normal names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns a multidimensional array with translated week days and months.
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
