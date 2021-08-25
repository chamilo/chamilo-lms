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

use Xabbuh\XApi\Common\Exception\UnsupportedOperationException;

/**
 * An xAPI document's data.
 *
 * Document data are immutable. This means that they can be accessed like an array.
 * But you can only do this to read data. Thus an {@link UnsupportedOperationException}
 * is thrown when you try to unset data or to manipulate them.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class DocumentData implements \ArrayAccess
{
    private $data = array();

    public function __construct(array $data = array())
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
        if (!isset($this->data[$offset])) {
            throw new \InvalidArgumentException(sprintf('No data for name "%s" registered.', $offset));
        }

        return $this->data[$offset];
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnsupportedOperationException Documents are immutable
     */
    public function offsetSet($offset, $value): void
    {
        throw new UnsupportedOperationException('A document is immutable.');
    }

    /**
     * {@inheritDoc}
     *
     * @throws UnsupportedOperationException Documents are immutable
     */
    public function offsetUnset($offset): void
    {
        throw new UnsupportedOperationException('A document is immutable.');
    }

    /**
     * Returns all data as an array.
     */
    public function getData(): array
    {
        return $this->data;
    }
}
