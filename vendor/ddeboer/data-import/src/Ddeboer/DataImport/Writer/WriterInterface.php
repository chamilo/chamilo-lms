<?php

namespace Ddeboer\DataImport\Writer;

/**
 * Persists data in a storage medium, such as a database, CSV or XML file, etc.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
interface WriterInterface
{
    /**
     * Prepare the writer before writing the items
     *
     * @return $this
     */
    public function prepare();

    /**
     * Write one data item
     *
     * @param array $item The data item with converted values
     *
     * @return $this
     */
    public function writeItem(array $item);

    /**
     * Wrap up the writer after all items have been written
     *
     * @return $this
     */
    public function finish();
}
