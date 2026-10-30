<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Service\Gradebook\CertificateZipImporter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ZipArchive;

final class CertificateZipImporterArchiveTest extends TestCase
{
    public function testAcceptsNestedHtmlAndRealImage(): void
    {
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j9xkAAAAASUVORK5CYII=', true);
        self::assertIsString($image);

        $result = $this->readEntries([
            'certificates/index.html' => '<html><img src="logo.png"></html>',
            'certificates/logo.png' => $image,
        ]);

        self::assertArrayHasKey('certificates/index.html', $result);
        self::assertArrayHasKey('certificates/logo.png', $result);
    }

    /**
     * @dataProvider unsafeNames
     */
    public function testRejectsUnsafePaths(string $name): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->readEntries([$name => '<html>test</html>']);
    }

    public static function unsafeNames(): iterable
    {
        yield 'parent traversal' => ['../index.html'];

        yield 'nested traversal' => ['a/../index.html'];

        yield 'absolute path' => ['/etc/index.html'];

        yield 'drive path' => ['C:/index.html'];
    }

    public function testRejectsSymbolicLinks(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->readEntries(['shortcut.html' => '../private.html'], ['shortcut.html']);
    }

    public function testRejectsInvalidImageBytes(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->readEntries(['index.html' => '<html></html>', 'logo.png' => 'not-an-image']);
    }

    public function testRejectsDisallowedFileExtensions(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->readEntries(['index.html' => '<html></html>', 'run.php' => '<?php echo 1;']);
    }

    public function testRejectsOversizedHtml(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->readEntries(['index.html' => str_repeat('x', 2000001)]);
    }

    public function testRejectsTooManyEntries(): void
    {
        $entries = [];
        for ($i = 0; $i <= 100; $i++) {
            $entries['file-'.$i.'.html'] = '<html></html>';
        }

        $this->expectException(BadRequestHttpException::class);
        $this->readEntries($entries);
    }

    /**
     * @param array<string, string> $files
     * @param list<string>          $symbolicLinks
     *
     * @return array<string, array{extension: string, content: string}>
     */
    private function readEntries(array $files, array $symbolicLinks = []): array
    {
        $path = tempnam(sys_get_temp_dir(), 'certificate-zip-test-');
        if (false === $path) {
            throw new RuntimeException('Unable to allocate temporary ZIP file.');
        }

        try {
            $zip = new ZipArchive();
            if (true !== $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                throw new RuntimeException('Unable to create test ZIP.');
            }

            try {
                foreach ($files as $name => $content) {
                    if (false === $zip->addFromString($name, $content)) {
                        throw new RuntimeException('Unable to add test ZIP entry.');
                    }
                }

                foreach ($symbolicLinks as $name) {
                    if (!$zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, 0120777 << 16)) {
                        throw new RuntimeException('Unable to set symbolic-link attributes.');
                    }
                }
            } finally {
                $zip->close();
            }

            if (true !== $zip->open($path)) {
                throw new RuntimeException('Unable to reopen test ZIP.');
            }

            try {
                $importer = (new ReflectionClass(CertificateZipImporter::class))->newInstanceWithoutConstructor();

                return (new ReflectionMethod(CertificateZipImporter::class, 'readArchive'))->invoke($importer, $zip);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($path);
        }
    }
}
