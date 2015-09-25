<?php

namespace Ddeboer\DataImport\Writer;

/**
 * Persists data in a storage medium, such as a database, XML file, etc.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
abstract class AbstractWriter implements WriterInterface
{
    /**
     * Prepare the writer before writing the items
     *
     * This template method can be overridden in concrete writer
     * implementations.
     *
     * @return $this
     */
    public function prepare()
    {
        return $this;
    }

    /**
     * Wrap up the writer after all items have been written
     *
     * This template method can be overridden in concrete writer
     * implementations.
     *
     * @return $this
     */
    public function finish()
    {
        return $this;
    }
}
