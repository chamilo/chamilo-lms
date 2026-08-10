<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

use const JSON_THROW_ON_ERROR;

class CourseControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testCheckTermsAndConditionJson(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('course 1');

        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        // Test as admin.
        $client->request(
            'GET',
            '/course/'.$course->getId().'/checkLegal.json'
        );
        $this->assertResponseIsSuccessful();

        $this->assertResponseStatusCodeSame(200);
    }

    public function testIndexJson(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('course 1');

        $admin = $this->getUser('admin');
        $client->loginUser($admin);

        // Test as admin.
        $client->request(
            'GET',
            '/course/'.$course->getId().'/home.json'
        );
        $this->assertResponseIsSuccessful();

        // Test as registered user (course is open).
        $test = $this->createUser('test');
        $client->loginUser($test);

        $client->request(
            'GET',
            '/course/'.$course->getId().'/home.json'
        );
        $this->assertResponseIsSuccessful();
    }

    public function testIndexJsonWithAccessRegistered(): void
    {
        $client = static::createClient();

        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('course with acess for registered users');
        $course->setVisibility(Course::REGISTERED);
        $courseRepo->update($course);

        $userTest1 = $this->createUser('test1');
        $client->loginUser($userTest1);

        $client->request('GET', '/course/'.$course->getId().'/home.json');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexJsonInviteeAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('course 1');
        $test = $this->createUser('test');
        $test->addRole('ROLE_INVITEE');

        $em = $this->getEntityManager();
        $em->persist($test);
        $em->flush();

        $test = $this->getUser('test');
        $course = $this->getCourse($course->getId());

        $course->addUserAsStudent($test);
        $em->persist($course);
        $em->flush();

        $client->loginUser($test);
        $client->request(
            'GET',
            '/course/'.$course->getId().'/home.json'
        );
        $this->assertResponseIsSuccessful();
    }

    public function testReorderToolsIsDeniedForStudent(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('course to reorder');

        $student = $this->createUser('student');
        $course->addUserAsStudent($student);

        $em = $this->getEntityManager();
        $em->persist($course);
        $em->flush();

        $client->loginUser($student);
        $this->postToolOrder($client, $course, $this->getFirstToolId($course), 0);

        // A student can read the course home, so the GET flow would let them
        // through: reordering must be gated on its own, not on VIEW.
        $this->assertResponseStatusCodeSame(403);

        // ...and denied by the voter, not by the central CSRF gate, which also
        // answers 403 and would keep this test green if the check were removed.
        $this->assertStringNotContainsString(
            'The security token is invalid.',
            (string) $client->getResponse()->getContent()
        );
    }

    public function testReorderToolsIsAllowedForTeacher(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('course to reorder by teacher');

        $teacher = $this->createUser('teacher');
        $course->addUserAsTeacher($teacher);

        $em = $this->getEntityManager();
        $em->persist($course);
        $em->flush();

        $client->loginUser($teacher);
        $this->postToolOrder($client, $course, $this->getFirstToolId($course), 0);

        $this->assertResponseIsSuccessful();
    }

    public function testRedirectTool(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new');
        $admin = $this->getUser('admin');

        $client->loginUser($admin);
        $client->request(
            'GET',
            '/course/'.$course->getId().'/tool/document'
        );

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseHasHeader('location');
        $this->assertResponseRedirects(
            '/resources/document/'.$course->getResourceNode()->getId().'/?cid='.$course->getId().'&sid=0&gid=0'
        );
    }

    public function testUpdateSettings(): void
    {
        $client = static::createClient();

        $course = $this->createCourse('new');
        $admin = $this->getUser('admin');

        $client->request(
            'GET',
            '/course/'.$course->getId().'/settings/announcement'
        );
        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());

        $client->loginUser($admin);
        $client->request(
            'GET',
            '/course/'.$course->getId().'/settings/announcement'
        );
        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString(
            'Allow user edit announcement',
            $client->getResponse()->getContent()
        );

        $client->submitForm('Save settings', [
            'form[enabled]' => '0',
        ]);
    }

    public function testListSettings(): void
    {
        $client = static::createClient();

        $course = $this->createCourse('new');
        $admin = $this->getUser('admin');

        $courseSettingsManager = $this->getContainer()->get(SettingsCourseManager::class);

        $client->loginUser($admin);
        $schemas = $courseSettingsManager->getSchemas();

        foreach ($schemas as $name => $schema) {
            $category = $courseSettingsManager->convertServiceToNameSpace($name);
            $client->request('GET', '/course/'.$course->getId().'/settings/'.$category);
            $this->assertResponseIsSuccessful();
        }
    }

    public function testWelcomeAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new course');
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/course/'.$course->getId().'/welcome');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('new course', $client->getResponse()->getContent());
    }

    public function testAboutAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new course');
        $admin = $this->getUser('admin');
        $teacher = $this->createUser('teacher');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $course->addUserAsTeacher($teacher);
        $em = $this->getEntityManager();

        $types = CCourseDescription::getTypes();
        foreach ($types as $type) {
            $item = (new CCourseDescription())
                ->setTitle('title')
                ->setContent('content')
                ->setDescriptionType($type)
                ->setProgress(100)
                ->setParent($course)
                ->setCreator($teacher)
                ->addCourseLink($course)
            ;
            $em->persist($item);
        }
        $em->persist($course);
        $em->flush();

        $client->request('GET', '/course/'.$course->getId().'/about');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('new course', $client->getResponse()->getContent());
    }

    private function getFirstToolId(Course $course): int
    {
        $tool = $course->getTools()->first();

        $this->assertNotFalse($tool, 'The course was created without tools.');

        return (int) $tool->getIid();
    }

    /**
     * Sec-Fetch-Site is what a browser sends on a same-origin write, and what
     * CsrfProtectionListener checks. Without it the central gate answers 403
     * before the controller runs, which would both break the teacher case and
     * make the student case pass for the wrong reason.
     */
    private function postToolOrder(KernelBrowser $client, Course $course, int $toolId, int $index): void
    {
        $client->request(
            'POST',
            '/course/'.$course->getId().'/home.json',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
            ],
            json_encode(['toolId' => $toolId, 'index' => $index], JSON_THROW_ON_ERROR)
        );
    }
}
