<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

/**
 * IteratorCallbackSource is IteratorSource with callback executed each row.
 *
 * @author Florent Denis <fdenis@ekino.com>
 */
class IteratorCallbackSourceIterator extends IteratorSourceIterator
{
    /**
     * @var \Closure
     */
    protected $transformer;

    /**
     * @param \Iterator $iterator    Iterator with string array elements
     * @param \Closure  $transformer Altering a data row
     */
    public function __construct(\Iterator $iterator, \Closure $transformer)
    {
        parent::__construct($iterator);

        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return call_user_func($this->transformer, $this->iterator->current());
    }
}
