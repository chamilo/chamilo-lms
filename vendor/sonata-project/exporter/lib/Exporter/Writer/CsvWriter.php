<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Writer;

use Exporter\Exception\InvalidDataFormatException;

class CsvWriter implements WriterInterface
{
    protected $filename;

    protected $delimiter;

    protected $enclosure;

    protected $escape;

    protected $file;

    protected $showHeaders;

    protected $position;

    /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool   $showHeaders
     */
    public function __construct($filename, $delimiter = ",", $enclosure = "\"", $escape = "\\", $showHeaders = true)
    {
        $this->filename    = $filename;
        $this->delimiter   = $delimiter;
        $this->enclosure   = $enclosure;
        $this->escape      = $escape;
        $this->showHeaders = $showHeaders;
        $this->position    = 0;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->file = fopen($this->filename, 'w', false);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data)
    {
        if ($this->position == 0 && $this->showHeaders) {
            $this->addHeaders($data);

            $this->position++;
        }

        $result = @fputcsv($this->file, $data, $this->delimiter, $this->enclosure);

        if (!$result) {
            throw new InvalidDataFormatException();
        }

        $this->position++;
    }

    /**
     * @param array $data
     *
     * @return void
     */
    protected function addHeaders(array $data)
    {
        $headers = array();
        foreach ($data as $header => $value) {
            $headers[] = $header;
        }

        fputcsv($this->file, $headers, $this->delimiter, $this->enclosure);
    }
}
