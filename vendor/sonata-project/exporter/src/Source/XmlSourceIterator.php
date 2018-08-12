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
 * Read data from a Xml file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
class XmlSourceIterator extends AbstractXmlSourceIterator
{
    /**
     * @var string
     */
    protected $mainTag;

    /**
     * @var string
     */
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
     * {@inheritdoc}
     */
    public function tagStart($parser, $name, $attributes = [])
    {
        switch ($name) {
            case $this->mainTag:
                break;
            case $this->dataTag:
                $this->bufferedRow['i_'.$this->currentRowIndex] = [];

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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function tagContent($parser, $data)
    {
        if (isset($this->bufferedRow['i_'.$this->currentRowIndex], $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex])
        ) {
            $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] .= $data;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareCurrentRow()
    {
        $this->currentRow = array_shift($this->bufferedRow);
        if (is_array($this->currentRow)) {
            $datas = [];
            foreach ($this->currentRow as $key => $value) {
                $datas[$this->columns[$key]] = $value;
            }
            $this->currentRow = $datas;
        }
    }
}
