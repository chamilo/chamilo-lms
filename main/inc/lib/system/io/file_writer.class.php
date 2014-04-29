<?php

/**
 * Write data to file. Default to UTF8 encoding.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class FileWriter
{

    /**
     *
     * @param string $path
     * @param Converter $converter 
     * @return FileWriter
     */
    static function create($path, $converter = null)
    {
        return new self($path, $converter);
    }

    const EOL = "\n";

    protected $path = '';
    protected $handle = null;
    protected $converter = null;

    /**
     *
     * @param string $path
     * @param Encoding $encoding 
     */
    function __construct($path, $converter = null)
    {
        $this->path = $path;
        $this->converter = $converter ? $converter : Encoding::utf8()->encoder();
    }

    /**
     *
     * @return Converter
     */
    function get_converter()
    {
        return $this->converter;
    }

    protected function handle()
    {
        if (is_null($this->handle)) {
            $this->handle = fopen($this->path, 'a+');
        }
        return $this->handle;
    }

    function write($text)
    {
        fwrite($this->handle(), $this->convert($text));
    }

    function writeln($text)
    {
        fwrite($this->handle(), $this->convert($text) . self::EOL);
    }

    function close()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
        $this->handle = null;
    }

    protected function convert($text)
    {
        return $this->converter->convert($text);
    }

}