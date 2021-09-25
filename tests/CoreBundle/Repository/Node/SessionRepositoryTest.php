<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class SessionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();
        /** @var SessionRepository $repo */
        $repo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('session');

        $this->assertHasNoEntityViolations($session);

        $count = $repo->count([]);

        $this->assertSame(1, $count);
    }

    public function testCreateSessionSameTitle(): void
    {
        self::bootKernel();
        $name = 'session';
        $session = $this->createSession($name);
        $this->assertHasNoEntityViolations($session);

        $repo = self::getContainer()->get(SessionRepository::class);

        $session = (new Session())
            ->setName($name)
            ->addGeneralCoach($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
        ;
        $errors = $this->getViolations($session);
        $this->assertCount(1, $errors);

        $this->expectException(UniqueConstraintViolationException::class);
        $repo->update($session);
    }

    public function testCreateWithApi(): void
    {
        $token = $this->getUserToken();
        $user = $this->getUser('admin');

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/sessions',
            [
                'json' => [
                    'name' => 'test',
                    'generalCoach' => '/api/users/'.$user->getId(),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Session',
                'name' => 'test',
            ]
        );
    }

    public function testUpdateWithApi(): void
    {
        $token = $this->getUserToken();

        $sessionName = 'simple';
        $newSessionName = 'simple2';
        $session = $this->createSession($sessionName);

        $this->createClientWithCredentials($token)->request(
            'PUT',
            '/api/sessions/'.$session->getId(),
            [
                'json' => [
                    'name' => $newSessionName,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Session',
                'name' => $newSessionName,
            ]
        );
    }

    public function testAddCourseToSessionWithApi(): void
    {
        $token = $this->getUserToken();

        $session = $this->createSession('test session');
        $course = $this->createCourse('test course');

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/session_rel_courses',
            [
                'json' => [
                    'session' => '/api/sessions/'.$session->getId(),
                    'course' => '/api/courses/'.$course->getId(),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/SessionRelCourse',
                '@type' => 'SessionRelCourse',
                'session' => '/api/sessions/'.$session->getId(),
                'course' => '/api/courses/'.$course->getId(),
            ]
        );

        // Add the same course again, it should fail.
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/session_rel_courses',
            [
                'json' => [
                    'session' => '/api/sessions/'.$session->getId(),
                    'course' => '/api/courses/'.$course->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(422);

        /** @var SessionRepository $courseRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $session = $sessionRepo->find($session->getId());

        $this->assertSame(1, $session->getCourses()->count());
    }

    public function testAddUserToSessionWithApi(): void
    {
        $token = $this->getUserToken();

        $user = $this->createUser('test');
        $course = $this->createCourse('course title');
        $session = $this->createSession('test session');

        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session->addCourse($course);
        $sessionRepo->update($session);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/session_rel_users',
            [
                'json' => [
                    'session' => '/api/sessions/'.$session->getId(),
                    'user' => '/api/users/'.$user->getId(),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/SessionRelUser',
                '@type' => 'SessionRelUser',
                'session' => [
                    '@id' => '/api/sessions/'.$session->getId(),
                ],
                'user' => '/api/users/'.$user->getId(),
            ]
        );

        /** @var Session $session */
        $session = $sessionRepo->find($session->getId());
        $this->assertSame(2, $session->getUsers()->count());

        // Add the user again!
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/session_rel_users',
            [
                'json' => [
                    'session' => '/api/sessions/'.$session->getId(),
                    'user' => '/api/users/'.$user->getId(),
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetSessionRelUser(): void
    {
        $em = $this->getEntityManager();
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        // 1. Add session + course + user.
        $name = 'session';
        $session = $this->createSession($name);
        $course = $this->createCourse('new');
        $user = $this->createUser('student');

        $this->assertHasNoEntityViolations($session);

        // 2. Add course to session.
        $session
            ->setDisplayStartDate(new DateTime('2010-01-01 15:00'))
            ->setDisplayEndDate(new DateTime('2010-01-01 18:00'))
            ->addCourse($course)
        ;
        $sessionRepo->update($session);
        $this->assertSame(1, $session->getCourses()->count());

        // 3. Add student to session - course - course
        $course = $courseRepo->find($course->getId());

        /** @var User $user */
        $user = $userRepo->find($user->getId());
        /** @var Session $session */
        $session = $sessionRepo->find($session->getId());
        $studentStatus = Session::STUDENT;

        $sessionRepo->addUserInCourse($studentStatus, $user, $course, $session);
        $sessionRepo->update($session);

        $em->clear();

        /** @var User $user */
        $user = $userRepo->find($user->getId());
        $session = $sessionRepo->find($session->getId());
        $this->assertSame(1, \count($user->getStudentSessions()));

        $sessions = $user->getSessions($studentStatus);
        $this->assertSame(1, \count($sessions));

        $hasUser = $session->hasUserInCourse($user, $course, $studentStatus);
        $this->assertTrue($hasUser);

        $this->assertSame(2, $session->getUsers()->count());

        // 4. Delete user
        $userRepo->delete($user);

        /** @var Session $session */
        $session = $sessionRepo->find($session->getId());
        $this->assertSame(1, $session->getUsers()->count());
        $this->assertSame(0, $session->getSessionRelCourseRelUsers()->count());
    }

    public function testGeneralCoachesInSession()
    {
        self::bootKernel();

        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('test for general coaches');
        $coach1 = $this->createUser('gencoach1');

        $session->addGeneralCoach($coach1);

        $sessionRepo->update($session);

        // when creating one user "admin" was already added (so 1 + 1)
        $this->assertSame(2, $session->getGeneralCoaches()->count());
        $this->assertTrue($session->hasUserAsGeneralCoach($coach1));
    }

    public function testAdminInSession()
    {
        self::bootKernel();

        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('test for session admin');
        $admin1 = $this->createUser('sessadmin1');

        $session->addSessionAdmin($admin1);

        $sessionRepo->update($session);

        // when creating one user "admin" was already added (so 1 + 1)
        $this->assertSame(1, $session->getSessionAdmins()->count());
        $this->assertTrue($session->hasUserAsSessionAdmin($admin1));
    }
}
