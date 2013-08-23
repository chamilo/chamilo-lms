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

/**
 * Provides you with an adapter that returns always the same data.
 *
 * Best used when you need to do a custom paging solution and don't
 * want to implement a full adapter for a one-off use case.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class FixedAdapter implements AdapterInterface
{
    private $nbResults;
    private $results;

    /**
     * @param int   $nbResults
     * @param mixed $results
     */
    public function __construct($nbResults, $results)
    {
        $this->nbResults = $nbResults;
        $this->results = $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }

    /**
     * {@inheritDoc}
     */
    public function getSlice($offset, $length)
    {
        return $this->results;
    }
}
