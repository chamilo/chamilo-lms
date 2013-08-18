<?php

namespace Flint\Console;

use Flint\PimpleAwareInterface;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication implements PimpleAwareInterface
{
    protected $pimple;

    /**
     * @param Pimple $pimple
     * @param string $name
     * @param string $version
     */
    public function __construct(\Pimple $pimple, $name = 'Flint', $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->setPimple($pimple);
    }

    /**
     * @return Pimple
     */
    public function getPimple()
    {
        return $this->pimple;
    }

    /**
     * {@inheritDoc}
     */
    public function setPimple(\Pimple $pimple = null)
    {
        $this->pimple = $pimple;
    }
}
