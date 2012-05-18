<?php

/**
 * Encode from another encoding to UTF8:
 * 
 *      - add BOM
 *      - change encoding
 *      - convert html entities if turned on
 * 
 * Note: 
 * 
 * Convert_html_entities cannot but turned on by default. This would be bad
 * for performances but more than anything else it may be perfectly valid to write
 * html entities wihtout transformation - i.e. when writing html content.
 * 
 * It may be better to move convert_html_entities to its own converter and to chain
 * converters together to achieve the same result.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Utf8Encoder extends Converter
{

    protected $started = false;
    protected $from_encoding;
    protected $encoding_converter;
    protected $convert_html_entities = false;

    function __construct($from_encoding = null , $convert_html_entities = false)
    {
        $this->from_encoding = $from_encoding ? $from_encoding : Encoding::system();
        $this->encoding_converter = EncodingConverter::create($this->from_encoding, Utf8::NAME);
        $this->convert_html_entities = $convert_html_entities;
        $this->reset();
    }

    function from_encoding()
    {
        return $this->from_encoding;
    }

    function to_encoding()
    {
        return Utf8::NAME;
    }

    function get_convert_html_entities()
    {
        return $this->convert_html_entities;
    }

    function reset()
    {
        $this->started = false;
    }

    function convert($string)
    {
        if ($this->convert_html_entities) {
            $string = html_entity_decode($string, ENT_COMPAT, Utf8::NAME);
        }
        $string = $this->encoding_converter->convert($string);
        if (!$this->started) {
            $this->started = true;
            $string = Utf8::BOM . $string;
        }
        return $string;
    }

}