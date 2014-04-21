<?php

/**
 * Read cvs data from a stream - string/FileReader. 
 * 
 * Returns data as associative arrays (headers are the keys of the array).
 * Skip blank lines ?? is it such a good idea?
 * 
 * Usage:
 * 
 *      $reader = CsvReader::create('path');
 *      foreach($reader as $items){
 *          foreach($items as $key=>$value){
 *              echo "$key : $value";
 *          }
 *      }
 * 
 * 
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CsvReader implements Iterator
{

    /**
     *
     * @param string|FileReader $stream
     * @param string $delimiter
     * @param string $enclosure
     * @return CsvReader 
     */
    static function create($stream, $delimiter = ';', $enclosure = '"')
    {
        return new self($stream, $delimiter, $enclosure);
    }

    protected $stream = null;
    protected $headers = array();
    protected $delimiter = '';
    protected $enclosure = '';
    protected $current = false;
    protected $index = -1;

    function __construct($stream, $delimiter = ';', $enclosure = '"')
    {
        $this->stream = $stream;
        $this->delimiter = $delimiter ? substr($delimiter, 0, 1) : ';';
        $this->enclosure = $enclosure ? substr($enclosure, 0, 1) : '"';
    }

    function get_delimiter()
    {
        return $this->delimiter;
    }

    function get_enclosure()
    {
        return $this->enclosure;
    }

    function headers()
    {
        return $this->headers;
    }

    /**
     * @return FileReader
     */
    function stream()
    {
        if (is_string($this->stream)) {
            $this->stream = new FileReader($this->stream);
        }
        return $this->stream;
    }

    protected function decode($line)
    {
        if (empty($line)) {
            return array();
        }
        $data = api_str_getcsv($line, $this->get_delimiter(), $this->get_enclosure());
        if ($this->headers) {
            $result = array();
            foreach ($data as $index => $value) {
                $key = isset($this->headers[$index]) ? $this->headers[$index] : false;
                if ($key) {
                    $result[$key] = $value;
                } else {
                    $result[] = $value;
                }
            }
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     * Returns the next non empty line
     * 
     * @return boolean|string
     */
    protected function next_line()
    {
        while (true) {
            $line = $this->stream()->next();
            if ($line === false) {
                return false;
            } else if ($line) {
                return $line;
            }
        }
        return false;
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
        if (empty($this->headers)) {
            $line = $this->next_line();
            $this->headers = $this->decode($line);
        }
        $line = $this->next_line();
        if ($line) {
            $this->current = $this->decode($line);
            $this->index++;
        } else {
            $this->current = false;
        }
        return $this->current;
    }

    public function rewind()
    {
        $this->stream()->rewind();
        $line = $this->stream()->current();
        if (empty($line)) {
            $line = $this->next_line();
        }
        $this->headers = $this->decode($line);
        $this->index = -1;
        $this->next();
    }

    public function valid()
    {
        return $this->current !== false;
    }

    function __clone()
    {
        $this->stream()->rewind();
        $this->current = false;
        $this->index = -1;
        $this->headers = array();
    }

}