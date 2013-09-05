<?php

namespace Alchemy\Zippy\Tests\Adapter\VersionProbe;

class BSDTarVersionProbeTest extends AbstractTarVersionProbeTest
{
    public function getProbeClassName()
    {
        return 'Alchemy\Zippy\Adapter\VersionProbe\BSDTarVersionProbe';
    }

    public function getCorrespondingVersionOutput()
    {
        return $this->getBSDTarVersionOutput();
    }

    public function getNonCorrespondingVersionOutput()
    {
        return $this->getGNUTarVersionOutput();
    }
}
