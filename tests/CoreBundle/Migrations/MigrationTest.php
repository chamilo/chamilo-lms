<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Migrations;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testMigrationsStatus(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('doctrine:migrations:status');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            '--no-interaction',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Chamilo\CoreBundle\Migrations\Schema\V200', $output);
    }

    public function testMigrationsList(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('doctrine:migrations:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            '--no-interaction',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Chamilo\CoreBundle\Migrations\Schema\V200', $output);
    }
}
