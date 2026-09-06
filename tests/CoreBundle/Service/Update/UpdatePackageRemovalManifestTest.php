<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Update;

use Chamilo\CoreBundle\Service\Update\UpdatePackageRemovalManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class UpdatePackageRemovalManifestTest extends TestCase
{
    private string $applicationPath;

    protected function setUp(): void
    {
        $this->applicationPath = sys_get_temp_dir().'/chamilo-update-metadata-'.bin2hex(random_bytes(8));
        mkdir($this->applicationPath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->applicationPath);
    }

    public function testMissingMetadataIsReportedAsAbsent(): void
    {
        $metadata = (new UpdatePackageRemovalManifest())->load($this->applicationPath);

        self::assertFalse($metadata['present']);
        self::assertSame([], $metadata['remove']);
        self::assertNull($metadata['sha256']);
    }

    public function testValidMetadataLoadsRemovalPathsAndHash(): void
    {
        $this->writeMetadata([
            'format' => 1,
            'remove' => [
                'config/packages/mcp.yaml',
                'src/Obsolete.php',
                'config/packages/mcp.yaml',
            ],
        ]);

        $metadata = (new UpdatePackageRemovalManifest())->load($this->applicationPath);

        self::assertTrue($metadata['present']);
        self::assertSame(1, $metadata['format']);
        self::assertSame([
            'config/packages/mcp.yaml',
            'src/Obsolete.php',
        ], $metadata['remove']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $metadata['sha256']);
    }

    public function testProtectedRemovalPathIsRejected(): void
    {
        $this->writeMetadata([
            'format' => 1,
            'remove' => ['public/upload/user.txt'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot remove protected path');

        (new UpdatePackageRemovalManifest())->load($this->applicationPath);
    }

    public function testTraversalRemovalPathIsRejected(): void
    {
        $this->writeMetadata([
            'format' => 1,
            'remove' => ['../.env'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsafe segment');

        (new UpdatePackageRemovalManifest())->load($this->applicationPath);
    }

    public function testPackageCannotIncludeAndRemoveSamePath(): void
    {
        $includedPath = $this->applicationPath.'/config/packages/kept.yaml';
        mkdir(\dirname($includedPath), 0777, true);
        file_put_contents($includedPath, "framework: {}\n");

        $this->writeMetadata([
            'format' => 1,
            'remove' => ['config/packages/kept.yaml'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot include and remove the same path');

        (new UpdatePackageRemovalManifest())->load($this->applicationPath);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function writeMetadata(array $metadata): void
    {
        file_put_contents(
            $this->applicationPath.'/'.UpdatePackageRemovalManifest::FILE_NAME,
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'
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
