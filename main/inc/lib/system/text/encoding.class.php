<?php

/**
 * Set the system encoding to the plateform encoding. 
 * 
 * @todo: 
 * Note: those lines are here for ease of use only. They should be move away:
 * 
 *      1 first autodetection should be done inside the Encoding class
 *      2 this library should not call a chamilo specific function (this should
 *        be the other way around, chamilo calling the encoding functions)
 */

$plateform_encoding =  api_get_system_encoding();
Encoding::system($plateform_encoding);

/**
 * Encoding class. Handles text encoding. Usage:
 * 
 *      $encoding = Encoding::get('name');
 *      $decoder = $encoding->decoder();
 *      $decoder->convert('text');
 * 
 * The system encoding is the platform/system/default encoding. This defaults to
 * UTF8 but can be changed:
 * 
 *      Encoding::system('name');
 * 
 * Note that Encoding returns to its name when converted to a string. As such it
 * can be used in places where a string is expected:
 * 
 *      $utf8 = Encoding::Utf8();
 *      echo $utf8;
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Encoding
{

    private static $system = null;

    /**
     * Returns encoding for $name.
     * 
     * @param string $name
     * @return Encoding
     */
    public static function get($name)
    {
        if (is_object($name)) {
            return $name;
        } else if (Encoding::utf8()->is($name)) {
            return self::utf8();
        } else {
            return new self($name);
        }
    }

    /**
     * Returns the Utf8 encoding.
     * 
     * @return Utf8
     */
    public static function utf8()
    {
        return Utf8::instance();
    }

    /**
     * Returns/set the system/default encoding.
     * 
     * @return Encoding
     */
    public static function system($value = null)
    {
        if (is_object($value)) {
            self::$system = $value;
        } else if (is_string($value)) {
            self::$system = self::get($value);
        }

        return self::$system ? self::$system : self::utf8();
    }

    /**
     * Detect encoding from an abstract.
     * 
     * @param string $abstract
     * @return Encoding 
     */
    public static function detect_encoding($abstract)
    {
        $encoding_name = api_detect_encoding($abstract);
        return self::get($encoding_name);
    }

    protected $name = '';

    protected function __construct($name = '')
    {
        $this->name = $name;
    }

    /**
     * The name of the encoding
     * 
     * @return string
     */
    function name()
    {
        return $this->name;
    }

    /**
     * The Byte Order Mark.
     * 
     * @see http://en.wikipedia.org/wiki/Byte_order_mark 
     * @return string 
     */
    function bom()
    {
        return '';
    }

    /**
     * Returns a decoder that convert encoding to another encoding.      
     * 
     * @param string|Encoder $to Encoding to convert to, defaults to system encoding
     * @return Converter 
     */
    public function decoder($to = null)
    {
        $from = $this;
        $to = $to ? $to : Encoding::system();
        return EncodingConverter::create($from, $to);
    }

    /**
     * Returns an encoder that convert from another encoding to this encoding.
     * 
     * @param string|Encoder $from Encoding to convert from, defaults to system encoding.
     * @return Converter
     */
    public function encoder($from = null)
    {
        $from = $from ? $from : Encoding::system();
        $to = $this;
        return EncodingConverter::create($from, $to);
    }
    
    function __toString()
    {
        return $this->name();
    }

}