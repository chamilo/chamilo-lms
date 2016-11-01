<?php

namespace Ddeboer\DataImport\Reader;

/**
 * Reads an array
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ArrayReader extends \ArrayIterator implements CountableReader
{
    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        // Examine first row
        if ($this->count() > 0) {
            return array_keys($this[0]);
        }

        return [];
    }
}
