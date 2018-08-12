<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\Events;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Events\ShowCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ShowCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'status' => 'OK',
            'event'  => (object) array(
                'id'            => 1,
                'action_status' => 'done',
                'droplet_id'    => 100824,
                'event_type_id' => 1,
                'percentage'    => '100',
            )
        );

        $ShowCommand = $this->getMock('\DigitalOcean\CLI\Events\ShowCommand', array('getDigitalOcean'));
        $ShowCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('events', $this->getMockDomains('show', $result))));

        $this->application->add($ShowCommand);

        $this->command = $this->application->find('events:show');

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
+----+--------+------------+---------------+------------+
| ID | Status | Droplet ID | Event Type ID | Percentage |
+----+--------+------------+---------------+------------+
| 1  | done   | 100824     | 1             | 100        |
+----+--------+------------+---------------+------------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
