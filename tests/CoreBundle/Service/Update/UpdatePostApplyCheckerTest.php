<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateMigrationPolicy;
use Chamilo\CoreBundle\Service\Update\UpdatePostApplyChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

final class UpdatePostApplyCheckerTest extends TestCase
{
    private string $projectDir;
    private string $stagingPath;
    private string $applicationPath;
    private string $backupPath;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/chamilo-post-apply-'.bin2hex(random_bytes(8));
        $this->stagingPath = $this->projectDir.'/var/update/staging/test-package';
        $this->applicationPath = $this->stagingPath.'/chamilo';
        $this->backupPath = $this->projectDir.'/var/update/backups/test-package';

        foreach ([
            $this->applicationPath.'/assets/vue',
            $this->applicationPath.'/public/build',
            $this->backupPath.'/files',
            $this->projectDir.'/vendor',
        ] as $directory) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->applicationPath.'/composer.json', '{"name":"chamilo/release"}');
        file_put_contents($this->applicationPath.'/composer.lock', '{"packages":[]}');
        file_put_contents($this->applicationPath.'/assets/vue/main.js', 'export default {}');
        file_put_contents($this->applicationPath.'/public/build/entrypoints.json', '{"entrypoints":{}}');
        file_put_contents($this->projectDir.'/composer.lock', '{"packages":[]}');
        file_put_contents($this->backupPath.'/files/composer.lock', '{"packages":[]}');
        file_put_contents($this->projectDir.'/vendor/autoload.php', '<?php');

        $this->writeJson($this->stagingPath.'/STAGING-INFO.json', [
            'application_path' => $this->applicationPath,
        ]);
        $this->writeJson($this->stagingPath.'/APPLY-PLAN.json', [
            'application_path' => $this->applicationPath,
            'backup_path' => $this->backupPath,
            'permission_restore' => ['restore_commands' => ['sudo chown -R 1000:1000 /safe/path']],
            'file_plan' => ['migration_files_new' => []],
        ]);
        $this->writeJson($this->stagingPath.'/APPLY-RESULT.json', ['success' => true]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testPreparedReleaseOnlyRecommendsCacheClear(): void
    {
        $result = $this->checker()->check($this->stagingPath);

        self::assertTrue($result->isValid(), implode("\n", $result->getErrors()));
        self::assertSame(['cache_clear'], array_column($result->getActions(), 'key'));
        self::assertFalse($result->getDetails()['package_signals']['composer_install_required']);
        self::assertFalse($result->getDetails()['package_signals']['frontend_build_missing']);
        self::assertSame(['sudo chown -R 1000:1000 /safe/path'], $result->getDetails()['permission_restore']['restore_commands']);
    }

    public function testChangedComposerLockBlocksCompletionRatherThanRequiringComposerInBrowser(): void
    {
        file_put_contents($this->projectDir.'/composer.lock', '{"packages":[{"name":"updated"}]}');

        $result = $this->checker()->check($this->stagingPath);

        self::assertFalse($result->isValid());
        self::assertTrue($result->getDetails()['package_signals']['composer_install_required']);
        self::assertStringContainsString('vendor/', implode("\n", $result->getWarnings()));
        $metadata = json_decode((string) file_get_contents($this->stagingPath.'/POST-APPLY-CHECKS.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($metadata['success']);
        self::assertSame([], $metadata['actions']);
    }

    public function testMissingCompiledAssetsBlocksCompletion(): void
    {
        unlink($this->applicationPath.'/public/build/entrypoints.json');

        $result = $this->checker()->check($this->stagingPath);

        self::assertFalse($result->isValid());
        self::assertTrue($result->getDetails()['package_signals']['frontend_build_missing']);
        self::assertStringContainsString('public/build', implode("\n", $result->getWarnings()));
    }

    public function testDatabaseMigrationsRemainInTheExecutableReview(): void
    {
        $this->writeJson($this->stagingPath.'/APPLY-PLAN.json', [
            'application_path' => $this->applicationPath,
            'backup_path' => $this->backupPath,
            'file_plan' => [
                'migration_files_new' => ['src/CoreBundle/Migrations/Schema/V300/Version20260101000000.php'],
            ],
        ]);

        $result = $this->checker()->check($this->stagingPath);

        self::assertTrue($result->isValid(), implode("\n", $result->getErrors()));
        self::assertSame(['database_migrations', 'cache_clear'], array_column($result->getActions(), 'key'));
    }

    private function checker(): UpdatePostApplyChecker
    {
        return new UpdatePostApplyChecker(
            new UpdateConfiguration('prod'),
            new UpdateMigrationPolicy(),
            $this->projectDir,
            new IdentityTranslator(),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $name) {
            if ('.' === $name || '..' === $name) {
                continue;
            }

            $path = $directory.'/'.$name;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
