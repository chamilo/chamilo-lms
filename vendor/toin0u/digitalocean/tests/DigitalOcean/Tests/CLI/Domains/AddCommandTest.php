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
use DigitalOcean\CLI\Domains\AddCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class AddCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status' => 'OK',
            'domain' => (object) array(
                'id'          => 123,
                'name'        => 'foo.org',
            ),
        );

        $AddCommand = $this->getMock('\DigitalOcean\CLI\Domains\AddCommand', array('getDigitalOcean'));
        $AddCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('domains', $this->getMockDomains('add', $result))));

        $this->application->add($AddCommand);

        $this->command = $this->application->find('domains:add');

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
            'command'    => $this->command->getName(),
            'ip_address' => '127.0.0.1',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteNotEnoughArgumentsWithoutIpaddress()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'name'    => 'foo.org',
        ));
    }

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command'    => $this->command->getName(),
            'name'       => 'foo.org',
            'ip_address' => '127.0.0.1',
        ));

        $expected = <<<EOT
+--------+-----+---------+
| Status | ID  | Name    |
+--------+-----+---------+
| OK     | 123 | foo.org |
+--------+-----+---------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
