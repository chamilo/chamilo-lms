<?php

namespace Flint;

/**
 * A Silex version of ContainerAwareInterface that is found in Symfony
 *
 * @package Flint
 */
interface PimpleAwareInterface
{
    /**
     * @param Pimple|null $flint
     */
    public function setPimple(\Pimple $pimple = null);
}
