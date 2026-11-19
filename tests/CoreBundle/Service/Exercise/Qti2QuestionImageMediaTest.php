<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Service\Exercise\Qti2MediaArchive;
use Chamilo\CoreBundle\Service\Exercise\Qti2QuestionImageMedia;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use ZipArchive;

final class Qti2QuestionImageMediaTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        $this->filename = (string) tempnam(sys_get_temp_dir(), 'qti17_');
    }

    protected function tearDown(): void
    {
        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testEmbeddedImageIsPackagedAndValidatesForImport(): void
    {
        $image = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/PK0AAAAASUVORK5CYII=', true);
        $html = '<p><img src="data:image/png;base64,'.base64_encode($image).'" alt="One pixel"></p>';
        $archive = new ZipArchive();
        self::assertTrue($archive->open($this->filename, ZipArchive::OVERWRITE | ZipArchive::CREATE));
        $exported = $this->service()->exportHtml($html, $archive, new Qti2MediaArchive(), new Course(), null);
        self::assertStringContainsString('src="qti_media/', $exported);
        self::assertStringNotContainsString('base64,', $exported);
        self::assertSame(1, $archive->numFiles);
        self::assertTrue($archive->close());

        $reader = new ZipArchive();
        self::assertTrue($reader->open($this->filename));
        $this->service()->validateImportedHtml($exported, $reader);
        self::assertTrue($reader->close());
    }

    public function testEscapedQtiChoiceImageIsValidated(): void
    {
        $image = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/PK0AAAAASUVORK5CYII=', true);
        $writer = new ZipArchive();
        self::assertTrue($writer->open($this->filename, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $path = (new Qti2MediaArchive())->addImage($writer, $image, 'sample.png');
        self::assertTrue($writer->close());

        $reader = new ZipArchive();
        self::assertTrue($reader->open($this->filename));
        $this->service()->validateImportedHtml('&lt;img src=&quot;'.$path.'&quot; alt=&quot;Sample&quot;&gt;', $reader);
        self::assertTrue($reader->close());
    }

    public function testEscapedRemoteImageIsRejected(): void
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($this->filename, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->service()->validateImportedHtml('&lt;img src=&quot;https://other.example/image.png&quot;&gt;', $archive);
        } finally {
            $archive->close();
        }
    }

    public function testTextOnlyOptionIsAllowedAlongsideImageChoices(): void
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($this->filename, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $this->service()->validateImportedHtml('A text option', $archive);
        self::assertTrue($archive->close());
    }

    public function testImportRejectsRemoteImage(): void
    {
        $archive = new ZipArchive();
        self::assertTrue($archive->open($this->filename, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->service()->validateImportedHtml('<img src="https://other.example/image.png">', $archive);
        } finally {
            $archive->close();
        }
    }

    public function testOnlyCurrentHostResourceViewLinksAreAllowed(): void
    {
        $uuid = '01234567-89ab-4cde-8f01-23456789abcd';
        self::assertSame($uuid, Qti2QuestionImageMedia::resourceUuidFromUrl('/r/course_tool/documents/'.$uuid.'/view', 'lms.example'));
        self::assertSame($uuid, Qti2QuestionImageMedia::resourceUuidFromUrl('https://lms.example/r/course_tool/documents/'.$uuid.'/view', 'lms.example'));
        self::assertNull(Qti2QuestionImageMedia::resourceUuidFromUrl('https://evil.example/r/course_tool/documents/'.$uuid.'/view', 'lms.example'));
        self::assertNull(Qti2QuestionImageMedia::resourceUuidFromUrl('file:///r/course_tool/documents/'.$uuid.'/view', 'lms.example'));
        self::assertNull(Qti2QuestionImageMedia::resourceUuidFromUrl('/outside/documents/'.$uuid.'/view', 'lms.example'));
        self::assertNull(Qti2QuestionImageMedia::resourceUuidFromUrl('https://a:b@lms.example/r/course_tool/documents/'.$uuid.'/view', 'lms.example'));
    }

    private function service(): Qti2QuestionImageMedia
    {
        return new Qti2QuestionImageMedia(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ResourceNodeRepository::class),
            new CQuizQuestionRepository($this->createMock(ManagerRegistry::class)),
            new RequestStack(),
        );
    }
}
