<?php

/**
 * Write objects to a stream in csv format based on map.
 * 
 * Usage
 * 
 *      $object->property_name_1 = 'name 1';
 *      $object->property_name_2 = 'name 2';
 * 
 *      $map = array( 'property_name_1' => 'Header title 1', 
 *                    'property_name_2'  => 'Header title 2');
 * 
 *      $writer = CsvObjectWriter::create($map, 'temp');
 *      $writer->add($object);
 * 
 *      Output
 * 
 *      "Header title 1";"Header title 2"
 *      "name 1";"name 2" 
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CsvObjectWriter extends CsvWriter
{

    /**
     *
     * @param string|object $stream
     * @return CsvWriter
     */
    static function create($stream, $map = '*', $delimiter = ';', $enclosure = '"')
    {
        return new self($stream, $map = '*', $map, $delimiter, $enclosure);
    }

    protected $map = '*';
    protected $headers_written = false;

    function __construct($stream, $map = '*', $delimiter = ';', $enclosure = '"')
    {
        parent::__construct($stream, $delimiter, $enclosure);
        $this->map = $map;
    }

    public function get_map()
    {
        return $this->map;
    }

    /**
     *
     * @param object $item
     * @return boolean 
     */
    public function put($item)
    {
        $data = $this->convert($item);
        if (empty($data)) {
            return false;
        }
        $this->writer_headers();
        parent::put($data);
        return true;
    }

    /**
     * Convert object to array of data 
     * @param object $object
     * @return array 
     */
    protected function convert($item)
    {
        $result = array();
        $map = $this->map;
        if ($map == '*') {
            return (array) $item;
        }
        foreach ($map as $key => $value) {
            $result[$key] = isset($item->{$key}) ? $item->{$key} : '';
        }
        return $result;
    }

    /**
     *
     * @param array $items 
     */
    public function add_all($items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     *
     * @param array|object $item
     */
    public function add($item)
    {
        if (is_array($item)) {
            $this->add_all($item);
            return;
        }
        $this->put($item);
    }

    protected function writer_headers()
    {
        if ($this->headers_written) {
            return;
        }
        $this->headers_written = true;

        $map = $this->map;
        if (!is_array($map)) {
            return;
        }

        $headers = array();
        foreach ($map as $key => $value) {
            $headers[] = $value;
        }
        parent::put($headers);
    }

}