<?php
/**
 * ==============================================================================
 * File: internationalization.lib.php
 * Main API extension library for Dokeos 1.8.6+ LMS
 * A library implementing internationalization related functions.
 * License: GNU/GPL version 2 or later (Free Software Foundation)
 * @author: Ivan Tcholakov, ivantcholakov@gmail.com
 * August 2009 - initial implementation.
 * @package dokeos.library
 * ==============================================================================
 */


/**
 * ----------------------------------------------------------------------------
 * Constants
 * ----------------------------------------------------------------------------
 */

// Predefined date formats in Dokeos provided by the language sub-system.
// To be used as a parameter for the function api_format_date().
define('TIME_NO_SEC_FORMAT', 0);	// 15:23
define('DATE_FORMAT_SHORT', 1);		// 25.08.2009
define('DATE_FORMAT_LONG', 2);		// Aug 25, 09
define('DATE_TIME_FORMAT_LONG', 3);	// August 25, 2009 at 03:28 PM


/**
 * ----------------------------------------------------------------------------
 * Date and time formats
 * ----------------------------------------------------------------------------
 */

/**
 * Returns formated date/time format correspondent to a given language.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Christophe Gesche<gesche@ipm.ucl.ac.be>
 *         originally inspired from from PhpMyAdmin
 * @author Ivan Tcholakov, 2009, code refactoring, adding support for predefined date/time formats.
 * @param string/int $date_format		The date pattern. See the php-manual about the function strftime().
 * Note: For $date_format the following integer constants may be used for using predefined date/time
 * formats in the Dokeos system: TIME_NO_SEC_FORMAT, DATE_FORMAT_SHORT, DATE_FORMAT_LONG, DATE_TIME_FORMAT_LONG.
 * @param int $time_stamp (optional)	Time as an integer value. The default value -1 means now, the function time() is called internally.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns the formatted date.
 * @link http://php.net/manual/en/function.strftime.php
 */
function api_format_date($date_format, $time_stamp = -1, $language = null) {
	if ($time_stamp == -1) {
		$time_stamp = time();
	}
	if (is_int($date_format)) {
		switch ($date_format) {
			case TIME_NO_SEC_FORMAT:
				$date_format = get_lang('timeNoSecFormat', '', $language);
				break;
			case DATE_FORMAT_SHORT:
				$date_format = get_lang('dateFormatShort', '', $language);
				break;
			case DATE_FORMAT_LONG:
				$date_format = get_lang('dateFormatShort', '', $language);
				break;
			case DATE_TIME_FORMAT_LONG:
				$date_format = get_lang('dateTimeFormatLong', '', $language);
				break;
			default:
				$date_format = get_lang('dateTimeFormatLong', '', $language);
		}
	}
	// We replace %a %A %b %B masks of date format with translated strings.
	$translated = &_api_get_day_month_names($language);
	$date_format = str_replace(array('%A', '%a', '%B', '%b'), 
		array($translated['days_long'][(int)strftime('%w', $time_stamp)],
			$translated['days_short'][(int)strftime('%w', $time_stamp)],
			$translated['months_long'][(int)strftime('%m', $time_stamp) - 1],
			$translated['months_short'][(int)strftime('%m', $time_stamp) - 1]),
		$date_format);
	return strftime($date_format, $time_stamp);
}

/**
 * Returns an array of translated week days in short names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of week days (short names).
 * Example: api_get_week_days_short('english') means array('Sun', 'Mon', ... 'Sat').
 */
function api_get_week_days_short($language = null) {
	$days = &_api_get_day_month_names($language);
	return $days['days_short'];
}

/**
 * Returns an array of translated week days.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of week days.
 * Example: api_get_week_days_long('english') means array('Sunday, 'Monday', ... 'Saturday').
 */
function api_get_week_days_long($language = null) {
	$days = &_api_get_day_month_names($language);
	return $days['days_long'];
}

/**
 * Returns an array of translated months in short names.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of months (short names).
 * Example: api_get_months_short('english') means array('Jan', 'Feb', ... 'Dec').
 */
function api_get_months_short($language = null) {
	$months = &_api_get_day_month_names($language);
	return $months['months_short'];
}

/**
 * Returns an array of translated months.
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string						Returns an array of months.
 * Example: api_get_months_long('english') means array('January, 'February' ... 'December').
 */
function api_get_months_long($language = null) {
	$months = &_api_get_day_month_names($language);
	return $months['months_long'];
}


/**
 * ----------------------------------------------------------------------------
 * Functions for internal use behind this API.
 * ----------------------------------------------------------------------------
 */

require_once dirname(__FILE__).'/internationalization_internal.lib.php';
