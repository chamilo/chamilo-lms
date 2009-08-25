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
 * Functions for internal use behind this API.
 * ----------------------------------------------------------------------------
 */

require_once dirname(__FILE__).'/internationalization_internal.lib.php';
