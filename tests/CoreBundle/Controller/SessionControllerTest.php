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

        $tokenFrom = $this->getUserTokenFromUser($courseCoach);

        $response = $this->createClientWithCredentials($tokenFrom)->request(
            'POST',
            '/sessions/'.$session->getId().'/about'
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('session 1', $response->getContent());
    }

    /**
     * Teachers may store any HTML in a course description, but the session about page is public and
     * renders it unescaped, so it must be filtered with the reader's profile: otherwise a stored event
     * handler runs in the session of whoever opens the page.
     */
    public function testAboutFiltersTheStoredCourseDescriptionHtml(): void
    {
        $session = $this->createSession('session html');
        $course = $this->createCourse('course html');
        $teacher = $this->createUser('session_about_teacher', '', '', 'ROLE_TEACHER');
        $reader = $this->createUser('session_about_reader');
        $em = $this->getEntityManager();

        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $session->addCourse($course);
        $sessionRepo->update($session);

        foreach ([CCourseDescription::TYPE_DESCRIPTION, CCourseDescription::TYPE_OBJECTIVES] as $type) {
            $item = (new CCourseDescription())
                ->setTitle('<b>sahtml-title</b><img src="x.png" alt="t" onerror="alert(1)">')
                ->setContent('<p>sahtml-content</p><img src="x.png" alt="c" onerror="alert(1)">')
                ->setDescriptionType($type)
                ->setProgress(0)
                ->setParent($course)
                ->setCreator($teacher)
                ->addCourseLink($course, $session)
            ;
            $em->persist($item);
        }
        $em->flush();

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($reader))->request(
            'GET',
            '/sessions/'.$session->getId().'/about'
        );

        $this->assertResponseStatusCodeSame(200);
        $content = $response->getContent();
        $this->assertStringContainsString('sahtml-content', $content);
        $this->assertStringContainsString('sahtml-title', $content);
        $this->assertStringNotContainsString('onerror', $content);
    }
}
