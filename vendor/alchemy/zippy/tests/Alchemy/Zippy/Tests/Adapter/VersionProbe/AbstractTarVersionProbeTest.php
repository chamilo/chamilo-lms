<?php

namespace Alchemy\Zippy\Tests\Adapter\VersionProbe;

use Alchemy\Zippy\Tests\TestCase;
use Alchemy\Zippy\Adapter\VersionProbe\BSDTarVersionProbe;
use Alchemy\Zippy\Adapter\VersionProbe\VersionProbeInterface;

abstract class AbstractTarVersionProbeTest extends TestCase
{
    /**
     * @covers Alchemy\Zippy\Adapter\VersionProbe\BSDTarVersionProbe::getStatus
     */
    public function testGetStatusIsOk()
    {
        $mockInflator = $this->getBuilder($this->getCorrespondingVersionOutput());
        $mockDeflator = $this->getBuilder($this->getCorrespondingVersionOutput());

        $classname = $this->getProbeClassName();

        $probe = new $classname($this->getMockedProcessBuilderFactory($mockInflator), $this->getMockedProcessBuilderFactory($mockDeflator));

        $this->assertEquals(VersionProbeInterface::PROBE_OK, $probe->getStatus());
        // second time is served from cache
        $this->assertEquals(VersionProbeInterface::PROBE_OK, $probe->getStatus());
    }

    /**
     * @dataProvider provideInvalidVersions
     * @covers Alchemy\Zippy\Adapter\VersionProbe\BSDTarVersionProbe::getStatus
     */
    public function testGetStatusIsNotOk($inflatorVersion, $deflatorVersion, $inflatorCall, $deflatorCall)
    {
        $mockInflatorBuilder = $inflatorVersion ? $this->getBuilder($inflatorVersion, $inflatorCall) : null;
        $mockDeflatorBuilder = $deflatorVersion ? $this->getBuilder($deflatorVersion, $deflatorCall) : null;

        $builderInflator = $mockInflatorBuilder ? $this->getMockedProcessBuilderFactory($mockInflatorBuilder, $inflatorCall ? 1 : 0) : null;
        $builderDeflator = $mockDeflatorBuilder ? $this->getMockedProcessBuilderFactory($mockDeflatorBuilder, $deflatorCall ? 1 : 0) : null;

        $classname = $this->getProbeClassName();

        $probe = new $classname($builderInflator, $builderDeflator);

        $this->assertEquals(VersionProbeInterface::PROBE_NOTSUPPORTED, $probe->getStatus());
        // second time is served from cache
        $this->assertEquals(VersionProbeInterface::PROBE_NOTSUPPORTED, $probe->getStatus());
    }

    public function provideInvalidVersions()
    {
        return array(
            array($this->getCorrespondingVersionOutput(), $this->getNonCorrespondingVersionOutput(), true, true),
            array($this->getNonCorrespondingVersionOutput(), $this->getCorrespondingVersionOutput(), true, false),
        );
    }

    protected function getBuilder($version, $call = true)
    {
        $mock = $this->getMock('Symfony\Component\Process\ProcessBuilder');

        $mockBuilder = $mock
            ->expects($call ? $this->once() : $this->never())
            ->method('add');
        if ($call) {
            $mockBuilder->with('--version');
        }
        $mockBuilder->will($this->returnSelf());

        $process = $this->getSuccessFullMockProcess($call ? 1 : 0);

        $mock
            ->expects($call ? $this->once() : $this->never())
            ->method('getProcess')
            ->will($this->returnValue($process));

        $process
            ->expects($call ? $this->once() : $this->never())
            ->method('getOutput')
            ->will($this->returnValue($version));

        return $mock;
    }

    protected function getBSDTarVersionOutput()
    {
        return 'bsdtar 2.8.3 - libarchive 2.8.3';
    }

    protected function getGNUTarVersionOutput()
    {
        return 'tar (GNU tar) 1.17
Copyright (C) 2007 Free Software Foundation, Inc.
License GPLv2+: GNU GPL version 2 or later <http://gnu.org/licenses/gpl.html>
This is free software: you are free to change and redistribute it.
There is NO WARRANTY, to the extent permitted by law.

Modified to support extended attributes.
Written by John Gilmore and Jay Fenlason.';
    }

    abstract public function getProbeClassName();
    abstract public function getCorrespondingVersionOutput();
    abstract public function getNonCorrespondingVersionOutput();
}
