<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\InstalledChamiloVersionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class InstalledChamiloVersionProviderTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/chamilo-version-provider-'.bin2hex(random_bytes(8));
        mkdir($this->projectDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
    }

    public function testItReadsPrivateMetadataAfterInstallDirectoryIsRemoved(): void
    {
        $this->writeVersionFile('version.php', '2.1.0');

        $provider = $this->createProvider();

        self::assertDirectoryDoesNotExist($this->projectDir.'/public/main/install');
        self::assertSame('2.1.0', $provider->getInstalledVersion());
        self::assertSame('2.1.0', $provider->getVersionDetails()['new_version'] ?? null);
    }

    public function testPrivateMetadataTakesPrecedenceOverInstallerCompatibilityFile(): void
    {
        $this->writeVersionFile('version.php', '2.1.0');
        $this->writeVersionFile('public/main/install/version.php', '2.0.3');

        self::assertSame('2.1.0', $this->createProvider()->getInstalledVersion());
    }

    public function testInstallerVersionRemainsAvailableAsBackwardCompatibleFallback(): void
    {
        $this->writeVersionFile('public/main/install/version.php', '2.0.3');

        self::assertSame('2.0.3', $this->createProvider()->getInstalledVersion());
    }

    public function testItRejectsComposerPlaceholderVersions(): void
    {
        $provider = $this->createProvider();

        self::assertNull($provider->normalizeVersion('1.0.0+no-version-set'));
        self::assertNull($provider->normalizeVersion('1.0.0.0'));
        self::assertNull($provider->normalizeVersion('dev-master'));
        self::assertNull($provider->normalizeVersion('dev-main'));
    }

    private function createProvider(): InstalledChamiloVersionProvider
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getProjectDir')
            ->willReturn($this->projectDir)
        ;

        return new InstalledChamiloVersionProvider($kernel);
    }

    private function writeVersionFile(string $relativePath, string $version): void
    {
        $path = $this->projectDir.'/'.$relativePath;
        $directory = \dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $path,
            "<?php\n\nreturn ['new_version' => ".var_export($version, true).", 'new_version_status' => 'stable'];\n"
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.'/'.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
