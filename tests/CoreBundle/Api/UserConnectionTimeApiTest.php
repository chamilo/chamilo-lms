<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use DateTimeZone;

/**
 * GET /api/users/{id}/connection-time replaces the 1.11.x get_user_total_connexion_time
 * webservice, which EFC uses to report the time a learner spent on the platform, optionally
 * restricted to a period (e.g. the dates of one training run).
 */
class UserConnectionTimeApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTotalIsSummedOverAllConnectionsOrThePeriodAsked(): void
    {
        $user = $this->createUser('connection_time_learner');
        // 1h in March, 30min in June, 10min in September (UTC).
        $this->addConnection($user, '2026-03-10 08:00:00', '2026-03-10 09:00:00');
        $this->addConnection($user, '2026-06-10 08:00:00', '2026-06-10 08:30:00');
        $this->addConnection($user, '2026-09-10 08:00:00', '2026-09-10 08:10:00');
        // An unclosed connection has no duration and must not break the sum.
        $this->addConnection($user, '2026-09-11 08:00:00', null);

        $cases = [
            '' => 6000,
            '?startDate=2026-06-01T00:00:00Z' => 2400,
            '?endDate=2026-07-01T00:00:00Z' => 5400,
            '?startDate=2026-06-01T00:00:00Z&endDate=2026-07-01T00:00:00Z' => 1800,
            // Offsets are honoured: 02:00+02:00 is midnight UTC.
            '?startDate=2026-06-01T02:00:00%2B02:00' => 2400,
        ];

        $client = $this->createClientWithCredentials($this->getUserToken());
        foreach ($cases as $query => $expected) {
            $client->request('GET', '/api/users/'.$user->getId().'/connection-time'.$query);

            $this->assertResponseIsSuccessful();
            $this->assertJsonContains(['totalConnectionTime' => $expected], true, 'Query "'.$query.'"');
        }

        $this->assertJsonContains(['totalConnectionTimeFormatted' => '00:40:00']);
    }

    public function testLearnerMaySeeTheirOwnTimeButNotSomeoneElses(): void
    {
        $learner = $this->createUser('connection_time_self');
        $other = $this->createUser('connection_time_other');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($learner));

        $client->request('GET', '/api/users/'.$learner->getId().'/connection-time');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/users/'.$other->getId().'/connection-time');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testInvalidPeriodIsRejected(): void
    {
        $user = $this->createUser('connection_time_period');
        $client = $this->createClientWithCredentials($this->getUserToken());

        $client->request('GET', '/api/users/'.$user->getId().'/connection-time?startDate=2026-09-20T00:00:00Z&endDate=2026-09-01T00:00:00Z');
        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', '/api/users/'.$user->getId().'/connection-time?startDate=not-a-date');
        $this->assertResponseStatusCodeSame(400);
    }

    private function addConnection(User $user, string $login, ?string $logout): void
    {
        $utc = new DateTimeZone('UTC');
        $connection = (new TrackELogin())
            ->setUser($user)
            ->setUserIp('127.0.0.1')
            ->setLoginDate(new DateTime($login, $utc))
        ;
        if (null !== $logout) {
            $connection->setLogoutDate(new DateTime($logout, $utc));
        }

        $this->getEntityManager()->persist($connection);
        $this->getEntityManager()->flush();
    }
}
