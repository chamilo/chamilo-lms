<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\CourseInvitationRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * A whole-session invitation keeps the course it was sent from, so a course shared by two sessions
 * has invitations of both sessions under the same course. The general coach of one session must
 * only see and revoke the invitations of that session: the links of the other session subscribe
 * their holder to it.
 */
class CourseInvitationSessionScopeApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testSessionCoachListsOnlyTheInvitationsOfHisSession(): void
    {
        [$coach, $course, $own, $foreign, $plain] = $this->createSharedCourseFixture('list');

        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($coach))
            ->request('GET', '/api/course-invitations', ['query' => ['cid' => $course['id'], 'sid' => $own['sessionId']]])
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $emails = array_column($response->toArray()['hydra:member'], 'email');
        $this->assertSame([$own['email']], $emails);
        $this->assertNotContains($foreign['email'], $emails);
        $this->assertNotContains($plain['email'], $emails);
    }

    public function testCourseContextListsOnlyThePlainCourseInvitations(): void
    {
        [, $course, $own, $foreign, $plain] = $this->createSharedCourseFixture('course');

        $response = $this
            ->createClientWithCredentials($this->getUserToken())
            ->request('GET', '/api/course-invitations', ['query' => ['cid' => $course['id']]])
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $emails = array_column($response->toArray()['hydra:member'], 'email');
        $this->assertSame([$plain['email']], $emails);
        $this->assertNotContains($own['email'], $emails);
        $this->assertNotContains($foreign['email'], $emails);
    }

    public function testSessionCoachCannotRevokeTheInvitationOfAnotherSession(): void
    {
        [$coach, $course, $own, $foreign] = $this->createSharedCourseFixture('revoke');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($coach));

        $client->request('DELETE', '/api/course-invitation/'.$foreign['id'], [
            'query' => ['cid' => $course['id'], 'sid' => $own['sessionId']],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('DELETE', '/api/course-invitation/'.$own['id'], [
            'query' => ['cid' => $course['id'], 'sid' => $own['sessionId']],
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    /**
     * One course shared by two sessions, the coach being the general coach of the first only, and
     * one invitation per session plus one plain course invitation, all sent from that course.
     *
     * @return array{0: User, 1: array{id: int}, 2: array<string, mixed>, 3: array<string, mixed>, 4: array<string, mixed>}
     */
    private function createSharedCourseFixture(string $label): array
    {
        $suffix = $label.'_'.bin2hex(random_bytes(3));

        $coach = $this->createUser('inv_coach_'.$suffix);
        $course = $this->createCourse('Invitation scope '.$suffix);

        $sessionRepo = static::getContainer()->get(SessionRepository::class);

        $own = $this->createSession('Own session '.$suffix);
        $own->addCourse($course)->addGeneralCoach($coach);
        $sessionRepo->update($own);

        $other = $this->createSession('Other session '.$suffix);
        $other->addCourse($course);
        $sessionRepo->update($other);

        return [
            $coach,
            ['id' => (int) $course->getId()],
            $this->createInvitation($course, $own, 'own_'.$suffix),
            $this->createInvitation($course, $other, 'foreign_'.$suffix),
            $this->createInvitation($course, null, 'plain_'.$suffix),
        ];
    }

    /**
     * @return array{id: int, email: string, sessionId: ?int}
     */
    private function createInvitation(Course $course, ?Session $session, string $label): array
    {
        $tokenService = static::getContainer()->get(CourseInvitationTokenService::class);
        $email = $label.'@example.com';

        $created = $tokenService->create($course, $session, null, $email, $this->getAdmin());

        $invitation = static::getContainer()->get(CourseInvitationRepository::class)->find($created['invitation']->getId());
        $this->assertInstanceOf(CourseInvitation::class, $invitation);

        return ['id' => (int) $invitation->getId(), 'email' => $email, 'sessionId' => $session?->getId()];
    }
}
