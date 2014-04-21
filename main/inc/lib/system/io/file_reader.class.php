<?php

/**
 * Read text from a file. Reader is line oriented and not char oriented. 
 * The default converter converts from the file encoding - auto-detected - to 
 * system encoding.
 * 
 * Usage:
 * 
 *  $file = FileReader::create('path');
 *  foreach($file as $line)
 * {
 *      ...
 * }
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class FileReader implements Iterator
{

    const EOL = "\n";

    /**
     *
     * @param string $path
     * @return FileReader
     */
    static function create($path, $converter = null)
    {
        return new self($path, $converter);
    }

    /**
     * Returns the file encoding
     * 
     * @return Encoding
     */
    static function detect_encoding($path)
    {
        $abstract = array();
        // We assume that 200 lines are enough for encoding detection.     
        // here we must get at the raw data so we don't use other functions
        // it's not possible to read x chars as this would not be safe with utf 
        // (chars may be split in the middle)
        $handle = fopen($path, 'r');

        $i = 0;
        while (($line = fgets($handle)) !== false && $i < 200) {
            $i++;
            $abstract[] = $line;
        }
        fclose($handle);
        $abstract = implode($abstract);
        return Encoding::detect_encoding($abstract);
    }

    protected $path = '';
    protected $handle = null;
    protected $current = false;
    protected $index = -1;
    protected $converter = null;

    function __construct($path, $converter = null)
    {
        if (empty($converter)) {
            $encoding = self::detect_encoding($path);
            $converter = $encoding->decoder();
        }
        $this->path = $path;
        $this->converter = $converter;
    }

    /**
     *
     * @return Converter
     */
    function get_converter()
    {
        return $this->converter;
    }

    function handle()
    {
        if (is_null($this->handle)) {
            $this->handle = fopen($this->path, 'r');
        }
        return $this->handle;
    }

    /**
     * Read at most $count lines.
     * 
     * @param int $count
     * @return array
     */
    function read_lines($count)
    {
        $result;
        $i = 0;
        foreach ($this as $line) {
            if ($i >= $count) {
                return $result;
            }
            $i++;
            $result[] = $line;
        }
        return $result;
    }

    function read_line()
    {
        return $this->next();
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

    public function current()
    {
        return $this->current;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $handle = $this->handle();
        if($handle === false)
        {
            $this->current = false;            
            return false;
        }
        $line = fgets($handle);
        if ($line !== false) {
            $line = rtrim($line, "\r\n");
            $line = $this->convert($line);
            $this->index++;
        }
        $this->current = $line;
        return $this->current;
    }

    public function rewind()
    {
        $this->converter->reset();
        if ($handle = $this->handle()) {
            rewind($handle);
        }
        $this->current = false;
        $this->index = -1;
        $this->next();
    }

    public function valid()
    {
        return $this->current !== false;
    }

    function __clone()
    {
        $this->handle = null;
        $this->current = false;
        $this->index = -1;
        $this->converter->reset();
    }

}