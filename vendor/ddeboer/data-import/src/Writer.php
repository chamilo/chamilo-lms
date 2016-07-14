<?php

namespace Ddeboer\DataImport;

/**
 * Persists data in a storage medium, such as a database, CSV or XML file, etc.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
interface Writer
{
    /**
     * Prepare the writer before writing the items
     */
    public function prepare();

    /**
     * Write one data item
     *
     * @param array $item The data item with converted values
     */
    public function writeItem(array $item);

    /**
     * Wrap up the writer after all items have been written
     */
    public function finish();
}
