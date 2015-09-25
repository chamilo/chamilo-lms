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
class AddRecordCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status'        => 'OK',
            'domain_record' => (object) array(
                'id'          => 7,
                'domain_id'   => '123',
                'record_type' => 'SRV',
                'name'        => 'foo',
                'data'        => '@',
                'priority'    => 1,
                'port'        => 2,
                'weight'      => 3,
            ),
        );

        $AddCommand = $this->getMock('\DigitalOcean\CLI\Domains\AddRecordCommand', array('getDigitalOcean'));
        $AddCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('domains', $this->getMockDomains('newRecord', $result))));

        $this->application->add($AddCommand);

        $this->command = $this->application->find('domains:records:add');

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
    public function testExecuteNotEnoughArgumentsWithoutRecordType()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'data'    => '@',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Not enough arguments.
     */
    public function testExecuteNotEnoughArgumentsWithoutData()
    {
        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'record_type' => 'CNAME',
        ));
    }

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command'     => $this->command->getName(),
            'record_type' => 'SRV',
            'data'        => '@',
            'name'        => 'foo',
            'priority'    => 1,
            'port'        => 2,
            'weight'      => 3,
        ));

        $expected = <<<EOT
+--------+----+-----------+------+------+------+----------+------+--------+
| Status | ID | Domain ID | Type | Name | Data | Priority | Port | Weight |
+--------+----+-----------+------+------+------+----------+------+--------+
| OK     | 7  | 123       | SRV  | foo  | @    | 1        | 2    | 3      |
+--------+----+-----------+------+------+------+----------+------+--------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
