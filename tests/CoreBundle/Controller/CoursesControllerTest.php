<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CoursesControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testHomeRedirectAction(): void
    {
        $client = static::createClient();
        $course = $this->createCourse('new');
        $admin = $this->getUser('admin');

        // simulate $testUser being logged in
        $client->loginUser($admin);

        $client->request('GET', '/courses/'.$course->getCode().'/index.php');
        $this->assertResponseRedirects('/courses/'.$course->getId().'/home');
    }
}
