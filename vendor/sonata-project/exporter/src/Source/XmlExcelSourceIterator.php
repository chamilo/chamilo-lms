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
 * Read data from a Xml Excel file.
 *
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 */
final class XmlExcelSourceIterator extends AbstractXmlSourceIterator
{
    public function __construct(string $filename, bool $hasHeaders = true)
    {
        parent::__construct($filename, $hasHeaders);
    }

    public function tagStart($parser, string $name, array $attributes = []): void
    {
        switch ($name) {
            case 'ss:Row':
            case 'Row':
                $this->bufferedRow['i_'.$this->currentRowIndex] = [];

                break;
            case 'ss:Cell':
            case 'Cell':
                // set empty values when opening Cell tag
                $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] = '';

                break;
        }
    }

    public function tagEnd($parser, string $name): void
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

    public function tagContent($parser, string $data): void
    {
        $this->bufferedRow['i_'.$this->currentRowIndex][$this->currentColumnIndex] .= $data;
    }
}
