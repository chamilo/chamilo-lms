<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class SessionControllerTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAbout(): void
    {
        $session = $this->createSession('session 1');
        $course = $this->createCourse('course title');
        $admin = $this->getUser('admin');
        $em = $this->getEntityManager();

        $courseCoach = $this->createUser('course_coach', '', '', 'ROLE_TEACHER');

        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $session->addCourse($course);
        $sessionRepo->update($session);

        $sessionRepo->addUserInCourse(Session::COURSE_COACH, $courseCoach, $course, $session);
        $sessionRepo->update($session);

        $item = (new CCourseDescription())
            ->setTitle('title')
            ->setContent('content')
            ->setDescriptionType(1)
            ->setProgress(100)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course, $session)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $tokenFrom = $this->getUserToken(
            [
                'username' => 'admin',
                'password' => 'admin',
            ]
        );

        $response = $this->createClientWithCredentials($tokenFrom)->request(
            'POST',
            '/sessions/'.$session->getId().'/about'
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('session 1', $response->getContent());
    }
}
