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
class GetRecordsCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status'  => 'OK',
            'records' => array(
                (object) array(
                    'id'          => 49,
                    'domain_id'   => '100',
                    'record_type' => 'A',
                    'name'        => 'example.com',
                    'data'        => '8.8.8.8',
                    'priority'    => null,
                    'port'        => null,
                    'weight'      => null,
                ),
                (object) array(
                    'id'          => 50,
                    'domain_id'   => '100',
                    'record_type' => 'CNAME',
                    'name'        => 'www',
                    'data'        => '@',
                    'priority'    => null,
                    'port'        => null,
                    'weight'      => null,
                ),
            )
        );

        $ShowCommand = $this->getMock('\DigitalOcean\CLI\Domains\GetRecordsCommand', array('getDigitalOcean'));
        $ShowCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('domains', $this->getMockDomains('getRecords', $result))));

        $this->application->add($ShowCommand);

        $this->command = $this->application->find('domains:records:all');

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

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
            'id'      => 123,
        ));

        $expected = <<<'EOT'
+----+-----------+-------+-------------+---------+----------+------+--------+
| ID | Domain ID | Type  | Name        | Data    | Priority | Port | Weight |
+----+-----------+-------+-------------+---------+----------+------+--------+
| 49 | 100       | A     | example.com | 8.8.8.8 |          |      |        |
| 50 | 100       | CNAME | www         | @       |          |      |        |
+----+-----------+-------+-------------+---------+----------+------+--------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
