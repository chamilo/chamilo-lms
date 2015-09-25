<?php

namespace Ddeboer\DataImport\ItemConverter;

/**
 * An item converter takes an input item from a reader, and returns a modified item.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
interface ItemConverterInterface
{
    /**
     * Convert an input
     *
     * @param mixed $input Input
     *
     * @return array|null the modified input or null to remove it
     */
    public function convert($input);
}
