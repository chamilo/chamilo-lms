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
 * xAPI statement extensions.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Extensions implements \ArrayAccess
{
    private $extensions;

    public function __construct(\SplObjectStorage $extensions = null)
    {
        $this->extensions = array();

        if (null !== $extensions) {
            foreach ($extensions as $iri) {
                if (!$iri instanceof IRI) {
                    throw new \InvalidArgumentException(sprintf('Expected an IRI instance as key (got %s).', is_object($iri) ? get_class($iri) : gettype($iri)));
                }

                $this->extensions[$iri->getValue()] = $extensions[$iri];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        if (!$offset instanceof IRI) {
            throw new \InvalidArgumentException(sprintf('Expected an IRI instance as key (got %s).', is_object($offset) ? get_class($offset) : gettype($offset)));
        }

        return isset($this->extensions[$offset->getValue()]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$offset instanceof IRI) {
            throw new \InvalidArgumentException(sprintf('Expected an IRI instance as key (got %s).', is_object($offset) ? get_class($offset) : gettype($offset)));
        }

        if (!isset($this->extensions[$offset->getValue()])) {
            throw new \InvalidArgumentException(sprintf('No extension for key "%s" registered.', $offset->getValue()));
        }

        return $this->extensions[$offset->getValue()];
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedOperationException Statement extensions are immutable
     */
    public function offsetSet($offset, $value): void
    {
        throw new UnsupportedOperationException('xAPI statement extensions are immutable.');
    }

    /**
     * {@inheritdoc}
     *
     * @throws UnsupportedOperationException Statement extensions are immutable
     */
    public function offsetUnset($offset): void
    {
        throw new UnsupportedOperationException('xAPI statement extensions are immutable.');
    }

    public function getExtensions(): \SplObjectStorage
    {
        $extensions = new \SplObjectStorage();

        foreach ($this->extensions as $iri => $value) {
            $extensions->attach(IRI::fromString($iri), $value);
        }

        return $extensions;
    }

    public function equals(Extensions $otherExtensions): bool
    {
        if (count($this->extensions) !== count($otherExtensions->extensions)) {
            return false;
        }

        foreach ($this->extensions as $iri => $value) {
            if (!array_key_exists($iri, $otherExtensions->extensions)) {
                return false;
            }

            if ($this->extensions[$iri] != $otherExtensions->extensions[$iri]) {
                return false;
            }
        }

        return true;
    }
}
