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
final class XmlSourceIterator extends AbstractXmlSourceIterator
{
    /**
     * @var string
     */
    private $mainTag;

    /**
     * @var string
     */
    private $dataTag;

    public function __construct(string $filename, string $mainTag = 'datas', string $dataTag = 'data')
    {
        parent::__construct($filename, false);
        $this->mainTag = $mainTag;
        $this->dataTag = $dataTag;
    }

    public function tagStart($parser, string $name, array $attributes = []): void
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

    public function tagEnd($parser, string $name): void
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

    public function tagContent($parser, string $data): void
    {
        if (isset(
            $this->bufferedRow['i_'.$this->currentRowIndex],
            $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex]
        )) {
            $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] .= $data;
        }
    }

    protected function prepareCurrentRow(): void
    {
        $this->currentRow = array_shift($this->bufferedRow);
        if (\is_array($this->currentRow)) {
            $datas = [];
            foreach ($this->currentRow as $key => $value) {
                $datas[$this->columns[$key]] = $value;
            }
            $this->currentRow = $datas;
        }
    }
}
