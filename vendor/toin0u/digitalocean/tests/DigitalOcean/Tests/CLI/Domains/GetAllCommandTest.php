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
use DigitalOcean\CLI\Domains\GetAllCommand;

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
            'domains' => array(
                (object) array(
                    'id'                   => 1,
                    'name'                 => 'foo.org',
                    'ttl'                  => 1800,
                    'live_zone_file'       => '$TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM.\\thostmaster.foo.org. (\\n\\t\\t\\t1369261882 ; last update: 2013-05-22 22:31:22 UTC\\n\\t\\t\\t3600 ; refresh\\n\\t\\t\\t900 ; retry\\n\\t\\t\\t1209600 ; expire\\n\\t\\t\\t10800 ; 3 hours ttl\\n\\t\\t\\t)\\n             IN      NS      NS1.DIGITALOCEAN.COM.\\n @\\tIN A\\t8.8.8.8\\n',
                    'error'                => null,
                    'zone_file_with_error' => null,
                ),
                (object) array(
                    'id'                   => 2,
                    'name'                 => 'bar.org',
                    'ttl'                  => 1800,
                    'live_zone_file'       => '$TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM.\\thostmaster.bar.org. (\\n\\t\\t\\t1369261882 ; last update: 2013-05-22 22:31:22 UTC\\n\\t\\t\\t3600 ; refresh\\n\\t\\t\\t900 ; retry\\n\\t\\t\\t1209600 ; expire\\n\\t\\t\\t10800 ; 3 hours ttl\\n\\t\\t\\t)\\n             IN      NS      NS1.DIGITALOCEAN.COM.\\n @\\tIN A\\t8.8.8.8\\n',
                    'error'                => null,
                    'zone_file_with_error' => null,
                ),
            )
        );

        $GetAllCommand = $this->getMock('\DigitalOcean\CLI\Domains\GetAllCommand', array('getDigitalOcean'));
        $GetAllCommand
            ->expects($this->once())
            ->method('getDigitalOcean')
            ->will($this->returnValue($this->getMockDigitalOcean('domains', $this->getMockDomains('getAll', $result))));

        $this->application->add($GetAllCommand);

        $this->command = $this->application->find('domains:all');

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute()
    {
        $this->commandTester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertTrue(is_string($this->commandTester->getDisplay()));
        $this->assertRegExp('/\| 1  \| foo\.org \|/', $this->commandTester->getDisplay());
        $this->assertRegExp('/\| 2  \| bar\.org \|/', $this->commandTester->getDisplay());
    }
}
