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
use DigitalOcean\CLI\Images\TransfertCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class TransferCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status'   => 'OK',
            'event_id' => 1234,
        );

        $TransferCommand = $this->getMock('\DigitalOcean\CLI\Images\TransferCommand', array('getDigitalOcean'));
        $TransferCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('images', $this->getMockImages('transfert', $result))));

        $this->application->add($TransferCommand);

        $this->command = $this->application->find('images:transfer');

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
    public function testExecuteNotEnoughArgumentsWithoutName()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'id'      => 123,
        ));
    }

    public function testExecuteCheckStatusConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(true);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'region_id' => 345,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| OK     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteCheckEventIdConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(true);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'region_id' => 345,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 1234     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteCheckStatusNotConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(false);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'region_id' => 345,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/Aborted!/', $this->commandTester->getDisplay());
    }

    public function testExecuteCheckEventIdNotConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(false);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'region_id' => 345,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/Aborted!/', $this->commandTester->getDisplay());
    }
}
