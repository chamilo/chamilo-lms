<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateApplyPlanner;
use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateFileApplier;
use Chamilo\CoreBundle\Service\Update\UpdateMigrationPolicy;
use Chamilo\CoreBundle\Service\Update\UpdateOperationLogger;
use Chamilo\CoreBundle\Service\Update\UpdatePackageRemovalManifest;
use PHPUnit\Framework\TestCase;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class UpdateFileApplierTest extends TestCase
{
    private string $projectDir;
    private string $stagingPath;
    private string $applicationPath;
    private UpdatePackageRemovalManifest $packageRemovalManifest;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/chamilo-update-apply-'.bin2hex(random_bytes(8));
        $this->stagingPath = $this->projectDir.'/var/update/staging/test-package';
        $this->applicationPath = $this->stagingPath.'/chamilo';
        $this->packageRemovalManifest = new UpdatePackageRemovalManifest();

        mkdir($this->projectDir.'/config/packages', 0777, true);
        mkdir($this->applicationPath.'/src', 0777, true);
        mkdir($this->applicationPath.'/public', 0777, true);

        file_put_contents($this->projectDir.'/composer.json', "{\"name\":\"chamilo/current\"}\n");
        file_put_contents($this->projectDir.'/config/packages/mcp.yaml', "mcp: {}\n");
        file_put_contents($this->applicationPath.'/composer.json', "{\"name\":\"chamilo/target\"}\n");
        file_put_contents(
            $this->applicationPath.'/'.UpdatePackageRemovalManifest::FILE_NAME,
            json_encode([
                'format' => 1,
                'remove' => ['config/packages/mcp.yaml'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
        );

        $this->writeStagingMetadata();

        $plan = $this->createPlanner()->buildPlan($this->stagingPath);
        self::assertTrue($plan->isValid(), implode("\n", $plan->getErrors()));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testApplyBacksUpAndRemovesPackageDeclaredObsoleteFile(): void
    {
        $applier = new UpdateFileApplier(
            $this->projectDir,
            new UpdateOperationLogger($this->projectDir),
            new UpdateConfiguration('test'),
            $this->packageRemovalManifest,
        );

        $result = $applier->apply($this->stagingPath, true);

        self::assertTrue($result->isValid(), implode("\n", $result->getErrors()));
        self::assertSame("{\"name\":\"chamilo/target\"}\n", file_get_contents($this->projectDir.'/composer.json'));
        self::assertFileDoesNotExist($this->projectDir.'/config/packages/mcp.yaml');

        $backupPath = $result->getBackupPath();
        self::assertNotNull($backupPath);
        self::assertFileExists($backupPath.'/files/config/packages/mcp.yaml');
        self::assertSame("mcp: {}\n", file_get_contents($backupPath.'/files/config/packages/mcp.yaml'));

        $backupMetadata = json_decode((string) file_get_contents($backupPath.'/BACKUP-INFO.json'), true);
        self::assertSame(1, $backupMetadata['files_to_remove'] ?? null);
        self::assertSame(2, $backupMetadata['files_backed_up'] ?? null);

        $details = $result->getDetails();
        self::assertSame(1, $details['file_operations']['files_to_remove'] ?? null);
    }

    private function createPlanner(): UpdateApplyPlanner
    {
        return new UpdateApplyPlanner(
            new UpdateMigrationPolicy(),
            $this->packageRemovalManifest,
            $this->projectDir,
        );
    }

    private function writeStagingMetadata(): void
    {
        $packageMetadata = $this->packageRemovalManifest->load($this->applicationPath);

        file_put_contents(
            $this->stagingPath.'/STAGING-INFO.json',
            json_encode([
                'created_at' => gmdate('c'),
                'manifest' => [
                    'channel' => 'test',
                    'version' => '3.0.1',
                ],
                'package_path' => '/tmp/chamilo-update-test.zip',
                'application_path' => $this->applicationPath,
                'package_metadata' => $packageMetadata,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
