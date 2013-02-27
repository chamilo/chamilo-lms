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
    private $nbResultsCallback;
    private $sliceCallback;

    /**
     * @param callable $nbResultsCallback
     * @param callable $sliceCallback
     */
    public function __construct($nbResultsCallback, $sliceCallback)
    {
        if (!is_callable($nbResultsCallback)) {
            throw new InvalidArgumentException('$nbResultsCallback should be a callable');
        }
        if (!is_callable($sliceCallback)) {
            throw new InvalidArgumentException('$sliceCallback should be a callable');
        }

        $this->nbResultsCallback = $nbResultsCallback;
        $this->sliceCallback = $sliceCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        return call_user_func($this->nbResultsCallback);
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        return call_user_func_array($this->sliceCallback, array($offset, $length));
    }
}
