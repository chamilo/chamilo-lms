<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseHomeControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    public function testIndexJsonAction(): void
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

        // Course is REGISTERED.
        $course = $this->getCourse($course->getId());

        $em = $this->getEntityManager();
        $course->setVisibility(Course::REGISTERED);
        $em->persist($course);
        $em->flush();

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
}
