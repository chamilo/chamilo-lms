<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

/**
 * Read data from a Xml file
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
abstract class AbstractXmlSourceIterator implements SourceIteratorInterface
{
    protected $filename = null;
    protected $file = null;
    protected $hasHeaders = null;
    protected $columns = array();
    protected $parser = null;

    protected $currentRowIndex = 0;
    protected $currentColumnIndex = 0;
    protected $currentRow = null;
    protected $bufferedRow = array();
    protected $currentRowEnded = false;
    protected $position = 0;

    /**
     * @param string  $filename
     * @param boolean $hasHeaders
     */
    public function __construct($filename, $hasHeaders = true)
    {
        $this->filename = $filename;
        $this->hasHeaders = $hasHeaders;
    }

    /**
     * Parse until </Row> reached
     *
     * @return void
     */
    protected function parseRow()
    {
        // only parse the next row if only one in buffer
        if ( count($this->bufferedRow) > 1 ) {
            return;
        }
        if ( feof($this->file) ) {
            $this->currentRow = null;

            return;
        }

        $this->currentRowEnded = false;
        // read file until row is ended
        while ( !$this->currentRowEnded && !feof($this->file) ) {
            $data = fread($this->file, 1024);
            xml_parse($this->parser, $data);
        }
    }

    /**
     * Prepare the row to return
     *
     * @return void
     */
    protected function prepareCurrentRow()
    {
        $this->currentRow = array_shift($this->bufferedRow);
        if ( is_array($this->currentRow) ) {
            $datas = array();
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

    /**
     * Start element handler
     *
     * @param resource $parser
     * @param string   $name
     * @param array    $attributes
     *
     * @return void
     */
    abstract public function tagStart($parser, $name, $attributes = array());

    /**
     * End element handler
     *
     * @param resource $parser
     * @param string   $name
     *
     * @return void
     */
    abstract public function tagEnd($parser, $name);

    /**
     * Tag content handler
     *
     * @param resource $parser
     * @param string   $data
     *
     * @return void
     */
    abstract public function tagContent($parser, $data);

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->currentRow;
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
        $this->parseRow();
        $this->prepareCurrentRow();
        $this->position++;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, 'tagStart', 'tagEnd');
        xml_set_character_data_handler($this->parser, 'tagContent');
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, 0);

        $this->file = fopen($this->filename, 'r');

        $this->bufferedRow = array();
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

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if ( !is_array($this->currentRow) ) {
            xml_parser_free($this->parser);
            fclose($this->file);

            return false;
        }

        return true;
    }
}
