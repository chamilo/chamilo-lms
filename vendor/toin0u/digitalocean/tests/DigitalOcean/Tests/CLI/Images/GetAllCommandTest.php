<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\Images;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Images\GetAllCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class GetAllCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'images' => array(
                (object) array('id' => 1, 'name' => 'foo', 'distribution' => 'foobar dist'),
                (object) array('id' => 2, 'name' => 'bar', 'distribution' => 'barqmx dist'),
            )
        );

        $GetAllCommand = $this->getMock('\DigitalOcean\CLI\Images\GetAllCommand', array('getDigitalOcean'));
        $GetAllCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('images', $this->getMockImages('getAll', $result))));

        $this->application->add($GetAllCommand);

        $this->command = $this->application->find('images:all');

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteFirstImage()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 1  \| foo  \| foobar dist  \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteSecondImage()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 2  \| bar  \| barqmx dist  \|/', $this->commandTester->getDisplay());
    }
}
