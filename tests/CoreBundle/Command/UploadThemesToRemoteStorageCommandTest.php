<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Command;

use Chamilo\CoreBundle\Command\UploadThemesToRemoteStorageCommand;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * The themes bundled with Chamilo live in var/themes, which is also what the default local
 * adapter serves. Uploading them to the configured remote storage is what makes that storage
 * usable, and these tests pin the two rules that keep the upload safe: files an administrator
 * uploaded are never discarded, and the command refuses to write var/themes onto itself.
 */
class UploadThemesToRemoteStorageCommandTest extends TestCase
{
    private string $projectDir;
    private string $targetDir;

    protected function setUp(): void
    {
        $unique = bin2hex(random_bytes(4));
        $this->projectDir = sys_get_temp_dir().'/chamilo-themes-source-'.$unique;
        $this->targetDir = sys_get_temp_dir().'/chamilo-themes-target-'.$unique;

        $filesystem = new SymfonyFilesystem();
        $filesystem->mkdir($this->projectDir.'/var/themes/chamilo/images');
        $filesystem->dumpFile($this->projectDir.'/var/themes/chamilo/colors.css', ':root { --color: red; }');
        $filesystem->dumpFile($this->projectDir.'/var/themes/chamilo/images/header-logo.svg', '<svg></svg>');
        $filesystem->mkdir($this->targetDir);
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove([$this->projectDir, $this->targetDir]);
    }

    public function testUploadsBundledThemesToTheConfiguredStorage(): void
    {
        $tester = $this->execute($this->targetDir);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('2 file(s) uploaded, 0 kept', $tester->getDisplay());
        $this->assertSame(':root { --color: red; }', file_get_contents($this->targetDir.'/chamilo/colors.css'));
        $this->assertFileExists($this->targetDir.'/chamilo/images/header-logo.svg');
    }

    public function testKeepsExistingFilesSoAdministratorUploadsAreNotDiscarded(): void
    {
        $uploaded = ':root { --color: blue; }';
        (new SymfonyFilesystem())->dumpFile($this->targetDir.'/chamilo/colors.css', $uploaded);

        $tester = $this->execute($this->targetDir);

        $this->assertStringContainsString('1 file(s) uploaded, 1 kept', $tester->getDisplay());
        $this->assertSame($uploaded, file_get_contents($this->targetDir.'/chamilo/colors.css'));
    }

    public function testOverwriteReplacesExistingFiles(): void
    {
        (new SymfonyFilesystem())->dumpFile($this->targetDir.'/chamilo/colors.css', ':root { --color: blue; }');

        $tester = $this->execute($this->targetDir, ['--overwrite' => true]);

        $this->assertStringContainsString('2 file(s) uploaded, 0 kept', $tester->getDisplay());
        $this->assertSame(':root { --color: red; }', file_get_contents($this->targetDir.'/chamilo/colors.css'));
    }

    public function testDryRunWritesNothing(): void
    {
        $tester = $this->execute($this->targetDir, ['--dry-run' => true]);

        $this->assertStringContainsString('DRY RUN: 2 file(s) uploaded', $tester->getDisplay());
        $this->assertFileDoesNotExist($this->targetDir.'/chamilo/colors.css');
    }

    /**
     * With no remote storage configured the themes filesystem is var/themes itself, so the
     * upload would mean writing every file onto itself.
     */
    public function testDoesNothingWhenTheFilesystemIsTheSourceDirectory(): void
    {
        $source = $this->projectDir.'/var/themes';

        $tester = $this->execute($source);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Nothing to upload', $tester->getDisplay());
        $this->assertSame([], glob($source.'/.chamilo-themes-upload-*'));
    }

    /**
     * Adapters are free to consume the handle they are given: the Azure one wraps it in a
     * Guzzle PSR-7 stream whose destructor closes it, and a bare fclose() afterwards then
     * fails with "supplied resource is not a valid stream resource", aborting the upload on
     * its very first file.
     */
    public function testSurvivesAnAdapterThatClosesTheStreamItself(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('fileExists')->willReturn(false);
        $filesystem
            ->method('writeStream')
            ->willReturnCallback(static function (string $path, $stream): void {
                fclose($stream);
            })
        ;

        $tester = new CommandTester(new UploadThemesToRemoteStorageCommand($filesystem, $this->projectDir));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('2 file(s) uploaded, 0 kept, 0 failed', $tester->getDisplay());
    }

    private function execute(string $targetDir, array $input = []): CommandTester
    {
        $filesystem = new Flysystem(new LocalFilesystemAdapter($targetDir));
        $tester = new CommandTester(new UploadThemesToRemoteStorageCommand($filesystem, $this->projectDir));
        $tester->execute($input);

        return $tester;
    }
}
