<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Component\CourseCopy\Course as BackupCourse;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

use const JSON_THROW_ON_ERROR;

/**
 * Importing a backup copies another course's content into the destination course, so a backup
 * picked from the shared server directory may only be used by someone allowed to view the
 * course it was made from, exactly as the course copy flow requires for its source course.
 * A backup the user uploaded is their own content and needs no such check.
 */
class CourseMaintenanceImportSourceAccessTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const SECRET_TITLE = 'source-course-secret.txt';

    /**
     * @var string[]
     */
    private array $backupFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->backupFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function testServerBackupOfAnotherCourseCannotBePicked(): void
    {
        [$client, $teacher, $destination, $backupId] = $this->setUpForeignBackup();

        $client->loginUser($teacher);
        $this->request($client, 'POST', $destination, '/import/server', ['filename' => $backupId]);

        // Same answer as for a file that does not exist, so the picker cannot be used to probe for backups.
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testServerBackupOfAnotherCourseDoesNotListItsResources(): void
    {
        [$client, $teacher, $destination, $backupId] = $this->setUpForeignBackup();

        $client->loginUser($teacher);
        $this->request($client, 'GET', $destination, '/import/'.$backupId.'/resources');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString(self::SECRET_TITLE, (string) $client->getResponse()->getContent());
    }

    public function testServerBackupOfAnotherCourseCannotBeRestored(): void
    {
        [$client, $teacher, $destination, $backupId] = $this->setUpForeignBackup();

        $client->loginUser($teacher);
        $this->request($client, 'POST', $destination, '/import/'.$backupId.'/restore', [
            'importOption' => 'full_backup',
            'sameFileNameOption' => 2,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testServerBackupIsUsableByATeacherOfItsSourceCourse(): void
    {
        [$client, $teacher, $destination, $backupId, $source] = $this->setUpForeignBackup();

        $source->addUserAsTeacher($teacher);
        self::getContainer()->get(CourseRepository::class)->update($source);

        $client->loginUser($teacher);
        $this->request($client, 'POST', $destination, '/import/server', ['filename' => $backupId]);
        $this->assertResponseIsSuccessful();

        $this->request($client, 'GET', $destination, '/import/'.$backupId.'/resources');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(self::SECRET_TITLE, (string) $client->getResponse()->getContent());
    }

    public function testServerBackupIsUsableByAnAdmin(): void
    {
        [$client, , $destination, $backupId] = $this->setUpForeignBackup();

        $client->loginUser($this->getAdmin());
        $this->request($client, 'GET', $destination, '/import/'.$backupId.'/resources');

        $this->assertResponseIsSuccessful();
    }

    public function testServerBackupOfADeletedCourseIsOnlyUsableByAnAdmin(): void
    {
        [$client, $teacher, $destination] = $this->setUpForeignBackup();
        $backupId = $this->writeBackup('NOLONGEREXISTS');

        $client->loginUser($teacher);
        $this->request($client, 'GET', $destination, '/import/'.$backupId.'/resources');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringNotContainsString(self::SECRET_TITLE, (string) $client->getResponse()->getContent());

        $client->loginUser($this->getAdmin());
        $this->request($client, 'GET', $destination, '/import/'.$backupId.'/resources');
        $this->assertResponseIsSuccessful();
    }

    public function testUploadedBackupIsUsableByItsUploader(): void
    {
        [$client, $teacher, $destination, $backupId] = $this->setUpForeignBackup();

        $colleague = $this->createUser('cm_import_colleague');
        $destination->addUserAsTeacher($colleague);
        self::getContainer()->get(CourseRepository::class)->update($destination);

        $upload = tempnam(sys_get_temp_dir(), 'cm_upload_');
        copy(CourseArchiver::getBackupDir().$backupId, $upload);

        $client->loginUser($teacher);
        $client->request(
            'POST',
            $this->url($destination, '/import/upload'),
            [],
            ['file' => new UploadedFile($upload, 'my-backup.zip', 'application/zip', null, true)],
            ['HTTP_SEC_FETCH_SITE' => 'same-origin']
        );
        $this->assertResponseIsSuccessful();

        $uploadedId = (string) json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['backupId'];
        $this->backupFiles[] = CourseArchiver::getBackupDir().$uploadedId;

        $this->request($client, 'GET', $destination, '/import/'.$uploadedId.'/resources');
        $this->assertResponseIsSuccessful();

        // Another teacher of the same course did not upload it, so it is a foreign backup for them.
        $client->loginUser($colleague);
        $this->request($client, 'GET', $destination, '/import/'.$uploadedId.'/resources');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * A teacher of their own course and a closed course they have no role in, plus a backup
     * of that closed course sitting in the shared backup directory.
     *
     * @return array{0: KernelBrowser, 1: User, 2: Course, 3: string, 4: Course}
     */
    private function setUpForeignBackup(): array
    {
        $client = static::createClient();
        Container::setContainer(self::getContainer());
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $teacher = $this->createUser('cm_import_teacher');

        $destination = $this->createCourse('CM import destination');
        $destination->addUserAsTeacher($teacher);
        $courseRepo->update($destination);

        $source = $this->createCourse('CM import source');
        $source->setVisibility(Course::CLOSED);
        $courseRepo->update($source);

        return [$client, $teacher, $destination, $this->writeBackup($source->getCode()), $source];
    }

    private function writeBackup(string $courseCode): string
    {
        $course = new BackupCourse();
        $course->code = $courseCode;
        $course->resources[RESOURCE_DOCUMENT][26] = new Document(26, '/'.self::SECRET_TITLE, '', self::SECRET_TITLE, 'file', 10);

        CourseArchiver::createBackupDir();
        $backupId = '1_'.$courseCode.'_'.date('Ymd-His').'_'.bin2hex(random_bytes(3)).'.zip';
        $path = CourseArchiver::getBackupDir().$backupId;

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('course_info.dat', base64_encode(serialize($course)));
        $zip->addFromString('document/'.self::SECRET_TITLE, 'secret');
        $zip->close();

        $this->backupFiles[] = $path;

        return $backupId;
    }

    private function request(KernelBrowser $client, string $method, Course $destination, string $path, array $payload = []): void
    {
        $client->request(
            $method,
            $this->url($destination, $path),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_SEC_FETCH_SITE' => 'same-origin'],
            'GET' === $method ? null : json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    private function url(Course $destination, string $path): string
    {
        return '/course_maintenance/'.$destination->getResourceNode()->getId().$path.'?cid='.$destination->getId().'&sid=0&gid=0';
    }
}
