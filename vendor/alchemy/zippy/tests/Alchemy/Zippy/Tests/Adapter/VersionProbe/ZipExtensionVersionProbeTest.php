<?php

namespace Alchemy\Zippy\Tests\Adapter\VersionProbe;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Adapter\VersionProbe\ZipExtensionVersionProbe;
use Alchemy\Zippy\Adapter\VersionProbe\VersionProbeInterface;

class ZipExtensionVersionProbeTest extends TestCase
{
    /**
     * @covers Alchemy\Zippy\Adapter\VersionProbe\ZipExtensionVersionProbe::getStatus
     */
    public function testGetStatus()
    {
        $expectation = VersionProbeInterface::PROBE_OK;
        if (false === class_exists('ZipArchive')) {
            $expectation = VersionProbeInterface::PROBE_NOTSUPPORTED;
        }

        $probe = new ZipExtensionVersionProbe();
        $this->assertEquals($expectation, $probe->getStatus());
    }
}
