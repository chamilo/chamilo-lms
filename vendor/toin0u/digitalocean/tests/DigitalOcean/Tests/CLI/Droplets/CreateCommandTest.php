<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\Droplets;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Droplets\CreateCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CreateCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status'  => 'OK',
            'droplet' => (object) array (
                'id'       => 100824,
                'name'     => 'foo',
                'image_id' => 9876,
                'size_id'  => 32,
                'event_id' => 1234,
            ),
        );

        $CreateCommand = $this->getMock('\DigitalOcean\CLI\Droplets\CreateCommand', array('getDigitalOcean'));
        $CreateCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('droplets', $this->getMockDroplets('create', $result))));

        $this->application->add($CreateCommand);

        $this->command = $this->application->find('droplets:create');

        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteNotEnoughArguments()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));
    }

    public function testExecuteCheckStatus()
    {
        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'name'      => 'foo',
            'size_id'   => 123,
            'image_id'  => 456,
            'region_id' => 789,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| OK     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteCheckEventId()
    {
        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'name'      => 'foo',
            'size_id'   => 123,
            'image_id'  => 456,
            'region_id' => 789,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 1234     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteWithSSHKeysCheckStatus()
    {
        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'name'        => 'foo',
            'size_id'     => 123,
            'image_id'    => 456,
            'region_id'   => 789,
            'ssh_key_ids' => '1,2,3',
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| OK     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteWithSSHKeysCheckEventId()
    {
        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'name'        => 'foo',
            'size_id'     => 123,
            'image_id'    => 456,
            'region_id'   => 789,
            'ssh_key_ids' => '1,2,3',
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 1234     \|/', $this->commandTester->getDisplay());
    }
}
