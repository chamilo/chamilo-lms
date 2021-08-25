<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * An Experience API document.
 *
 * A document is immutable. This means that it can be accessed like an array.
 * But you can only do this to read data. Thus an {@link UnsupportedOperationException}
 * is thrown when you try to unset data or to manipulate them.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class Document implements \ArrayAccess
{
    private $data;

    public function __construct(DocumentData $data)
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Returns the document's data.
     */
    public function getData(): DocumentData
    {
        return $this->data;
    }
}
