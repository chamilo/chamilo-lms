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

// Formatting person's name.
define('PERSON_NAME_COMMON_CONVENTION', 0);	// Formatting person's name using the pattern as it has been
											// configured in the internationalization database for every language.
											// This (default) option would be the most used.
// The followind options may be used in limited number of places for overriding the common convention:
define('PERSON_NAME_WESTERN_ORDER', 1);		// Formatting person's name in Western order: first_name last_name
define('PERSON_NAME_EASTERN_ORDER', 2);		// Formatting person's name in Eastern order: last_name first_name
define('PERSON_NAME_LIBRARY_ORDER', 3);		// Formatting person's name in library order: last_name, first_name

/**
 * ----------------------------------------------------------------------------
 * Language support
 * ----------------------------------------------------------------------------
 */

/**
 * Whenever the server type in the Dokeos Config settings is
 * @param string $variable		This is the identificator (name) of the translated string to be retrieved.
 * @param string $notrans		This parameter directs whether a link to DLTT to be shown for untranslated strings
 * 								($notrans = 'DLTT' means "yes", any other value means "no").
 * @param string $language (optional)	Language indentificator. If it is omited, the current interface language is assumed.
 * @return string				Returns the requested string in the correspondent language.
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @author Ivan Tcholakov, April-August 2009 (caching functionality, additional parameter $language).
 *
 * Notes:
 * 1. If the name of a given language variable has the prefix "lang" it may be omited,
 * i.e. get_lang('langYes') == get_lang('Yes').
 * 2. Whenever the server type in the Dokeos Config settings is set to
 * test/development server you will get an indication that a language variable
 * is not translated and a link to a suggestions form of DLTT.
 * 3. DLTT means Dokeos Language Translation Tool.
 * @link http://www.dokeos.com/DLTT/
 */
function get_lang($variable, $notrans = 'DLTT', $language = null) {

	// We introduced the possibility to request specific language
	// by the aditional parameter $language to this function.

	// By manipulating this global variable the translation
	// may be done in different languages too (not the elegant way).
	global $language_interface;

	// Because of possibility for manipulations of the global variable
	// $language_interface, we need its initial value.
	global $language_interface_initial_value;

	// This is a cache for already translated language variables.
	// By using it we will avoid repetitive translations.
	static $cache = array();

	// Combining both ways for requesting specific language.
	if (empty($language)) {
		$language = $language_interface;
	}

	// This is a flag for showing the link to the Dokeos Language Translation Tool
	// when the requested variable has no translation within the language files.
	$dltt = $notrans == 'DLTT' ? true : false;

	// Cache initialization.
	if (!is_array($cache[$language])) {
		$cache[$language] = array(false => array(), true => array());
	}

	// Looking up into the cache for existing translation.
	if (isset($cache[$language][$dltt][$variable])) {
		// There is a previously saved translation, returning it.
		return $cache[$language][$dltt][$variable];
	}

	// There is no saved translation, we have to extract it.

	// If the language files have been reloaded, then the language
	// variables should be accessed as local ones.
	$seek_local_variables = false;

	// We reload the language variables when the requested language is different to
	// the language of the interface or when the server is in testing mode.
	if ($language != $language_interface_initial_value || api_get_setting('server_type') == 'test') {
		$seek_local_variables = true;
		global $language_files;
		$langpath = api_get_path(SYS_CODE_PATH).'lang/';

		if (isset ($language_files)) {
			if (!is_array($language_files)) {
				@include $langpath.$language.'/'.$language_files.'.inc.php';
			} else {
				foreach ($language_files as $index => $language_file) {
					@include $langpath.$language.'/'.$language_file.'.inc.php';
				}
			}
		}
	}

	$ot = '[='; //opening tag for missing vars
	$ct = '=]'; //closing tag for missing vars
	if (api_get_setting('hide_dltt_markup') == 'true') {
		$ot = '';
		$ct = '';
	}

	// Translation mode for production servers.

	if (api_get_setting('server_type') != 'test') {
		if (!$seek_local_variables) {
			$lvv = isset ($GLOBALS['lang'.$variable]) ? $GLOBALS['lang'.$variable] : (isset ($GLOBALS[$variable]) ? $GLOBALS[$variable] : $ot.$variable.$ct);
		} else {
			@eval('$lvv = $'.$variable.';');
			if (!isset($lvv)) {
				@eval('$lvv = $lang'.$variable.';');
				if (!isset($lvv)) {
					$lvv = $ot.$variable.$ct;
				}
			}
		}
		if (!is_string($lvv)) {
			$cache[$language][$dltt][$variable] = $lvv;
			return $lvv;
		}
		$lvv = str_replace("\\'", "'", $lvv);
		$lvv = _get_lang_purify($lvv, $language);
		$cache[$language][$dltt][$variable] = $lvv;
		return $lvv;
	}

	// Translation mode for test/development servers.

	if (!is_string($variable)) {
		$cache[$language][$dltt][$variable] = $ot.'get_lang(?)'.$ct;
		return $cache[$language][$dltt][$variable];
	}
	@ eval ('$langvar = $'.$variable.';'); // Note (RH): $$var doesn't work with arrays, see PHP doc
	if (isset ($langvar) && is_string($langvar) && strlen($langvar) > 0) {
		$langvar = str_replace("\\'", "'", $langvar);
		$langvar = _get_lang_purify($langvar, $language);
		$cache[$language][$dltt][$variable] = $langvar;
		return $langvar;
	}
	@ eval ('$langvar = $lang'.$variable.';');
	if (isset ($langvar) && is_string($langvar) && strlen($langvar) > 0) {
		$langvar = str_replace("\\'", "'", $langvar);
		$langvar = _get_lang_purify($langvar, $language);
		$cache[$language][$dltt][$variable] = $langvar;
		return $langvar;
	}
	if (!$dltt) {
		$cache[$language][$dltt][$variable] = $ot.$variable.$ct;
		return $cache[$language][$dltt][$variable];
	}
	if (!is_array($language_files)) {
		$language_file = $language_files;
	} else {
		$language_file = implode('.inc.php', $language_files);
	}
	$cache[$language][$dltt][$variable] =
		$ot.$variable.$ct."<a href=\"http://www.dokeos.com/DLTT/suggestion.php?file=".$language_file.".inc.php&amp;variable=$".$variable."&amp;language=".$language_interface."\" target=\"_blank\" style=\"color:#FF0000\"><strong>#</strong></a>";
	return $cache[$language][$dltt][$variable];
}

/**
 * Gets the current interface language.
 * @param bool $purified (optional)	When it is true, a purified (refined) language value will be returned, for example 'french' instead of 'french_unicode'.
 * @return string					The current language of the interface.
 */
function api_get_interface_language($purified = false) {
	global 	$language_interface;
	if (empty($language_interface)) {
		return 'english';
	}
	if ($purified) {
		return api_refine_language_id($language_interface);
	}
	return $language_interface;
}


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
 * Note: For all languges returned days are in the English order.
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
 * Note: For all languges returned days are in the English order.
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
 * Name order conventions
 * ----------------------------------------------------------------------------
 */

/**
 * Builds a person (full) name depending on the convention for a given language.
 * @param string $first_name			The first name of the preson.
 * @param string $last_name				The last name of the person.
 * @param string $title					The title of the person.
 * @param int/string $format (optional)	The person name format. It may be a pattern-string (for example '%t. %l, %f') or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)	The language indentificator. If it is omited, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @return bool							The result is sort of full name of the person.
 * Sample results:
 * Peter Ustinoff or Dr. Peter Ustinoff     - the Western order
 * Ustinoff Peter or Dr. Ustinoff Peter     - the Eastern order
 * Ustinoff, Peter or - Dr. Ustinoff, Peter - the library order
 * Note: See the file dokeos/main/inc/lib/internationalization_database/name_order_conventions.php where you can revise the convention for your language.
 * @author Carlos Vargas <carlos.vargas@dokeos.com> - initial implementation.
 * @author Ivan Tcholakov
 */
function api_get_person_name($first_name, $last_name, $title = null, $format = null, $language = null) {
	static $valid = array();
	if (empty($format)) {
		$format = PERSON_NAME_COMMON_CONVENTION;
	}
	if (empty($language)) {
		$language = api_get_interface_language();
	}
	if (!isset($valid[$format][$language])) {
		if (is_int($format)) {
			switch ($format) {
				case PERSON_NAME_COMMON_CONVENTION:
					$valid[$format][$language] = _api_get_person_name_convention($language, 'format');
					break;
				case PERSON_NAME_WESTERN_ORDER:
					$valid[$format][$language] = '%t %f %l';
					break;
				case PERSON_NAME_EASTERN_ORDER:
					$valid[$format][$language] = '%t %l %f';
					break;
				case PERSON_NAME_LIBRARY_ORDER:
					$valid[$format][$language] = '%t %l, %f';
					break;
				default:
					$valid[$format][$language] = '%t %f %l';
			}
		} else {
			$valid[$format][$language] = _api_validate_person_name_format($format);
		}
	}
	return _api_clean_person_name(str_replace(array('%f', '%l', '%t'), array($first_name, $last_name, $title), $valid[$format][$language]));
}

/**
 * Checks whether a given format represents person name in Western order (for which first name is first).
* @param int/string $format (optional)	The person name format. It may be a pattern-string (for example '%t. %l, %f') or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)	The language indentificator. If it is omited, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @return bool							The result TRUE means that the order is first_name last_name, FALSE means last_name first_name.
 * Note: You may use this function for determing the order of the fields or columns "First name" and "Last name" in forms, tables and reports.
 * @author Ivan Tcholakov
 */
function api_is_western_name_order($format = null, $language = null) {
	static $order = array();
	if (empty($format)) {
		$format = PERSON_NAME_COMMON_CONVENTION;
	}
	if (empty($language)) {
		$language = api_get_interface_language();
	}
	if (!isset($order[$format][$language])) {
		$test_name = api_get_person_name('%f', '%l', '%t', $format, $language);
		$order[$format][$language] = strpos($test_name, '%f') <= strpos($test_name, '%l');
	}
	return $order[$format][$language];
}

/**
 * Returns a directive for sorting person names depending on a given language and based on the options in the internationalization "database".
 * @param string $language (optional)	The input language. If it is omited, the current interface language is assumed.
 * @return bool							Returns boolean value. TRUE means ORDER BY first_name, last_name; FALSE means ORDER BY last_name, first_name.
 * Note: You may use this function:
 * 2. for constructing the ORDER clause of SQL queries, related to first_name and last_name;
 * 3. for adjusting php-implemented sorting in tables and reports.
 * @author Ivan Tcholakov
 */
function api_sort_by_first_name($language = null) {
	static $sort_by_first_name = array();
	if (empty($language)) {
		$language = api_get_interface_language();
	}
	if (!isset($sort_by_first_name[$language])) {
		$sort_by_first_name[$language] = _api_get_person_name_convention($language, 'sort_by');
	}
	return $sort_by_first_name[$language];
}


/**
 * ----------------------------------------------------------------------------
 * Functions for internal use behind this API.
 * ----------------------------------------------------------------------------
 */

require_once dirname(__FILE__).'/internationalization_internal.lib.php';
