<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('new course', $client->getResponse()->getContent());
    }

    public function testAboutAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new course');
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/course/'.$course->getId().'/about');

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('new course', $client->getResponse()->getContent());
    }
}
