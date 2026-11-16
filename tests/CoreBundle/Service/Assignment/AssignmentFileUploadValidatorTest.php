<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Service\Assignment\AssignmentFileUploadValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use ZipArchive;

final class AssignmentFileUploadValidatorTest extends TestCase
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

        parent::tearDown();
    }

    public function testRejectsZeroByteFile(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('The selected file is empty.');

        (new AssignmentFileUploadValidator())->validate($this->upload('', 'submission.pdf'));
    }

    public function testRejectsTruncatedZip(): void
    {
        $this->requireZip();
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('The selected ZIP file is invalid.');

        (new AssignmentFileUploadValidator())->validate($this->upload("PK\x03\x04", 'submission.zip'));
    }

    public function testRejectsValidZipWithoutEntries(): void
    {
        $this->requireZip();
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('The selected ZIP file contains no non-empty files.');

        // Empty ZIP end-of-central-directory record (22 bytes, zero entries).
        (new AssignmentFileUploadValidator())->validate($this->upload("PK\x05\x06".str_repeat("\0", 18), 'submission.zip'));
    }

    public function testRejectsZipContainingOnlyDirectories(): void
    {
        $this->requireZip();
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('The selected ZIP file contains no non-empty files.');

        (new AssignmentFileUploadValidator())->validate($this->zipWithEntries(['folder/' => null]));
    }

    public function testRejectsZipContainingOnlyEmptyFiles(): void
    {
        $this->requireZip();
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('The selected ZIP file contains no non-empty files.');

        (new AssignmentFileUploadValidator())->validate($this->zipWithEntries(['empty.txt' => '']));
    }

    public function testAcceptsZipContainingARealFile(): void
    {
        $this->requireZip();

        $upload = $this->zipWithEntries([
            'folder/' => null,
            'folder/empty.txt' => '',
            'folder/answer.txt' => 'Completed assignment',
        ]);

        (new AssignmentFileUploadValidator())->validate($upload);

        self::assertGreaterThan(0, $upload->getSize());
    }

    public function testAcceptsNonEmptyRegularFile(): void
    {
        $upload = $this->upload('PDF content', 'submission.pdf');

        (new AssignmentFileUploadValidator())->validate($upload);

        self::assertSame(11, $upload->getSize());
    }

    private function requireZip(): void
    {
        if (!class_exists(ZipArchive::class)) {
            self::markTestSkipped('The PHP ZIP extension is required for ZIP validation tests.');
        }
    }

    private function upload(string $contents, string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'assignment_upload_');
        self::assertNotFalse($path);
        $this->temporaryFiles[] = $path;
        self::assertSame(\strlen($contents), file_put_contents($path, $contents));

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @param array<string, ?string> $entries
     */
    private function zipWithEntries(array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'assignment_zip_');
        self::assertNotFalse($path);
        $this->temporaryFiles[] = $path;
        $zip = new ZipArchive();
        self::assertTrue(true === $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $content) {
            if (null === $content) {
                self::assertTrue($zip->addEmptyDir($name));
            } else {
                self::assertTrue($zip->addFromString($name, $content));
            }
        }

        self::assertTrue($zip->close());

        return new UploadedFile($path, 'submission.zip', 'application/zip', null, true);
    }
}
