<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\BackupArchive;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use RuntimeException;
use ZipArchive;

use const STR_PAD_LEFT;

/**
 * Moodle .mbz files are gzip tar, including when the upload was stored as .zip.
 * Legacy .mbz files are real zip archives and must keep importing too.
 */
final class BackupArchiveTest extends TestCase
{
    private string $work = '';

    /**
     * @var list<string>
     */
    private array $extraDirs = [];

    protected function setUp(): void
    {
        $this->work = sys_get_temp_dir().'/chamilo_backup_archive_'.bin2hex(random_bytes(4));
        mkdir($this->work, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->extraDirs as $dir) {
            $this->removeDir($dir);
        }
        if ('' !== $this->work && is_dir($this->work)) {
            $this->removeDir($this->work);
        }
    }

    public function testGzipMoodleBackupIsRecognisedWhenTheStoredNameIsZip(): void
    {
        $path = $this->work.'/import_file.zip';
        file_put_contents($path, $this->gzipTar([
            'moodle_backup.xml' => '<moodle_backup><information></information></moodle_backup>',
            'course/course.xml' => '<course></course>',
        ]));

        // A .zip suffix must not hide a gzip payload, and must not be how we store it.
        self::assertSame(BackupArchive::FORMAT_GZIP, BackupArchive::detectFormat($path));
        self::assertSame('mbz', BackupArchive::storageExtension($path, 'zip'));
        self::assertSame('mbz', BackupArchive::storageExtension($path, 'mbz'));
        self::assertSame('gz', BackupArchive::storageExtension($path, 'gz'));
        self::assertSame(
            ['moodle_backup.xml' => true, 'course_info.dat' => false],
            BackupArchive::contains($path, ['moodle_backup.xml', 'course_info.dat'])
        );

        $destination = $this->work.'/gzip-zip-out';
        BackupArchive::extract($path, $destination);
        self::assertStringEqualsFile(
            $destination.'/moodle_backup.xml',
            '<moodle_backup><information></information></moodle_backup>'
        );
        self::assertFileExists($destination.'/course/course.xml');

        $this->assertMoodleImporterExtracts($path);
        self::assertSame([], glob(sys_get_temp_dir().'/chamilo_mbz_*.tar.gz') ?: []);
    }

    public function testLegacyZipMoodleBackupIsRecognisedWhenTheStoredNameIsMbz(): void
    {
        $path = $this->work.'/backup.mbz';
        $this->writeZip($path, [
            'moodle_backup.xml' => '<moodle_backup></moodle_backup>',
            'course/course.xml' => '<course></course>',
        ]);

        self::assertSame(BackupArchive::FORMAT_ZIP, BackupArchive::detectFormat($path));
        self::assertSame('mbz', BackupArchive::storageExtension($path, 'mbz'));
        self::assertSame('zip', BackupArchive::storageExtension($path, 'zip'));
        self::assertSame(
            ['moodle_backup.xml' => true, 'course_info.dat' => false],
            BackupArchive::contains($path, ['moodle_backup.xml', 'course_info.dat'])
        );

        $destination = $this->work.'/zip-mbz-out';
        BackupArchive::extract($path, $destination);
        self::assertFileExists($destination.'/moodle_backup.xml');
        self::assertFileExists($destination.'/course/course.xml');

        $this->assertMoodleImporterExtracts($path);
    }

    public function testChamiloZipStillExposesCourseInfoDat(): void
    {
        $path = $this->work.'/course.zip';
        $this->writeZip($path, [
            'course/course_info.dat' => 'serialized-course',
            'document/index.html' => '<p>hello</p>',
        ]);

        self::assertSame(BackupArchive::FORMAT_ZIP, BackupArchive::detectFormat($path));
        self::assertSame('zip', BackupArchive::storageExtension($path, 'zip'));
        self::assertSame(
            ['moodle_backup.xml' => false, 'course_info.dat' => true],
            BackupArchive::contains($path, ['moodle_backup.xml', 'course_info.dat'])
        );

        $destination = $this->work.'/chamilo-out';
        BackupArchive::extract($path, $destination);
        self::assertStringEqualsFile($destination.'/course/course_info.dat', 'serialized-course');
    }

    public function testTraversalEntryIsRejectedAndNotWritten(): void
    {
        $path = $this->work.'/evil.zip';
        $this->writeZip($path, [
            '../evil.txt' => 'pwn',
            'moodle_backup.xml' => '<moodle_backup></moodle_backup>',
        ]);

        $destination = $this->work.'/evil-out';

        try {
            BackupArchive::extract($path, $destination);
            self::fail('A path-traversal entry must not be extracted.');
        } catch (RuntimeException $e) {
            self::assertSame('Malicious archive entry detected', $e->getMessage());
        }

        self::assertFileDoesNotExist($this->work.'/evil.txt');
        self::assertFileDoesNotExist($destination.'/moodle_backup.xml');
    }

    public function testGzipTraversalEntryCannotEscapeTheDestination(): void
    {
        // Phar rewrites "../" onto a path inside the destination and hides that
        // entry from iteration, so the zip-style rejection does not always fire.
        // What matters is that the payload is not written next to the destination.
        $path = $this->work.'/evil.mbz';
        file_put_contents($path, $this->gzipTar([
            '../evil.txt' => 'pwn',
            'moodle_backup.xml' => '<moodle_backup></moodle_backup>',
        ]));

        $destination = $this->work.'/evil-gzip-out';

        try {
            BackupArchive::extract($path, $destination);
        } catch (RuntimeException $e) {
            self::assertSame('Malicious archive entry detected', $e->getMessage());
        }

        self::assertFileDoesNotExist($this->work.'/evil.txt');
    }

    private function assertMoodleImporterExtracts(string $archivePath): void
    {
        $method = new ReflectionMethod(MoodleImport::class, 'extractToTemp');
        $extracted = $method->invoke(new MoodleImport(false), $archivePath);
        self::assertIsArray($extracted);
        $workDir = (string) ($extracted[0] ?? '');
        $this->extraDirs[] = $workDir;
        self::assertFileExists($workDir.'/moodle_backup.xml');
    }

    /**
     * @param array<string, string> $files
     */
    private function writeZip(string $path, array $files): void
    {
        $zip = new ZipArchive();
        self::assertTrue(true === $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $name => $contents) {
            self::assertTrue($zip->addFromString($name, $contents));
        }
        $zip->close();
    }

    /**
     * @param array<string, string> $files
     */
    private function gzipTar(array $files): string
    {
        $tar = '';
        foreach ($files as $name => $contents) {
            $tar .= $this->tarHeader((string) $name, \strlen($contents));
            $tar .= $contents;
            $remainder = \strlen($contents) % 512;
            if (0 !== $remainder) {
                $tar .= str_repeat("\0", 512 - $remainder);
            }
        }
        $tar .= str_repeat("\0", 1024);

        $gzip = gzencode($tar);
        self::assertIsString($gzip);

        return $gzip;
    }

    private function tarHeader(string $filename, int $size): string
    {
        $header = str_pad(substr($filename, 0, 100), 100, "\0");
        $header .= str_pad(decoct(0o644), 7, '0', STR_PAD_LEFT)."\0";
        $header .= str_pad(decoct(0), 7, '0', STR_PAD_LEFT)."\0";
        $header .= str_pad(decoct(0), 7, '0', STR_PAD_LEFT)."\0";
        $header .= str_pad(decoct($size), 11, '0', STR_PAD_LEFT)."\0";
        $header .= str_pad(decoct(time()), 11, '0', STR_PAD_LEFT)."\0";
        $header .= str_repeat(' ', 8);
        $header .= '0';
        $header .= str_repeat("\0", 100);
        $header .= "ustar\0";
        $header .= '00';
        $header .= str_repeat("\0", 512 - \strlen($header));

        $checksum = 0;
        $length = \strlen($header);
        for ($index = 0; $index < $length; ++$index) {
            $checksum += \ord($header[$index]);
        }
        $checksumField = str_pad(decoct($checksum), 6, '0', STR_PAD_LEFT)."\0 ";

        return substr_replace($header, $checksumField, 148, 8);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }
}
