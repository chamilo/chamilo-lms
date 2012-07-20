<?php

namespace CourseDescription;

use Chamilo;

/**
 * Write course descriptions to a file in CSV format.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CsvWriter
{

    /**
     *
     * @return \CourseDescription\CsvWriter
     */
    public static function create($path = '')
    {
        return new self($path);
    }

    protected $path;
    protected $writer;
    protected $headers_written = false;

    function __construct($path = '')
    {
        $path = $path ? $path : Chamilo::temp_file();
        $this->path = $path;
    }

    public function get_path()
    {
        return $this->path;
    }

    /**
     *
     * @param array $descriptions 
     */
    public function add_all($descriptions)
    {
        foreach ($descriptions as $description) {
            $this->add($description);
        }
    }

    /**
     *
     * @param array|CourseDescription $description
     */
    public function add($description)
    {
        if (is_array($description)) {
            $this->add_all($description);
            return;
        }
        $this->writer_headers();
        $data = array();
        $data[] = $description->title;
        $data[] = $description->content;
        $data[] = $description->type->name;
        $this->put($data);
    }

    /**
     *
     * @return \CsvWriter
     */
    protected function get_writer()
    {
        if ($this->writer) {
            return $this->writer;
        }

        $writer = \CsvWriter::create(new \FileWriter($this->path));
        $this->writer = $writer;
        return $writer;
    }

    protected function writer_headers()
    {
        if ($this->headers_written) {
            return;
        }
        $this->headers_written = true;
        $headers = array();
        $headers[] = 'title';
        $headers[] = 'content';
        $headers[] = 'type';
        $this->put($headers);
    }

    protected function put($data)
    {
        $writer = $this->get_writer();
        $writer->put($data);
    }

}