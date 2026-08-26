<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

use const JSON_THROW_ON_ERROR;

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

    public function testImportCourseSettingsMetaKeepsSafeRowsAndDropsMalformedOnes(): void
    {
        file_put_contents($this->workDir.'/chamilo/course/settings.json', json_encode([
            'settings' => [
                [
                    'variable' => 'show_course_in_user_language',
                    'value' => '1',
                    'category' => '',
                    'title' => 'show_course_in_user_language',
                ],
                [
                    'variable' => 'documents_default_visibility',
                    'value' => '2',
                    'category' => 'document',
                    'title' => 'documents_default_visibility',
                ],
                ['variable' => '../unsafe', 'value' => '1'],
                ['variable' => 'valid_but_bad_value', 'value' => ['not', 'scalar']],
            ],
        ], JSON_THROW_ON_ERROR));

        $resources = ['course_settings' => []];
        $ok = $this->invokePrivate('tryImportCourseSettingsMeta', [$this->workDir, &$resources]);

        self::assertTrue($ok);
        self::assertCount(2, $resources['course_settings']);

        /** @var list<stdClass> $rows */
        $rows = array_values($resources['course_settings']);
        self::assertSame('show_course_in_user_language', $rows[0]->variable);
        self::assertSame('1', $rows[0]->value);
        self::assertSame('documents_default_visibility', $rows[1]->variable);
        self::assertSame('document', $rows[1]->category);
    }

    public function testImportDocumentMetaPreservesVisibility(): void
    {
        $hash = str_repeat('ef', 20);
        mkdir($this->workDir.'/files/ef', 0775, true);
        mkdir($this->workDir.'/chamilo/document', 0775, true);
        file_put_contents($this->workDir.'/files/ef/'.$hash, 'secret-bytes');
        file_put_contents($this->workDir.'/chamilo/document/index.json', json_encode([
            'documents' => [
                [
                    'file_type' => 'folder',
                    'path' => 'Secret',
                    'title' => 'Secret',
                    'visibility' => 0,
                ],
                [
                    'file_type' => 'file',
                    'path' => 'Secret/private.txt',
                    'title' => 'private.txt',
                    'contenthash' => $hash,
                    'visibility' => 0,
                    'size' => 12,
                ],
                [
                    'file_type' => 'file',
                    'path' => 'public.txt',
                    'title' => 'public.txt',
                    'contenthash' => $hash,
                    'visibility' => 2,
                    'size' => 12,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $resources = ['document' => []];
        $ok = $this->invokePrivate('tryImportDocumentMeta', [$this->workDir, &$resources]);

        self::assertTrue($ok);

        $byPath = [];
        foreach ($resources['document'] as $item) {
            $path = (string) ($item->path ?? '');
            $byPath[$path] = $item;
        }

        self::assertArrayHasKey('/document/Secret', $byPath);
        self::assertSame(0, (int) $byPath['/document/Secret']->visibility);
        self::assertArrayHasKey('/document/Secret/private.txt', $byPath);
        self::assertSame(0, (int) $byPath['/document/Secret/private.txt']->visibility);
        self::assertArrayHasKey('/document/public.txt', $byPath);
        self::assertSame(2, (int) $byPath['/document/public.txt']->visibility);
    }

    public function testImportDocumentMetaResolvesFileFolderPathCollisionFromFilesXml(): void
    {
        $hash = str_repeat('aa', 20);
        $title = 'Module 1: Finding Your Way Around Chamilo 3.0';
        $filename = $title.'.html';

        mkdir($this->workDir.'/chamilo/document', 0775, true);
        file_put_contents($this->workDir.'/files/aa/'.$hash, '<html><body>Module 1</body></html>');
        file_put_contents($this->workDir.'/files.xml', <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<files>
  <file id="1900000122">
    <contenthash>{$hash}</contenthash>
    <component>mod_resource</component>
    <filearea>content</filearea>
    <filepath>/</filepath>
    <filename>{$filename}</filename>
    <filesize>34</filesize>
    <mimetype>text/html</mimetype>
  </file>
</files>
XML);
        file_put_contents($this->workDir.'/chamilo/document/index.json', json_encode([
            'documents' => [
                [
                    'id' => 449,
                    'source_id' => 449,
                    'file_type' => 'folder',
                    'path' => $title.'/Learning paths',
                    'title' => 'Learning paths',
                    'visibility' => 0,
                ],
                [
                    'id' => 322,
                    'source_id' => 322,
                    'file_type' => 'file',
                    'path' => $title,
                    'title' => $title,
                    'contenthash' => $hash,
                    'visibility' => 0,
                    'size' => 34,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $resources = ['document' => []];
        $ok = $this->invokePrivate('tryImportDocumentMeta', [$this->workDir, &$resources]);

        self::assertTrue($ok);

        $bySourceId = [];
        foreach ($resources['document'] as $item) {
            $sourceId = (int) ($item->source_id ?? 0);
            if ($sourceId > 0) {
                $bySourceId[$sourceId] = $item;
            }
        }

        self::assertArrayHasKey(322, $bySourceId);
        self::assertSame('/document/'.$filename, (string) $bySourceId[322]->path);
        self::assertSame('file', (string) $bySourceId[322]->file_type);
        self::assertFileExists($this->workDir.'/document/'.$filename);
        self::assertDirectoryExists($this->workDir.'/document/'.$title.'/Learning paths');
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
