<?php

namespace Ddeboer\DataImport\Exception;

/**
 * @author David de Boer <david@ddeboer.nl>
 */
class DuplicateHeadersException extends ReaderException
{
    /**
     * @param array $duplicates
     */
    public function __construct(array $duplicates)
    {
        parent::__construct(sprintf('File contains duplicate headers: %s', implode($duplicates, ', ')));
    }
}
