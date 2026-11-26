<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * My sessions must expose only the target user's course subscriptions, without
 * changing the managed course collection or granting access to another user's list.
 */
final class UserSessionSubscriptionsApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCurrentSessionCoursesAreScopedToTheTargetUser(): void
    {
        $learner = $this->createUser('b13_learner', '', '', 'ROLE_STUDENT');
        $coach = $this->createUser('b13_coach', '', '', 'ROLE_TEACHER');
        $generalCoach = $this->createUser('b13_general', '', '', 'ROLE_TEACHER');
        $admin = $this->createUser('b13_admin', '', '', 'ROLE_ADMIN');
        $globalAdmin = $this->createUser('b13_global', '', '', 'ROLE_GLOBAL_ADMIN');
        $unassigned = $this->createUser('b13_unassigned', '', '', 'ROLE_STUDENT');
        $otherCoach = $this->createUser('b13_other_coach', '', '', 'ROLE_TEACHER');
        $courses = [
            $this->createCourse('B13 course first'),
            $this->createCourse('B13 course second'),
            $this->createCourse('B13 course third'),
        ];
        $session = $this->createSession('B13 current session');
        $this->setDateWindow($session, '-2 days', '+2 days');
        foreach ($courses as $position => $course) {
            $session->addCourse($course);
            $session->getCourseSubscription($course)->setPosition($position);
        }
        $session->addUserInSession(Session::STUDENT, $learner);
        $session->addUserInCourse(Session::STUDENT, $learner, $courses[0]);
        $session->addUserInCourse(Session::STUDENT, $learner, $courses[1]);
        $session->addUserInSession(Session::COURSE_COACH, $coach);
        $session->addUserInCourse(Session::COURSE_COACH, $coach, $courses[0]);
        $session->addUserInSession(Session::COURSE_COACH, $otherCoach);
        $session->addUserInCourse(Session::COURSE_COACH, $otherCoach, $courses[2]);
        $session->addGeneralCoach($generalCoach);
        // Neither administrator has a general-coach or course assignment.
        $session->addUserInSession(Session::STUDENT, $admin);
        $session->addUserInSession(Session::STUDENT, $globalAdmin);
        $session->addUserInSession(Session::STUDENT, $unassigned);
        $this->getEntityManager()->flush();

        $sid = $session->getId();
        $ids = array_map(static fn ($course): int => $course->getId(), $courses);
        $cases = [
            [$learner, [$ids[0], $ids[1]]],
            [$coach, [$ids[0]]],
            [$generalCoach, $ids],
            [$admin, $ids],
            [$globalAdmin, $ids],
            [$unassigned, []],
        ];
        foreach ($cases as [$user, $expected]) {
            $token = $this->getUserTokenFromUser($user);
            $row = $this->getSessionRow($token, $user->getId(), $sid, 'current');
            self::assertSame($expected, $this->extractCourseIds($row), $user->getUsername());
            // Coach metadata must remain available for visible courses only.
            self::assertArrayHasKey('courseCoachesSubscriptions', $row);
            self::assertEqualsCanonicalizing(
                array_values(array_intersect($expected, [$ids[0], $ids[2]])),
                array_map(static fn (array $subscription): int => (int) $subscription['course']['id'], $row['courseCoachesSubscriptions'])
            );
        }

        // A platform admin viewing another user's subscriptions sees that user's course scope.
        $adminToken = $this->getUserTokenFromUser($admin);
        $row = $this->getSessionRow($adminToken, $learner->getId(), $sid, 'current');
        self::assertSame([$ids[0], $ids[1]], $this->extractCourseIds($row));

        // Other session endpoints must retain the original complete course collection.
        $sessionItem = $this->createClientWithCredentials($adminToken)->request('GET', '/api/sessions/'.$sid)->toArray();
        $this->assertResponseIsSuccessful();
        self::assertSame($ids, $this->extractCourseIds($sessionItem));

        $this->createClientWithCredentials($this->getUserTokenFromUser($learner))->request(
            'GET',
            '/api/users/'.$coach->getId().'/session_subscriptions/current'
        );
        $this->assertResponseStatusCodeSame(403);

        // The response projection must never remove persisted session courses.
        self::assertSame(3, (int) $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM session_rel_course WHERE session_id = ?',
            [$sid]
        ));
    }

    public function testPastAndUpcomingSessionCoursesUseTheSameScope(): void
    {
        $learner = $this->createUser('b13_date_learner', '', '', 'ROLE_STUDENT');
        $allowed = $this->createCourse('B13 date allowed');
        $unassigned = $this->createCourse('B13 date unassigned');
        $sessionIds = [];
        foreach (['past' => ['-5 days', '-3 days'], 'upcoming' => ['+3 days', '+5 days']] as $type => [$start, $end]) {
            $session = $this->createSession('B13 '.$type.' session');
            $this->setDateWindow($session, $start, $end);
            $session->addCourse($allowed)->addCourse($unassigned);
            $session->addUserInSession(Session::STUDENT, $learner);
            $session->addUserInCourse(Session::STUDENT, $learner, $allowed);
            $this->getEntityManager()->flush();
            $sessionIds[$type] = $session->getId();
        }

        $token = $this->getUserTokenFromUser($learner);
        foreach ($sessionIds as $type => $sid) {
            $row = $this->getSessionRow($token, $learner->getId(), $sid, $type);
            self::assertSame([$allowed->getId()], $this->extractCourseIds($row));
        }
    }

    private function setDateWindow(Session $session, string $start, string $end): void
    {
        $session
            ->setVisibility(Session::VISIBLE)
            ->setAccessStartDate(new DateTime($start))
            ->setAccessEndDate(new DateTime($end))
            ->setDisplayStartDate(new DateTime($start))
            ->setDisplayEndDate(new DateTime($end))
            ->setCoachAccessStartDate(new DateTime($start))
            ->setCoachAccessEndDate(new DateTime($end))
        ;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSessionRow(string $token, int $userId, int $sessionId, string $type): array
    {
        $payload = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/users/'.$userId.'/session_subscriptions/'.$type
        )->toArray();
        $this->assertResponseIsSuccessful();
        foreach ($payload['hydra:member'] as $row) {
            if ($sessionId === (int) $row['id']) {
                return $row;
            }
        }

        self::fail('Expected session was not returned: '.$type.' / '.$sessionId);
    }

    /**
     * @return list<int>
     */
    private function extractCourseIds(array $row): array
    {
        self::assertArrayHasKey('courses', $row);
        self::assertTrue(array_is_list($row['courses']));

        return array_map(static fn (array $course): int => (int) $course['course']['id'], $row['courses']);
    }
}
