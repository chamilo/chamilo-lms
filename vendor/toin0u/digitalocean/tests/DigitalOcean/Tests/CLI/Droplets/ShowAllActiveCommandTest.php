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
use DigitalOcean\CLI\Droplets\ShowAllActiveCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ShowAllActiveCommandTest extends TestCase
{
    protected $application;
    protected $command;
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        $result = (object) array(
            'droplets' => array(
                (object) array(
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
                ),
                (object) array(
                    'id'                 => 456,
                    'name'               => 'bar',
                    'image_id'           => 34,
                    'size_id'            => 56,
                    'region_id'          => 78,
                    'backups_active'     => 0,
                    'ip_address'         => '127.0.0.1',
                    'private_ip_address' => '127.0.0.1',
                    'status'             => 'active',
                    'locked'             => false,
                    'created_at'         => '2013-01-01T09:30:00Z',
                ),
            )
        );

        $ShowAllActiveCommand = $this->getMock('\DigitalOcean\CLI\Droplets\ShowAllActiveCommand', array('getDigitalOcean'));
        $ShowAllActiveCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('droplets', $this->getMockDroplets('showAllActive', $result))));

        $this->application->add($ShowAllActiveCommand);

        $this->command = $this->application->find('droplets:show-all-active');

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteFirstDroplet()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 123 \| foo  \| 98       \| 76      \| 54        \| 1              \| 127\.0\.0\.1  \|                    \| active \|        \| 2013\-01\-01T09\:30\:00Z \|/', $this->commandTester->getDisplay());
    }

    public function testExecuteSecondDroplet()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 456 \| bar  \| 34       \| 56      \| 78        \| 0              \| 127\.0\.0\.1  \| 127\.0\.0\.1          \| active \|        \| 2013\-01\-01T09\:30:00Z \|/', $this->commandTester->getDisplay());
    }

    public function testReturnsNoDroplets()
    {
        $result = (object) array(
            'droplets' => array()
        );

        $ShowAllActiveCommand = $this->getMock('\DigitalOcean\CLI\Droplets\ShowAllActiveCommand', array('getDigitalOcean'));
        $ShowAllActiveCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('droplets', $this->getMockDroplets('showAllActive', $result))));

        $this->application->add($ShowAllActiveCommand);

        $this->command = $this->application->find('droplets:show-all-active');

        $this->commandTester = new CommandTester($this->command);

        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $expected = <<<EOT
+----+------+----------+---------+-----------+----------------+------------+--------------------+--------+--------+------------+
| ID | Name | Image ID | Size ID | Region ID | Backups Active | IP Address | Private IP Address | Status | Locked | Created At |
+----+------+----------+---------+-----------+----------------+------------+--------------------+--------+--------+------------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertTrue($expected === $this->commandTester->getDisplay());
    }
}
