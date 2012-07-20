<?php

namespace Glossary;

/**
 * Read a csv file and returns glossary entries contained in the file.
 *
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CsvReader implements \Iterator
{

    protected $path;
    protected $items = null;
    protected $index = 0;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function get_items()
    {
        if (is_null($this->items)) {
            $this->items = $this->read();
        }
        return $this->items;
    }

    /**
     * Read file and returns an array filled up with its' content.
     * 
     * @return array of objects
     */
    protected function read()
    {
        $result = array();

        $path = $this->path;
        if (!is_readable($path)) {
            return array();
        }

        $items = \Import::csv_reader($path);
        foreach ($items as $item) {
            $item = (object) $item;
            $name = isset($item->name) ? trim($item->name) : '';
            $description = isset($item->description) ? trim($item->description) : '';

            $name = \Security::remove_XSS($name);
            $description = \Security::remove_XSS($description);

            $is_blank_line = empty($name) && empty($description);
            if ($is_blank_line) {
                continue;
            }

            $item = new Glossary();
            $item->name = $name;
            $item->description = $description;

            $result[] = $item;
        }
        return $result;
    }

    public function current()
    {
        $items = $this->get_items();
        return isset($items[$this->index]) ? $items[$this->index] : null;
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index++;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        $items = $this->get_items();
        return count($items) > $this->index;
    }

}