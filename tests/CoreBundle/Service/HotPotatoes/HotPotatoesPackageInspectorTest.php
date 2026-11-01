<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\HotPotatoes;

use Chamilo\CoreBundle\Service\HotPotatoes\HotPotatoesPackageInspector;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class HotPotatoesPackageInspectorTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }
    }

    public function testItAcceptsAStandaloneHtmlExport(): void
    {
        $file = $this->temporaryFile('.html');
        file_put_contents($file, '<!doctype html><html><title>JQuiz</title></html>');

        $result = (new HotPotatoesPackageInspector())->inspect($file, 'quiz.html');

        self::assertSame('html', $result['format']);
        self::assertSame(['index.html'], $result['entrypoints']);
        self::assertSame(1, $result['files']);
    }

    public function testItRejectsUnsupportedFileTypes(): void
    {
        $file = $this->temporaryFile('.php');
        file_put_contents($file, '<?php echo 1;');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only HTML and ZIP');

        (new HotPotatoesPackageInspector())->inspect($file, 'quiz.php');
    }

    public function testItFindsActivitiesInAValidZip(): void
    {
        $zipFile = $this->createArchive([
            'activity/index.html' => '<html>Hot Potatoes</html>',
            'activity/images/picture.png' => 'fake-image',
            'activity/lib/helpers.js' => 'function load() {}',
            'second.htm' => '<html>JMatch</html>',
        ]);

        $result = (new HotPotatoesPackageInspector())->inspect($zipFile, 'activities.zip');

        self::assertSame('zip', $result['format']);
        self::assertSame(['activity/index.html', 'second.htm'], $result['entrypoints']);
        self::assertSame(4, $result['files']);
    }

    /**
     * @dataProvider unsafeEntries
     */
    public function testItRejectsUnsafeArchiveMembers(string $filename): void
    {
        $zipFile = $this->createArchive([
            'index.html' => '<html>Hot Potatoes</html>',
            $filename => 'bad',
        ]);

        $this->expectException(RuntimeException::class);

        (new HotPotatoesPackageInspector())->inspect($zipFile, 'activities.zip');
    }

    public static function unsafeEntries(): iterable
    {
        yield 'parent directory' => ['../escape.js'];

        yield 'absolute path' => ['/etc/passwd'];

        yield 'backslash' => ['foo\bar.js'];

        yield 'script file' => ['shell.php'];

        yield 'hidden configuration' => ['.htaccess'];

        yield 'encoded traversal' => ['%2e%2e/escape.js'];
    }

    public function testItRejectsDuplicateCaseInsensitivePaths(): void
    {
        $zipFile = $this->createArchive([
            'index.html' => '<html>JQuiz</html>',
            'INDEX.HTML' => '<html>JQuiz</html>',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duplicate');

        (new HotPotatoesPackageInspector())->inspect($zipFile, 'activities.zip');
    }

    public function testItRejectsArchivesWithoutHtml(): void
    {
        $zipFile = $this->createArchive(['main.js' => 'alert(1)']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not contain an HTML activity');

        (new HotPotatoesPackageInspector())->inspect($zipFile, 'activities.zip');
    }

    public function testItRejectsExtremeCompressionRatios(): void
    {
        $zipFile = $this->createArchive([
            'index.html' => '<html>JQuiz</html>',
            'long.js' => str_repeat('A', 200000),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('excessively compressed');

        (new HotPotatoesPackageInspector())->inspect($zipFile, 'activities.zip');
    }

    /**
     * @param array<string, string> $files
     */
    private function createArchive(array $files): string
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('PHP ZIP extension is not available.');
        }

        $path = $this->temporaryFile('.zip');
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $name => $contents) {
            self::assertTrue($zip->addFromString($name, $contents));
        }
        self::assertTrue($zip->close());

        return $path;
    }

    private function temporaryFile(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'chamilo-hotpotatoes-');
        self::assertIsString($path);
        $renamed = $path.$suffix;
        self::assertTrue(rename($path, $renamed));
        $this->temporaryFiles[] = $renamed;

        return $renamed;
    }
}
