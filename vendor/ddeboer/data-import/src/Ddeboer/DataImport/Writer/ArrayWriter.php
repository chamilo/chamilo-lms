<?php

namespace Ddeboer\DataImport\Writer;

/**
 * This class writes an item into an array that was passed by reference
 *
 * Class ArrayWriter
 */
class ArrayWriter implements WriterInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    /**
     * {@inheritDoc}
     */
    public function prepare()
    {
        $this->data = array();
    }

    /**
     * {@inheritDoc}
     */
    public function writeItem(array $item)
    {
        $this->data[] = $item;
    }

    /**
     * {@inheritDoc}
     */
    public function finish()
    {

    }
}
