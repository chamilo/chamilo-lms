<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateApplyPlanner;
use Chamilo\CoreBundle\Service\Update\UpdateMigrationPolicy;
use Chamilo\CoreBundle\Service\Update\UpdatePackageRemovalManifest;
use PHPUnit\Framework\TestCase;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class UpdateApplyPlannerTest extends TestCase
{
    private string $projectDir;
    private string $stagingPath;
    private string $applicationPath;
    private UpdatePackageRemovalManifest $packageRemovalManifest;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/chamilo-update-plan-'.bin2hex(random_bytes(8));
        $this->stagingPath = $this->projectDir.'/var/update/staging/test-package';
        $this->applicationPath = $this->stagingPath.'/chamilo';
        $this->packageRemovalManifest = new UpdatePackageRemovalManifest();

        mkdir($this->projectDir.'/config/packages', 0777, true);
        mkdir($this->applicationPath.'/src', 0777, true);
        mkdir($this->applicationPath.'/public', 0777, true);

        file_put_contents($this->projectDir.'/composer.json', "{\"name\":\"chamilo/current\"}\n");
        file_put_contents($this->projectDir.'/config/packages/mcp.yaml', "mcp: {}\n");
        file_put_contents($this->applicationPath.'/composer.json', "{\"name\":\"chamilo/target\"}\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testPlanIncludesSignedObsoleteFileRemoval(): void
    {
        $this->writePackageMetadata(['config/packages/mcp.yaml']);
        $this->writeStagingMetadata();

        $result = $this->createPlanner()->buildPlan($this->stagingPath);

        self::assertTrue($result->isValid(), implode("\n", $result->getErrors()));

        $filePlan = $result->getDetails()['file_plan'] ?? [];

        self::assertSame(1, $filePlan['files_to_remove'] ?? null);
        self::assertSame(['config/packages/mcp.yaml'], $filePlan['files_to_remove_paths'] ?? null);
        self::assertSame(['config/packages/mcp.yaml'], $filePlan['removal_paths_declared'] ?? null);
        self::assertFileExists($this->stagingPath.'/APPLY-PLAN.json');
    }

    public function testApplyPlanIsBlockedWithoutSignedCleanupMetadata(): void
    {
        $this->writeStagingMetadata();

        $result = $this->createPlanner()->buildPlan($this->stagingPath);

        self::assertFalse($result->isValid());
        self::assertStringContainsString(
            'Signed cleanup metadata is required',
            implode("\n", $result->getErrors())
        );
        self::assertFileDoesNotExist($this->stagingPath.'/APPLY-PLAN.json');
    }

    public function testApplyPlanRejectsCleanupMetadataChangedAfterStaging(): void
    {
        $this->writePackageMetadata(['config/packages/mcp.yaml']);
        $this->writeStagingMetadata();

        $this->writePackageMetadata([]);

        $result = $this->createPlanner()->buildPlan($this->stagingPath);

        self::assertFalse($result->isValid());
        self::assertStringContainsString(
            'cleanup metadata changed after staging',
            implode("\n", $result->getErrors())
        );
    }

    private function createPlanner(): UpdateApplyPlanner
    {
        return new UpdateApplyPlanner(
            new UpdateMigrationPolicy(),
            $this->packageRemovalManifest,
            $this->projectDir,
        );
    }

    /**
     * @param string[] $remove
     */
    private function writePackageMetadata(array $remove): void
    {
        file_put_contents(
            $this->applicationPath.'/'.UpdatePackageRemovalManifest::FILE_NAME,
            json_encode([
                'format' => 1,
                'remove' => $remove,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
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
