<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Installer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

use const PATHINFO_FILENAME;

final class MigrationMetadataConfigurationTest extends TestCase
{
    private const int UTF8MB4_SAFE_VERSION_LENGTH = 191;

    public function testConsoleAndInstallerUseTheSameUtf8mb4SafeVersionLength(): void
    {
        $projectDir = \dirname(__DIR__, 3);

        $consoleConfiguration = Yaml::parseFile($projectDir.'/config/packages/doctrine_migrations.yaml');
        $installerConfiguration = require $projectDir.'/public/main/install/migrations.php';

        $this->assertSame(
            self::UTF8MB4_SAFE_VERSION_LENGTH,
            $consoleConfiguration['doctrine_migrations']['storage']['table_storage']['version_column_length']
        );
        $this->assertSame(
            self::UTF8MB4_SAFE_VERSION_LENGTH,
            $installerConfiguration['table_storage']['version_column_length']
        );
    }

    public function testBundledMigrationIdentifiersFitTheMetadataColumn(): void
    {
        $projectDir = \dirname(__DIR__, 3);
        $migrationsRoot = $projectDir.'/src/CoreBundle/Migrations/Schema';
        $longest = 0;

        foreach (glob($migrationsRoot.'/V*/Version*.php') ?: [] as $migrationFile) {
            $versionDirectory = basename(\dirname($migrationFile));
            $className = pathinfo($migrationFile, PATHINFO_FILENAME);
            $identifier = 'Chamilo\CoreBundle\Migrations\Schema\\'.$versionDirectory.'\\'.$className;
            $longest = max($longest, \strlen($identifier));
        }

        $this->assertGreaterThan(0, $longest);
        $this->assertLessThanOrEqual(self::UTF8MB4_SAFE_VERSION_LENGTH, $longest);
    }
}
