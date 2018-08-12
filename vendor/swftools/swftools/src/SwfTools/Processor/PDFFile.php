<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools\Processor;

use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use SwfTools\Exception\InvalidArgumentException;
use SwfTools\Exception\RuntimeException;

class PDFFile extends File
{
    /**
     *
     * @param string $inputfile
     * @param string $outputFile
     *
     * @throws InvalidArgumentException
     */
    public function toSwf($inputfile, $outputFile)
    {
        if (!$outputFile) {
            throw new InvalidArgumentException('Bad destination');
        }

        try {
            $this->container['pdf2swf']->toSwf($inputfile, $outputFile);
        } catch (ExecutableNotFoundException $e) {
            throw new RuntimeException('Unable to load pdf2swf', $e->getCode(), $e);
        }
    }
}
