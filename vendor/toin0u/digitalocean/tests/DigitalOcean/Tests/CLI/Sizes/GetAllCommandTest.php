<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\Sizes;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Sizes\GetAllCommand;

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
            'sizes' => array(
                (object) array('id' => 1, 'name' => 'foo'),
                (object) array('id' => 2, 'name' => 'bar'),
            )
        );

        $GetAllCommand = $this->getMock('\DigitalOcean\CLI\Sizes\GetAllCommand', array('getDigitalOcean'));
        $GetAllCommand
            ->expects($this->once())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('sizes', $this->getMockSizes($result))));

        $this->application->add($GetAllCommand);

        $this->command = $this->application->find('sizes:all');

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 2  \| bar/', $this->commandTester->getDisplay());
    }
}
