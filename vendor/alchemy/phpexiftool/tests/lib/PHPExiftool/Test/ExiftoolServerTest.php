<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Test;

use PHPExiftool\ExiftoolServer;

class ExiftoolServerTest extends \PHPUnit_Framework_TestCase
{
    protected $exiftool;


    public function setUp()
    {
        $this->exiftool = new ExiftoolServer();
        $this->exiftool->start();
    }

    public function tearDown()
    {
        $this->exiftool->stop();
    }

    /**
     * @covers PHPExiftool\ExiftoolServer::executeCommand
     */
    public function testExecuteCommand()
    {
        $this->assertRegExp('/\d+\.\d+/', $this->exiftool->executeCommand('-ver'));
    }

    /**
     * @covers PHPExiftool\ExiftoolServer::executeCommand
     * @covers \PHPExiftool\Exception\RuntimeException
     * @expectedException \PHPExiftool\Exception\RuntimeException
     */
    public function testExecuteCommandFailed()
    {
        $this->markTestSkipped('Currently disable server support');
        $this->exiftool->executeCommand('-prout');
    }

    public function testReset()
    {
        $this->exiftool->reset();
        $this->exiftool->start();
        $this->assertTrue($this->exiftool->isRunning());
    }

    public function testStop()
    {
        $this->exiftool->stop();
        $this->assertFalse($this->exiftool->isRunning());
    }
}

