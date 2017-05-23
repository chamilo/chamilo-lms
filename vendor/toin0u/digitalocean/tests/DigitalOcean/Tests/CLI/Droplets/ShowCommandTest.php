<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\CLI\Droplets;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use DigitalOcean\Tests\TestCase;
use DigitalOcean\CLI\Droplets\ShowCommand;

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
            'droplet' => (object) array(
                'id'                 => 123,
                'name'               => 'foo',
                'image_id'           => 98,
                'size_id'            => 76,
                'region_id'          => 54,
                'backups_active'     => 1,
                'ip_address'         => '127.0.0.1',
                'private_ip_address' => null,
                'status'             => 'active',
                'locked'             => false,
                'created_at'         => '2013-01-01T09:30:00Z',
                'backups'            => array(0, 1, 2, 3, 4, 5, 6),
                'snapshots'          => array(0),
            )
        );

        $ShowCommand = $this->getMock('\DigitalOcean\CLI\Droplets\ShowCommand', array('getDigitalOcean'));
        $ShowCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('droplets', $this->getMockDroplets('show', $result))));

        $this->application->add($ShowCommand);

        $this->command = $this->application->find('droplets:show');

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

        $expected = <<<EOT
+-----+------+----------+---------+-----------+----------------+---------+-----------+------------+--------------------+--------+--------+----------------------+
| ID  | Name | Image ID | Size ID | Region ID | Backups Active | Backups | Snapshots | IP Address | Private IP Address | Status | Locked | Created At           |
+-----+------+----------+---------+-----------+----------------+---------+-----------+------------+--------------------+--------+--------+----------------------+
| 123 | foo  | 98       | 76      | 54        | 1              | 7       | 1         | 127.0.0.1  |                    | active |        | 2013-01-01T09:30:00Z |
+-----+------+----------+---------+-----------+----------------+---------+-----------+------------+--------------------+--------+--------+----------------------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
