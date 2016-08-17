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
use DigitalOcean\CLI\Images\DestroyCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DestroyCommandTest extends TestCase
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

        $DestroyCommand = $this->getMock('\DigitalOcean\CLI\Images\DestroyCommand', array('getDigitalOcean'));
        $DestroyCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('images', $this->getMockImages('destroy', $result))));

        $this->application->add($DestroyCommand);

        $this->command = $this->application->find('images:destroy');

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

    public function testExecuteCheckStatusConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(true);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'id'      => 999,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| OK     \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteCheckStatusNotConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(false);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'id'      => 999,
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/Aborted!/', $this->commandTester->getDisplay());
    }
}
