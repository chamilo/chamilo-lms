<?php
/* For licensing terms, see /license.txt */

/**
 * File: internationalization.lib.php
 * Internationalization library for Chamilo 1.8.7 LMS
 * A library implementing internationalization related functions.
 * License: GNU General Public License Version 3 (Free Software Foundation)
 * @author Ivan Tcholakov, <ivantcholakov@gmail.com>, 2009, 2010
 * @author More authors, mentioned in the correpsonding fragments of this source.
 * @package chamilo.library
 */
use Patchwork\Utf8 as u;
use Symfony\Component\Intl\DateFormatter\IntlDateFormatter;

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
define('PERSON_NAME_COMMON_CONVENTION', 0); // Formatting a person's name using the pattern as it has been
// configured in the internationalization database for every language.
// This (default) option would be the most used.
// The followind options may be used in limited number of places for overriding the common convention:
define('PERSON_NAME_WESTERN_ORDER', 1); // Formatting a person's name in Western order: first_name last_name
define('PERSON_NAME_EASTERN_ORDER', 2); // Formatting a person's name in Eastern order: last_name first_name
define('PERSON_NAME_LIBRARY_ORDER', 3); // Contextual: formatting person's name in library order: last_name, first_name
define('PERSON_NAME_EMAIL_ADDRESS', PERSON_NAME_WESTERN_ORDER); // Contextual: formatting a person's name assotiated with an email-address. Ivan: I am not sure how seems email servers an clients would interpret name order, so I assign the Western order.
define('PERSON_NAME_DATA_EXPORT', PERSON_NAME_EASTERN_ORDER); // Contextual: formatting a person's name for data-exporting operarions. For backward compatibility this format has been set to Eastern order.

/**
 * Returns a translated (localized) string, called by its identificator.
 * @param string $variable                This is the identificator (name) of the translated string to be retrieved.
 * Notes:
 * Translations are created many contributors through using a special tool: Chamilo Translation Application.
 * @link http://translate.chamilo.org/
 */
function get_lang($variable)
{
    global $app;
    $translated = $app['translator']->trans($variable);
    if ($translated == $variable) {
        // Check the langVariable for BC
        $translated = $app['translator']->trans("lang$variable");
        if ($translated == "lang$variable") {
            return $variable;
        }
    }
    return $translated;
}

/**
 * Gets the current interface language.
  * @return string  The current language of the interface.
 */
function api_get_interface_language($purified = false, $check_sub_language = false)
{
    global $app;
    return $app['language'];
}

/**
 * Validates the input language (english, spanish, etc)
 * in order always to return a language that is enabled in the system.
 * This function is to be used for data import when provided language should be validated.
 * @param string $language The language to be validated.
 * @return string Returns the input language identificator. If the input language is not enabled, platform language is returned then.
 */
function api_get_valid_language($language)
{
    static $enabled_languages;
    if (!isset($enabled_languages)) {
        $enabled_languages_info = api_get_languages();
        $enabled_languages = $enabled_languages_info['folder'];
    }
    $language = str_replace('_km', '_KM', strtolower(trim($language)));
    if (empty($language) || !in_array($language, $enabled_languages)) {
        $language = api_get_setting('platformLanguage');
    }
    return $language;
}

/**
 * Returns a purified language id, without possible suffixes that will disturb language identification in certain cases.
 * @param string $language    The input language identificator, for example 'french_unicode'.
 * @param string            The same purified or filtered language identificator, for example 'french'.
 */
function api_purify_language_id($language)
{
    static $purified = array();
    if (!isset($purified[$language])) {
        $purified[$language] = trim(
            str_replace(array('_unicode', '_latin', '_corporate', '_org', '_km'), '', strtolower($language))
        );
    }
    return $purified[$language];
}

/**
 * Gets language isocode column from the language table, taking the given language as a query parameter.
 * @param string $language        This is the name of the folder containing translations for the corresponding language (e.g arabic, english).
 * @param string $default_code    This is the value to be returned if there was no code found corresponding to the given language.
 * If $language is omitted, interface language is assumed then.
 * @return string            The found isocode or null on error.
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
        $sql = "SELECT isocode
                FROM ".Database::get_main_table(TABLE_MAIN_LANGUAGE)."
                WHERE dokeos_folder = '$language'";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $result = Database::fetch_array($result);
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
 * @return array    An array with the current iso codes
 *
 * */
function api_get_platform_isocodes()
{
    $iso_code = array();
    $sql_result = Database::query(
        "SELECT isocode FROM ".Database::get_main_table(TABLE_MAIN_LANGUAGE)."
        ORDER BY isocode "
    );
    if (Database::num_rows($sql_result)) {
        while ($row = Database::fetch_array($sql_result)) {
            $iso_code[] = trim($row['isocode']);
        }
    }
    return $iso_code;
}

/**
 * Gets text direction according to the given language.
 * @param string $language    This is the name of the folder containing translations for the corresponding language (e.g 'arabic', 'english', ...).
 * ISO-codes are acceptable too ('ar', 'en', ...). If $language is omitted, interface language is assumed then.
 * @return string            The correspondent to the language text direction ('ltr' or 'rtl').
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
 * Returns an alphabetized list of timezones in an associative array that can be used to populate a select
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
function _api_get_timezone()
{
    $userId = api_get_user_id();

    // First, get the default timezone of the server
    $to_timezone = date_default_timezone_get();
    // Second, see if a timezone has been chosen for the platform
    $timezone_value = api_get_setting('timezone_value', 'timezones');
    if ($timezone_value != null) {
        $to_timezone = $timezone_value;
    }

    // If allowed by the administrator
    $use_users_timezone = api_get_setting('use_users_timezone', 'timezones');

    if ($use_users_timezone == 'true' && !empty($userId) && !api_is_anonymous()) {
        $userInfo = api_get_user_info();
        $extraFields = $userInfo['extra_fields'];
        // Get the timezone based on user preference, if it exists
        // $timezone_user = UserManager::get_extra_user_data_by_field($userId, 'timezone');
        if (isset($extraFields['extra_timezone']) && $extraFields['extra_timezone'] != null) {
            $to_timezone = $extraFields['extra_timezone'];
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
 * @return string The DATETIME in UTC to be inserted in the DB, or null if the format of the argument is not supported
 *
 * @author Julio Montoya - Adding the 2nd parameter
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */
function api_get_utc_datetime($time = null, $return_null_if_invalid_date = false, $returnObj = false)
{
    $from_timezone = _api_get_timezone();

    $to_timezone = 'UTC';
    if (is_null($time) || empty($time) || $time == '0000-00-00 00:00:00') {
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
    $return_null_if_invalid_date = false
) {
    // Determining the timezone to be converted from
    if (is_null($from_timezone)) {
        $from_timezone = 'UTC';
    }
    // Determining the timezone to be converted to
    if (is_null($to_timezone)) {
        $to_timezone = _api_get_timezone();
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
    try {
        $date = new DateTime($time, new DateTimezone($from_timezone));
        $date->setTimezone(new DateTimeZone($to_timezone));
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Converts a string into a timestamp safely (handling timezones), using strtotime
 *
 * @param string String to be converted
 * @param string Timezone (if null, the timezone will be determined based on user preference, or timezone chosen by the admin for the platform)
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
 * The given date should be in the timezone chosen by the administrator and/or user. Use api_get_local_time to get it.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Christophe Gesche<gesche@ipm.ucl.ac.be>
 *         originally inspired from from PhpMyAdmin
 * @author Ivan Tcholakov, 2009, code refactoring, adding support for predefined date/time formats.
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 *
 * @param mixed Timestamp or datetime string
 * @param mixed Date format (string or int; see date formats in the Chamilo system: TIME_NO_SEC_FORMAT, DATE_FORMAT_SHORT, DATE_FORMAT_LONG, DATE_TIME_FORMAT_LONG)
 * @param string $language (optional)        Language indentificator. If it is omited, the current interface language is assumed.
 * @return string                            Returns the formatted date.
 *
 * @link http://php.net/manual/en/function.strftime.php
 */
function api_format_date($time, $format = null, $language = null)
{
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
                $datetype = IntlDateFormatter::SHORT;
                $timetype = IntlDateFormatter::NONE;
                break;
            case DATE_FORMAT_NUMBER_NO_YEAR:
                $datetype = IntlDateFormatter::SHORT;
                $timetype = IntlDateFormatter::NONE;
                break;
            case DATE_FORMAT_NUMBER:
                $datetype = IntlDateFormatter::SHORT;
                $timetype = IntlDateFormatter::NONE;
                break;
            case TIME_NO_SEC_FORMAT:
                $datetype = IntlDateFormatter::NONE;
                $timetype = IntlDateFormatter::SHORT;
                break;
            case DATE_FORMAT_SHORT:
                $datetype = IntlDateFormatter::LONG;
                $timetype = IntlDateFormatter::NONE;
                break;
            case DATE_FORMAT_LONG:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::NONE;
                break;
            case DATE_TIME_FORMAT_LONG:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::SHORT;
                break;
            case DATE_FORMAT_LONG_NO_DAY:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::SHORT;
                break;
            case DATE_TIME_FORMAT_SHORT:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::SHORT;
                break;
            case DATE_TIME_FORMAT_SHORT_TIME_FIRST:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::SHORT;
                break;
            case DATE_TIME_FORMAT_LONG_24H:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::SHORT;
                break;
            default:
                $datetype = IntlDateFormatter::FULL;
                $timetype = IntlDateFormatter::SHORT;
        }
    }

    // Use ICU
    if (is_null($language)) {
        $language = api_get_language_isocode();
    }

    $date_formatter = new IntlDateFormatter(
        $language,
        $datetype,
        $timetype,
        date_default_timezone_get()
    );

    $formatted_date = api_to_system_encoding(
        $date_formatter->format($time),
        'UTF-8'
    );

    return $formatted_date;
}

/**
 * Returns the difference between the current date (date(now)) with the parameter $date in a string format like "2 days, 1 hour"
 * Example: $date = '2008-03-07 15:44:08';
 *             date_to_str($date) it will return 3 days, 20 hours
 * The given date should be in the timezone chosen by the user or administrator. Use api_get_local_time() to get it...
 *
 * @param  string The string has to be the result of a date function in this format -> date('Y-m-d H:i:s', time());
 * @return string The difference between the current date and the parameter in a literal way "3 days, 2 hour" *
 * @author Julio Montoya
 */

function date_to_str_ago($date)
{
    static $initialized = false;
    static $today, $yesterday;
    static $min_decade, $min_year, $min_month, $min_week, $min_day, $min_hour, $min_minute;
    static $min_decades, $min_years, $min_months, $min_weeks, $min_days, $min_hours, $min_minutes;
    static $sec_time_time, $sec_time_sing, $sec_time_plu;

    $system_timezone = date_default_timezone_get();
    date_default_timezone_set(_api_get_timezone());

    if (!$initialized) {
        $today = get_lang('Today');
        $yesterday = get_lang('Yesterday');

        $min_decade = get_lang('MinDecade');
        $min_year = get_lang('MinYear');
        $min_month = get_lang('MinMonth');
        $min_week = get_lang('MinWeek');
        $min_day = get_lang('MinDay');
        $min_hour = get_lang('MinHour');
        $min_minute = get_lang('MinMinute');

        $min_decades = get_lang('MinDecades');
        $min_years = get_lang('MinYears');
        $min_months = get_lang('MinMonths');
        $min_weeks = get_lang('MinWeeks');
        $min_days = get_lang('MinDays');
        $min_hours = get_lang('MinHours');
        $min_minutes = get_lang('MinMinutes');

        $sec_time_time = array(315569260, 31556926, 2629743.83, 604800, 86400, 3600, 60);
        $sec_time_sing = array($min_decade, $min_year, $min_month, $min_week, $min_day, $min_hour, $min_minute);
        $sec_time_plu = array($min_decades, $min_years, $min_months, $min_weeks, $min_days, $min_hours, $min_minutes);
        $initialized = true;
    }

    $dst_date = is_string($date) ? strtotime($date) : $date;
    // For avoiding calling date() several times
    $date_array = date('s/i/G/j/n/Y', $dst_date);
    $date_split = explode('/', $date_array);

    $dst_s = $date_split[0];
    $dst_m = $date_split[1];
    $dst_h = $date_split[2];
    $dst_day = $date_split[3];
    $dst_mth = $date_split[4];
    $dst_yr = $date_split[5];

    $dst_date = mktime($dst_h, $dst_m, $dst_s, $dst_mth, $dst_day, $dst_yr);
    $time = $offset = time() - $dst_date; // Seconds between current days and today.

    // Here start the functions sec_to_str()
    $act_day = date('d');
    $act_mth = date('n');
    $act_yr = date('Y');

    if ($dst_day == $act_day && $dst_mth == $act_mth && $dst_yr == $act_yr) {
        return $today;
    }

    if ($dst_day == $act_day - 1 && $dst_mth == $act_mth && $dst_yr == $act_yr) {
        return $yesterday;
    }

    $str_result = array();
    $time_result = array();
    $key_result = array();

    $str = '';
    $i = 0;
    for ($i = 0; $i < count($sec_time_time); $i++) {
        $seconds = $sec_time_time[$i];
        if ($seconds > $time) {
            continue;
        }
        $current_value = intval($time / $seconds);

        if ($current_value != 1) {
            $date_str = $sec_time_plu[$i];
        } else {
            $date_str = $sec_time_sing[$i];

        }
        $key_result[] = $sec_time_sing[$i];

        $str_result[] = $current_value.' '.$date_str;
        $time_result[] = $current_value;
        $str .= $current_value.$date_str;
        $time %= $seconds;
    }

    if ($key_result[0] == $min_day && $key_result[1] == $min_minute) {
        $key_result[1] = ' 0 '.$min_hours;
        $str_result[0] = $time_result[0].' '.$key_result[0];
        $str_result[1] = $key_result[1];
    }

    if ($key_result[0] == $min_year && ($key_result[1] == $min_day || $key_result[1] == $min_week)) {
        $key_result[1] = ' 0 '.$min_months;
        $str_result[0] = $time_result[0].' '.$key_result[0];
        $str_result[1] = $key_result[1];
    }

    if (!empty($str_result[1])) {
        $str = $str_result[0].', '.$str_result[1];
    } else {
        $str = $str_result[0];
    }

    date_default_timezone_set($system_timezone);
    return $str;
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
 * @param string $language (optional)    Language indentificator. If it is omited, the current interface language is assumed.
 * @return string                        Returns an array of week days (short names).
 * Example: api_get_week_days_short('english') means array('Sun', 'Mon', ... 'Sat').
 * Note: For all languages returned days are in the English order.
 */
function api_get_week_days_short($language = null)
{
    $days = & _api_get_day_month_names($language);
    return $days['days_short'];
}

/**
 * Returns an array of translated week days.
 * @param string $language (optional)    Language indentificator. If it is omited, the current interface language is assumed.
 * @return string                        Returns an array of week days.
 * Example: api_get_week_days_long('english') means array('Sunday, 'Monday', ... 'Saturday').
 * Note: For all languages returned days are in the English order.
 */
function api_get_week_days_long($language = null)
{
    $days = & _api_get_day_month_names($language);
    return $days['days_long'];
}

/**
 * Returns an array of translated months in short names.
 * @param string $language (optional)    Language indentificator. If it is omited, the current interface language is assumed.
 * @return string                        Returns an array of months (short names).
 * Example: api_get_months_short('english') means array('Jan', 'Feb', ... 'Dec').
 */
function api_get_months_short($language = null)
{
    $months = & _api_get_day_month_names($language);
    return $months['months_short'];
}

/**
 * Returns an array of translated months.
 * @param string $language (optional)    Language indentificator. If it is omited, the current interface language is assumed.
 * @return string                        Returns an array of months.
 * Example: api_get_months_long('english') means array('January, 'February' ... 'December').
 */
function api_get_months_long($language = null)
{
    $months = & _api_get_day_month_names($language);
    return $months['months_long'];
}

/**
 * Name order conventions
 */

/**
 * Builds a person (full) name depending on the convention for a given language.
 * @param string $first_name            The first name of the preson.
 * @param string $last_name                The last name of the person.
 * @param string $title                    The title of the person.
 * @param int/string $format (optional)    The person name format. It may be a pattern-string (for example '%t %l, %f' or '%T %F %L', ...) or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)    The language identificator. if it is omitted, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
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
function api_get_person_name($first_name, $last_name, $title = null, $format = null, $language = null, $encoding = null)
{
    static $valid = array();
    if (empty($format)) {
        $format = PERSON_NAME_COMMON_CONVENTION;
    }

    if (empty($language)) {
        $language = api_get_interface_language(false, true);
    }

    if (empty($encoding)) {
        $encoding = mb_internal_encoding();
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
    $person_name = str_replace(array('%f', '%l', '%t'), array($first_name, $last_name, $title), $format);
    if (strpos($format, '%F') !== false || strpos($format, '%L') !== false || strpos($format, '%T') !== false) {
        $person_name = str_replace(
            array(
                '%F',
                '%L',
                '%T'
            ),
            array(
                api_strtoupper($first_name, $encoding),
                api_strtoupper($last_name, $encoding),
                api_strtoupper($title, $encoding)
            ),
            $person_name
        );
    }
    return _api_clean_person_name($person_name);
}

/**
 * Checks whether a given format represents person name in Western order (for which first name is first).
 * @param int/string $format (optional)    The person name format. It may be a pattern-string (for example '%t. %l, %f') or some of the constants PERSON_NAME_COMMON_CONVENTION (default), PERSON_NAME_WESTERN_ORDER, PERSON_NAME_EASTERN_ORDER, PERSON_NAME_LIBRARY_ORDER.
 * @param string $language (optional)    The language indentificator. If it is omited, the current interface language is assumed. This parameter has meaning with the format PERSON_NAME_COMMON_CONVENTION only.
 * @return bool                            The result TRUE means that the order is first_name last_name, FALSE means last_name first_name.
 * Note: You may use this function for determing the order of the fields or columns "First name" and "Last name" in forms, tables and reports.
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
 * @param string $language (optional) The input language. If it is omited, the current interface language is assumed.
 * @return bool Returns boolean value. TRUE means ORDER BY first_name, last_name; FALSE means ORDER BY last_name, first_name.
 * Note: You may use this function:
 * 2. for constructing the ORDER clause of SQL queries, related to first_name and last_name;
 * 3. for adjusting php-implemented sorting in tables and reports.
 * @author Ivan Tcholakov
 */
function api_sort_by_first_name($language = null)
{
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
 * @param string $string                    The string being converted.
 * @param string $to_encoding                The encoding that $string is being converted to.
 * @param string $from_encoding (optional)    The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string                            Returns the converted string.
 * This function is aimed at replacing the function mb_convert_encoding() for human-language strings.
 * @link http://php.net/manual/en/function.mb-convert-encoding
 */
function api_convert_encoding($string, $to_encoding, $from_encoding = null)
{
    return mb_convert_encoding($string, $to_encoding, $from_encoding);
}

/**
 * Converts a given string into UTF-8 encoded string.
 * @param string $string                    The string being converted.
 * @param string $from_encoding (optional)    The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string                            Returns the converted string.
 * This function is aimed at replacing the function utf8_encode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-encode
 */
function api_utf8_encode($string, $from_encoding = null)
{
    return u::utf8_encode($string);
}

/**
 * Converts a given string from UTF-8 encoding to a specified encoding.
 * @param string $string                    The string being converted.
 * @param string $to_encoding (optional)    The encoding that $string is being converted to. If it is omited, the platform character set is assumed.
 * @return string                            Returns the converted string.
 * This function is aimed at replacing the function utf8_decode() for human-language strings.
 * @link http://php.net/manual/en/function.utf8-decode
 */
function api_utf8_decode($string, $to_encoding = null)
{
    return u::utf8_decode($string);
}

/**
 * Converts a given string into the system ecoding (or platform character set).
 * When $from encoding is omited on UTF-8 platforms then language dependent encoding
 * is guessed/assumed. On non-UTF-8 platforms omited $from encoding is assumed as UTF-8.
 * When the parameter $check_utf8_validity is true the function checks string's
 * UTF-8 validity and decides whether to try to convert it or not.
 * This function is useful for problem detection or making workarounds.
 * @param string $string                        The string being converted.
 * @param string $from_encoding (optional)        The encoding that $string is being converted from. It is guessed when it is omited.
 * @param bool $check_utf8_validity (optional)    A flag for UTF-8 validity check as condition for making conversion.
 * @return string                                Returns the converted string.
 */
function api_to_system_encoding($string, $from_encoding = null, $check_utf8_validity = false)
{
    $system_encoding = api_get_system_encoding();
    return api_convert_encoding($string, $system_encoding, $from_encoding);
}

/**
 * Converts all applicable characters to HTML entities.
 * @param string $string                The input string.
 * @param int $quote_style (optional)    The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)    The encoding (of the input string) used in conversion. If it is omited, the platform character set is assumed.
 * @return string                        Returns the converted string.
 * This function is aimed at replacing the function htmlentities() for human-language strings.
 * @link http://php.net/manual/en/function.htmlentities
 */
function api_htmlentities($string, $quote_style = ENT_COMPAT, $encoding = null)
{
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }

    return htmlentities($string, $quote_style, $encoding);
}


/**
 * Checks whether the specified encoding is supported by the html-entitiy related functions.
 * @param string $encoding	The specified encoding.
 * @return bool				Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function _api_html_entity_supports($encoding) {
    static $supports = array();
    if (!isset($supports[$encoding])) {
        // See http://php.net/manual/en/function.htmlentities.php
        $html_entity_encodings = array(
            'ISO-8859-1',
            'ISO-8859-15',
            'UTF-8',
            'CP866',
            'CP1251',
            'CP1252',
            'KOI8-R',
            'BIG5', '950',
            'GB2312', '936',
            'BIG5-HKSCS',
            'Shift_JIS', 'SJIS', '932',
            'EUC-JP', 'EUCJP'
        );
        $supports[$encoding] = api_equal_encodings($encoding, $html_entity_encodings);
    }
    return $supports[$encoding];
}

/**
 * Converts HTML entities into normal characters.
 * @param string $string                The input string.
 * @param int $quote_style (optional)    The quote style - ENT_COMPAT (default), ENT_QUOTES, ENT_NOQUOTES.
 * @param string $encoding (optional)    The encoding (of the result) used in conversion.
 * If it is omitted, the platform character set is assumed.
 * @return string                        Returns the converted string.
 * This function is aimed at replacing the function html_entity_decode() for human-language strings.
 * @link http://php.net/html_entity_decode
 */
function api_html_entity_decode($string, $quote_style = ENT_COMPAT, $encoding = null)
{
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    return html_entity_decode($string, $quote_style, $encoding);
}

/**
 * This function encodes (conditionally) a given string to UTF-8 if XmlHttp-request has been detected.
 * @param string $string                    The string being converted.
 * @param string $from_encoding (optional)    The encoding that $string is being converted from. If it is omited, the platform character set is assumed.
 * @return string                            Returns the converted string.
 */
function api_xml_http_response_encode($string, $from_encoding = null)
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

function _api_mb_internal_encoding()
{
    return mb_internal_encoding();
}

/**
 * Transliterates a string with arbitrary encoding into a plain ASCII string.
 *
 * Example:
 * echo api_transliterate(api_html_entity_decode(
 *     '&#1060;&#1105;&#1076;&#1086;&#1088; '.
 *     '&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
 *     '&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
 *     ENT_QUOTES, 'UTF-8'), 'X', 'UTF-8');
 * The output should be: Fyodor Mihaylovich Dostoevkiy
 *
 * @param string $string                    The input string.
 * @param string $unknown (optional)        Replacement character for unknown characters and illegal UTF-8 sequences.
 * @param string $from_encoding (optional)    The encoding of the input string. If it is omited, the platform character set is assumed.
 * @return string                            Plain ASCII output.

 */
function api_transliterate($string, $unknown = '?', $from_encoding = null)
{
    return URLify::transliterate($string);
    //return u::toAscii($string, $unknown);
}

/**
 * @see str_ireplace
 */
function api_str_ireplace($search, $replace, $subject, & $count = null, $encoding = null)
{
    return str_ireplace($search, $replace, $subject, $count);
}

/**
 * @see str_split
 */
function api_str_split($string, $split_length = 1, $encoding = null)
{
    return str_split($string, $split_length);
}

/**
 * @see stripos
 */
function api_stripos($haystack, $needle, $offset = 0, $encoding = null)
{
    return stripos($haystack, $needle, $offset);
}

/**
 * @see stristr
 */
function api_stristr($haystack, $needle, $before_needle = false, $encoding = null)
{
    return stristr($haystack, $needle, $before_needle);
}

/**
 * @see mb_strlen
 */
function api_strlen($string, $encoding = null)
{
    return mb_strlen($string);
}

/**
 * @see mb_strpos
 */
function api_strpos($haystack, $needle, $offset = 0, $encoding = null)
{
    return mb_strpos($haystack, $needle, $offset, $encoding);
}

/**
 * Finds the position of last occurrence (case insensitive) of a string in a string.
 * @param string $haystack                The string from which to get the position of the last occurrence.
 * @param string $needle                The string to be found.
 * @param int $offset (optional)        $offset may be specified to begin searching an arbitrary position. Negative values will stop searching at an arbitrary point prior to the end of the string.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return mixed                        Returns the numeric position of the first occurrence (case insensitive) of $needle in the $haystack, or FALSE if $needle is not found.
 * Note: The first character's position is 0, the second character position is 1, and so on.
 * This function is aimed at replacing the functions strripos() and mb_strripos() for human-language strings.
 * @link http://php.net/manual/en/function.strripos
 * @link http://php.net/manual/en/function.mb-strripos
 */
function api_strripos($haystack, $needle, $offset = 0, $encoding = null)
{
    return mb_strripos($haystack, $needle, $offset, $encoding);
}

/**
 * @see mb_strrpos
 */
function api_strrpos($haystack, $needle, $offset = 0, $encoding = null)
{
    return mb_strrpos($haystack, $needle, $offset);
}

/**
 * @see mb_strstr
 **/
function api_strstr($haystack, $needle, $before_needle = false, $encoding = null)
{
    return mb_strstr($haystack, $needle, $before_needle);
}

/**
 * @see strtolower
 */
function api_strtolower($string, $encoding = null)
{
    return strtolower($string);
}

/**
 * @see strtoupper
 */
function api_strtoupper($string, $encoding = null)
{
    return strtoupper($string);
}

/**
 * @see substr
 */
function api_substr($string, $start, $length = null, $encoding = null)
{
    return substr($string, $start, $length);
}

/**
 * @see substr_replace
 */
function api_substr_replace($string, $replacement, $start, $length = null, $encoding = null)
{
    return substr_replace($string, $replacement, $start, $length);
}

/**
 * @see ucfirst
 */
function api_ucfirst($string, $encoding = null)
{
    return ucfirst($string);
}

/**
 * @see ucwords
 */
function api_ucwords($string, $encoding = null)
{
    return ucwords($string);
}

/**
 * Performs a regular expression match, UTF-8 aware when it is applicable.
 * @param string $pattern                The pattern to search for, as a string.
 * @param string $subject                The input string.
 * @param array &$matches (optional)    If matches is provided, then it is filled with the results of search (as an array).
 *                                         $matches[0] will contain the text that matched the full pattern, $matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.
 * @param int $flags (optional)            Could be PREG_OFFSET_CAPTURE. If this flag is passed, for every occurring match the appendant string offset will also be returned.
 *                                         Note that this changes the return value in an array where every element is an array consisting of the matched string at index 0 and its string offset into subject at index 1.
 * @param int $offset (optional)        Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int|boolean                    Returns the number of times pattern matches or FALSE if an error occurred.
 * @link http://php.net/preg_match
 */
function api_preg_match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0, $encoding = null)
{
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    return preg_match(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $matches, $flags, $offset);
}

/**
 * Performs a global regular expression match, UTF-8 aware when it is applicable.
 * @param string $pattern                The pattern to search for, as a string.
 * @param string $subject                The input string.
 * @param array &$matches (optional)    Array of all matches in multi-dimensional array ordered according to $flags.
 * @param int $flags (optional)            Can be a combination of the following flags (note that it doesn't make sense to use PREG_PATTERN_ORDER together with PREG_SET_ORDER):
 * PREG_PATTERN_ORDER - orders results so that $matches[0] is an array of full pattern matches, $matches[1] is an array of strings matched by the first parenthesized subpattern, and so on;
 * PREG_SET_ORDER - orders results so that $matches[0] is an array of first set of matches, $matches[1] is an array of second set of matches, and so on;
 * PREG_OFFSET_CAPTURE - If this flag is passed, for every occurring match the appendant string offset will also be returned. Note that this changes the value of matches
 * in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
 * If no order flag is given, PREG_PATTERN_ORDER is assumed.
 * @param int $offset (optional)        Normally, the search starts from the beginning of the subject string. The optional parameter offset can be used to specify the alternate place from which to start the search.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int|boolean                    Returns the number of full pattern matches (which might be zero), or FALSE if an error occurred.
 * @link http://php.net/preg_match_all
 */
function api_preg_match_all($pattern, $subject, &$matches, $flags = PREG_PATTERN_ORDER, $offset = 0, $encoding = null)
{
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
 * @param string|array $pattern            The pattern to search for. It can be either a string or an array with strings.
 * @param string|array $replacement        The string or an array with strings to replace.
 * @param string|array $subject            The string or an array with strings to search and replace.
 * @param int $limit                    The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @param int &$count                    If specified, this variable will be filled with the number of replacements done.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array|string|null            returns an array if the subject parameter is an array, or a string otherwise.
 * If matches are found, the new subject will be returned, otherwise subject will be returned unchanged or NULL if an error occurred.
 * @link http://php.net/preg_replace
 */
function api_preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = 0, $encoding = null)
{
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
 * Performs a regular expression search and replace using a callback function, UTF-8 aware when it is applicable.
 * @param string|array $pattern            The pattern to search for. It can be either a string or an array with strings.
 * @param function $callback            A callback that will be called and passed an array of matched elements in the $subject string. The callback should return the replacement string.
 * @param string|array $subject            The string or an array with strings to search and replace.
 * @param int $limit (optional)            The maximum possible replacements for each pattern in each subject string. Defaults to -1 (no limit).
 * @param int &$count (optional)        If specified, this variable will be filled with the number of replacements done.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array|string                    Returns an array if the subject parameter is an array, or a string otherwise.
 * @link http://php.net/preg_replace_callback
 */
function api_preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = 0, $encoding = null)
{
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    if (is_array($pattern)) {
        foreach ($pattern as &$p) {
            $p = api_is_utf8($encoding) ? $p.'u' : $p;
        }
    } else {
        $pattern = api_is_utf8($encoding) ? $pattern.'u' : $pattern;
    }
    return preg_replace_callback($pattern, $callback, $subject, $limit, $count);
}

/**
 * Splits a string by a regular expression, UTF-8 aware when it is applicable.
 * @param string $pattern                The pattern to search for, as a string.
 * @param string $subject                The input string.
 * @param int $limit (optional)            If specified, then only substrings up to $limit are returned with the rest of the string being placed in the last substring. A limit of -1, 0 or null means "no limit" and, as is standard across PHP.
 * @param int $flags (optional)            $flags can be any combination of the following flags (combined with bitwise | operator):
 * PREG_SPLIT_NO_EMPTY - if this flag is set, only non-empty pieces will be returned;
 * PREG_SPLIT_DELIM_CAPTURE - if this flag is set, parenthesized expression in the delimiter pattern will be captured and returned as well;
 * PREG_SPLIT_OFFSET_CAPTURE - If this flag is set, for every occurring match the appendant string offset will also be returned.
 * Note that this changes the return value in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return array                        Returns an array containing substrings of $subject split along boundaries matched by $pattern.
 * @link http://php.net/preg_split
 */
function api_preg_split($pattern, $subject, $limit = -1, $flags = 0, $encoding = null)
{
    if (empty($encoding)) {
        $encoding = _api_mb_internal_encoding();
    }
    return preg_split(api_is_utf8($encoding) ? $pattern.'u' : $pattern, $subject, $limit, $flags);
}

/**
 * Note: Try to avoid using this function. Use api_preg_match() with Perl-compatible regular expression syntax.
 *
 * Executes a regular expression match with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern            The regular expression pattern.
 * @param string $string            The searched string.
 * @param array $regs (optional)    If specified, by this passed by reference parameter an array containing found match and its substrings is returned.
 * @return mixed                    1 if match is found, FALSE if not. If $regs has been specified, byte-length of the found match is returned, or FALSE if no match has been found.
 * This function is aimed at replacing the functions ereg() and mb_ereg() for human-language strings.
 * @link http://php.net/manual/en/function.ereg
 * @link http://php.net/manual/en/function.mb-ereg
 * @deprecated
 */
function api_ereg($pattern, $string, & $regs = null)
{
    $count = func_num_args();
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if ($count < 3) {
            return @mb_ereg($pattern, $string);
        }
        return @mb_ereg($pattern, $string, $regs);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        global $_api_encoding;
        $_api_encoding = $encoding;
        _api_mb_regex_encoding('UTF-8');
        if ($count < 3) {
            $result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
        } else {
            $result = @mb_ereg(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
            $regs = _api_array_utf8_decode($regs);
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    if ($count < 3) {
        return ereg($pattern, $string);
    }
    return ereg($pattern, $string, $regs);
}

/**
 * Note: Try to avoid using this function. Use api_preg_match() with Perl-compatible regular expression syntax.
 *
 * Executes a regular expression match, ignoring case, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern            The regular expression pattern.
 * @param string $string            The searched string.
 * @param array $regs (optional)    If specified, by this passed by reference parameter an array containing found match and its substrings is returned.
 * @return mixed                    1 if match is found, FALSE if not. If $regs has been specified, byte-length of the found match is returned, or FALSE if no match has been found.
 * This function is aimed at replacing the functions eregi() and mb_eregi() for human-language strings.
 * @link http://php.net/manual/en/function.eregi
 * @link http://php.net/manual/en/function.mb-eregi
 */
function api_eregi($pattern, $string, & $regs = null)
{
    $count = func_num_args();
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if ($count < 3) {
            return @mb_eregi($pattern, $string);
        }
        return @mb_eregi($pattern, $string, $regs);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        global $_api_encoding;
        $_api_encoding = $encoding;
        _api_mb_regex_encoding('UTF-8');
        if ($count < 3) {
            $result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding));
        } else {
            $result = @mb_eregi(api_utf8_encode($pattern, $encoding), api_utf8_encode($string, $encoding), $regs);
            $regs = _api_array_utf8_decode($regs);
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    if ($count < 3) {
        return eregi($pattern, $string);
    }
    return eregi($pattern, $string, $regs);
}

/**
 * Note: Try to avoid using this function. Use api_preg_replace() with Perl-compatible regular expression syntax.
 *
 * Scans string for matches to pattern, then replaces the matched text with replacement, ignoring case, with extended multibyte support.
 * By default this function uses the platform character set.
 * @param string $pattern                The regular expression pattern.
 * @param string $replacement            The replacement text.
 * @param string $string                The searched string.
 * @param string $option (optional)        Matching condition.
 * If i is specified for the matching condition parameter, the case will be ignored.
 * If x is specified, white space will be ignored.
 * If m is specified, match will be executed in multiline mode and line break will be included in '.'.
 * If p is specified, match will be executed in POSIX mode, line break will be considered as normal character.
 * If e is specified, replacement string will be evaluated as PHP expression.
 * @return mixed                        The modified string is returned. If no matches are found within the string, then it will be returned unchanged. FALSE will be returned on error.
 * This function is aimed at replacing the functions eregi_replace() and mb_eregi_replace() for human-language strings.
 * @link http://php.net/manual/en/function.eregi-replace
 * @link http://php.net/manual/en/function.mb-eregi-replace
 */
function api_eregi_replace($pattern, $replacement, $string, $option = null)
{
    $encoding = _api_mb_regex_encoding();
    if (_api_mb_supports($encoding)) {
        if (is_null($option)) {
            return @mb_eregi_replace($pattern, $replacement, $string);
        }
        return @mb_eregi_replace($pattern, $replacement, $string, $option);
    }
    if (MBSTRING_INSTALLED && api_is_encoding_supported($encoding)) {
        _api_mb_regex_encoding('UTF-8');
        if (is_null($option)) {
            $result = api_utf8_decode(
                @mb_eregi_replace(
                    api_utf8_encode($pattern, $encoding),
                    api_utf8_encode($replacement, $encoding),
                    api_utf8_encode($string, $encoding)
                ),
                $encoding
            );
        } else {
            $result = api_utf8_decode(
                @mb_eregi_replace(
                    api_utf8_encode($pattern, $encoding),
                    api_utf8_encode($replacement, $encoding),
                    api_utf8_encode($string, $encoding),
                    $option
                ),
                $encoding
            );
        }
        _api_mb_regex_encoding($encoding);
        return $result;
    }
    return eregi_replace($pattern, $replacement, $string);
}

/**
 * String comparison
 */

/**
 * Performs string comparison, case insensitive, language sensitive, with extended multibyte support.
 * @param string $string1                The first string.
 * @param string $string2                The second string.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int                            Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcasecmp
 */
function api_strcasecmp($string1, $string2, $language = null, $encoding = null)
{
    return strcasecmp($string1, $string2);
}

/**
 * Performs string comparison, case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1                The first string.
 * @param string $string2                The second string.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int                            Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strcmp($string1, $string2, $language = null, $encoding = null)
{
    return strcmp($string1, $string2);
}

/**
 * Performs string comparison in so called "natural order", case insensitive, language sensitive, with extended multibyte support.
 * @param string $string1                The first string.
 * @param string $string2                The second string.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int                            Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcasecmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcasecmp
 */
function api_strnatcasecmp($string1, $string2, $language = null, $encoding = null)
{
    return strnatcasecmp($string1, $string2);
}

/**
 * Performs string comparison in so called "natural order", case sensitive, language sensitive, with extended multibyte support.
 * @param string $string1                The first string.
 * @param string $string2                The second string.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return int                            Returns < 0 if $string1 is less than $string2; > 0 if $string1 is greater than $string2; and 0 if the strings are equal.
 * This function is aimed at replacing the function strnatcmp() for human-language strings.
 * @link http://php.net/manual/en/function.strnatcmp.php
 * @link http://php.net/manual/en/collator.compare.php
 */
function api_strnatcmp($string1, $string2, $language = null, $encoding = null)
{
    return strnatcmp($string1, $string2);
}

/**
 * Sorts an array with maintaining index association, elements will be arranged from the lowest to the highest.
 * @param array $array                    The input array.
 * @param int $sort_flag (optional)        Shows how elements of the array to be compared.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool                            Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function asort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.asort.php
 * @link http://php.net/manual/en/collator.asort.php
 */
function api_asort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null)
{
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if (api_is_utf8($encoding)) {
                $sort_flag = ($sort_flag == SORT_LOCALE_STRING) ? SORT_STRING : $sort_flag;
                return collator_asort($collator, $array, _api_get_collator_sort_flag($sort_flag));
            } elseif ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return uasort($array, '_api_cmp');
            }
        }
    }
    return asort($array, $sort_flag);
}

/**
 * Sorts an array with maintaining index association, elements will be arranged from the highest to the lowest (in reverse order).
 * @param array $array                    The input array.
 * @param int $sort_flag (optional)        Shows how elements of the array to be compared.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool                            Returns TRUE on success, FALSE on error.
 * Note: $sort_flag may have the following values:
 * SORT_REGULAR - internal PHP-rules for comparison will be applied, without preliminary changing types;
 * SORT_NUMERIC - items will be compared as numbers;
 * SORT_STRING - items will be compared as strings. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale;
 * SORT_LOCALE_STRING - items will be compared as strings depending on the current POSIX locale. If intl extension is enabled, then comparison will be language-sensitive using internally a created ICU locale.
 * This function is aimed at replacing the function arsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.arsort.php
 */
function api_arsort(&$array, $sort_flag = SORT_REGULAR, $language = null, $encoding = null)
{
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_collator($language);
        if (is_object($collator)) {
            if ($sort_flag == SORT_STRING || $sort_flag == SORT_LOCALE_STRING) {
                global $_api_collator, $_api_encoding;
                $_api_collator = $collator;
                $_api_encoding = $encoding;
                return uasort($array, '_api_rcmp');
            }
        }
    }
    return arsort($array, $sort_flag);
}

/**
 * Sorts an array using natural order algorithm.
 * @param array $array                    The input array.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool                            Returns TRUE on success, FALSE on error.
 * This function is aimed at replacing the function natsort() for sorting human-language strings.
 * @link http://php.net/manual/en/function.natsort.php
 */
function api_natsort(&$array, $language = null, $encoding = null)
{
    if (INTL_INSTALLED) {
        if (empty($encoding)) {
            $encoding = _api_mb_internal_encoding();
        }
        $collator = _api_get_alpha_numerical_collator($language);
        if (is_object($collator)) {
            global $_api_collator, $_api_encoding;
            $_api_collator = $collator;
            $_api_encoding = $encoding;
            return uasort($array, '_api_cmp');
        }
    }
    return natsort($array);
}

/**
 * A reverse function from php-core function strnatcmp(), performs string comparison in reverse natural (alpha-numerical) order.
 * @param string $string1		The first string.
 * @param string $string2		The second string.
 * @return int					Returns 0 if $string1 = $string2; >0 if $string1 < $string2; <0 if $string1 > $string2.
 */
function _api_strnatrcmp($string1, $string2) {
    return strnatcmp($string2, $string1);
}

/**
 * Sorts an array using natural order algorithm in reverse order.
 * @param array $array                    The input array.
 * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool                            Returns TRUE on success, FALSE on error.
 */
function api_natrsort(&$array, $language = null, $encoding = null)
{
    return uasort($array, '_api_strnatrcmp');
}

/**
 * Common sting operations with arrays
 */

/**
 * Checks if a value exists in an array, a case insensitive version of in_array() function with extended multibyte support.
 * @param mixed $needle                    The searched value. If needle is a string, the comparison is done in a case-insensitive manner.
 * @param array $haystack                The array.
 * @param bool $strict (optional)        If is set to TRUE then the function will also check the types of the $needle in the $haystack. The default value if FALSE.
 * @param string $encoding (optional)    The used internally by this function character encoding. If it is omitted, the platform character set will be used by default.
 * @return bool                            Returns TRUE if $needle is found in the array, FALSE otherwise.
 * @link http://php.net/manual/en/function.in-array.php
 */
function api_in_array_nocase($needle, $haystack, $strict = false, $encoding = null)
{
    if (is_array($needle)) {
        foreach ($needle as $item) {
            if (api_in_array_nocase($item, $haystack, $strict, $encoding)) {
                return true;
            }
        }
        return false;
    }
    if (!is_string($needle)) {
        return in_array($needle, $haystack, $strict);
    }
    $needle = api_strtolower($needle, $encoding);
    if (!is_array($haystack)) {
        return false;
    }
    foreach ($haystack as $item) {
        if ($strict && !is_string($item)) {
            continue;
        }
        if (api_strtolower($item, $encoding) == $needle) {
            return true;
        }
    }
    return false;
}

/**
 * This function returns the encoding, currently used by the system.
 * @return string    The system's encoding, set in the configuration file
 */
function api_get_system_encoding()
{
    global $configuration;
    return isset($configuration['platform_charset']) ? $configuration['platform_charset'] : 'utf-8';
}

/**
 * Checks whether a specified encoding is supported by this API.
 * @param string $encoding    The specified encoding.
 * @return bool                Returns TRUE when the specified encoding is supported, FALSE othewise.
 */
function api_is_encoding_supported($encoding)
{
    static $supported = array();
    if (!isset($supported[$encoding])) {
        $supported[$encoding] = _api_mb_supports($encoding) || _api_iconv_supports(
            $encoding
        ) || _api_convert_encoding_supports($encoding);
    }
    return $supported[$encoding];
}


/**
 * Detects encoding of plain text.
 * @param string $string                The input text.
 * @param string $language (optional)    The language of the input text, provided if it is known.
 * @return string                        Returns the detected encoding.
 */
function api_detect_encoding($string, $language = null)
{
    return mb_detect_encoding($string);
}

/**
 * Checks a string for UTF-8 validity.
 *
 */
function api_is_valid_utf8($string)
{
    return u::isUtf8($string);
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
 * Returns returns person name convention for a given language.
 * @param string $language	The input language.
 * @param string $type		The type of the requested convention. It may be 'format' for name order convention or 'sort_by' for name sorting convention.
 * @return mixed			Depending of the requested type, the returned result may be string or boolean; null is returned on error;
 */
function _api_get_person_name_convention($language, $type)
{
    global $app;
    $conventions = $app['name_order_conventions'];
    $language = api_purify_language_id($language);

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
    if (empty($format) ||
        stripos($format, '%f') === false ||
        stripos($format, '%l') === false
    ) {
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
    return preg_replace(
        array('/\s+/', '/, ,/', '/,+/', '/^[ ,]/', '/[ ,]$/'),
        array(' ', ', ', ',', '', ''),
        $person_name
    );
}

/**
 * Returns an array of translated week days and months, short and normal names.
 * @param string $language (optional)	If it is omitted, the current interface language is assumed.
 * @return array						Returns a multidimensional array with translated week days and months.
 */
function &_api_get_day_month_names($language = null)
{
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
