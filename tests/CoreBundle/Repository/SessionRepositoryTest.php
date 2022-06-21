<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;

class SessionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testSession(): void
    {
        $this->assertIsArray(Session::getRelationTypeList());
    }

    public function testCreate(): void
    {
        /** @var SessionRepository $repo */
        $repo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('session');

        $this->assertHasNoEntityViolations($session);

        $count = $repo->count([]);

        $this->assertSame(0, $session->getPosition());
        $this->assertSame(1, $count);
    }

    public function testCreateSessionSameTitle(): void
    {
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
                    'generalCoach' => $user->getIri(),
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
        $sessionName = 'simple';
        $newSessionName = 'simple2';
        $session = $this->createSession($sessionName);

        $token = $this->getUserToken();
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
                'course' => [
                    '@type' => 'https://schema.org/Course',
                    'id' => $course->getId(),
                ],
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

    public function testAddUserToSessionInactive(): void
    {
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        // Add session
        $session = $this->createSession('session');
        $course = $this->createCourse('new');

        $student = $this->createUser('student');
        $sessionAdmin = $this->createUser('session_admin');
        $generalCoach = $this->createUser('general_coach');

        // Add session admin + add course to session.
        $session
            ->setAccessStartDate(new DateTime('now +30 days'))
            ->addCourse($course)
            ->addSessionAdmin($sessionAdmin)
            ->addGeneralCoach($generalCoach)
        ;

        $sessionRepo->update($session);

        $this->assertSame(1, $session->getCourses()->count());

        $this->expectException(Exception::class);
        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);

        $session->setAccessStartDate(new DateTime());
        $sessionRepo->update($session);
        $student->setActive(false);
        $userRepo->update($student);

        $this->expectException(Exception::class);
        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);
    }

    public function testAddUserToSession(): void
    {
        $em = $this->getEntityManager();
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        // Add session
        $session = $this->createSession('session');
        $course = $this->createCourse('new');

        $student = $this->createUser('student');
        $sessionAdmin = $this->createUser('session_admin');
        $generalCoach = $this->createUser('general_coach');
        $drh = $this->createUser('drh', '', '', 'ROLE_RRHH');
        $courseCoach = $this->createUser('course_coach', '', '', 'ROLE_TEACHER');

        // Add session admin + add course to session.
        $session
            ->addCourse($course)
            ->addSessionAdmin($sessionAdmin)
            ->addGeneralCoach($generalCoach)
        ;
        $sessionRepo->update($session);

        $this->assertSame(1, $session->getCourses()->count());

        $this->expectException(Exception::class);
        $sessionRepo->addUserInCourse(8888, $student, $course, $session);

        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);
        $sessionRepo->addUserInCourse(Session::DRH, $drh, $course, $session);
        $sessionRepo->addUserInCourse(Session::COURSE_COACH, $courseCoach, $course, $session);

        $sessionRepo->update($session);

        $this->assertFalse($session->hasCourseCoachInCourse($sessionAdmin));
        $this->assertFalse($session->hasCourseCoachInCourse($sessionAdmin, $course));
        $this->assertFalse($session->hasCourseCoachInCourse($generalCoach, $course));
        $this->assertFalse($session->hasCourseCoachInCourse($student, $course));
        $this->assertFalse($session->hasCourseCoachInCourse($drh, $course));
        $this->assertTrue($session->hasCourseCoachInCourse($courseCoach, $course));

        $coaches = $userRepo->getCoachesForSessionCourse($session, $course);
        $this->assertCount(1, $coaches);

        $sessions = $userRepo->getSessionAdmins($sessionAdmin);
        $this->assertCount(1, $sessions);

        $this->assertTrue($session->hasStudentInCourse($student, $course));
        $this->assertFalse($session->hasStudentInCourse($sessionAdmin, $course));

        $this->assertCount(1, $student->getSessionsAsStudent());
        $this->assertCount(0, $sessionAdmin->getSessionsAsStudent());

        $em->clear();

        $this->assertCount(1, $student->getSessions(Session::STUDENT));
        $this->assertCount(0, $sessionAdmin->getSessions(Session::STUDENT));
        $this->assertCount(1, $sessionAdmin->getSessions(Session::SESSION_ADMIN));

        $hasUser = $session->hasUserInCourse($student, $course, Session::STUDENT);

        $this->assertTrue($hasUser);
        $this->assertTrue($session->hasStudentInCourse($student, $course));
        $this->assertTrue($session->hasStudentInCourseList($student));
        $this->assertFalse($session->hasCoachInCourseList($student));
        $this->assertTrue($session->hasCoachInCourseList($courseCoach));

        $this->assertSame(5, $session->getUsers()->count());

        // First SessionRelUser - session admin
        $sessionRelUser1 = $session->getUsers()[0];

        $this->assertNotNull($sessionRelUser1->getSession());
        $this->assertSame($session, $sessionRelUser1->getSession());
        $this->assertSame($sessionAdmin, $sessionRelUser1->getUser());
        $this->assertSame(0, $sessionRelUser1->getCourses()->count());

        // General coach.
        $sessionRelUser2 = $session->getUsers()[1];
        $this->assertNotNull($sessionRelUser2->getSession());
        $this->assertSame($session, $sessionRelUser2->getSession());
        $this->assertSame($generalCoach, $sessionRelUser2->getUser());
        $this->assertSame(0, $sessionRelUser2->getCourses()->count());

        // Student.
        $sessionRelUser3 = $session->getUsers()[2];
        $this->assertNotNull($sessionRelUser3->getSession());
        $this->assertSame($session, $sessionRelUser3->getSession());
        $this->assertSame($student, $sessionRelUser3->getUser());
        $this->assertSame(1, $sessionRelUser3->getCourses()->count());

        // drh
        $sessionRelUser4 = $session->getUsers()[3];
        $this->assertNotNull($sessionRelUser4->getSession());
        $this->assertSame($session, $sessionRelUser4->getSession());
        $this->assertSame($drh, $sessionRelUser4->getUser());
        $this->assertSame(0, $sessionRelUser4->getCourses()->count());

        // Course coach
        $sessionRelUser5 = $session->getUsers()[4];
        $this->assertNotNull($sessionRelUser5->getSession());
        $this->assertSame($session, $sessionRelUser5->getSession());
        $this->assertSame($courseCoach, $sessionRelUser5->getUser());
        $this->assertSame(1, $sessionRelUser5->getCourses()->count());

        $this->assertSame(1, $session->getNbrCourses());
        $this->assertSame(0, $session->getNbrClasses());
        $this->assertSame(5, $session->getNbrUsers());

        // Delete student
        $userRepo->delete($student);

        /** @var Session $session */
        $session = $sessionRepo->find($session->getId());

        $this->assertSame(4, $session->getNbrUsers());
        $this->assertSame(4, $session->getUsers()->count());
        $this->assertSame(1, $session->getSessionRelCourseRelUsers()->count());
    }

    public function testGetSessionRelUser(): void
    {
        $em = $this->getEntityManager();
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        // Add session + course + user.
        $course = $this->createCourse('new');
        $user = $this->createUser('student');
        $sessionAdmin = $this->createUser('session_admin');
        $name = 'session';
        $session = $this->createSession($name)
            ->addSessionAdmin($sessionAdmin)
            ->setDisplayStartDate(new DateTime('2010-01-01 15:00'))
            ->setDisplayEndDate(new DateTime('2010-01-01 18:00'))
        ;

        $this->assertHasNoEntityViolations($session);
        $sessionRepo->update($session);

        // 2. Add course to session.
        $session->addCourse($course);
        $sessionRepo->update($session);
        $this->assertSame(
            1,
            $session->getCourses()->count()
        );

        // 3. Add student to session - course.
        $sessionRepo->addUserInCourse(Session::STUDENT, $user, $course, $session);
        $sessionRepo->update($session);

        $this->assertCount(1, $user->getSessionsAsStudent());

        $this->assertTrue($session->hasUserInCourse($user, $course, Session::STUDENT));
        $this->assertTrue($session->hasStudentInCourse($user, $course));
        $this->assertTrue($session->hasStudentInCourseList($user));

        $this->assertSame(3, $session->getUsers()->count());

        //general coach
        $sessionRelUser1 = $session->getUsers()[0];
        $admin = $this->getUser('admin');

        $this->assertSame($session, $sessionRelUser1->getSession());
        $this->assertSame(0, $sessionRelUser1->getCourses()->count());
        $this->assertSame($admin, $sessionRelUser1->getUser());

        //session admin
        $sessionRelUser2 = $session->getUsers()[1];
        $this->assertSame($session, $sessionRelUser2->getSession());
        $this->assertSame(0, $sessionRelUser2->getCourses()->count());
        $this->assertSame($sessionAdmin, $sessionRelUser2->getUser());

        // Student is to one course.
        $sessionRelUser3 = $session->getUsers()[2];
        $this->assertSame($session, $sessionRelUser3->getSession());
        $this->assertSame(1, $sessionRelUser3->getCourses()->count());
        $this->assertSame($user, $sessionRelUser3->getUser());
    }

    public function testGeneralCoachesInSession(): void
    {
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('test for general coaches');
        $coach1 = $this->createUser('gencoach1');

        $session->addGeneralCoach($coach1);

        $sessionRepo->update($session);

        // when creating one user "admin" was already added (so 1 + 1)
        $this->assertSame(2, $session->getGeneralCoaches()->count());
        $this->assertTrue($session->hasUserAsGeneralCoach($coach1));
    }

    public function testAdminInSession(): void
    {
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('test for session admin');
        $admin1 = $this->createUser('sessadmin1');

        $session->addSessionAdmin($admin1);

        $sessionRepo->update($session);

        // when creating one user "admin" was already added (so 1 + 1)
        $this->assertSame(1, $session->getSessionAdmins()->count());
        $this->assertTrue($session->hasUserAsSessionAdmin($admin1));
    }

    public function testCreateWithSessionCategory(): void
    {
        $em = $this->getEntityManager();
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $url = $this->getAccessUrl();
        $coach = $this->createUser('coach');

        $category = (new SessionCategory())
            ->setName('cat')
            ->setDateStart(new DateTime())
            ->setDateEnd(new DateTime())
            ->setUrl($this->getAccessUrl())
        ;
        $em->persist($category);
        $this->assertHasNoEntityViolations($category);
        $em->flush();

        $this->assertSame('cat', (string) $category);
        $this->assertNotNull($category->getDateStart());
        $this->assertNotNull($category->getDateEnd());
        $this->assertNotNull($category->getUrl());

        $session = ($sessionRepo->create())
            ->setName('session 1')
            ->addGeneralCoach($coach)
            ->addAccessUrl($url)
            ->setCategory($category)
            ->setDuration(100)
            ->setStatus(1)
            ->setAccessStartDate(new DateTime())
            ->setAccessEndDate(new DateTime())
            ->setDisplayStartDate(new DateTime())
            ->setDisplayEndDate(new DateTime())
            ->setPosition(1)
            ->setShowDescription(true)
            ->setDescription('desc')
            ->setNbrClasses(0)
            ->setNbrUsers(0)
            ->setNbrCourses(0)
            ->setVisibility(Session::INVISIBLE)
        ;
        $this->assertHasNoEntityViolations($session);
        $em->persist($session);
        $em->flush();

        $this->assertSame(1, $sessionRepo->count([]));
        $this->assertNotNull($session->getCategory());

        $this->assertSame('session 1', (string) $session);
        $this->assertCount(0, $session->getAllUsersFromCourse(Session::STUDENT));
        $this->assertSame(100, $session->getDuration());
        $this->assertTrue($session->isActiveForStudent());

        $this->assertTrue($session->isActiveForCoach());
        $this->assertFalse($session->isCurrentlyAccessible());

        $user = $this->createUser('test');
        $this->assertFalse($session->hasUserAsGeneralCoach($user));

        $this->assertIsArray(Session::getStatusList());
    }

    public function testSessionRelUser(): void
    {
        $em = $this->getEntityManager();
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $url = $this->getAccessUrl();
        $coach = $this->createUser('coach');
        $course = $this->createCourse('new');

        $session = ($sessionRepo->create())
            ->setName('session 1')
            ->addGeneralCoach($coach)
            ->addAccessUrl($url)
            ->setVisibility(Session::INVISIBLE)
        ;
        $em->persist($session);

        $this->assertSame(0, $session->getCourses()->count());

        $session->addCourse($course);

        $em->flush();

        $this->assertSame(1, $session->getCourses()->count());
        $this->assertSame(1, $session->getUsers()->count());
        $this->assertSame($coach->getUsername(), $session->getUsers()->first()->getUser()->getUserName());

        $student1 = $this->createUser('student1');
        $student2 = $this->createUser('student2');

        $sessionRepo->addUserInCourse(Session::STUDENT, $student1, $course, $session);
        $sessionRepo->addUserInCourse(Session::STUDENT, $student2, $course, $session);

        $sessionRepo->update($session);

        $session = $sessionRepo->find($session->getId());

        $this->assertSame(3, $session->getUsers()->count());
        $this->assertSame(3, $session->getNbrUsers());
        $this->assertSame(2, $session->getAllUsersFromCourse(Session::STUDENT)->count());

        $student1 = $this->getUser('student1');
        $sessions = $sessionRepo->getSessionsByUser($student1, $url);
        $this->assertCount(1, $sessions);

        $sessions = $sessionRepo->getSessionCoursesByStatusInUserSubscription($student1, $session, Session::STUDENT);
        $this->assertCount(1, $sessions);

        $sessions = $sessionRepo->getSessionCoursesByStatusInCourseSubscription($student1, $session, Session::STUDENT);
        $this->assertCount(1, $sessions);

        $sessions = $sessionRepo->getUsersByAccessUrl($session, $url);
        $this->assertCount(3, $sessions);

        $sessions = $sessionRepo->getUsersByAccessUrl($session, $url, [Session::STUDENT]);
        $this->assertCount(2, $sessions);

        $sessions = $sessionRepo->getUsersByAccessUrl($session, $url, [Session::STUDENT, Session::DRH]);
        $this->assertCount(2, $sessions);
    }
}
