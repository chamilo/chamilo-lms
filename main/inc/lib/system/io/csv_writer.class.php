<?php

/**
 * Write array data to a stream in CSV format. Usage:
 * 
 *      $writer = CsvWriter::create('path');
 * 
 *      $writer->put($headers);
 *      $writer->put($line_1);
 *      $writer->put($line_2);
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CsvWriter
{

    /**
     *
     * @param string|object $stream
     * @return FileWriter
     */
    static function create($stream, $delimiter = ';', $enclosure = '"')
    {
        return new self($stream, $delimiter, $enclosure);
    }

    protected $stream = null;
    protected $delimiter = '';
    protected $enclosure = '';

    function __construct($stream, $delimiter = ';', $enclosure = '"')
    {
        $this->stream = $stream;
        $this->delimiter = $delimiter ? substr($delimiter, 0, 1) : ';';;
        $this->enclosure = $enclosure ? substr($enclosure, 0, 1) : '"';;
    }

    function get_delimiter()
    {
        return $this->delimiter;
    }

    function get_enclosure()
    {
        return $this->enclosure;
    }

    /**
     *
     * @return FileWriter
     */
    protected function stream()
    {
        if (is_string($this->stream)) {
            $this->stream = new FileWriter($this->stream);
        }
        return $this->stream;
    }

    function write($items)
    {
        $this->put($items);
    }

    function writeln($items)
    {
        $this->put($items);
    }

    function put($items)
    {
        $enclosure = $this->enclosure;
        $fields = array();
        foreach ($items as $item) {
            $fields[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $item) . $enclosure;
        }

        $delimiter = $this->delimiter;
        $line = implode($delimiter, $fields);
        $this->stream()->writeln($line);
    }

    function close()
    {
        if (is_object($this->stream)) {
            $this->stream->close();
        }
        $this->stream = null;
    }

}