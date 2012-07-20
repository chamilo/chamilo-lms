<?php

namespace CourseDescription;

/**
 * Read a csv file and returns course descriptions contained in the file.
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
            $title = isset($item->title) ? trim($item->title) : '';
            $content = isset($item->content) ? trim($item->content) : '';
            $type = isset($item->type) ? trim($item->type) : '';

            $title = \Security::remove_XSS($title);
            $content = \Security::remove_XSS($content);
            $type = \Security::remove_XSS($type);

            $is_blank_line = empty($title) && empty($content) && empty($type);
            if ($is_blank_line) {
                continue;
            }

            $type = CourseDescriptionType::repository()->find_one_by_name($type);
            $type_id = $type ? $type->id : 0;

            $description = CourseDescription::create();
            $description->title = $title;
            $description->content = $content;
            $description->description_type = $type_id;

            $result[] = $description;
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