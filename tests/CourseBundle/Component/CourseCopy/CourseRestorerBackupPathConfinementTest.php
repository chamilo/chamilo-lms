<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression test for GHSA-6556-53fq-3r6m.
 *
 * Every path the course restorer handles travels inside course_info.dat, which any
 * teacher can rebuild before uploading the archive through import_backup.php. The
 * restorer used to trust those paths: an asset path chose its own destination name,
 * and an attachment or a SCORM package path chose its own source file. A crafted
 * archive could therefore write a file outside the sub-trees the exporter fills, or
 * read a file of the server and publish it as a downloadable attachment.
 *
 * These cases lock the two guarantees that close the report. No backup-supplied path
 * ever resolves outside the extracted backup, and no asset ever lands outside the
 * three sub-trees the exporter fills, nor keeps an executable extension.
 */
final class CourseRestorerBackupPathConfinementTest extends TestCase
{
    private string $root = '';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/chamilo_restorer_'.uniqid('', true);

        mkdir($this->root.'/backup/upload/calendar', 0777, true);
        mkdir($this->root.'/backup/scorm/package', 0777, true);
        file_put_contents($this->root.'/backup/upload/calendar/agenda.pdf', 'legitimate');
        file_put_contents($this->root.'/secret.txt', 'database credentials');
    }

    protected function tearDown(): void
    {
        if ('' !== $this->root && is_dir($this->root)) {
            $this->removeDirectory($this->root);
        }
    }

    public function testAFileOfTheBackupResolves(): void
    {
        $file = $this->root.'/backup/upload/calendar/agenda.pdf';

        $this->assertSame(realpath($file), $this->call('resolveFileInsideBackup', $file));
    }

    public function testADirectoryOfTheBackupResolves(): void
    {
        $directory = $this->root.'/backup/scorm/package';

        $this->assertSame(realpath($directory), $this->call('resolveInsideBackup', $directory));
    }

    /**
     * @dataProvider escapingPathProvider
     */
    public function testAPathThatEscapesTheBackupIsRejected(string $candidate): void
    {
        $this->assertNull($this->call('resolveFileInsideBackup', $candidate));
        $this->assertNull($this->call('resolveInsideBackup', $candidate));
    }

    /**
     * @return array<string,array{string}>
     */
    public static function escapingPathProvider(): array
    {
        return [
            'traversal out of the backup' => ['%root%/backup/upload/calendar/../../../secret.txt'],
            'sibling of the backup' => ['%root%/secret.txt'],
            'absolute path of the server' => ['/etc/passwd'],
            'parent directory' => ['%root%/backup/..'],
        ];
    }

    /**
     * @dataProvider assetPathProvider
     */
    public function testTheDestinationOfAnAssetStaysInTheExpectedSubTrees(string $path, ?string $expected): void
    {
        $this->assertSame($expected, $this->call('getSafeAssetPath', $path));
    }

    /**
     * @return array<string,array{string,null|string}>
     */
    public static function assetPathProvider(): array
    {
        return [
            // The proof of concept of the report: no traversal, but a destination the
            // exporter never fills, and an extension the web server may execute.
            'proof of concept of the report' => ['document/certificates/pwned.php', 'document/certificates/pwned.phps'],
            'course image at the root' => ['course-pic85x85.png', 'course-pic85x85.png'],
            'audio of a lesson item' => ['document/audio/intro.mp3', 'document/audio/intro.mp3'],
            'cover of a lesson' => ['upload/learning_path/images/cover.jpg', 'upload/learning_path/images/cover.jpg'],
            'sub-tree the exporter never fills' => ['config/services.yaml', null],
            'traversal' => ['../../../../public/index.php', null],
            'absolute path' => ['/var/www/html/shell.php', null],
            'remote url' => ['https://example.org/shell.php', null],
            'empty path' => ['', null],
            'directory' => ['document/', null],
        ];
    }

    /**
     * Calls a private method of a restorer whose course only carries a backup path.
     */
    private function call(string $method, string $argument): ?string
    {
        $reflection = new ReflectionClass(CourseRestorer::class);

        $restorer = $reflection->newInstanceWithoutConstructor();
        $restorer->course = (object) ['backup_path' => $this->root.'/backup'];

        $call = $reflection->getMethod($method);
        $call->setAccessible(true);

        return $call->invoke($restorer, str_replace('%root%', $this->root, $argument));
    }

    private function removeDirectory(string $directory): void
    {
        foreach (array_diff(scandir($directory) ?: [], ['.', '..']) as $entry) {
            $child = $directory.'/'.$entry;

            if (is_dir($child) && !is_link($child)) {
                $this->removeDirectory($child);

                continue;
            }

            unlink($child);
        }

        rmdir($directory);
    }
}
