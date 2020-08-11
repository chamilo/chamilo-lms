<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Command;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CommandTest extends TestCase
{
    protected $distFile;
    protected $command;

    protected function setUp()
    {
        $this->distFile = 'credentials.yml.dist';
        $this->command  = new Command('foo');
    }

    public function testGetDigitalOcean()
    {
        $digitalOcean = $this->command->getDigitalOcean($this->distFile);

        $this->assertTrue(is_object($digitalOcean));
        $this->assertInstanceOf('\\DigitalOcean\\DigitalOcean', $digitalOcean);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Impossible to get credentials informations in ./foo/bar
     */
    public function testGetDigitalOceanThrowsRuntimeException()
    {
        $this->command->getDigitalOcean('./foo/bar');
    }
}
