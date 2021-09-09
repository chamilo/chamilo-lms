<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CourseControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testHomeRedirectAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new');
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/course/'.$course->getCode().'/index.php');
        $this->assertResponseRedirects('/course/'.$course->getId().'/home');
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
