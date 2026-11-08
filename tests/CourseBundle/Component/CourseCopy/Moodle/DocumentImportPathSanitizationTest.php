<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Component\CourseCopy\Moodle;

use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use const JSON_THROW_ON_ERROR;

/**
 * The imported document path becomes a directory on the server's disk, so it must not contain
 * a character Windows refuses: the bundled demo courses ("Module 1: Finding Your Way Around
 * Chamilo 3.0", "What's next?.html") made the 3.0 upgrade migration fail on Windows servers.
 */
final class DocumentImportPathSanitizationTest extends TestCase
{
    private string $workDir = '';

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir().'/chamilo_document_import_'.bin2hex(random_bytes(4));
        mkdir($this->workDir.'/chamilo/document', 0775, true);
        mkdir($this->workDir.'/files/ab', 0775, true);
    }

    protected function tearDown(): void
    {
        if ('' !== $this->workDir && is_dir($this->workDir)) {
            $this->removeDir($this->workDir);
        }
    }

    public function testReplacesCharactersWindowsRefusesInEverySegment(): void
    {
        self::assertSame(
            'Learning paths/Module 1- Finding Your Way Around Chamilo 3.0/What\'s next-.html',
            $this->normalize('document/Learning paths/Module 1: Finding Your Way Around Chamilo 3.0/What\'s next?.html')
        );
        self::assertSame('a-b-c-d-e-f-.txt', $this->normalize('a<b>c"d|e*f?.txt'));
    }

    public function testDropsTheTrailingDotsAndSpacesWindowsWouldStrip(): void
    {
        self::assertSame('Folder/notes', $this->normalize('Folder. /notes...'));
    }

    public function testStillCleansTechnicalFolderSuffixes(): void
    {
        self::assertSame('certificates/Module 1- Intro', $this->normalize('certificates-23443/Module 1: Intro-12345'));
    }

    public function testLeavesPortablePathsUnchanged(): void
    {
        self::assertSame('Learning paths/Getting Started with Chamilo 3.0/intro.html', $this->normalize('document/Learning paths/Getting Started with Chamilo 3.0/intro.html'));
    }

    public function testImportsTheDocumentUnderAPortablePathAndKeepsItsTitle(): void
    {
        $hash = 'ab'.str_repeat('0', 38);
        file_put_contents($this->workDir.'/files/ab/'.$hash, '<p>Module 1</p>');
        file_put_contents($this->workDir.'/chamilo/document/index.json', json_encode([
            'documents' => [
                [
                    'path' => 'document/Learning paths/Module 1: Finding Your Way Around/What\'s next?.html',
                    'file_type' => 'file',
                    'title' => 'What\'s next?',
                    'contenthash' => $hash,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $import = new MoodleImport();
        $method = (new ReflectionClass($import))->getMethod('tryImportDocumentMeta');
        $resources = [];

        self::assertTrue($method->invokeArgs($import, [$this->workDir, &$resources]));

        $legacyPath = '/document/Learning paths/Module 1- Finding Your Way Around/What\'s next-.html';
        self::assertFileExists($this->workDir.$legacyPath);

        $titles = [];
        foreach ($resources['document'] as $document) {
            $titles[(string) $document->path] = (string) $document->title;
        }
        self::assertSame('What\'s next?', $titles[$legacyPath] ?? null);
    }

    private function normalize(string $path): string
    {
        $import = new MoodleImport();
        $method = (new ReflectionClass($import))->getMethod('normalizeImportedDocumentRelativePath');

        return (string) $method->invoke($import, $path);
    }

    private function removeDir(string $dir): void
    {
        foreach ((array) scandir($dir) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir.'/'.$entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
