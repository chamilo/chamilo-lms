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
use DigitalOcean\CLI\SSHKeys\ShowCommand;

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
            'ssh_key' => (object) array(
                'id'          => 123,
                'name'        => 'office-imac',
                'ssh_pub_key' => 'ssh-dss AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhfgBAFFHGBJbju8)H3hnNGjASGFkjgZn86ZCqk02NX3BTcMV4YI2I4/sebg8VnuebDn0XUbbmVrAq4YqGiobn86ZCqk02NX3BTcMp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YIaSCO43Y+ghI2of4+E1TDJ1R9Znk9XJsald/U0u0uXwtyHXP2sommNWuAGtzp4QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzCT/NT6L6VoD5n+jSZflFD684gdLsW1+gjVoFBk0MZWuGSXEQyIwlBRq/8jAAAAFQDrxI/h35BewJUmVjid8Qk1NprMvQAAAIAspspAelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YI2IEzb6R2vzZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow== me@office-imac',
            )
        );

        $ShowCommand = $this->getMock('\DigitalOcean\CLI\SSHKeys\ShowCommand', array('getDigitalOcean'));
        $ShowCommand
            ->expects($this->any())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('sshkeys', $this->getMockSSHKeys('show', $result))));

        $this->application->add($ShowCommand);

        $this->command = $this->application->find('ssh-keys:show');

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
+-----+-------------+----------------------------------------------------+
| ID  | Name        | Pub Key                                            |
+-----+-------------+----------------------------------------------------+
| 123 | office-imac | ssh-dss                                            |
|     |             | AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhf |
|     |             | gBAFFHGBJbju8)H3hnNGjASGFkjgZn86ZCqk02NX3BTcMV4YI2 |
|     |             | I4/sebg8VnuebDn0XUbbmVrAq4YqGiobn86ZCqk02NX3BTcMp4 |
|     |             | QGmyL4/sebg8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4 |
|     |             | YqGiob48KzCT/NT6L6VoD5n+jSZvQAAAIAspspAelh4bW5ncO5 |
|     |             | +CedFZPZn86ZCqk02NX3BTcMV4YIaSCO43Y+ghI2of4+E1TDJ1 |
|     |             | R9Znk9XJsald/U0u0uXwtyHXP2sommNWuAGtzp4QGmyL4/sebg |
|     |             | 8Vnusytv93cA2PsXOxvbU0CdebDn0XUbbmVrAq4YqGiob48KzC |
|     |             | T/NT6L6VoD5n+jSZflFD684gdLsW1+gjVoFBk0MZWuGSXEQyIw |
|     |             | lBRq/8jAAAAFQDrxI/h35BewJUmVjid8Qk1NprMvQAAAIAspsp |
|     |             | Aelh4bW5ncO5+CedFZPZn86ZCqk02NX3BTcMV4YI2IEzb6R2vz |
|     |             | ZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow==          |
|     |             | me@office-imac                                     |
+-----+-------------+----------------------------------------------------+

EOT
        ;

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertSame($expected, $this->commandTester->getDisplay());
    }
}
