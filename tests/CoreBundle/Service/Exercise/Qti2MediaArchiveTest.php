<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\Qti2MediaArchive;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class Qti2MediaArchiveTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        $this->filename = (string) tempnam(sys_get_temp_dir(), 'qti_media_test_');
    }

    protected function tearDown(): void
    {
        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testImageIsEmbeddedAndRestoredWithoutNetworkAccess(): void
    {
        $image = self::validPng();
        $writer = $this->createArchive();
        $media = new Qti2MediaArchive();
        $html = $media->exportHtml(
            '<p><img src="/r/authorized" alt="Diagram" srcset="https://invalid.example/resource.png" data-zoom-image="/r/old"></p>',
            $writer,
            static function (string $src) use ($image): array {
                self::assertSame('/r/authorized', $src);

                return ['content' => $image, 'filename' => 'diagram.png'];
            }
        );
        self::assertStringContainsString('src="qti_media/', $html);
        self::assertStringNotContainsString('srcset=', $html);
        self::assertStringNotContainsString('data-zoom-image=', $html);
        self::assertSame(1, $writer->numFiles);
        self::assertTrue($writer->close());

        $reader = $this->readArchive();
        $restored = (new Qti2MediaArchive())->importHtml(
            $html,
            $reader,
            static function (string $bytes, string $name, string $mime) use ($image): string {
                self::assertSame($image, $bytes);
                self::assertStringEndsWith('.png', $name);
                self::assertSame('image/png', $mime);

                return '/r/imported';
            }
        );
        self::assertStringContainsString('src="/r/imported"', $restored);
        self::assertTrue($reader->close());
    }

    public function testDuplicateImageIsStoredOnce(): void
    {
        $writer = $this->createArchive();
        $archive = new Qti2MediaArchive();
        $first = $archive->addImage($writer, self::validPng(), 'first.png');
        $second = $archive->addImage($writer, self::validPng(), 'second.png');
        self::assertSame($first, $second);
        self::assertSame(1, $writer->numFiles);
        self::assertTrue($writer->close());
    }

    public function testRejectsUnresolvedImage(): void
    {
        $writer = $this->createArchive();
        $this->expectException(InvalidArgumentException::class);

        try {
            (new Qti2MediaArchive())->exportHtml('<img src="https://foreign.example/photo.png">', $writer, static fn (): ?array => null);
        } finally {
            $writer->close();
        }
    }

    public function testRejectsInvalidImageData(): void
    {
        $writer = $this->createArchive();
        $this->expectException(InvalidArgumentException::class);

        try {
            (new Qti2MediaArchive())->addImage($writer, '<script>alert(1)</script>', 'fake.png');
        } finally {
            $writer->close();
        }
    }

    public function testRejectsOversizedImage(): void
    {
        $writer = $this->createArchive();
        $this->expectException(InvalidArgumentException::class);

        try {
            (new Qti2MediaArchive())->addImage($writer, str_repeat('x', 5 * 1024 * 1024 + 1), 'oversized.png');
        } finally {
            $writer->close();
        }
    }

    public function testRejectsTraversingImagePath(): void
    {
        $writer = $this->createArchive();
        $writer->addFromString('qti_media/../outside.png', self::validPng());
        self::assertTrue($writer->close());
        $reader = $this->readArchive();
        $this->expectException(InvalidArgumentException::class);

        try {
            Qti2MediaArchive::readImage($reader, 'qti_media/../outside.png');
        } finally {
            $reader->close();
        }
    }

    public function testRejectsSpoofedHash(): void
    {
        $writer = $this->createArchive();
        $path = 'qti_media/'.str_repeat('a', 64).'.png';
        $writer->addFromString($path, self::validPng());
        self::assertTrue($writer->close());
        $reader = $this->readArchive();
        $this->expectException(InvalidArgumentException::class);

        try {
            Qti2MediaArchive::readImage($reader, $path);
        } finally {
            $reader->close();
        }
    }

    private function createArchive(): ZipArchive
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($this->filename, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        return $archive;
    }

    private function readArchive(): ZipArchive
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($this->filename));

        return $archive;
    }

    private static function validPng(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/PK0AAAAASUVORK5CYII=', true);
    }
}
