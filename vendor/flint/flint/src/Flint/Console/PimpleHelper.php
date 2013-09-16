<?php

namespace Flint\Console;

use Flint\PimpleAwareInterface;
use Symfony\Component\Console\Helper\Helper;
use Pimple;

/**
 * Provides access to a pimple instance.
 *
 * @package Flint
 */
class PimpleHelper extends Helper implements PimpleAwareInterface
{
    protected $pimple;

    /**
     * {@inheritDoc}
     */
    public function __construct(Pimple $pimple)
    {
        $this->setPimple($pimple);
    }

    /**
     * {@inheritDoc}
     */
    public function setPimple(Pimple $pimple = null)
    {
        $this->pimple = $pimple;
    }

    /**
     * {@inheritDoc}
     */
    public function getPimple()
    {
        return $this->pimple;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'pimple';
    }
}
