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
use DigitalOcean\CLI\Domains\ShowCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ShowRecordCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status' => 'OK',
            'record' => (object) array(
                'id'          => 3,
                'domain_id'   => '123',
                'record_type' => 'CNAME',
                'name'        => 'www',
                'data'        => '@',
                'priority'    => null,
                'port'        => null,
                'weight'      => null,
            ),
        );

        $ShowCommand = $this->getMock('\DigitalOcean\CLI\Domains\ShowRecordCommand', array('getDigitalOcean'));
        $ShowCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('domains', $this->getMockDomains('getRecord', $result))));

        $this->application->add($ShowCommand);

        $this->command = $this->application->find('domains:records:show');

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

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command'   => $this->command->getName(),
            'id'        => 123,
            'record_id' => 456,
        ));

        $expected = <<<'EOT'
+--------+----+-----------+-------+------+------+----------+------+--------+
| Status | ID | Domain ID | Type  | Name | Data | Priority | Port | Weight |
+--------+----+-----------+-------+------+------+----------+------+--------+
| OK     | 3  | 123       | CNAME | www  | @    |          |      |        |
+--------+----+-----------+-------+------+------+----------+------+--------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
