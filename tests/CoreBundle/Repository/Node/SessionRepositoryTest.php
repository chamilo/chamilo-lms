<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * @covers \SessionRepository
 */
class SessionRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Create a session.
     */
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
            ->setGeneralCoach($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
        ;
        $errors = $this->getViolations($session);
        $this->assertCount(1, $errors);

        $this->expectException(UniqueConstraintViolationException::class);
        $repo->create($session);
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
        $this->assertResponseStatusCodeSame(200);
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
    }

    public function testAddUserToSessionWithApi(): void
    {
        $token = $this->getUserToken();

        $testUser = $this->createUser('test');

        $session = $this->createSession('test session');

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/session_rel_users',
            [
                'json' => [
                    'session' => '/api/sessions/'.$session->getId(),
                    'user' => '/api/users/'.$testUser->getId(),
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
                'user' => [
                    '@id' => '/api/users/'.$testUser->getId(),
                ],
            ]
        );
    }
}
