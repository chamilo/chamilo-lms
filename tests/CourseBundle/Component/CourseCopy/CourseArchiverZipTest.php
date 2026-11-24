<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use ZipArchive;

/**
 * Course backups used to be packed with PclZip and PCLZIP_OPT_REMOVE_PATH.
 * PclZip is no longer installed, so createBackup() died before it wrote the file.
 * The zip must still keep the old layout: course_info.dat at the archive root,
 * document paths relative to the backup directory, and empty assignment folders
 * present, because the importer reads that layout back with ZipArchive.
 */
final class CourseArchiverZipTest extends TestCase
{
    private string $work = '';

    protected function setUp(): void
    {
        $this->work = sys_get_temp_dir().'/chamilo_course_zip_'.bin2hex(random_bytes(4));
        mkdir($this->work, 0775, true);
    }

    protected function tearDown(): void
    {
        if ('' !== $this->work && is_dir($this->work)) {
            $this->removeDir($this->work);
        }
    }

    public function testBackupZipKeepsTheLegacyLayoutIncludingEmptyFolders(): void
    {
        $source = $this->work.'/CourseArchiver_sample/';
        mkdir($source.'document/folder', 0775, true);
        mkdir($source.'work/assignment-a/nested', 0775, true);
        file_put_contents($source.'course_info.dat', 'serialized-course');
        file_put_contents($source.'document/folder/page.html', '<p>hello</p>');

        $zipPath = $this->work.'/backup.zip';
        $method = new ReflectionMethod(CourseArchiver::class, 'zipDirectory');
        $method->invoke(null, $source, $zipPath);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath));
        $names = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $name = $zip->getNameIndex($index);
            self::assertIsString($name);
            self::assertStringStartsNotWith('/', $name);
            self::assertStringNotContainsString('CourseArchiver_sample', $name);
            $names[] = rtrim($name, '/');
        }
        $zip->close();

        self::assertContains('course_info.dat', $names);
        self::assertContains('document/folder/page.html', $names);
        self::assertContains('work/assignment-a', $names);
        self::assertContains('work/assignment-a/nested', $names);

        $extracted = $this->work.'/out';
        mkdir($extracted);
        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath));
        self::assertTrue($zip->extractTo($extracted));
        $zip->close();

        self::assertStringEqualsFile($extracted.'/course_info.dat', 'serialized-course');
        self::assertStringEqualsFile($extracted.'/document/folder/page.html', '<p>hello</p>');
        self::assertDirectoryExists($extracted.'/work/assignment-a/nested');
    }

    private function removeDir(string $dir): void
    {
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
