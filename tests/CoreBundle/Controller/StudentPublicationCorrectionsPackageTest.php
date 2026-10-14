<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Regression tests for the corrections package upload advisory.
 *
 * The ZIP is extracted to disk before it is read: the extraction directory must not outlive the
 * request, and an archive with too many entries must be refused before anything is written.
 */
final class StudentPublicationCorrectionsPackageTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const int CORRECTIONS_MAX_ENTRIES = 5000;

    /**
     * @return array{teacher: User, assignment: CStudentPublication}
     */
    private function bootstrapAssignment(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Corrections Zip Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $teacher = $this->createUser('corrections_zip_teacher_'.$suffix);
        $course->addUserAsTeacher($teacher);
        $courseRepo->update($course);

        $assignment = (new CStudentPublication())
            ->setTitle('Corrections Zip Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->flush();

        return ['teacher' => $teacher, 'assignment' => $assignment];
    }

    private function buildZip(int $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'corrections_test_');
        $zip = new ZipArchive();
        $this->assertTrue(true === $zip->open($path, ZipArchive::OVERWRITE));
        for ($index = 0; $index < $entries; $index++) {
            $zip->addFromString('unmatched_'.$index.'.txt', 'x');
        }
        $zip->close();

        return $path;
    }

    private function upload(User $teacher, CStudentPublication $assignment, string $zipPath): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/assignments/'.$assignment->getIid().'/upload-corrections-package',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['files' => ['file' => new UploadedFile($zipPath, 'corrections.zip', 'application/zip', null, true)]],
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    private function extractionDirectories(): array
    {
        return glob(api_get_path(SYS_ARCHIVE_PATH).'corrections_*') ?: [];
    }

    public function testExtractionDirectoryIsRemovedAfterTheUpload(): void
    {
        $ctx = $this->bootstrapAssignment('cleanup');
        $zipPath = $this->buildZip(2);
        $before = $this->extractionDirectories();

        $this->upload($ctx['teacher'], $ctx['assignment'], $zipPath);
        @unlink($zipPath);

        $this->assertResponseIsSuccessful();
        $this->assertSame($before, $this->extractionDirectories());
    }

    public function testArchiveWithTooManyEntriesIsRefusedBeforeExtraction(): void
    {
        $ctx = $this->bootstrapAssignment('too_many');
        $zipPath = $this->buildZip(self::CORRECTIONS_MAX_ENTRIES + 1);
        $before = $this->extractionDirectories();

        $this->upload($ctx['teacher'], $ctx['assignment'], $zipPath);
        @unlink($zipPath);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame($before, $this->extractionDirectories());
    }
}
