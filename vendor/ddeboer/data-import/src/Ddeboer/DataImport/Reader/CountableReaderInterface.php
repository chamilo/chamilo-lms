<?php

namespace Ddeboer\DataImport\Reader;

/**
 * Reader that provides the count of total items
 *
 * @author David de Boer <david@ddeboer.nl>
 */
interface CountableReaderInterface extends ReaderInterface, \Countable
{
    // Don't add count() to interface: see https://github.com/ddeboer/data-import/pull/5
}
