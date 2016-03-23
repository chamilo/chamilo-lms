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
 * Read data from a Xml Excel file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
class XmlExcelSourceIterator extends AbstractXmlSourceIterator
{
    /**
     * @param string $filename
     * @param bool   $hasHeaders
     */
    public function __construct($filename, $hasHeaders = true)
    {
        parent::__construct($filename, $hasHeaders);
    }

    /**
     * {@inheritdoc}
     */
    public function tagStart($parser, $name, $attributes = array())
    {
        switch ($name) {
            case 'ss:Row':
            case 'Row':
                $this->bufferedRow['i_'.$this->currentRowIndex] = array();
                break;
            case 'ss:Cell':
            case 'Cell':
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
            case 'ss:Row':
            case 'Row':
                $this->currentRowIndex++;
                $this->currentColumnIndex = 0;
                $this->currentRowEnded = true;
                break;
            case 'ss:Cell':
            case 'Cell':
                $this->currentColumnIndex++;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tagContent($parser, $data)
    {
        $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] .= $data;
    }
}
