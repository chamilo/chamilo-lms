<?php
/* For licensing terms, see /license.txt */

use Patchwork\Utf8;

/**
 * File: internationalization.lib.php
 * Internationalization library for Chamilo 1.x LMS
 * A library implementing internationalization related functions.
 * License: GNU General Public License Version 3 (Free Software Foundation)ww
 * @author Ivan Tcholakov, <ivantcholakov@gmail.com>, 2009, 2010
 * @author More authors, mentioned in the correpsonding fragments of this source.
 * @package chamilo.library
 */

/**
 * Constants
 */

// Special tags for marking untranslated variables.
define('SPECIAL_OPENING_TAG', '[=');
define('SPECIAL_CLOSING_TAG', '=]');

// Predefined date formats in Chamilo provided by the language sub-system.
// To be used as a parameter for the function api_format_date()

define('TIME_NO_SEC_FORMAT', 0); // 15:23
define('DATE_FORMAT_SHORT', 1); // Aug 25, 09
define('DATE_FORMAT_LONG', 2); // Monday August 25, 09
define('DATE_FORMAT_LONG_NO_DAY', 10); // August 25, 2009
define('DATE_TIME_FORMAT_LONG', 3); // Monday August 25, 2009 at 03:28 PM

define('DATE_FORMAT_NUMBER', 4); // 25.08.09
define('DATE_TIME_FORMAT_LONG_24H', 5); // August 25, 2009 at 15:28
define('DATE_TIME_FORMAT_SHORT', 6); // Aug 25, 2009 at 03:28 PM
define('DATE_TIME_FORMAT_SHORT_TIME_FIRST', 7); // 03:28 PM, Aug 25 2009
define('DATE_FORMAT_NUMBER_NO_YEAR', 8); // 25.08 dd-mm
define('DATE_FORMAT_ONLY_DAYNAME', 9); // Monday, Sunday, etc

// Formatting person's name.
// Formatting a person's name using the pattern as it has been
// configured in the internationalization database for every language.
// This (default) option would be the most used.
define('PERSON_NAME_COMMON_CONVENTION', 0);
// The following options may be used in limited number of places for overriding the common convention:

// Formatting a person's name in Western order: first_name last_name
define('PERSON_NAME_WESTERN_ORDER', 1);
// Formatting a person's name in Eastern order: last_name first_name
define('PERSON_NAME_EASTERN_ORDER', 2);
// Contextual: formatting person's name in library order: last_name, first_name
define('PERSON_NAME_LIBRARY_ORDER', 3);
// Contextual: formatting a person's name assotiated with an email-address.
// Ivan: I am not sure how seems email servers an clients would interpret name order, so I assign the Western order.
define('PERSON_NAME_EMAIL_ADDRESS', PERSON_NAME_WESTERN_ORDER);
// Contextual: formatting a person's name for data-exporting operations.
// For backward compatibility this format has been set to Eastern order.
define('PERSON_NAME_DATA_EXPORT', PERSON_NAME_EASTERN_ORDER);

/**
 * Returns a translated (localized) string, called by its identificator.
 * @param string $variable This is the identificator (name) of the translated string to be retrieved.
 * @param string $reserved This parameter has been reserved for future use.
 * @param string $language (optional)    Language indentificator. If it is omited, the current interface language is assumed.
 *
 * @return string                        Returns the requested string in the correspondent language.
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @author Ivan Tcholakov, 2009-2010 (caching functionality, additional parameter $language, other adaptations).
 *
 * Notes:
 * 1. If the name of a given language variable has the prefix "lang" it may be omited, i.e. get_lang('Yes') == get_lang('Yes').
 * 2. Untranslated variables might be indicated by special opening and closing tags  -  [=  =]
 * The special tags do not show up in these two cases:
 * - when the system has been switched to "production server mode";
 * - when a special platform setting 'hide_dltt_markup' is set to "true" (the name of this setting comes from history);
 * 3. Translations are created many contributors through using a special tool: Chamilo Translation Application.
 * @link http://translate.chamilo.org/
 */
function get_lang($variable, $reserved = null, $language = null) {
    global
        // For serving some old hacks:
        // By manipulating this global variable the translation may be done in different languages too (not the elegant way).
        $language_interface,
        // Because of possibility for manipulations of the global variable $language_interface, we need its initial value.
        $language_interface_initial_value;

    global $used_lang_vars, $_configuration;
    // add language_measure_frequency to your main/inc/conf/configuration.php in order to generate language
    // variables frequency measurements (you can then see them trhough main/cron/lang/langstats.php)
    // The $langstats object is instanciated at the end of main/inc/global.inc.php
    if (isset($_configuration['language_measure_frequency']) &&
        $_configuration['language_measure_frequency'] == 1
    ) {
        require_once api_get_path(SYS_CODE_PATH).'/cron/lang/langstats.class.php';
        global $langstats;
        $langstats->add_use($variable, '');
    }

    if (!isset($used_lang_vars)) {
        $used_lang_vars = array();
    }

    // Caching results from some API functions, for speed.
    static $initialized, $show_special_markup;
    if (!isset($initialized)) {
        $test_server_mode = api_get_setting('server_type') === 'test';
        $show_special_markup = api_get_setting('hide_dltt_markup') != 'true' || $test_server_mode;
        $initialized = true;
    }

    // Combining both ways for requesting specific language.
    if (empty($language)) {
        $language = $language_interface;
    }
    $lang_postfix = isset($is_interface_language) && $is_interface_language ? '' : '('.$language.')';

    $is_interface_language = $language == $language_interface_initial_value;

    // This is a cache for already translated language variables. By using it, we avoid repetitive translations, gaining speed.
    static $cache;

    // Looking up into the cache for existing translation.
    if (isset($cache[$language][$variable])) {
        // There is a previously saved translation, returning it.
        //return $cache[$language][$variable];
        $ret = $cache[$language][$variable];
        $used_lang_vars[$variable.$lang_postfix] = $ret;
        return $ret;
    }

    // There is no cached translation, we have to retrieve it:
    // - from a global variable (the faster way) - on production server mode;
    // - from a local variable after reloading the language files - on test server mode or when requested language
    // is different than the genuine interface language.
    $read_global_variables = $is_interface_language;

    if ($read_global_variables) {
        if (isset($GLOBALS[$variable])) {
            $langvar = $GLOBALS[$variable];
        } elseif (isset($GLOBALS["lang$variable"])) {
            $langvar = $GLOBALS["lang$variable"];
        } else {
            $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
        }
    }
    if (empty($langvar) || !is_string($langvar)) {
        $langvar = $show_special_markup ? SPECIAL_OPENING_TAG.$variable.SPECIAL_CLOSING_TAG : $variable;
    }
    $ret = $cache[$language][$variable] = $langvar;
    $used_lang_vars[$variable.$lang_postfix] = $ret;

    return $ret;
}

/**
 * Gets the current interface language.
 * @param bool $purified (optional)    When it is true, a purified (refined)
 * language value will be returned, for example 'french' instead of 'french_unicode'.
 * @param bool $setParentLanguageName
 * @return string                    The current language of the interface.
 */
function api_get_interface_language(
    $purified = false,
    $check_sub_language = false,
    $setParentLanguageName = true
) {
    global $language_interface;

    if (empty($language_interface)) {
        return 'english';
    }

    if ($check_sub_language) {
        static $parent_language_name = null;

        if (!isset($parent_language_name)) {
            // 2. The current language is a sub language so we grab the father's
            // setting according to the internalization_database/name_order_convetions.php file
            $language_id = api_get_language_id($language_interface);
            $language_info = api_get_language_info($language_id);

            if (!empty($language_id) &&
                !empty($language_info)
            ) {
                if (!empty($language_info['parent_id'])) {
                    $language_info = api_get_language_info($language_info['parent_id']);
                    if ($setParentLanguageName) {
                        $parent_language_name = $language_info['english_name'];
                    }

                    if (!empty($parent_language_name)) {
                        return $parent_language_name;
                    }
                }

                return $language_info['english_name'];
            }

            return 'english';
        } else {
            return $parent_language_name;
        }
    } else {
        // 2. Normal way
        $interface_language = $purified ? api_purify_language_id($language_interface) : $language_interface;
    }

    return $interface_language;
}

/**
 * Returns a purified language id, without possible suffixes that will disturb language identification in certain cases.
 * @param string $language The input language identificator, for example 'french_unicode'.
 * @param string            The same purified or filtered language identificator, for example 'french'.
 * @return string
 */
function api_purify_language_id($language)
{
    static $purified = array();
    if (!isset($purified[$language])) {
        $purified[$language] = trim(
            str_replace(
                array('_unicode', '_latin', '_corporate', '_org', '_km'),
                '',
                strtolower($language)
            )
        );
    }
    return $purified[$language];
}

/**
 * Gets language isocode column from the language table, taking the given language as a query parameter.
 * @param string $language This is the name of the folder containing translations for the corresponding language (e.g arabic, english).
 * @param string $default_code This is the value to be returned if there was no code found corresponding to the given language.
 * If $language is omitted, interface language is assumed then.
 * @return string The found isocode or null on error.
 * Returned codes are according to the following standards (in order of preference):
 * -  ISO 639-1 : Alpha-2 code (two-letters code - en, fr, es, ...)
 * -  RFC 4646  : five-letter code based on the ISO 639 two-letter language codes
 *    and the ISO 3166 two-letter territory codes (pt-BR, ...)
 * -  ISO 639-2 : Alpha-3 code (three-letters code - ast, fur, ...)
 */
function api_get_language_isocode($language = null, $default_code = 'en')
{
    static $iso_code = array();
    if (empty($language)) {
        $language = api_get_interface_language(false, true);
    }
    if (!isset($iso_code[$language])) {
        if (!class_exists('Database')) {
            // This might happen, in case of calling this function early during the global initialization.
            return $default_code; // This might happen, in case of calling this function early during the global initialization.
        }
        $sql = "SELECT isocode 
                FROM ".Database::get_main_table(TABLE_MAIN_LANGUAGE)." 
                WHERE dokeos_folder = '$language'";
        $sql_result = Database::query($sql);
        if (Database::num_rows($sql_result)) {
            $result = Database::fetch_array($sql_result);
            $iso_code[$language] = trim($result['isocode']);
        } else {
            $language_purified_id = api_purify_language_id($language);
            $iso_code[$language] = isset($iso_code[$language_purified_id]) ? $iso_code[$language_purified_id] : null;
        }
        if (empty($iso_code[$language])) {
            $iso_code[$language] = $default_code;
        }
    }

    return $iso_code[$language];
}

/**
 * Gets language iso code column from the language table
 *
 * @return array    An array with the current isocodes
 *
 * */
function api_get_platform_isocodes()
{
    $iso_code = array();
    $sql = "SELECT isocode 
            FROM ".Database::get_main_table(TABLE_MAIN_LANGUAGE)." 
            ORDER BY isocode ";
    $sql_result = Database::query($sql);
    if (Database::num_rows($sql_result)) {
        while ($row = Database::fetch_array($sql_result)) {
            $iso_code[] = trim($row['isocode']);
        }
    }
    return $iso_code;
}

/**
 * Gets text direction according to the given language.
 * @param string $language This is the name of the
 * folder containing translations for the corresponding language (e.g 'arabic', 'english', ...).
 * ISO-codes are acceptable too ('ar', 'en', ...).
 * If $language is omitted, interface language is assumed then.
 * @return string   The correspondent to the language text direction ('ltr' or 'rtl').
 */
function api_get_text_direction($language = null)
{
    static $text_direction = array();

    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($text_direction[$language])) {
        $text_direction[$language] = in_array(
            api_purify_language_id($language),
            array(
                'arabic',
                'ar',
                'dari',
                'prs',
                'hebrew',
                'he',
                'iw',
                'pashto',
                'ps',
                'persian',
                'fa',
                'ur',
                'yiddish',
                'yid'
            )
        ) ? 'rtl' : 'ltr';
    }

    return $text_direction[$language];
}

/**
 * Returns an alphabetized list of timezones in an associative array
 * that can be used to populate a select
 *
 * @return array List of timezone identifiers
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_timezones()
{
    $timezone_identifiers = DateTimeZone::listIdentifiers();
    sort($timezone_identifiers);
    $out = array();
    foreach ($timezone_identifiers as $tz) {
        $out[$tz] = $tz;
    }
    $null_option = array('' => '');
    $result = array_merge($null_option, $out);

    return $result;
}

/**
 * Returns the timezone to be converted to/from, based on user or admin preferences
 *
 * @return string The timezone chosen
 */
function api_get_timezone()
{
    // First, get the default timezone of the server
    $to_timezone = date_default_timezone_get();
    // Second, see if a timezone has been chosen for the platform
    $timezone_value = api_get_setting('timezone_value', 'timezones');

    if ($timezone_value != null) {
        $to_timezone = $timezone_value;
    }
    // If allowed by the administrator
    $use_users_timezone = api_get_setting('use_users_timezone', 'timezones');

    if ($use_users_timezone === 'true') {
        $userId = api_get_user_id();
        // Get the timezone based on user preference, if it exists
        $timezone_user = UserManager::get_extra_user_data_by_field($userId, 'timezone');
        if (isset($timezone_user['timezone']) && $timezone_user['timezone'] != null) {
            $to_timezone = $timezone_user['timezone'];
        }
    }

    return $to_timezone;
}

/**
 * Returns the given date as a DATETIME in UTC timezone.
 * This function should be used before entering any date in the DB.
 *
 * @param mixed $time The date to be converted (can be a string supported by date() or a timestamp)
 * @param bool $return_null_if_invalid_date if the date is not correct return null instead of the current date
 * @param bool $returnObj
 *
 * @return string The DATETIME in UTC to be inserted in the DB,
 * or null if the format of the argument is not supported
 *
 * @author Julio Montoya - Adding the 2nd parameter
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_utc_datetime($time = null, $return_null_if_invalid_date = false, $returnObj = false)
{
    $from_timezone = api_get_timezone();
    $to_timezone = 'UTC';
    if (is_null($time) || empty($time) || $time === '0000-00-00 00:00:00') {
        if ($return_null_if_invalid_date) {
            return null;
        }
        if ($returnObj) {
            return $date = new DateTime(gmdate('Y-m-d H:i:s'));
        }

        return gmdate('Y-m-d H:i:s');
    }

    // If time is a timestamp, return directly in utc
    if (is_numeric($time)) {
        $time = intval($time);

        return gmdate('Y-m-d H:i:s', $time);
    }
    try {
        $date = new DateTime($time, new DateTimezone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        if ($returnObj) {
            return $date;
        } else {
            return $date->format('Y-m-d H:i:s');
        }
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Returns a DATETIME string converted to the right timezone
 * @param mixed The time to be converted
 * @param string The timezone to be converted to.
 * If null, the timezone will be determined based on user preference,
 * or timezone chosen by the admin for the platform.
 * @param string The timezone to be converted from. If null, UTC will be assumed.
 * @return string The converted time formatted as Y-m-d H:i:s
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_local_time(
    $time = null,
    $to_timezone = null,
    $from_timezone = null,
    $return_null_if_invalid_date = false,
    $showTime = true,
    $humanForm = false
) {
    // Determining the timezone to be converted from
    if (is_null($from_timezone)) {
        $from_timezone = 'UTC';
    }
    // Determining the timezone to be converted to
    if (is_null($to_timezone)) {
        $to_timezone = api_get_timezone();
    }

    // If time is a timestamp, convert it to a string
    if (is_null($time) || empty($time) || $time == '0000-00-00 00:00:00') {
        if ($return_null_if_invalid_date) {
            return null;
        }
        $from_timezone = 'UTC';
        $time = gmdate('Y-m-d H:i:s');
    }
    if (is_numeric($time)) {
        $time = intval($time);
        $from_timezone = 'UTC';
        $time = gmdate('Y-m-d H:i:s', $time);
    }
    if ($time instanceof DateTime) {
        $time = $time->format('Y-m-d H:i:s');
        $from_timezone = 'UTC';
    }

    try {
        $date = new DateTime($time, new DateTimezone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));

        return api_get_human_date_time($date, $showTime, $humanForm);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Converts a string into a timestamp safely (handling timezones), using strtotime
 *
 * @param string $time to be converted
 * @param string $timezone (if null, the timezone will be determined based
 * on user preference, or timezone chosen by the admin for the platform)
 * @return int Timestamp
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_strtotime($time, $timezone = null)
{
    $system_timezone = date_default_timezone_get();
    if (!empty($timezone)) {
        date_default_timezone_set($timezone);
    }
    $timestamp = strtotime($time);
    date_default_timezone_set($system_timezone);

    return $timestamp;
}

/**
 * Returns formatted date/time, correspondent to a given language.
 * The given date should be in the timezone chosen by the administrator
 * and/or user. Use api_get_local_time to get it.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Christophe Gesche<gesche@ipm.ucl.ac.be>
 *         originally inspired from from PhpMyAdmin
 * @author Ivan Tcholakov, 2009, code refactoring, adding support for predefined date/time formats.
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 *
 * @param mixed Timestamp or datetime string
 * @param mixed Date format (string or int; see date formats in the Chamilo system: TIME_NO_SEC_FORMAT, DATE_FORMAT_SHORT, DATE_FORMAT_LONG, DATE_TIME_FORMAT_LONG)
 * @param string $language (optional)        Language identificator. If it is omited, the current interface language is assumed.
 * @return string                            Returns the formatted date.
 *
 * @link http://php.net/manual/en/function.strftime.php
 */
function api_format_date($time, $format = null, $language = null)
{
    $system_timezone = date_default_timezone_get();
    date_default_timezone_set(api_get_timezone());

    if (is_string($time)) {
        $time = strtotime($time);
    }

    if (is_null($format)) {
        $format = DATE_TIME_FORMAT_LONG;
    }

    $datetype = null;
    $timetype = null;

    if (is_int($format)) {
        switch ($format) {
            case DATE_FORMAT_ONLY_DAYNAME:
                $date_format = get_lang('dateFormatOnlyDayName', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::SHORT;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case DATE_FORMAT_NUMBER_NO_YEAR:
                $date_format = get_lang('dateFormatShortNumberNoYear', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::SHORT;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case DATE_FORMAT_NUMBER:
                $date_format = get_lang('dateFormatShortNumber', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::SHORT;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case TIME_NO_SEC_FORMAT:
                $date_format = get_lang('timeNoSecFormat', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::NONE;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_FORMAT_SHORT:
                $date_format = get_lang('dateFormatShort', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::LONG;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case DATE_FORMAT_LONG:
                $date_format = get_lang('dateFormatLong', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::NONE;
                }
                break;
            case DATE_TIME_FORMAT_LONG:
                $date_format = get_lang('dateTimeFormatLong', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_FORMAT_LONG_NO_DAY:
                $date_format = get_lang('dateFormatLongNoDay', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_TIME_FORMAT_SHORT:
                $date_format = get_lang('dateTimeFormatShort', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_TIME_FORMAT_SHORT_TIME_FIRST:
                $date_format = get_lang('dateTimeFormatShortTimeFirst', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            case DATE_TIME_FORMAT_LONG_24H:
                $date_format = get_lang('dateTimeFormatLong24H', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
                break;
            default:
                $date_format = get_lang('dateTimeFormatLong', '', $language);
                if (INTL_INSTALLED) {
                    $datetype = IntlDateFormatter::FULL;
                    $timetype = IntlDateFormatter::SHORT;
                }
        }
    } else {
        $date_format = $format;
    }

    if (0) {
        //if using PHP 5.3 format dates like: $dateFormatShortNumber, can't be used
        //
        // Use ICU
        if (is_null($language)) {
            $language = api_get_language_isocode();
        }
        $date_formatter = new IntlDateFormatter($language, $datetype, $timetype, date_default_timezone_get());
        //$date_formatter->setPattern($date_format);
        $formatted_date = api_to_system_encoding($date_formatter->format($time), 'UTF-8');

    } else {
        // We replace %a %A %b %B masks of date format with translated strings
        $translated = &_api_get_day_month_names($language);
        $date_format = str_replace(
            array('%A', '%a', '%B', '%b'),
            array(
                $translated['days_long'][(int) strftime('%w', $time)],
                $translated['days_short'][(int) strftime('%w', $time)],
                $translated['months_long'][(int) strftime('%m', $time) - 1],
                $translated['months_short'][(int) strftime('%m', $time) - 1],
            ),
            $date_format
        );
        $formatted_date = api_to_system_encoding(strftime($date_format, $time), 'UTF-8');
    }
    date_default_timezone_set($system_timezone);

    return $formatted_date;
}

/**
 * Returns the difference between the current date (date(now)) with the parameter
 * $date in a string format like "2 days ago, 1 hour ago".
 * You can use it like this:
 * Display::dateToStringAgoAndLongDate($dateInUtc);
 *
 * @param string $date Result of a date function in this format -> date('Y-m-d H:i:s', time());
 * @param string $timeZone
 * @return string
 *
 * @author Julio Montoya
 */
function date_to_str_ago($date, $timeZone = 'UTC')
{
    if ($date === '0000-00-00 00:00:00') {
        return '';
    }

    $getOldTimezone = api_get_timezone();

    $isoCode = api_get_language_isocode();
    if ($isoCode == 'pt') {
        $isoCode = 'pt-BR';
    }
    $checkFile = api_get_path(SYS_PATH).'vendor/jimmiw/php-time-ago/translations/'.$isoCode.'.php';
    if (!file_exists($checkFile)) {
        $isoCode = 'en';
    }
    $timeAgo = new TimeAgo($timeZone, $isoCode);
    $value = $timeAgo->inWords($date);

    date_default_timezone_set($getOldTimezone);

    return $value;
}

/**
 * Converts a date to the right timezone and localizes it in the format given as an argument
 * @param mixed The time to be converted
 * @param mixed Format to be used (TIME_NO_SEC_FORMAT, DATE_FORMAT_SHORT, DATE_FORMAT_LONG, DATE_TIME_FORMAT_LONG)
 * @param string Timezone to be converted from. If null, UTC will be assumed.
 * @return string Converted and localized date
 *
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_convert_and_format_date($time = null, $format = null, $from_timezone = null)
{
    // First, convert the datetime to the right timezone
    $time = api_get_local_time($time, null, $from_timezone);
    // Second, localize the date
    return api_format_date($time, $format);
}

/**
 * Returns an array of translated week days in short names.
 * @param string $language (optional)    Language id. If it is omitted, the current interface language is assumed.
 * @return string                        Returns an array of week days (short names).
 * Example: api_get_week_days_short('english') means array('Sun', 'Mon', ... 'Sat').
 * Note: For all languges returned days are in the English order.
 */
function api_get_week_days_short($language = null)
{
    $days = &_api_get_day_month_names($language);
    return $days['days_short'];
}

/**
 * Returns an array of translated week days.
 * @param string $language (optional)    Language id. If it is omitted, the current interface language is assumed.
 * @return string                        Returns an array of week days.
 * Example: api_get_week_days_long('english') means array('Sunday, 'Monday', ... 'Saturday').
 * Note: For all languges returned days are in the English order.
 */
function api_get_week_days_long($language = null)
{
    $days = &_api_get_day_month_names($language);
    return $days['days_long'];
}

/**
 * Returns an array of translated months in short names.
 * @param string $language (optional)    Language id. If it is omitted, the current interface language is assumed.
 * @return string                        Returns an array of months (short names).
 * Example: api_get_months_short('english') means array('Jan', 'Feb', ... 'Dec').
 */
function api_get_months_short($language = null)
{
    $months = &_api_get_day_month_names($language);
    return $months['months_short'];
}

/**
 * Returns an array of translated months.
 * @param string $language (optional)    Language id. If it is omitted, the current interface language is assumed.
 * @return string                        Returns an array of months.
 * Example: api_get_months_long('english') means array('January, 'February' ... 'December').
 */
function api_get_months_long($language = null)
{
    $months = &_api_get_day_month_names($language);
    return $months['months_long'];
}

/**
 * Name order conventions
 */

/**
 * Builds a person (full) name depending on the convention for a given language.
 * @param string $first_name The first name of the person.
 * @param string $last_name The last name of the person.
 * @param string $title The title of the person.
 * @param int|string $format (optional)    The person name format. It may be a pattern-string (for example '%t %l, %f' or '%T %F %L', ...) or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)    The language id. If it is omitted, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool                            The result is sort of full name of the person.
 * Sample results:
 * Peter Ustinoff or Dr. Peter Ustinoff     - the Western order
 * Ustinoff Peter or Dr. Ustinoff Peter     - the Eastern order
 * Ustinoff, Peter or - Dr. Ustinoff, Peter - the library order
 * Note: See the file chamilo/main/inc/lib/internationalization_database/name_order_conventions.php where you can revise the convention for your language.
 * @author Carlos Vargas <carlos.vargas@dokeos.com> - initial implementation.
 * @author Ivan Tcholakov
 */
function api_get_person_name(
    $first_name,
    $last_name,
    $title = null,
    $format = null,
    $language = null,
    $encoding = null,
    $username = null
) {
    static $valid = array();
    if (empty($format)) {
        $format = PERSON_NAME_COMMON_CONVENTION;
    }
    //We check if the language is supported, otherwise we check the interface language of the parent language of sublanguage
    if (empty($language)) {
        // Do not set $setParentLanguageName because this function is called before
        // the main language is loaded in global.inc.php
        $language = api_get_interface_language(false, true, false);
    }

    if (!isset($valid[$format][$language])) {
        if (is_int($format)) {
            switch ($format) {
                case PERSON_NAME_COMMON_CONVENTION:
                    $valid[$format][$language] = _api_get_person_name_convention($language, 'format');
                    $usernameOrderFromDatabase = api_get_setting('user_name_order');
                    if (isset($usernameOrderFromDatabase) && !empty($usernameOrderFromDatabase)) {
                        $valid[$format][$language] = $usernameOrderFromDatabase;
                    }
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
                    break;
            }
        } else {
            $valid[$format][$language] = _api_validate_person_name_format($format);
        }
    }

    $format = $valid[$format][$language];

    $keywords = array(
        '%firstname',
        '%f',
        '%F',
        '%lastname',
        '%l',
        '%L',
        '%title',
        '%t',
        '%T',
        '%username',
        '%u',
        '%U',
    );

    $values = array(
        $first_name,
        $first_name,
        api_strtoupper($first_name),
        $last_name,
        $last_name,
        api_strtoupper($last_name),
        $title,
        $title,
        api_strtoupper($title),
        $username,
        $username,
        api_strtoupper($username),
    );
    $person_name = str_replace($keywords, $values, $format);

    return _api_clean_person_name($person_name);
}

/**
 * Checks whether a given format represents person name in Western order (for which first name is first).
 * @param int|string $format (optional)    The person name format. It may be a pattern-string (for example '%t. %l, %f') or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)    The language id. If it is omitted, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @return bool The result TRUE means that the order is first_name last_name, FALSE means last_name first_name.
 * Note: You may use this function for determining the order of the fields or columns "First name" and "Last name" in forms, tables and reports.
 * @author Ivan Tcholakov
 */
function api_is_western_name_order($format = null, $language = null)
{
    static $order = array();
    if (empty($format)) {
        $format = PERSON_NAME_COMMON_CONVENTION;
    }

    if (empty($language)) {
        $language = api_get_interface_language(false, true);
    }
    if (!isset($order[$format][$language])) {
        $test_name = api_get_person_name('%f', '%l', '%t', $format, $language);
        $order[$format][$language] = stripos($test_name, '%f') <= stripos($test_name, '%l');
    }
    return $order[$format][$language];
}

/**
 * Returns a directive for sorting person names depending on a given language and based on the options in the internationalization "database".
 * @param string $language (optional) The input language. If it is omitted, the current interface language is assumed.
 * @return bool Returns boolean value. TRUE means ORDER BY first_name, last_name; FALSE means ORDER BY last_name, first_name.
 * Note: You may use this function:
 * 2. for constructing the ORDER clause of SQL queries, related to first_name and last_name;
 * 3. for adjusting php-implemented sorting in tables and reports.
 * @author Ivan Tcholakov
 */
function api_sort_by_first_name($language = null) {
    $userNameSortBy = api_get_setting('user_name_sort_by');
    if (!empty($userNameSortBy) && in_array($userNameSortBy, array('firstname', 'lastname'))) {
        return $userNameSortBy == 'firstname' ? true : false;
    }

    static $sort_by_first_name = array();

    if (empty($language)) {
        $language = api_get_interface_language(false, true);
    }
    if (!isset($sort_by_first_name[$language])) {
        $sort_by_first_name[$language] = _api_get_person_name_convention($language, 'sort_by');
    }

    return $sort_by_first_name[$language];
}

/**
 * Multibyte string conversion functions
 */

/**
 * Converts character encoding of a given string.
 * @param string $string The string being converted.
 * @param string $to_encoding The encoding that $string is being converted to.
 * @param string $from_encoding (optional)    The encoding that $string is being converted from.
 * If it is omitted, the platform character set is assumed.
 * @return string Returns the converted string.
 * This function is aimed at replacing the function mb_convert_encoding() for human-language strings.
 * @link http://php.net/manual/en/function.mb-convert-encoding
 */
function api_convert_encoding($string, $to_encoding, $from_encoding = 'UTF-8')
{
    if (strtoupper($to_encoding) === strtoupper($from_encoding)) {
        return $string;
    }

    return mb_convert_encoding($string, $to_encoding, $from_encoding);
}

/**
 * Converts a given string into UTF-8 encoded string.
 * @param string $string The string being converted.
 * @param string $from_encoding (optional) The encoding that $string is being converted from.
 * If it is omitted, the platform character set is assumed.
 * @return string Returns the converted string.
 * This function is aimed at replacing the function utf8_encode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-encode
 */
function api_utf8_encode($string, $from_encoding = 'UTF-8')
{
    return mb_convert_encoding($string, 'UTF-8', $from_encoding);
}

/**
 * Converts a given string from UTF-8 encoding to a specified encoding.
 * @param string $string The string being converted.
 * @param string $to_encoding (optional)    The encoding that $string is being converted to.
 * If it is omitted, the platform character set is assumed.
 * @return string                            Returns the converted string.
 * This function is aimed at replacing the function utf8_decode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-decode
 */
function api_utf8_decode($string, $to_encoding = null)
{
    return mb_convert_encoding($string, $to_encoding, 'UTF-8');
}

/**
 * Converts a given string into the system ecoding (or platform character set).
 * When $from encoding is omited on UTF-8 platforms then language dependent encoding
 * is guessed/assumed. On non-UTF-8 platforms omited $from encoding is assumed as UTF-8.
 * When the parameter $check_utf8_validity is true the function checks string's
 * UTF-8 validity and decides whether to try to convert it or not.
 * This function is useful for problem detection or making workarounds.
 * @param string $string The string being converted.
 * @param string $from_encoding (optional) The encoding that $string is being converted from.
 * It is guessed when it is omitted.
 * @param bool $check_utf8_validity (optional)    A flag for UTF-8 validity check as condition for making conversion.
 * @return string Returns the converted string.
 */
function api_to_system_encoding($string, $from_encoding = null, $check_utf8_validity = false)
{
    $system_encoding = api_get_system_encoding();
    return api_convert_encoding($string, $system_encoding, $from_encoding);
}

/**
 * Converts all applicable characters to HTML entities.
 * @param string $string The input string.
 * @param int $quote_style (optional)    The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)    The encoding (of the input string) used in conversion.
 * If it is omitted, the platform character set is assumed.
 * @return string Returns the converted string.
 * This function is aimed at replacing the function htmlentities() for human-language strings.
 * @link http://php.net/manual/en/function.htmlentities
 */
function api_htmlentities($string, $quote_style = ENT_COMPAT, $encoding = 'UTF-8')
{
    switch ($quote_style) {
        case ENT_COMPAT:
            $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
            break;
        case ENT_QUOTES:
            $string = str_replace(array('&', '\'', '"', '<', '>'), array('&amp;', '&#039;', '&quot;', '&lt;', '&gt;'), $string);
            break;
    }

    return mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8');
}

/**
 * Converts HTML entities into normal characters.
 * @param string $string The input string.
 * @param int $quote_style (optional)    The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)    The encoding (of the result) used in conversion.
 * If it is omitted, the platform character set is assumed.
 * @return string Returns the converted string.
 * This function is aimed at replacing the function html_entity_decode() for human-language strings.
 * @link http://php.net/html_entity_decode
 */
function api_html_entity_decode($string, $quote_style = ENT_COMPAT, $encoding = 'UTF-8') {
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
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
    return $string; // Here the function gives up.
}

/**
 * This function encodes (conditionally) a given string to UTF-8 if XmlHttp-request has been detected.
 * @param string $string The string being converted.
 * @param string $from_encoding (optional)    The encoding that $string is being converted from.
 * If it is omitted, the platform character set is assumed.
 * @return string Returns the converted string.
 */
function api_xml_http_response_encode($string, $from_encoding = 'UTF8')
{
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
 * Transliterates a string with arbitrary encoding into a plain ASCII string.
 *
 * Example:
 * echo api_transliterate(api_html_entity_decode(
 *    '&#1060;&#1105;&#1076;&#1086;&#1088; '.
 *    '&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
 *    '&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
 *    ENT_QUOTES, 'UTF-8'), 'X', 'UTF-8');
 * The output should be: Fyodor Mihaylovich Dostoevkiy
 *
 * @param string $string The input string.
 * @param string $unknown (optional)        Replacement character for unknown characters and illegal UTF-8 sequences.
 * @param string $from_encoding (optional)    The encoding of the input string.
 * If it is omitted, the platform character set is assumed.
 * @return string Plain ASCII output.
 *
 */
function api_transliterate($string, $unknown = '?', $from_encoding = null)
{
    return URLify::transliterate($string);
}

/**
 * Takes the first character in a string and returns its Unicode codepoint.
 * @param string $character The input string.
 * @param string $encoding (optional)    The encoding of the input string. If it is omitted, the platform character set will be used by default.
 * @return int Returns: the codepoint of the first character; or 0xFFFD (unknown character) when the input string is empty.
 * This is a multibyte aware version of the function ord().
 * @link http://php.net/manual/en/function.ord.php
 * Note the difference with the original funtion ord(): ord('') returns 0, api_ord('') returns 0xFFFD (unknown character).
 */
function api_ord($character, $encoding = 'UTF-8')
{
    return Utf8::ord(api_utf8_encode($character, $encoding));
}

/**
 * This function returns a string or an array with all occurrences of search in subject (ignoring case) replaced with the given replace value.
 * @param mixed $search String or array of strings to be found.
 * @param mixed $replace String or array of strings used for replacement.
 * @param mixed $subject String or array of strings being searched.
 * @param int $count (optional) The number of matched and replaced needles will be returned in count, which is passed by reference.
 * @param string $encoding (optional) The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return mixed String or array as a result.
 * Notes:
 * If $subject is an array, then the search and replace is performed with every entry of subject, the return value is an array.
 * If $search and $replace are arrays, then the function takes a value from each array and uses it to do search and replace on subject.
 * If $replace has fewer values than search, then an empty string is used for the rest of replacement values.
 * If $search is an array and $replace is a string, then this replacement string is used for every value of search.
 * This function is aimed at replacing the function str_ireplace() for human-language strings.
 * @link http://php.net/manual/en/function.str-ireplace
 * @author Henri Sivonen, mailto:hsivonen@iki.fi
 * @link http://hsivonen.iki.fi/php-utf8/
 * Adaptation for Chamilo 1.8.7, 2010
 * Initial implementation Dokeos LMS, August 2009
 * @author Ivan Tcholakov
 */
function api_str_ireplace($search, $replace, $subject, & $count = null, $encoding = null)
{
    return Utf8::str_ireplace($search, $replace, $subject, $count);
}

/**
 * Converts a string to an array.
 * @param string $string The input string.
 * @param int $split_length Maximum character-length of the chunk, one character by default.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array The result array of chunks with the spcified length.
 * Notes:
 * If the optional split_length parameter is specified, the returned array will be broken down into chunks
 * with each being split_length in length, otherwise each chunk will be one character in length.
 * FALSE is returned if split_length is less than 1.
 * If the split_length length exceeds the length of string, the entire string is returned as the first (and only) array element.
 * This function is aimed at replacing the function str_split() for human-language strings.
 * @link http://php.net/str_split
 */
function api_str_split($string, $split_length = 1, $encoding = null)
{
    return Utf8::str_split($string, $split_length);
}

/**
 * Finds position of first occurrence of a string within another, case insensitive.
 * @param string $haystack The string from which to get the position of the first occurrence.
 * @param string $needle The string to be found.
 * @param int $offset The position in $haystack to start searching from. If it is omitted, searching starts from the beginning.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions stripos() and mb_stripos() for human-language strings.
 * @link http://php.net/manual/en/function.stripos
 * @link http://php.net/manual/en/function.mb-stripos
 */
function api_stripos($haystack, $needle, $offset = 0, $encoding = null)
{
    return Utf8::stripos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another, case insensitive.
 * @param string $haystack The string from which to get the first occurrence.
 * @param mixed $needle The string to be found.
 * @param bool $before_needle (optional) Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions stristr() and mb_stristr() for human-language strings.
 * @link http://php.net/manual/en/function.stristr
 * @link http://php.net/manual/en/function.mb-stristr
 */
function api_stristr($haystack, $needle, $before_needle = false, $encoding = null)
{
    return Utf8::stristr($haystack, $needle, $before_needle);
}

/**
 * Returns length of the input string.
 * @param string $string The string which length is to be calculated.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int Returns the number of characters within the string. A multi-byte character is counted as 1.
 * This function is aimed at replacing the functions strlen() and mb_strlen() for human-language strings.
 * @link http://php.net/manual/en/function.strlen
 * @link http://php.net/manual/en/function.mb-strlen
 * Note: When you use strlen() to test for an empty string, you needn't change it to api_strlen().
 * For example, in lines like the following:
 * if (strlen($string) > 0)
 * if (strlen($string) != 0)
 * there is no need the original function strlen() to be changed, it works correctly and faster for these cases.
 */
function api_strlen($string, $encoding = null)
{
    return Utf8::strlen($string);
}

/**
 * Finds position of first occurrence of a string within another.
 * @param string $haystack The string from which to get the position of the first occurrence.
 * @param string $needle The string to be found.
 * @param int $offset (optional) The position in $haystack to start searching from. If it is omitted, searching starts from the beginning.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strpos() and mb_strpos() for human-language strings.
 * @link http://php.net/manual/en/function.strpos
 * @link http://php.net/manual/en/function.mb-strpos
 */
function api_strpos($haystack, $needle, $offset = 0, $encoding = null)
{
    return Utf8::strpos($haystack, $needle, $offset);
}

/**
 * Finds the last occurrence of a character in a string.
 * @param string $haystack The string from which to get the last occurrence.
 * @param mixed $needle The string which first character is to be found.
 * @param bool $before_needle (optional) Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the portion of $haystack, or FALSE if the first character from $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence to the end.
 * This function is aimed at replacing the functions strrchr() and mb_strrchr() for human-language strings.
 * @link http://php.net/manual/en/function.strrchr
 * @link http://php.net/manual/en/function.mb-strrchr
 */
function api_strrchr($haystack, $needle, $before_needle = false, $encoding = null)
{
    return Utf8::strrchr($haystack, $needle);
}

/**
 * Reverses a string.
 * @param string $string The string to be reversed.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return string Returns the reversed string.
 * This function is aimed at replacing the function strrev() for human-language strings.
 * @link http://php.net/manual/en/function.strrev
 */
function api_strrev($string, $encoding = null)
{
    return Utf8::strrev($string);
}

/**
 * Finds the position of last occurrence (case insensitive) of a string in a string.
 * @param string $haystack The string from which to get the position of the last occurrence.
 * @param string $needle The string to be found.
 * @param int $offset (optional) $offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the numeric position of the first occurrence (case insensitive) of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strripos() and mb_strripos() for human-language strings.
 * @link http://php.net/manual/en/function.strripos
 * @link http://php.net/manual/en/function.mb-strripos
 */
function api_strripos($haystack, $needle, $offset = 0, $encoding = null)
{
    return Utf8::strripos($haystack, $needle, $offset);
}

/**
 * Finds the position of last occurrence of a string in a string.
 * @param string $haystack The string from which to get the position of the last occurrence.
 * @param string $needle The string to be found.
 * @param int $offset (optional) $offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the numeric position of the first occurrence of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strrpos() and mb_strrpos() for human-language strings.
 * @link http://php.net/manual/en/function.strrpos
 * @link http://php.net/manual/en/function.mb-strrpos
 */
function api_strrpos($haystack, $needle, $offset = 0, $encoding = null)
{
    return Utf8::strrpos($haystack, $needle, $offset);
}

/**
 * Finds first occurrence of a string within another.
 * @param string $haystack The string from which to get the first occurrence.
 * @param mixed $needle The string to be found.
 * @param bool $before_needle (optional) Determines which portion of $haystack this function returns. The default value is FALSE.
 * @param string $encoding (optional) The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed Returns the portion of $haystack, or FALSE if $needle is not found.
 * Notes:
 * If $needle is not a string, it is converted to an integer and applied as the ordinal value (codepoint if the encoding is UTF-8) of a character.
 * If $before_needle is set to TRUE, the function returns all of $haystack from the beginning to the first occurrence of $needle.
 * If $before_needle is set to FALSE, the function returns all of $haystack from the first occurrence of $needle to the end.
 * This function is aimed at replacing the functions strstr() and mb_strstr() for human-language strings.
 * @link http://php.net/manual/en/function.strstr
 * @link http://php.net/manual/en/function.mb-strstr
 */
function api_strstr($haystack, $needle, $before_needle = false, $encoding = null)
{
    return Utf8::strstr($haystack, $needle, $before_needle);
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
function api_strtolower($string, $encoding = null)
{
    return Utf8::strtolower($string);
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
function api_strtoupper($string, $encoding = null)
{
    return Utf8::strtoupper($string);
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
function api_substr($string, $start, $length = null, $encoding = null)
{
    if (is_null($length)) {
        $length = api_strlen($string, $encoding);
    }
    return Utf8::substr($string, $start, $length);
}

/**
 * Counts the number of substring occurrences.
 * @param string $haystack				The string being checked.
 * @param string $needle				The string being found.
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return int							The number of times the needle substring occurs in the haystack string.
 * @link http://php.net/manual/en/function.mb-substr-count.php
 */
function api_substr_count($haystack, $needle, $encoding = null)
{
    return Utf8::substr_count($haystack, $needle);
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
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return string						The result string is returned.
 * This function is aimed at replacing the function substr_replace() for human-language strings.
 * @link http://php.net/manual/function.substr-replace
 */
function api_substr_replace($string, $replacement, $start, $length = null, $encoding = null)
{
    if (is_null($length)) {
        $length = api_strlen($string);
    }

    return UTf8::substr_replace($string, $replacement, $start, $length);
}

/**
 * Makes a string's first character uppercase.
 * @param string $string				The input string.
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return string						Returns a string with the first character capitalized, if that character is alphabetic.
 * This function is aimed at replacing the function ucfirst() for human-language strings.
 * @link http://php.net/manual/en/function.ucfirst
 */
function api_ucfirst($string, $encoding = null)
{
    return Utf8::ucfirst($string);
}

/**
 * Uppercases the first character of each word in a string.
 * @param string $string				The input string.
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return string						Returns the modified string.
 * This function is aimed at replacing the function ucwords() for human-language strings.
 * @link http://php.net/manual/en/function.ucwords
 */
function api_ucwords($string, $encoding = null)
{
    return Utf8::ucwords($string);
}

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
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
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
 * String comparison
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
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strcmp($string1, $string2, $language = null, $encoding = null)
{
    return strcmp($string1, $string2);
}

/**
 * Performs string comparison in so called "natural order", case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1				The first string.
 * @param string $string2				The second string.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return int							Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strnatcmp($string1, $string2, $language = null, $encoding = null)
{
    return strnatcmp($string1, $string2);
}

/**
 * Sorting arrays
 */

/**
 * Sorts an array using natural order algorithm.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 * This function is aimed at replacing the function natsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.natsort.php
 */
function api_natsort(&$array, $language = null, $encoding = null)
{
    return natsort($array);
}

/**
 * Sorts an array using natural order algorithm in reverse order.
 * @param array $array					The input array.
 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)	The used internally by this function character encoding.
 * If it is omitted, the platform character set will be used by default.
 * @return bool							Returns TRUE on success, FALSE on error.
 */
function api_natrsort(&$array, $language = null, $encoding = null)
{
    return uasort($array, '_api_strnatrcmp');
}

/**
 * Encoding management functions
 */

/**
 * This function unifies the encoding identificators, so they could be compared.
 * @param string|array $encoding	The specified encoding.
 * @return string					Returns the encoding identificator modified in suitable for comparison way.
 */
function api_refine_encoding_id($encoding)
{
    if (is_array($encoding)) {
        return array_map('api_refine_encoding_id', $encoding);
    }
    return strtoupper(str_replace('_', '-', $encoding));
}

/**
 * This function checks whether two $encoding are equal (same, equvalent).
 * @param string|array $encoding1		The first encoding
 * @param string|array $encoding2		The second encoding
 * @param bool $strict					When this parameter is TRUE the comparison ignores aliases of encodings.
 * When the parameter is FALSE, aliases are taken into account.
 * @return bool							Returns TRUE if the encodings are equal, FALSE otherwise.
 */
function api_equal_encodings($encoding1, $encoding2, $strict = false)
{
    static $equal_encodings = array();
    if (is_array($encoding1)) {
        foreach ($encoding1 as $encoding) {
            if (api_equal_encodings($encoding, $encoding2, $strict)) {
                return true;
            }
        }
        return false;
    }
    elseif (is_array($encoding2)) {
        foreach ($encoding2 as $encoding) {
            if (api_equal_encodings($encoding1, $encoding, $strict)) {
                return true;
            }
        }
        return false;
    }
    if (!isset($equal_encodings[$encoding1][$encoding2][$strict])) {
        $encoding_1 = api_refine_encoding_id($encoding1);
        $encoding_2 = api_refine_encoding_id($encoding2);
        if ($encoding_1 == $encoding_2) {
            $result = true;
        } else {
            if ($strict) {
                $result = false;
            } else {
                $alias1 = _api_get_character_map_name($encoding_1);
                $alias2 = _api_get_character_map_name($encoding_2);
                $result = !empty($alias1) && !empty($alias2) && $alias1 == $alias2;
            }
        }
        $equal_encodings[$encoding1][$encoding2][$strict] = $result;
    }
    return $equal_encodings[$encoding1][$encoding2][$strict];
}

/**
 * This function checks whether a given encoding is UTF-8.
 * @param string $encoding		The tested encoding.
 * @return bool					Returns TRUE if the given encoding id means UTF-8, otherwise returns false.
 */
function api_is_utf8($encoding)
{
    static $result = array();
    if (!isset($result[$encoding])) {
        $result[$encoding] = api_equal_encodings($encoding, 'UTF-8');
    }
    return $result[$encoding];
}

/**
 * This function returns the encoding, currently used by the system.
 * @return string	The system's encoding.
 * Note: The value of api_get_setting('platform_charset') is tried to be returned first,
 * on the second place the global variable $charset is tried to be returned. If for some
 * reason both attempts fail, then the libraly's internal value will be returned.
 */
function api_get_system_encoding()
{
    return 'UTF-8';
}

/**
 * Checks whether a specified encoding is supported by this API.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_is_encoding_supported($encoding)
{
    static $supported = array();
    if (!isset($supported[$encoding])) {
        $supported[$encoding] = _api_mb_supports($encoding) || _api_iconv_supports($encoding) || _api_convert_encoding_supports($encoding);
    }
    return $supported[$encoding];
}

/**
 * Detects encoding of plain text.
 * @param string $string				The input text.
 * @param string $language (optional)	The language of the input text, provided if it is known.
 * @return string						Returns the detected encoding.
 */
function api_detect_encoding($string, $language = null)
{
    // Testing against valid UTF-8 first.
    if (api_is_valid_utf8($string)) {
        return 'UTF-8';
    }

    return mb_detect_encoding($string);
}

/**
 * String validation functions concerning certain encodings
 */

/**
 * Checks a string for UTF-8 validity.
 * @param string $string
 *
 * @return string
 */
function api_is_valid_utf8($string)
{
    return Utf8::isUtf8($string);
}

/**
 * Checks whether a string contains 7-bit ASCII characters only.
 * @param string $string	The string to be tested/validated.
 * @return bool				Returns TRUE when the tested string contains 7-bit ASCII characters only, FALSE othewise.
 */
function api_is_valid_ascii(&$string)
{
    return mb_detect_encoding($string, 'ASCII', true) == 'ASCII' ? true : false;
}

/**
 * Return true a date is valid

 * @param string $date example: 2014-06-30 13:05:05
 * @param string $format example: "Y-m-d H:i:s"
 *
 * @return bool
 */
function api_is_valid_date($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

/**
 * Returns the variable translated
 * @param string $variable the string to translate
 * @param string $pluginName the Plugin name
 * @return string the variable translated
 */
function get_plugin_lang($variable, $pluginName) {
    $plugin = $pluginName::create();
    return $plugin->get_lang($variable);
}

/**
 * Returns an array of translated week days and months, short and normal names.
 * @param string $language (optional)    Language id. If it is omitted, the current interface language is assumed.
 * @return array                        Returns a multidimensional array with translated week days and months.
 */
function &_api_get_day_month_names($language = null) {
    static $date_parts = array();
    if (empty($language)) {
        $language = api_get_interface_language();
    }
    if (!isset($date_parts[$language])) {
        $week_day = array(
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        );
        $month = array(
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        );
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
 * Returns returns person name convention for a given language.
 * @param string $language	The input language.
 * @param string $type		The type of the requested convention.
 * It may be 'format' for name order convention or 'sort_by' for name sorting convention.
 * @return mixed Depending of the requested type,
 * the returned result may be string or boolean; null is returned on error;
 */
function _api_get_person_name_convention($language, $type)
{
    static $conventions;
    $language = api_purify_language_id($language);
    if (!isset($conventions)) {
        $file = __DIR__.'/internationalization_database/name_order_conventions.php';
        if (file_exists($file)) {
            $conventions = include ($file);
        } else {
            $conventions = array(
                'english' => array(
                    'format' => 'title first_name last_name',
                    'sort_by' => 'first_name'
                )
            );
        }
        // Overwrite classic conventions
        $customConventions = api_get_configuration_value('name_order_conventions');

        if (!empty($customConventions)) {
            foreach ($customConventions as $key => $data) {
                $conventions[$key] = $data;
            }
        }

        $search1 = array('FIRST_NAME', 'LAST_NAME', 'TITLE');
        $replacement1 = array('%F', '%L', '%T');
        $search2 = array('first_name', 'last_name', 'title');
        $replacement2 = array('%f', '%l', '%t');
        foreach (array_keys($conventions) as $key) {
            $conventions[$key]['format'] = str_replace($search1, $replacement1, $conventions[$key]['format']);
            $conventions[$key]['format'] = _api_validate_person_name_format(_api_clean_person_name(str_replace('%', ' %', str_ireplace($search2, $replacement2, $conventions[$key]['format']))));
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
function _api_validate_person_name_format($format)
{
    if (empty($format) || stripos($format, '%f') === false || stripos($format, '%l') === false) {
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
function _api_clean_person_name($person_name)
{
    return preg_replace(array('/\s+/', '/, ,/', '/,+/', '/^[ ,]/', '/[ ,]$/'), array(' ', ', ', ',', '', ''), $person_name);
}

/**
 * Appendix to "Multibyte string conversion functions"
 */

/**
 * This is a php-implementation of a function that is similar to mb_convert_encoding() from mbstring extension.
 * The function converts a given string from one to another character encoding.
 * @param string $string					The string being converted.
 * @param string $to_encoding				The encoding that $string is being converted to.
 * @param string $from_encoding				The encoding that $string is being converted from.
 * @return string							Returns the converted string.
 */
function _api_convert_encoding(&$string, $to_encoding, $from_encoding)
{
    return mb_convert_encoding($string, $to_encoding, $from_encoding);
}

/**
 * This function determines the name of corresponding to a given encoding conversion table.
 * It is able to deal with some aliases of the encoding.
 * @param string $encoding		The given encoding identificator, for example 'WINDOWS-1252'.
 * @return string				Returns the name of the corresponding conversion table, for the same example - 'CP1252'.
 */
function _api_get_character_map_name($encoding)
{
    static $character_map_selector;
    if (!isset($character_map_selector)) {
        $file = __DIR__.'/internationalization_database/conversion/character_map_selector.php';
        if (file_exists($file)) {
            $character_map_selector = include ($file);
        } else {
            $character_map_selector = array();
        }
    }
    return isset($character_map_selector[$encoding]) ? $character_map_selector[$encoding] : '';
}

/**
 * Appendix to "String comparison"
 */

/**
 * A reverse function from php-core function strnatcmp(), performs string comparison in reverse natural (alpha-numerical) order.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2; >0 if $string1 < $string2; <0 if $string1 > $string2.
 */
function _api_strnatrcmp($string1, $string2)
{
    return strnatcmp($string2, $string1);
}

/**
 * Sets/Gets internal character encoding of the common string functions within the PHP mbstring extension.
 * @param string $encoding (optional)	When this parameter is given, the function sets the internal encoding.
 * @return string						When $encoding parameter is not given, the function returns the internal encoding.
 * Note: This function is used in the global initialization script for setting the internal encoding to the platform's character set.
 * @link http://php.net/manual/en/function.mb-internal-encoding
 */
function _api_mb_internal_encoding($encoding = 'UTF-8')
{
    return mb_internal_encoding($encoding);
}

/**
 * Checks whether the specified encoding is supported by the PHP mbstring extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_mb_supports($encoding)
{
    static $supported = array();
    if (!isset($supported[$encoding])) {
        if (MBSTRING_INSTALLED) {
            $supported[$encoding] = api_equal_encodings($encoding, mb_list_encodings(), true);
        } else {
            $supported[$encoding] = false;
        }
    }
    return $supported[$encoding];
}

/**
 * Checks whether the specified encoding is supported by the PHP iconv extension.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_iconv_supports($encoding)
{
    static $supported = array();
    if (!isset($supported[$encoding])) {
        if (ICONV_INSTALLED) {
            $enc = api_refine_encoding_id($encoding);
            if ($enc != 'HTML-ENTITIES') {
                $test_string = '';
                for ($i = 32; $i < 128; $i++) {
                    $test_string .= chr($i);
                }
                $supported[$encoding] = (@iconv_strlen($test_string, $enc)) ? true : false;
            } else {
                $supported[$encoding] = false;
            }
        } else {
            $supported[$encoding] = false;
        }
    }
    return $supported[$encoding];
}

// This function checks whether the function _api_convert_encoding() (the php-
// implementation) is able to convert from/to a given encoding.
function _api_convert_encoding_supports($encoding)
{
    static $supports = array();
    if (!isset($supports[$encoding])) {
        $supports[$encoding] = _api_get_character_map_name(api_refine_encoding_id($encoding)) != '';
    }
    return $supports[$encoding];
}

/**
 * Given a date object, return a human or ISO format, with or without h:m:s
 * @param object $date The Date object
 * @param bool $showTime Whether to show the time and date (true) or only the date (false)
 * @param bool $humanForm Whether to show day-month-year (true) or year-month-day (false)
 * @return string Formatted date
 */
function api_get_human_date_time($date, $showTime = true, $humanForm = false)
{
    if ($showTime) {
        if ($humanForm) {
           return $date->format('j M Y H:i:s');
        } else {
           return $date->format('Y-m-d H:i:s');
        }
    } else {
        if ($humanForm) {
           return $date->format('j M Y');
        } else {
           return $date->format('Y-m-d');
        }
    }
}
