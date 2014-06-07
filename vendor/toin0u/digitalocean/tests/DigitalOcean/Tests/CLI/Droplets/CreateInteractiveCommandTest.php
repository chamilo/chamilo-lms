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
use DigitalOcean\CLI\Droplets\CreateInteractiveCommand;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CreateInteractiveCommandTest extends TestCase
{
    protected $application;

    protected function setUp()
    {
        $this->application = new Application();
    }

    public function testExecuteWithoutDropletName()
    {
        $CreateInteractiveCommand = $this->getMock('\DigitalOcean\CLI\Droplets\CreateInteractiveCommand', array('getDigitalOcean'));
        $CreateInteractiveCommand
            ->expects($this->once())
            ->method('getDigitalOcean')
            ->will($this->returnValue(
                $this->getMockDigitalOcean('droplets', $this->getMockDroplets('create', null))
            ));

        $this->application->add($CreateInteractiveCommand);
        $command = $this->application->find('droplets:create-interactively');
        $command->getHelperSet()->set($this->getDialogAsk(null), 'dialog');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'   => $command->getName(),
        ));

        $this->assertTrue(is_string($commandTester->getDisplay()));
        $this->assertRegExp('/Aborted!/', $commandTester->getDisplay());
    }

    public function testExecuteWithDropletName()
    {
        $this->markTestIncomplete('Make a test with a droplet name');
    }

    public function testGetSizes()
    {
        $sizes = (object) array(
            'sizes' => array(
                (object) array('id' => 1, 'name' => 'foo'),
                (object) array('id' => 2, 'name' => 'bar'),
            )
        );

        $digitalOcean = $this->getMockDigitalOcean('sizes', $this->getMockSizes($sizes));

        $CreateInteractiveCommand = new CreateInteractiveCommand();

        $method = new \ReflectionMethod(
            $CreateInteractiveCommand, 'getSizes'
        );
        $method->setAccessible(true);

        $results = $method->invoke($CreateInteractiveCommand, $digitalOcean);

        $this->assertTrue(is_array($results));
        $this->assertCount(2, $results);
        $this->assertSame('foo', $results[1]);
        $this->assertSame('bar', $results[2]);
    }

    public function testGetRegions()
    {
        $regions = (object) array(
            'regions' => array(
                (object) array('id' => 1, 'name' => 'foo 1'),
                (object) array('id' => 2, 'name' => 'bar 2'),
            )
        );

        $digitalOcean = $this->getMockDigitalOcean('regions', $this->getMockRegions($regions));

        $CreateInteractiveCommand = new CreateInteractiveCommand();

        $method = new \ReflectionMethod(
            $CreateInteractiveCommand, 'getRegions'
        );
        $method->setAccessible(true);

        $results = $method->invoke($CreateInteractiveCommand, $digitalOcean);

        $this->assertTrue(is_array($results));
        $this->assertCount(2, $results);
        $this->assertSame('foo 1', $results[1]);
        $this->assertSame('bar 2', $results[2]);
    }

    public function testGetGlobalImages()
    {
        $images = (object) array(
            'images' => array(
                (object) array('id' => 1, 'name' => 'foo', 'distribution' => 'foobar dist'),
                (object) array('id' => 2, 'name' => 'bar', 'distribution' => 'barqmx dist'),
            )
        );

        $digitalOcean = $this->getMockDigitalOcean('images', $this->getMockImages('getGlobal', $images));

        $CreateInteractiveCommand = new CreateInteractiveCommand();

        $method = new \ReflectionMethod(
            $CreateInteractiveCommand, 'getImages'
        );
        $method->setAccessible(true);

        $results = $method->invoke($CreateInteractiveCommand, $digitalOcean, 0);

        $this->assertTrue(is_array($results));
        $this->assertCount(2, $results);
        $this->assertSame('foo, foobar dist', $results[1]);
        $this->assertSame('bar, barqmx dist', $results[2]);
    }

    public function testGetMyImages()
    {
        $images = (object) array(
            'images' => array(
                (object) array('id' => 1, 'name' => 'foo', 'distribution' => 'foobar dist'),
                (object) array('id' => 2, 'name' => 'bar', 'distribution' => 'barqmx dist'),
            )
        );

        $digitalOcean = $this->getMockDigitalOcean('images', $this->getMockImages('getMyImages', $images));

        $CreateInteractiveCommand = new CreateInteractiveCommand();

        $method = new \ReflectionMethod(
            $CreateInteractiveCommand, 'getImages'
        );
        $method->setAccessible(true);

        $results = $method->invoke($CreateInteractiveCommand, $digitalOcean, 1);

        $this->assertTrue(is_array($results));
        $this->assertCount(2, $results);
        $this->assertSame('foo, foobar dist', $results[1]);
        $this->assertSame('bar, barqmx dist', $results[2]);
    }

    public function testGetAllImages()
    {
        $images = (object) array(
            'images' => array(
                (object) array('id' => 1, 'name' => 'foo', 'distribution' => 'foobar dist'),
                (object) array('id' => 2, 'name' => 'bar', 'distribution' => 'barqmx dist'),
            )
        );

        $digitalOcean = $this->getMockDigitalOcean('images', $this->getMockImages('getAll', $images));

        $CreateInteractiveCommand = new CreateInteractiveCommand();

        $method = new \ReflectionMethod(
            $CreateInteractiveCommand, 'getImages'
        );
        $method->setAccessible(true);

        $results = $method->invoke($CreateInteractiveCommand, $digitalOcean, 2);

        $this->assertTrue(is_array($results));
        $this->assertCount(2, $results);
        $this->assertSame('foo, foobar dist', $results[1]);
        $this->assertSame('bar, barqmx dist', $results[2]);
    }

    public function testGetSshKays()
    {
        $sshKeys = (object) array(
            'ssh_keys' => array(
                (object) array('id' => 123, 'name' => 'office-imac'),
                (object) array('id' => 456, 'name' => 'macbook-pro'),
            )
        );

        $digitalOcean = $this->getMockDigitalOcean('sshkeys', $this->getMockSSHKeys('getAll', $sshKeys));

        $CreateInteractiveCommand = new CreateInteractiveCommand();

        $method = new \ReflectionMethod(
            $CreateInteractiveCommand, 'getSshKeys'
        );
        $method->setAccessible(true);

        $results = $method->invoke($CreateInteractiveCommand, $digitalOcean);

        $this->assertTrue(is_array($results));
        $this->assertCount(3, $results);
        $this->assertSame('None (default)', $results[0]);
        $this->assertSame('office-imac', $results[123]);
        $this->assertSame('macbook-pro', $results[456]);
    }
}
