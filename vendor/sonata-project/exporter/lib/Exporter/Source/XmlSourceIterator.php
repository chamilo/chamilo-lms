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
class XmlSourceIterator extends AbstractXmlSourceIterator
{
    /**
     * @param string $filename
     */
    public function __construct($filename)
    {
        parent::__construct($filename, false);
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
                $datas[$this->columns[$key]] = $value;
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
    public function tagStart($parser, $name, $attributes = array())
    {
        switch ($name) {
            case 'datas':
                break;
            case 'data':
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
     * End element handler
     *
     * @param resource $parser
     * @param string   $name
     *
     * @return void
     */
    public function tagEnd($parser, $name)
    {
        switch ($name) {
            case 'datas':
                break;
            case 'data':
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
     * Tag content handler
     *
     * @param resource $parser
     * @param string   $data
     *
     * @return void
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
