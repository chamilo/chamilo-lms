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
use DigitalOcean\CLI\SSHKeys\GetAllCommand;

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
            'ssh_keys' => array(
                (object) array('id' => 123, 'name' => 'office-imac'),
                (object) array('id' => 456, 'name' => 'macbook-pro'),
            )
        );

        $GetAllCommand = $this->getMock('\DigitalOcean\CLI\SSHKeys\GetAllCommand', array('getDigitalOcean'));
        $GetAllCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('sshkeys', $this->getMockSSHKeys('getAll', $result))));

        $this->application->add($GetAllCommand);

        $this->command = $this->application->find('ssh-keys:all');

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteFirstSshKey()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 123 \| office\-imac \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteSecondSshKey()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 456 \| macbook\-pro \|/', $this->commandTester->getDisplay());
    }

    public function testReturnsNoKeys()
    {
        $result = (object) array(
            'ssh_keys' => array()
        );

        $GetAllCommand = $this->getMock('\DigitalOcean\CLI\SSHKeys\GetAllCommand', array('getDigitalOcean'));
        $GetAllCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('sshkeys', $this->getMockSSHKeys('getAll', $result))));

        $this->application->add($GetAllCommand);

        $this->command = $this->application->find('ssh-keys:all');

        $this->commandTester = new CommandTester($this->command);

        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $expected = <<<EOT
+----+------+
| ID | Name |
+----+------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertTrue($expected === $this->commandTester->getDisplay());
    }
}
