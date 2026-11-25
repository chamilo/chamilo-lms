<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * CourseArchiver copies assignment folders with an unqualified call to
 * copyDirWithoutFilesTo(). That helper used to live in fileManage.lib.php and
 * was deleted while the call stayed, so a backup that contains assignments
 * died with "undefined function". The folders have to be nested the same way
 * as the course, and the submitted files must not be copied into the backup.
 */
final class CopyDirWithoutFilesToTest extends TestCase
{
    private string $work = '';

    protected function setUp(): void
    {
        $this->work = sys_get_temp_dir().'/chamilo_copy_dirs_'.bin2hex(random_bytes(4));
        mkdir($this->work, 0775, true);
    }

    protected function tearDown(): void
    {
        if ('' !== $this->work && is_dir($this->work)) {
            $this->removeDir($this->work);
        }
    }

    public function testNestedAssignmentFoldersAreCopiedAndFilesAreLeftBehind(): void
    {
        $source = $this->work.'/work';
        mkdir($source.'/assignment-a/nested', 0775, true);
        mkdir($source.'/assignment-b', 0775, true);
        file_put_contents($source.'/assignment-a/submission.txt', 'student file');
        file_put_contents($source.'/assignment-a/nested/draft.txt', 'nested file');

        $destination = $this->work.'/backup/work';

        // Same kind of call as CourseArchiver: no leading "\", from inside a namespace.
        $copied = copyDirWithoutFilesTo($source, $destination);

        self::assertTrue($copied);
        self::assertDirectoryExists($destination.'/assignment-a/nested');
        self::assertDirectoryExists($destination.'/assignment-b');
        self::assertFileDoesNotExist($destination.'/assignment-a/submission.txt');
        self::assertFileDoesNotExist($destination.'/assignment-a/nested/draft.txt');
    }

    public function testMissingSourceIsNotAnError(): void
    {
        self::assertFalse(copyDirWithoutFilesTo($this->work.'/missing', $this->work.'/backup'));
        self::assertDirectoryDoesNotExist($this->work.'/backup');
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
