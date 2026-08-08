<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CourseMetaImportTest extends TestCase
{
    private string $workDir = '';

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir().'/chamilo_course_meta_'.bin2hex(random_bytes(4));
        mkdir($this->workDir.'/chamilo/course', 0775, true);
        mkdir($this->workDir.'/files/ab', 0775, true);
    }

    protected function tearDown(): void
    {
        if ('' !== $this->workDir && is_dir($this->workDir)) {
            $this->removeDir($this->workDir);
        }
    }

    public function testImportToolsMetaKeepsValidKeysAndDropsUnsafeOnes(): void
    {
        file_put_contents($this->workDir.'/chamilo/course/tools.json', json_encode([
            'tools' => [
                ['title' => 'document', 'visibility' => false, 'position' => 1],
                ['title' => 'course_homepage', 'visibility' => true, 'position' => 2],
                ['title' => '../evil', 'visibility' => true],
                ['title' => 'bad title', 'visibility' => true],
            ],
        ], JSON_THROW_ON_ERROR));

        $resources = ['course_tools' => []];
        $ok = $this->invokePrivate('tryImportCourseToolsMeta', [$this->workDir, &$resources]);

        self::assertTrue($ok);
        self::assertCount(2, $resources['course_tools']);
        self::assertSame('document', $resources['course_tools'][1]->title);
        self::assertFalse((bool) $resources['course_tools'][1]->visibility);
        self::assertSame('course_homepage', $resources['course_tools'][2]->title);
        self::assertTrue((bool) $resources['course_tools'][2]->visibility);
    }

    public function testImportIllustrationFromSidecar(): void
    {
        $hash = str_repeat('ab', 20);
        file_put_contents($this->workDir.'/files/ab/'.$hash, 'fake-image-bytes');
        file_put_contents($this->workDir.'/chamilo/course/illustration.json', json_encode([
            'contenthash' => $hash,
            'filename' => 'cover.png',
            'mimetype' => 'image/png',
            'filesize' => 16,
        ], JSON_THROW_ON_ERROR));

        $resources = ['course_illustration' => []];
        $ok = $this->invokePrivate('tryImportCourseIllustrationMeta', [$this->workDir, &$resources]);

        self::assertTrue($ok);
        self::assertArrayHasKey(1, $resources['course_illustration']);
        self::assertSame($hash, $resources['course_illustration'][1]->contenthash);
        self::assertSame('cover.png', $resources['course_illustration'][1]->filename);
        self::assertSame('image/png', $resources['course_illustration'][1]->mimetype);
    }

    public function testImportIllustrationFallsBackToFilesXml(): void
    {
        $hash = str_repeat('cd', 20);
        mkdir($this->workDir.'/files/cd', 0775, true);
        file_put_contents($this->workDir.'/files/cd/'.$hash, 'fake-image-bytes');
        file_put_contents($this->workDir.'/files.xml', <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<files>
  <file id="1">
    <contenthash>{$hash}</contenthash>
    <component>course</component>
    <filearea>overviewfiles</filearea>
    <filename>.</filename>
    <filesize>0</filesize>
    <mimetype></mimetype>
  </file>
  <file id="2">
    <contenthash>{$hash}</contenthash>
    <component>course</component>
    <filearea>overviewfiles</filearea>
    <filename>from_xml.jpg</filename>
    <filesize>16</filesize>
    <mimetype>image/jpeg</mimetype>
  </file>
</files>
XML);

        $resources = ['course_illustration' => []];
        $ok = $this->invokePrivate('tryImportCourseIllustrationMeta', [$this->workDir, &$resources]);

        self::assertTrue($ok);
        self::assertSame('from_xml.jpg', $resources['course_illustration'][1]->filename);
        self::assertSame('image/jpeg', $resources['course_illustration'][1]->mimetype);
        self::assertSame($hash, $resources['course_illustration'][1]->contenthash);
    }

    /**
     * @param list<mixed> $args
     */
    private function invokePrivate(string $method, array $args): mixed
    {
        $import = new MoodleImport(false);
        $ref = new ReflectionClass($import);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($import, $args);
    }

    private function removeDir(string $dir): void
    {
        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
