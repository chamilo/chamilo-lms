<?php

namespace Flint;

/**
 * @package Flint
 */
abstract class PimpleAware implements PimpleAwareInterface
{
    protected $pimple;

    /**
     * {@inheritDoc}
     */
    public function setPimple(\Pimple $pimple = null)
    {
        $this->pimple = $pimple;
    }
}
