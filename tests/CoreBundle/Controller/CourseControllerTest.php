<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

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
        /*$this->assertJsonContains(
            [
                'course' => [
                    'code' => $course->getCode(),
                ],
            ]
        );*/

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

        $client->request(
            'GET',
            '/course/'.$course->getId().'/home.json'
        );
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

        $course->addStudent($test);
        $em->persist($course);
        $em->flush();

        $client->loginUser($test);
        $client->request(
            'GET',
            '/course/'.$course->getId().'/home.json'
        );
        $this->assertResponseIsSuccessful();
        /*$this->assertJsonContains(
            [
                'course' => [
                    'code' => $course->getCode(),
                ],
            ]
        );*/
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

        $course->addTeacher($teacher);
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
}
