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
 * Read data from a Xml file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
class XmlSourceIterator extends AbstractXmlSourceIterator
{
    protected $mainTag;
    protected $dataTag;

    /**
     * @param string $filename
     * @param string $mainTag
     * @param string $dataTag
     */
    public function __construct($filename, $mainTag = 'datas', $dataTag = 'data')
    {
        parent::__construct($filename, false);
        $this->mainTag = $mainTag;
        $this->dataTag = $dataTag;
    }

    /**
     * Prepare the row to return.
     */
    protected function prepareCurrentRow()
    {
        $this->currentRow = array_shift($this->bufferedRow);
        if (is_array($this->currentRow)) {
            $datas = array();
            foreach ($this->currentRow as $key => $value) {
                $datas[$this->columns[$key]] = $value;
            }
            $this->currentRow = $datas;
        }
    }

    /**
     * Start element handler.
     *
     * @param resource $parser
     * @param string   $name
     * @param array    $attributes
     */
    public function tagStart($parser, $name, $attributes = array())
    {
        switch ($name) {
            case $this->mainTag:
                break;
            case $this->dataTag:
                $this->bufferedRow['i_'.$this->currentRowIndex] = array();
                break;
            default:
                if (!isset($this->columns[$this->currentColumnIndex])) {
                    $this->columns[$this->currentColumnIndex] = $name;
                }
                // set empty values when opening Cell tag
                $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] = '';
                break;
        }
    }

    /**
     * End element handler.
     *
     * @param resource $parser
     * @param string   $name
     */
    public function tagEnd($parser, $name)
    {
        switch ($name) {
            case $this->mainTag:
                break;
            case $this->dataTag:
                $this->currentRowIndex++;
                $this->currentColumnIndex = 0;
                $this->currentRowEnded = true;
                break;
            default:
                $this->currentColumnIndex++;
                break;
        }
    }

    /**
     * Tag content handler.
     *
     * @param resource $parser
     * @param string   $data
     */
    public function tagContent($parser, $data)
    {
        if (isset($this->bufferedRow['i_'.$this->currentRowIndex])
            && isset($this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex])
        ) {
            $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] .= $data;
        }
    }
}
