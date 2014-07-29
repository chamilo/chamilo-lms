<?php
/* For licensing terms, see /license.txt */

/**
 * This is the text library for Chamilo.
 * It is loaded during the global initialization,
 * so the functions below are available everywhere.
 *
 * @package chamilo.library
 */

class Text
{

    /**
     * This function strips all html-tags found in the input string and outputs a pure text.
     * Mostly, the function is to be used before language or encoding detection of the input string.
     * @param  string $string    The input string with html-tags to be converted to plain text.
     * @return string            The returned plain text as a result.
     */
    static function api_html_to_text($string)
    {
        // These purifications have been found experimentally, for nice looking output.
        $string = preg_replace('/<br[^>]*>/i', "\n", $string);
        $string = preg_replace('/<\/?(div|p|h[1-6]|table|ol|ul|blockquote)[^>]*>/i', "\n", $string);
        $string = preg_replace('/<\/(tr|li)[^>]*>/i', "\n", $string);
        $string = preg_replace('/<\/(td|th)[^>]*>/i', "\t", $string);

        $string = strip_tags($string);

        // Line endings unification and cleaning.
        $string = str_replace(array("\r\n", "\n\r", "\r"), "\n", $string);
        $string = preg_replace('/\s*\n/', "\n", $string);
        $string = preg_replace('/\n+/', "\n", $string);

        return trim($string);
    }

    /**
     * Detects encoding of html-formatted text.
     * @param  string $string                The input html-formatted text.
     * @return string                        Returns the detected encoding.
     */
    static function api_detect_encoding_html($string)
    {
        if (@preg_match('/<head.*(<meta[^>]*content=[^>]*>).*<\/head>/si', $string, $matches)) {
            if (@preg_match('/<meta[^>]*charset=(.*)["\';][^>]*>/si', $matches[1], $matches)) {
                return api_refine_encoding_id(trim($matches[1]));
            }
        }
        return api_detect_encoding(self::api_html_to_text($string));
    }

    /**
     * Converts the text of a html-document to a given encoding, the meta-tag is changed accordingly.
     * @param string $string                The input full-html document.
     * @param string                        The new encoding value to be set.
     */
    static function api_set_encoding_html(&$string, $encoding) {
        $old_encoding = self::api_detect_encoding_html($string);
        if (@preg_match('/(.*<head.*)(<meta[^>]*content=[^>]*>)(.*<\/head>.*)/si', $string, $matches)) {
            $meta = $matches[2];
            if (@preg_match("/(<meta[^>]*charset=)(.*)([\"';][^>]*>)/si", $meta, $matches1)) {
                $meta = $matches1[1] . $encoding . $matches1[3];
                $string = $matches[1] . $meta . $matches[3];
            } else {
                $string = $matches[1] . '<meta http-equiv="Content-Type" content="text/html; charset=' . $encoding . '"/>' . $matches[3];
            }
        } else {
            $count = 1;
            $string = str_ireplace('</head>', '<meta http-equiv="Content-Type" content="text/html; charset=' . $encoding . '"/></head>', $string, $count);
        }
        $string = api_convert_encoding($string, $encoding, $old_encoding);
    }

    /**
     * Returns the title of a html document.
     * @param string $string                The contents of the input document.
     * @param string $input_encoding        The encoding of the input document. If the value is not set, it is detected.
     * @param string $$output_encoding      The encoding of the retrieved title. If the value is not set, the system encoding is assumend.
     * @return string                       The retrieved title, html-entities and extra-whitespace between the words are cleaned.
     */
    static function api_get_title_html(&$string, $output_encoding = null, $input_encoding = null)
    {
        if (@preg_match('/<head.+<title[^>]*>(.*)<\/title>/msi', $string, $matches)) {
            if (empty($output_encoding)) {
                $output_encoding = api_get_system_encoding();
            }
            if (empty($input_encoding)) {
                $input_encoding = self::api_detect_encoding_html($string);
            }
            return trim(@preg_replace('/\s+/', ' ', api_html_entity_decode(api_convert_encoding($matches[1], $output_encoding, $input_encoding), ENT_QUOTES, $output_encoding)));
        }
        return '';
    }

    /**
     * Detects encoding of xml-formatted text.
     * @param string $string                The input xml-formatted text.
     * @param string $default_encoding      This is the default encoding to be returned if there is no way the xml-text's encoding to be detected. If it not spesified, the system encoding is assumed then.
     * @return string                       Returns the detected encoding.
     * @todo The second parameter is to be eliminated. See api_detect_encoding_html().
     */
    static function api_detect_encoding_xml($string, $default_encoding = null) {
        if (preg_match(_PCRE_XML_ENCODING, $string, $matches)) {
            return api_refine_encoding_id($matches[1]);
        }
        if (api_is_valid_utf8($string)) {
            return 'UTF-8';
        }
        if (empty($default_encoding)) {
            $default_encoding = _api_mb_internal_encoding();
        }
        return api_refine_encoding_id($default_encoding);
    }

    /**
     * Converts character encoding of a xml-formatted text. If inside the text the encoding is declared, it is modified accordingly.
     * @param string $string                    The text being converted.
     * @param string $to_encoding               The encoding that text is being converted to.
     * @param string $from_encoding (optional)  The encoding that text is being converted from. If it is omited, it is tried to be detected then.
     * @return string                           Returns the converted xml-text.
     */
    static function api_convert_encoding_xml($string, $to_encoding, $from_encoding = null) {
        return self::_api_convert_encoding_xml($string, $to_encoding, $from_encoding);
    }

    /**
     * Converts character encoding of a xml-formatted text into UTF-8. If inside the text the encoding is declared, it is set to UTF-8.
     * @param string $string                    The text being converted.
     * @param string $from_encoding (optional)  The encoding that text is being converted from. If it is omited, it is tried to be detected then.
     * @return string                           Returns the converted xml-text.
     */
    static function api_utf8_encode_xml($string, $from_encoding = null) {
        return self::_api_convert_encoding_xml($string, 'UTF-8', $from_encoding);
    }

    /**
     * Converts character encoding of a xml-formatted text from UTF-8 into a specified encoding. If inside the text the encoding is declared, it is modified accordingly.
     * @param string $string                    The text being converted.
     * @param string $to_encoding (optional)    The encoding that text is being converted to. If it is omited, the platform character set is assumed.
     * @return string                           Returns the converted xml-text.
     */
    static function api_utf8_decode_xml($string, $to_encoding = null) {
        if (empty($to_encoding)) {
            $to_encoding = _api_mb_internal_encoding();
        }
        return self::_api_convert_encoding_xml($string, $to_encoding, 'UTF-8');
    }

    /**
     * Converts character encoding of a xml-formatted text. If inside the text the encoding is declared, it is modified accordingly.
     * @param string $string                    The text being converted.
     * @param string $to_encoding               The encoding that text is being converted to.
     * @param string $from_encoding (optional)  The encoding that text is being converted from. If the value is empty, it is tried to be detected then.
     * @return string                           Returns the converted xml-text.
     */
    static function _api_convert_encoding_xml(&$string, $to_encoding, $from_encoding) {
        if (empty($from_encoding)) {
            $from_encoding = self::api_detect_encoding_xml($string);
        }
        $to_encoding = api_refine_encoding_id($to_encoding);
        if (!preg_match('/<\?xml.*\?>/m', $string, $matches)) {
            return api_convert_encoding('<?xml version="1.0" encoding="' . $to_encoding . '"?>' . "\n" . $string, $to_encoding, $from_encoding);
        }
        if (!preg_match(_PCRE_XML_ENCODING, $string)) {
            if (strpos($matches[0], 'standalone') !== false) {
                // The encoding option should precede the standalone option, othewise DOMDocument fails to load the document.
                $replace = str_replace('standalone', ' encoding="' . $to_encoding . '" standalone', $matches[0]);
            } else {
                $replace = str_replace('?>', ' encoding="' . $to_encoding . '"?>', $matches[0]);
            }
            return api_convert_encoding(str_replace($matches[0], $replace, $string), $to_encoding, $from_encoding);
        }
        global $_api_encoding;
        $_api_encoding = api_refine_encoding_id($to_encoding);
        return api_convert_encoding(preg_replace_callback(_PCRE_XML_ENCODING, array('Text', '_api_convert_encoding_xml_callback'), $string), $to_encoding, $from_encoding);
    }

    /**
     * A callback for serving the function _api_convert_encoding_xml().
     * @param array $matches    Input array of matches corresponding to the xml-declaration.
     * @return string           Returns the xml-declaration with modified encoding.
     */
    static function _api_convert_encoding_xml_callback($matches) {
        global $_api_encoding;
        return str_replace($matches[1], $_api_encoding, $matches[0]);
    }

    /* CSV processing functions */

    /**
     * Parses CSV data (one line) into an array. This function is not affected by the OS-locale settings.
     * @param string $string                  The input string.
     * @param string $delimiter (optional)    The field delimiter, one character only. The default delimiter character is comma {,).
     * @param string $enclosure (optional)    The field enclosure, one character only. The default enclosure character is quote (").
     * @param string $escape (optional)       The escape character, one character only. The default escape character is backslash (\).
     * @return array                          Returns an array containing the fields read.
     * Note: In order this function to work correctly with UTF-8, limitation for the parameters $delimiter, $enclosure and $escape
     * should be kept. These parameters should be single ASCII characters only. Thus the implementation of this function is faster.
     * @link http://php.net/manual/en/function.str-getcsv.php   (exists as of PHP 5 >= 5.3.0)
     */
    static function & api_str_getcsv(& $string, $delimiter = ',', $enclosure = '"', $escape = '\\') {
        $delimiter = (string) $delimiter;
        if (api_byte_count($delimiter) > 1) {
            $delimiter = $delimiter[1];
        }
        $enclosure = (string) $enclosure;
        if (api_byte_count($enclosure) > 1) {
            $enclosure = $enclosure[1];
        }
        $escape = (string) $escape;
        if (api_byte_count($escape) > 1) {
            $escape = $escape[1];
        }
        $str = (string) $string;
        $len = api_byte_count($str);
        $enclosed = false;
        $escaped = false;
        $value = '';
        $result = array();

        for ($i = 0; $i < $len; $i++) {
            $char = $str[$i];
            if ($char == $escape) {
                if (!$escaped) {
                    $escaped = true;
                    continue;
                }
            }
            $escaped = false;
            switch ($char) {
                case $enclosure:
                    if ($enclosed && $str[$i + 1] == $enclosure) {
                        $value .= $char;
                        $i++;
                    } else {
                        $enclosed = !$enclosed;
                    }
                    break;
                case $delimiter:
                    if (!$enclosed) {
                        $result[] = $value;
                        $value = '';
                    } else {
                        $value .= $char;
                    }
                    break;
                default:
                    $value .= $char;
                    break;
            }
        }
        if (!empty($value)) {
            $result[] = $value;
        }

        return $result;
    }

    /**
     * Reads a line from a file pointer and parses it for CSV fields. This function is not affected by the OS-locale settings.
     * @param resource $handle                The file pointer, it must be valid and must point to a file successfully opened by fopen().
     * @param int $length (optional)          Reading ends when length - 1 bytes have been read, on a newline (which is included in the return value), or on EOF (whichever comes first).
     *                                        If no length is specified, it will keep reading from the stream until it reaches the end of the line.
     * @param string $delimiter (optional)    The field delimiter, one character only. The default delimiter character is comma {,).
     * @param string $enclosure (optional)    The field enclosure, one character only. The default enclosure character is quote (").
     * @param string $escape (optional)       The escape character, one character only. The default escape character is backslash (\).
     * @return array                          Returns an array containing the fields read.
     * Note: In order this function to work correctly with UTF-8, limitation for the parameters $delimiter, $enclosure and $escape
     * should be kept. These parameters should be single ASCII characters only.
     * @link http://php.net/manual/en/function.fgetcsv.php
     */
    static function api_fgetcsv($handle, $length = null, $delimiter = ',', $enclosure = '"', $escape = '\\') {
        if (($line = is_null($length) ? fgets($handle) : fgets($handle, $length)) !== false) {
            $line = rtrim($line, "\r\n");
            return self::api_str_getcsv($line, $delimiter, $enclosure, $escape);
        }
        return false;
    }

    /* Functions for supporting ASCIIMathML mathematical formulas and ASCIIsvg maathematical graphics */

    /**
     * Dectects ASCIIMathML formula presence within a given html text.
     * @param string $html      The input html text.
     * @return bool             Returns TRUE when there is a formula found or FALSE otherwise.
     */
    static function api_contains_asciimathml($html) {
        if (!preg_match_all('/<span[^>]*class\s*=\s*[\'"](.*?)[\'"][^>]*>/mi', $html, $matches)) {
            return false;
        }
        foreach ($matches[1] as $string) {
            $string = ' ' . str_replace(',', ' ', $string) . ' ';
            if (preg_match('/\sAM\s/m', $string)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Dectects ASCIIsvg graphics presence within a given html text.
     * @param string $html      The input html text.
     * @return bool             Returns TRUE when there is a graph found or FALSE otherwise.
     */
    static function api_contains_asciisvg($html) {
        if (!preg_match_all('/<embed([^>]*?)>/mi', $html, $matches)) {
            return false;
        }
        foreach ($matches[1] as $string) {
            $string = ' ' . str_replace(',', ' ', $string) . ' ';
            if (preg_match('/sscr\s*=\s*[\'"](.*?)[\'"]/m', $string)) {
                return true;
            }
        }
        return false;
    }

    /* Miscellaneous text processing functions */

    /**
     * Convers a string from camel case into underscore.
     * Works correctly with ASCII strings only, implementation for human-language strings is not necessary.
     * @param string $string                            The input string (ASCII)
     * @return string                                   The converted result string
     */
    static function api_camel_case_to_underscore($string) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $string));
    }

    /**
     * Converts a string with underscores into camel case.
     * Works correctly with ASCII strings only, implementation for human-language strings is not necessary.
     * @param string $string                            The input string (ASCII)
     * @param bool $capitalise_first_char (optional)    If true (default), the function capitalises the first char in the result string.
     * @return string                                   The converted result string
     */
    static function api_underscore_to_camel_case($string, $capitalise_first_char = true) {
        if ($capitalise_first_char) {
            $string = ucfirst($string);
        }
        return preg_replace_callback('/_([a-z])/', array('Text', '_api_camelize'), $string);
    }

    // A function for internal use, only for this library.
    static function _api_camelize($match) {
        return strtoupper($match[1]);
    }

    /**
     * Truncates a string.
     *
     * @author Brouckaert Olivier
     * @param  string $text                  The text to truncate.
     * @param  integer $length               The approximate desired length. The length of the suffix below is to be added to have the total length of the result string.
     * @param  string $suffix                A suffix to be added as a replacement.
     * @param string $encoding (optional)    The encoding to be used. If it is omitted, the platform character set will be used by default.
     * @param  boolean $middle               If this parameter is true, truncation is done in the middle of the string.
     * @return string                        Truncated string, decorated with the given suffix (replacement).
     */
    static function api_trunc_str($text, $length = 30, $suffix = '...', $middle = false, $encoding = null) {
        if (empty($encoding)) {
            $encoding = api_get_system_encoding();
        }
        $text_length = api_strlen($text, $encoding);
        if ($text_length <= $length) {
            return $text;
        }
        if ($middle) {
            return rtrim(api_substr($text, 0, round($length / 2), $encoding)) . $suffix . ltrim(api_substr($text, - round($length / 2), $text_length, $encoding));
        }
        return rtrim(api_substr($text, 0, $length, $encoding)) . $suffix;
    }

    /**
     * Handling simple and double apostrofe in order that strings be stored properly in database
     *
     * @author Denes Nagy
     * @param  string variable - the variable to be revised
     */
    static function domesticate($input) {
        $input = stripslashes($input);
        $input = str_replace("'", "''", $input);
        $input = str_replace('"', "''", $input);
        return ($input);
    }

    /**
     * function make_clickable($string)
     *
     * @desc   Completes url contained in the text with "<a href ...".
     *         However the function simply returns the submitted text without any
     *         transformation if it already contains some "<a href:" or "<img src=".
     * @param string $text text to be converted
     * @return text after conversion
     * See http://php.net/manual/fr/function.eregi-replace.php
     *
     * - Goes through the given string, and replaces xxxx://yyyy with an HTML <a> tag linking
     *     to that URL
     * - Goes through the given string, and replaces www.xxxx.yyyy[zzzz] with an HTML <a> tag linking
     *     to http://www.xxxx.yyyy[/zzzz]
     * - Goes through the given string, and replaces xxxx@yyyy with an HTML mailto: tag linking
     *        to that email address
     * - Only matches these 2 patterns either after a space, or at the beginning of a line
     *
     */
    static function make_clickable($text) {
        $regex = '/(\S+@\S+\.\S+)/i';
        $replace = "<a href='mailto:$1'>$1</a>";
        $result = preg_replace($regex, $replace, $text);
        return preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $result);
    }

    /**
     * This functions cuts a paragraph
     * i.e cut('Merry Xmas from Lima',13) = "Merry Xmas fr..."
     * @param string    The text to "cut"
     * @param int       Count of chars
     * @param bool      Whether to embed in a <span title="...">...</span>
     * @return string
     * */
    static function cut($text, $maxchar, $embed = false) {
        if (api_strlen($text) > $maxchar) {
            if ($embed) {
                return '<span title="' . $text . '">' . api_substr($text, 0, $maxchar) . '...</span>';
            }
            return api_substr($text, 0, $maxchar) . ' ...';
        }
        return $text;
    }

    /**
     * Show a number as only integers if no decimals, but will show 2 decimals if exist.
     *
     * @param mixed     Number to convert
     * @param int       Decimal points 0=never, 1=if needed, 2=always
     * @return mixed    An integer or a float depends on the parameter
     */
    static function float_format($number, $flag = 1) {
        if (is_numeric($number)) {
            if (!$number) {
                $result = ($flag == 2 ? '0.' . str_repeat('0', EXERCISE_NUMBER_OF_DECIMALS) : '0');
            } else {

                if (floor($number) == $number) {
                    $result = number_format($number, ($flag == 2 ? EXERCISE_NUMBER_OF_DECIMALS : 0));
                } else {
                    $result = number_format(round($number, 2), ($flag == 0 ? 0 : EXERCISE_NUMBER_OF_DECIMALS));
                }
            }
            return $result;
        }
    }

    // TODO: To be checked for correct timezone management.
    /**
     * Function to obtain last week timestamps
     * @return array    Times for every day inside week
     */
    static function get_last_week() {
        $week = date('W');
        $year = date('Y');

        $lastweek = $week - 1;
        if ($lastweek == 0) {
            $week = 52;
            $year--;
        }

        $lastweek = sprintf("%02d", $lastweek);
        $arrdays = array();
        for ($i = 1; $i <= 7; $i++) {
            $arrdays[] = strtotime("$year" . "W$lastweek" . "$i");
        }
        return $arrdays;
    }

    /**
     * Gets the week from a day
     * @param   string   Date in UTC (2010-01-01 12:12:12)
     * @return  int      Returns an integer with the week number of the year
     */
    static function get_week_from_day($date) {
        if (!empty($date)) {
            $time = api_strtotime($date, 'UTC');
            return date('W', $time);
        } else {
            return date('W');
        }
    }

    /**
     * This function splits the string into words and then joins them back together again one by one.
     * Example: "Test example of a long string"
     * 			substrwords(5) = Test ... *
     * @param string
     * @param int the max number of character
     * @param string how the string will be end
     * @return a reduce string
     */
    static function substrwords($text, $maxchar, $end = '...') {
        if (strlen($text) > $maxchar) {
            $words = explode(" ", $text);
            $output = '';
            $i = 0;
            while (1) {
                $length = (strlen($output) + strlen($words[$i]));
                if ($length > $maxchar) {
                    break;
                } else {
                    $output = $output . " " . $words[$i];
                    $i++;
                };
            };
        } else {
            $output = $text;
            return $output;
        }
        return $output . $end;
    }

    static function implode_with_key($glue, $array) {
        if (!empty($array)) {
            $string = '';
            foreach ($array as $key => $value) {
                if (empty($value)) {
                    $value = 'null';
                }
                $string .= $key . " : " . $value . " $glue ";
            }
            return $string;
        }
        return '';
    }

    /**
     * function string2binary converts the string "true" or "false" to the boolean true false (0 or 1)
     * This is used for the Chamilo Config Settings as these store true or false as string
     * and the api_get_setting('course_create_active_tools') should be 0 or 1 (used for
     * the visibility of the tool)
     * @param string    $variable
     * @author Patrick Cool, patrick.cool@ugent.be
     */
    static function string2binary($variable) {
        if ($variable == 'true') {
            return true;
        }
        if ($variable == 'false') {
            return false;
        }
    }

    /**
     * Transform the file size in a human readable format.
     *
     * @param  int      Size of the file in bytes
     * @return string A human readable representation of the file size
     */
    static function format_file_size($file_size) {
        $file_size = intval($file_size);
        if($file_size >= 1073741824) {
            $file_size = round($file_size / 1073741824 * 100) / 100 . 'G';
        } elseif($file_size >= 1048576) {
            $file_size = round($file_size / 1048576 * 100) / 100 . 'M';
        } elseif($file_size >= 1024) {
            $file_size = round($file_size / 1024 * 100) / 100 . 'k';
        } else {
            $file_size = $file_size . 'B';
        }
        return $file_size;
    }

    /**
     * @param $array
     * @return string
     */
    static function return_datetime_from_array($array)
    {
        $year	 = '0000';
        $month = $day = $hours = $minutes = $seconds = '00';
        if (isset($array['Y']) && (isset($array['F']) || isset($array['M']))  && isset($array['d']) && isset($array['H']) && isset($array['i'])) {
            $year = $array['Y'];
            $month = isset($array['F'])?$array['F']:$array['M'];
            if (intval($month) < 10 ) $month = '0'.$month;
            $day = $array['d'];
            if (intval($day) < 10 ) $day = '0'.$day;
            $hours = $array['H'];
            if (intval($hours) < 10 ) $hours = '0'.$hours;
            $minutes = $array['i'];
            if (intval($minutes) < 10 ) $minutes = '0'.$minutes;
        }
        if (checkdate($month,$day,$year)) {
            $datetime = $year.'-'.$month.'-'.$day.' '.$hours.':'.$minutes.':'.$seconds;
        }
        return $datetime;
    }


    /**
     * Converts 2008-10-06 12:45:00 to -> array('prefix' => array(year'=>2008, 'month'=>10, etc...)
     * @param string
     * @param string
     * @param array
     */
    static function convert_date_to_array($date, $group)
    {
        $parts = explode(' ', $date);
        $date_parts = explode('-', $parts[0]);
        $date_parts_tmp = array();
        foreach ($date_parts as $item) {
            $date_parts_tmp[] = intval($item);
        }
        $time_parts = explode(':', $parts[1]);
        $time_parts_tmp = array();
        foreach ($time_parts as $item) {
            $time_parts_tmp[] = intval($item);
        }
        list($data[$group]['year'], $data[$group]['month'], $data[$group]['day']) = $date_parts_tmp;
        list($data[$group]['hour'], $data[$group]['minute']) = $time_parts_tmp;
        return $data;
    }

    /**
     * converts 1-9 to 01-09
     */
    static function two_digits($number)
    {
        $number = (int)$number;
        return ($number < 10) ? '0'.$number : $number;
    }

    
    /**
     * Transform the file size in a human readable format.
     *
     * @param  int      Size of the file in bytes
     * @return string A human readable representation of the file size
     */
    function format_file_size($file_size) {
        $file_size = intval($file_size);
        if($file_size >= 1073741824) {
            $file_size = round($file_size / 1073741824 * 100) / 100 . 'G';
        } elseif($file_size >= 1048576) {
            $file_size = round($file_size / 1048576 * 100) / 100 . 'M';
        } elseif($file_size >= 1024) {
            $file_size = round($file_size / 1024 * 100) / 100 . 'k';
        } else {
            $file_size = $file_size . 'B';
        }
        return $file_size;
    }

    function return_datetime_from_array($array) {
        $year	 = '0000';
        $month = $day = $hours = $minutes = $seconds = '00';
        if (isset($array['Y']) && (isset($array['F']) || isset($array['M']))  && isset($array['d']) && isset($array['H']) && isset($array['i'])) {
            $year = $array['Y'];
            $month = isset($array['F'])?$array['F']:$array['M'];
            if (intval($month) < 10 ) $month = '0'.$month;
            $day = $array['d'];
            if (intval($day) < 10 ) $day = '0'.$day;
            $hours = $array['H'];
            if (intval($hours) < 10 ) $hours = '0'.$hours;
            $minutes = $array['i'];
            if (intval($minutes) < 10 ) $minutes = '0'.$minutes;
        }
        if (checkdate($month,$day,$year)) {
            $datetime = $year.'-'.$month.'-'.$day.' '.$hours.':'.$minutes.':'.$seconds;
        }
        return $datetime;
    }

    /**
     * Converts an string CLEANYO[admin][amann,acostea]
     * into an array:
     *
     * array(
     *  CLEANYO
     *  admin
     *  amann,acostea
     * )
     *
     * @param $array
     * @return array
     */
    function bracketsToArray($array)
    {
        return preg_split('/[\[\]]+/', $array, -1, PREG_SPLIT_NO_EMPTY);
    }
}
