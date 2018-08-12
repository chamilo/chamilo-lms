<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

/**
 * Read data from a csv file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
class CsvSourceIterator implements SourceIteratorInterface
{
    /**
     * @var string
     */
    protected $filename = null;

    /**
     * @var resource
     */
    protected $file = null;

    /**
     * @var string|null
     */
    protected $delimiter = null;

    /**
     * @var string|null
     */
    protected $enclosure = null;

    /**
     * @var string|null
     */
    protected $escape = null;

    /**
     * @var bool|null
     */
    protected $hasHeaders = null;

    /**
     * @var array
     */
    protected $lines = [];

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var array
     */
    protected $currentLine = [];

    /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool   $hasHeaders
     */
    public function __construct($filename, $delimiter = ',', $enclosure = '"', $escape = '\\', $hasHeaders = true)
    {
        $this->filename = $filename;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->hasHeaders = $hasHeaders;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->currentLine;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $line = fgetcsv($this->file, 0, $this->delimiter, $this->enclosure, $this->escape);
        $this->currentLine = $line;
        ++$this->position;
        if ($this->hasHeaders && is_array($line)) {
            $data = [];
            foreach ($line as $key => $value) {
                $data[$this->columns[$key]] = $value;
            }
            $this->currentLine = $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->file = fopen($this->filename, 'r');
        $this->position = 0;
        $line = fgetcsv($this->file, 0, $this->delimiter, $this->enclosure, $this->escape);
        if ($this->hasHeaders) {
            $this->columns = $line;
            $line = fgetcsv($this->file, 0, $this->delimiter, $this->enclosure, $this->escape);
        }
        $this->currentLine = $line;
        if ($this->hasHeaders && is_array($line)) {
            $data = [];
            foreach ($line as $key => $value) {
                $data[$this->columns[$key]] = $value;
            }
            $this->currentLine = $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if (!is_array($this->currentLine)) {
            if (is_resource($this->file)) {
                fclose($this->file);
            }

            return false;
        }

        return true;
    }
}
