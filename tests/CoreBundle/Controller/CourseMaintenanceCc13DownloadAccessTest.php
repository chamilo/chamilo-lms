<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

use const DIRECTORY_SEPARATOR;

/**
 * Common Cartridge exports of every course are written to the same shared temporary directory,
 * so the download must only serve the packages of the course the request is made in. Otherwise
 * a teacher of any course could read another course's content by naming its export file.
 */
class CourseMaintenanceCc13DownloadAccessTest extends WebTestCase
{
    use ChamiloTestTrait;

    private const CONTENT = 'cc13-package-content';

    /**
     * @var string[]
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function testExportOfAnotherCourseCannotBeDownloaded(): void
    {
        [$client, $teacher, $own, $foreign] = $this->setUpCourses();
        $file = $this->writeExport($foreign->getCode());

        $client->loginUser($teacher);
        $client->request('GET', $this->url($own, $file));

        // Same answer as for a file that does not exist, so the endpoint cannot be used to probe for exports.
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringNotContainsString(self::CONTENT, $this->content($client));
    }

    public function testExportOfAnotherCourseCannotBeDownloadedByAnAdminFromTheWrongCourse(): void
    {
        [$client, , $own, $foreign] = $this->setUpCourses();
        $file = $this->writeExport($foreign->getCode());

        $client->loginUser($this->getAdmin());
        $client->request('GET', $this->url($own, $file));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testExportOfTheCurrentCourseIsDownloadedByItsTeacher(): void
    {
        [$client, $teacher, $own] = $this->setUpCourses();
        $file = $this->writeExport($own->getCode());

        $client->loginUser($teacher);
        $client->request('GET', $this->url($own, $file));

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::CONTENT, $this->content($client));
    }

    /**
     * @return array{0: KernelBrowser, 1: User, 2: Course, 3: Course}
     */
    private function setUpCourses(): array
    {
        $client = static::createClient();
        Container::setContainer(self::getContainer());
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $teacher = $this->createUser('cm_cc13_teacher');

        $own = $this->createCourse('CM cc13 own');
        $own->addUserAsTeacher($teacher);
        $courseRepo->update($own);

        $foreign = $this->createCourse('CM cc13 foreign');
        $foreign->setVisibility(Course::CLOSED);
        $courseRepo->update($foreign);

        return [$client, $teacher, $own, $foreign];
    }

    private function writeExport(string $courseCode): string
    {
        $file = $courseCode.'_cc13_'.date('Ymd_His').'.imscc';
        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
        file_put_contents($path, self::CONTENT);
        $this->files[] = $path;

        return $file;
    }

    private function url(Course $course, string $file): string
    {
        return '/course_maintenance/'.$course->getResourceNode()->getId().'/cc13/export/download?file='
            .rawurlencode($file).'&cid='.$course->getId().'&sid=0&gid=0';
    }

    private function content(KernelBrowser $client): string
    {
        return $client->getInternalResponse()->getContent();
    }
}
