<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter\Source;

/**
 * Read data from a Xml file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
abstract class AbstractXmlSourceIterator implements SourceIteratorInterface
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var resource|null
     */
    protected $file;

    /**
     * @var bool
     */
    protected $hasHeaders;

    /**
     * @var string[]
     */
    protected $columns = [];

    /**
     * @var resource|null
     */
    protected $parser;

    /**
     * @var int
     */
    protected $currentRowIndex = 0;

    /**
     * @var int
     */
    protected $currentColumnIndex = 0;

    /**
     * @var mixed
     */
    protected $currentRow;

    /**
     * @var array
     */
    protected $bufferedRow = [];

    /**
     * @var bool
     */
    protected $currentRowEnded = false;

    /**
     * @var int
     */
    protected $position = 0;

    public function __construct(string $filename, bool $hasHeaders = true)
    {
        $this->filename = $filename;
        $this->hasHeaders = $hasHeaders;
    }

    /**
     * Start element handler.
     *
     * @param resource $parser
     */
    abstract public function tagStart($parser, string $name, array $attributes = []);

    /**
     * End element handler.
     *
     * @param resource $parser
     */
    abstract public function tagEnd($parser, string $name);

    /**
     * Tag content handler.
     *
     * @param resource $parser
     */
    abstract public function tagContent($parser, string $data);

    final public function current()
    {
        return $this->currentRow;
    }

    final public function key()
    {
        return $this->position;
    }

    final public function next(): void
    {
        $this->parseRow();
        $this->prepareCurrentRow();
        ++$this->position;
    }

    final public function rewind(): void
    {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'tagStart', 'tagEnd');
        xml_set_character_data_handler($this->parser, 'tagContent');
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);

        $this->file = fopen($this->filename, 'r');

        $this->bufferedRow = [];
        $this->currentRowIndex = 0;
        $this->currentColumnIndex = 0;
        $this->position = 0;
        $this->parseRow();
        if ($this->hasHeaders) {
            $this->columns = array_shift($this->bufferedRow);
            $this->parseRow();
        }
        $this->prepareCurrentRow();
    }

    final public function valid(): bool
    {
        if (!\is_array($this->currentRow)) {
            xml_parser_free($this->parser);
            fclose($this->file);

            return false;
        }

        return true;
    }

    /**
     * Parse until </Row> reached.
     */
    final protected function parseRow(): void
    {
        // only parse the next row if only one in buffer
        if (\count($this->bufferedRow) > 1) {
            return;
        }
        if (feof($this->file)) {
            $this->currentRow = null;

            return;
        }

        $this->currentRowEnded = false;
        // read file until row is ended
        while (!$this->currentRowEnded && !feof($this->file)) {
            $data = fread($this->file, 1024);
            xml_parse($this->parser, $data);
        }
    }

    /**
     * Prepare the row to return.
     */
    protected function prepareCurrentRow(): void
    {
        $this->currentRow = array_shift($this->bufferedRow);
        if (\is_array($this->currentRow)) {
            $datas = [];
            foreach ($this->currentRow as $key => $value) {
                if ($this->hasHeaders) {
                    $datas[$this->columns[$key]] = html_entity_decode($value);
                } else {
                    $datas[$key] = html_entity_decode($value);
                }
            }
            $this->currentRow = $datas;
        }
    }
}
