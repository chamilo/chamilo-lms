<?php

namespace Ddeboer\DataImport\Writer;

/**
 * Writes to a CSV file
 */
class CsvWriter extends AbstractStreamWriter
{
    private $delimiter = ';';
    private $enclosure = '"';
    private $utf8Encoding = false;

    /**
     * Constructor
     *
     * @param string   $delimiter The delimiter
     * @param string   $enclosure The enclosure
     * @param resource $stream
     * @param bool     $utf8Encoding
     */
    public function __construct($delimiter = ';', $enclosure = '"', $stream = null, $utf8Encoding = false)
    {
        parent::__construct($stream);

        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->utf8Encoding = $utf8Encoding;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        if ($this->utf8Encoding) {
            fprintf($this->getStream(), chr(0xEF) . chr(0xBB) . chr(0xBF));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function writeItem(array $item)
    {
        fputcsv($this->getStream(), $item, $this->delimiter, $this->enclosure);

        return $this;
    }
}
