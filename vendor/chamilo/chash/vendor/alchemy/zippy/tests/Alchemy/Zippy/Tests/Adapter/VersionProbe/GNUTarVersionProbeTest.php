<?php

namespace Alchemy\Zippy\Tests\Adapter\VersionProbe;

class GNUTarVersionProbeTest extends AbstractTarVersionProbeTest
{
    public function getProbeClassName()
    {
        return 'Alchemy\Zippy\Adapter\VersionProbe\GNUTarVersionProbe';
    }

    public function getCorrespondingVersionOutput()
    {
        return $this->getGNUTarVersionOutput();
    }

    public function getNonCorrespondingVersionOutput()
    {
        return $this->getBSDTarVersionOutput();
    }
}
