<?php

/**
 * Convert text from one encoding to another. Usage:
 * 
 *      $converter = EncodingConverter::create($from, $to);
 *      $converter->convert($text);
 * 
 * Note that the create function will returns an identify converter if from and to 
 * encodings are the same. Reason why the constructor is private.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class EncodingConverter extends Converter
{

    /**
     *
     * @param string $from_encoding
     * @param string $to_encoding 
     * 
     * @return EncodingConverter
     */
    public static function create($from_encoding, $to_encoding)
    {
        $from_encoding = (string) $from_encoding;
        $to_encoding = (string) $to_encoding;
        if (strtolower($from_encoding) == strtolower($to_encoding)) {
            return Converter::identity();
        } else {
            new self($from_encoding, $to_encoding);
        }
    }

    protected $from_encoding;
    protected $to_encoding;

    protected function __construct($from_encoding, $to_encoding)
    {
        $this->from_encoding = $from_encoding;
        $this->to_encoding = $to_encoding;
    }

    function from_encoding()
    {
        return $this->from_encoding;
    }

    function to_encoding()
    {
        return $this->to_encoding;
    }

    function convert($string)
    {
        $from = $this->from_encoding;
        $to = $this->to_encoding;
        if ($from == $to) {
            return $string;
        }
        api_convert_encoding($string, $to, $from);
    }

}