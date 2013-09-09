<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pagerfanta\Adapter;

use Pagerfanta\Exception\InvalidArgumentException;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class CallbackAdapter implements AdapterInterface
{
    private $getNbResultsCallback;
    private $getSliceCallback;

    /**
     * @param callable $getNbResultsCallback
     * @param callable $getSliceCallback
     */
    public function __construct($getNbResultsCallback, $getSliceCallback)
    {
        if (!is_callable($getNbResultsCallback)) {
            throw new InvalidArgumentException('$getNbResultsCallback should be a callable');
        }
        if (!is_callable($getSliceCallback)) {
            throw new InvalidArgumentException('$getSliceCallback should be a callable');
        }

        $this->getNbResultsCallback = $getNbResultsCallback;
        $this->getSliceCallback = $getSliceCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return call_user_func($this->getNbResultsCallback);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        return call_user_func($this->getSliceCallback, $offset, $length);
    }
}
