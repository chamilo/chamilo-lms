<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\Domains;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Domains\DestroyCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DestroyRecordCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status' => 'OK',
        );

        $DestroyCommand = $this->getMock('\DigitalOcean\CLI\Domains\DestroyRecordCommand', array('getDigitalOcean'));
        $DestroyCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('domains', $this->getMockDomains('destroyRecord', $result))));

        $this->application->add($DestroyCommand);

        $this->command = $this->application->find('domains:records:destroy');

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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteWithoutRecordId()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'id'      => 123,
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteWithoutDomainId()
    {
        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'record_id' => 456,
        ));
    }

    public function testExecuteCheckStatusConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(true);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'record_id' => 456,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| OK     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteCheckStatusNotConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(false);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'record_id' => 456,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/Aborted!/', $this->commandTester->getDisplay());
    }
}
