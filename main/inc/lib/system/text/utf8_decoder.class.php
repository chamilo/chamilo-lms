<?php

/**
 * Convert from Utf8 to another encoding: 
 * 
 *      - remove BOM
 *      - change encoding
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Utf8Decoder extends Converter
{

    protected $started = false;
    protected $to_encoding;
    protected $encoding_converter;

    function __construct($to_encoding = null)
    {
        $this->to_encoding = $to_encoding ? $to_encoding : Encoding::system();
        $this->encoding_converter = EncodingConverter::create(Utf8::NAME, $this->to_encoding);
        $this->reset();
    }

    function from_encoding()
    {
        return Utf8::NAME;
    }

    function to_encoding()
    {
        return $this->to_encoding;
    }

    function reset()
    {
        $this->started = false;
    }

    function convert($string)
    {
        if (!$this->started) {
            $this->started = true;
            $string = Utf8::instance()->trim($string);
            return $this->encoding_converter->convert($string);
        } else {
            return $this->encoding_converter->convert($string);
        }
        return $string;
    }

}