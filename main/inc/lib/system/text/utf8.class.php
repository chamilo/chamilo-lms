<?php

/**
 * Utf8 encoding class. Provides utility function to deal with UTF8 encoding.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 * @author More authors, mentioned in the correpsonding fragments of this source.
 */
class Utf8 extends Encoding
{

    const PATTERN_NOT_VISIBLE_CHARS = '/[^[:print:]-]/'; //Visible characters and the space character

    /**
     * @see http://en.wikipedia.org/wiki/Byte_order_mark 
     */
    const BOM = "\xEF\xBB\xBF";
    const NAME = 'UTF-8';

    /**
     *
     * @return Utf8
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    /**
     * Returns true if encoding is UTF8.
     * 
     * @param string|Encoding $encoding
     * @return bool 
     */
    function is($encoding)
    {
        $encoding = (string) $encoding;
        return strtolower($encoding) == strtolower(self::NAME);
    }

    protected function __construct()
    {
        parent::__construct(self::NAME);
    }

    function name()
    {
        return self::NAME;
    }

    function bom()
    {
        return self::BOM;
    }

    /**
     * Returns the hexa decimal representation of an utf8 string. Usefull to understand
     * what is going on - not printable chars, rare patterns such as e' for é, etc. 
     * 
     * @param type $text
     * @return string 
     */
    function to_hex($text)
    {
        $result = '';
        mb_internal_encoding('utf-8');

        for ($i = 0, $n = mb_strlen($text); $i < $n; $i++) {
            $char = mb_substr($text, $i, 1);
            $num = strlen($char);
            for ($j = 0; $j < $num; $j++) {
                $result .= sprintf('%02x', ord($char[$j]));
            }
            $result .= ' ';
        }
        return $result;
    }

    /**
     * Trim the BOM from an utf-8 string
     * 
     * @param string $text
     * @return string 
     */
    function trim($text)
    {
        $bom = self::BOM;
        if (strlen($text) < strlen($bom)) {
            return $text;
        }

        if (substr($text, 0, 3) == $bom) {
            return substr($text, 3);
        }
        return $text;
    }

    /**
     * Checks a string for UTF-8 validity.
     * 
     * @param string $string	The string to be tested.
     * @return bool				Returns TRUE when the tested string is valid UTF-8, FALSE othewise.
     * @link http://en.wikipedia.org/wiki/UTF-8
     * @author see internationalization.lib.php
     */
    static function is_valid(&$string)
    {

        //return @mb_detect_encoding($string, 'UTF-8', true) == 'UTF-8' ? true : false;
        // Ivan Tcholakov, 05-OCT-2008: I do not trust mb_detect_encoding(). I have
        // found a string with a single cyrillic letter (single byte), that is
        // wrongly detected as UTF-8. Possibly, there would be problems with other
        // languages too. An alternative implementation will be used.

        $str = (string) $string;
        $len = api_byte_count($str);
        $i = 0;
        while ($i < $len) {
            $byte1 = ord($str[$i++]);  // Here the current character begins. Its size is
            // determined by the senior bits in the first byte.

            if (($byte1 & 0x80) == 0x00) {  // 0xxxxxxx
                //    &
                // 10000000
                // --------
                // 00000000
                // This is s valid character and it contains a single byte.
            } elseif (($byte1 & 0xE0) == 0xC0) { // 110xxxxx 10xxxxxx
                //    &        &
                // 11100000 11000000
                // -------- --------
                // 11000000 10000000
                // The character contains two bytes.
                if ($i == $len) {
                    return false;    // Here the string ends unexpectedly.
                }

                if (!((ord($str[$i++]) & 0xC0) == 0x80))
                    return false;    // Invalid second byte, invalid string.
            }

            elseif (($byte1 & 0xF0) == 0xE0) { // 1110xxxx 10xxxxxx 10xxxxxx
                //    &        &        &
                // 11110000 11000000 11000000
                // -------- -------- --------
                // 11100000 10000000 10000000
                // This is a character of three bytes.
                if ($i == $len) {
                    return false;    // Unexpected end of the string.
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;    // Invalid second byte.
                }
                if ($i == $len) {
                    return false;    // Unexpected end of the string.
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;    // Invalid third byte, invalid string.
                }
            } elseif (($byte1 & 0xF8) == 0xF0) { // 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
                //    &        &        &        &
                // 11111000 11000000 11000000 11000000
                // -------- -------- -------- --------
                // 11110000 10000000 10000000 10000000
                // This is a character of four bytes.
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
            } elseif (($byte1 & 0xFC) == 0xF8) { // 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
                //    &        &        &        &        &
                // 11111100 11000000 11000000 11000000 11000000
                // -------- -------- -------- -------- --------
                // 11111000 10000000 10000000 10000000 10000000
                // This is a character of five bytes.
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
            } elseif (($byte1 & 0xFE) == 0xFC) { // 1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
                //    &        &        &        &        &        &
                // 11111110 11000000 11000000 11000000 11000000 11000000
                // -------- -------- -------- -------- -------- --------
                // 11111100 10000000 10000000 10000000 10000000 10000000
                // This is a character of six bytes.
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
                if ($i == $len) {
                    return false;
                }
                if (!((ord($str[$i++]) & 0xC0) == 0x80)) {
                    return false;
                }
            } else {
                return false;     // In any other case the character is invalid.
            }
            // Here the current character is valid, it
            // matches to some of the cases above.
            // The next character is to be examinated.
        }
        return true;       // Empty strings are valid too.
    }

    /**
     *
     * @param type $to
     * @return Utf8Decoder 
     */
    public function decoder($to = null)
    {
        $to = $to ? $to : Encoding::system();
        return new Utf8Decoder($to);
    }

    /**
     *
     * @param type $from
     * @return Utf8Encoder
     */
    public function encoder($from = null)
    {
        $from = $from ? $from : Encoding::system();
        return new Utf8Encoder($from);
    }

}