<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\SSHKeys;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\SSHKeys\EditCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class EditCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status'  => 'OK',
            'ssh_key' => (object) array(
                'id'          => 123,
                'name'        => 'my_ssh_key',
                'ssh_pub_key' => 'the new ssh key string',
            ),
        );

        $EditCommand = $this->getMock('\DigitalOcean\CLI\SSHKeys\EditCommand', array('getDigitalOcean'));
        $EditCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('sshkeys', $this->getMockSSHKeys('edit', $result))));

        $this->application->add($EditCommand);

        $this->command = $this->application->find('ssh-keys:edit');

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
    public function testExecuteNotEnoughArgumentsWithoutSSHKeyId()
    {
        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'ssh_pub_key' => 'foo',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteNotEnoughArgumentsWithoutNewSSHKey()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'id'      => 123,
        ));
    }

    public function testExecuteNotConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(false);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'id'          => 123,
            'ssh_pub_key' => 'foo',
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/Aborted!/', $this->commandTester->getDisplay());
    }

    public function testExecuteConfirmed()
    {
        $dialog = $this->getDialogAskConfirmation(true);
        $this->command->getHelperSet()->set($dialog, 'dialog');

        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'id'          => 123,
            'ssh_pub_key' => 'foo',
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| OK     \|/', $this->commandTester->getDisplay());
    }
}
