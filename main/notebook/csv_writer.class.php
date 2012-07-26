<?php

namespace Notebook;

use Chamilo;

/**
 * Write notebook entries to a file in CSV format.
 * 
 * @license /licence.txt
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class CsvWriter extends \CsvObjectWriter
{

    /**
     *
     * @return \Notebook\CsvWriter
     */
    public static function create($path = '', $delimiter = ';', $enclosure = '"')
    {
        return new self($path, $delimiter, $enclosure);
    }

    protected $path = '';

    function __construct($path = '', $delimiter = ';', $enclosure = '"')
    {
        $path = $path ? $path : Chamilo::temp_file();
        $this->path = $path;
        $stream = new \FileWriter($path);
        $map = array(
            'title' => 'title',
            'description' => 'description'
        );
        parent::__construct($stream, $map, $delimiter, $enclosure);
    }

    function get_path()
    {
        return $this->path;
    }

}