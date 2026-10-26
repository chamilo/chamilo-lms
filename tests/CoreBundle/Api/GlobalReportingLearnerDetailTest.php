<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The learner report (formerly main/my_space/myStudents.php) shows the direct courses and each session in
 * its own table, the learner's classes, and the follow-up messages written about the learner.
 */
final class GlobalReportingLearnerDetailTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private ?User $admin = null;

    public function testACourseFollowedDirectlyAndInASessionIsReportedOncePerSubscription(): void
    {
        $learner = $this->createUser('report_learner');
        $course = $this->createCourse('Shared course');
        $course->addUserAsStudent($learner);
        $this->getEntityManager()->flush();
        $session = $this->subscribeToSession($learner, $course, 'Report session');

        $meta = $this->getLearnerReport($learner)['meta'];
        $items = $this->getLearnerReport($learner)['items'];

        $this->assertSame([0, $session->getId()], array_column($items, 'sessionId'));
        $this->assertSame([0, $session->getId()], array_column($meta['subscriptions'], 'sessionId'));
        $this->assertSame('Report session', $meta['subscriptions'][1]['title']);
        $this->assertSame(1, $meta['subscriptions'][1]['totals']['courses']);
    }

    /**
     * A teacher or coach only follows the learner where they teach or coach, as in the legacy page; the
     * other subscriptions belong to other teachers.
     */
    public function testATeacherOnlySeesTheCoursesAndSessionsTheyFollow(): void
    {
        $learner = $this->createUser('report_learner');
        $teacher = $this->createUser('report_teacher', '', '', 'ROLE_TEACHER');

        $taughtCourse = $this->createCourse('Taught course');
        $taughtCourse->addUserAsTeacher($teacher)->addUserAsStudent($learner);
        $otherCourse = $this->createCourse('Other course');
        $otherCourse->addUserAsStudent($learner);
        $this->getEntityManager()->flush();

        $coachedSession = $this->subscribeToSession($learner, $this->createCourse('Coached course'), 'Coached session');
        $coachedSession->addGeneralCoach($teacher);
        $this->getEntityManager()->flush();
        $this->subscribeToSession($learner, $this->createCourse('Unrelated course'), 'Unrelated session');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher));
        $report = $client->request('GET', '/api/global-reporting/report', [
            'query' => ['section' => 'learner-detail', 'userId' => $learner->getId()],
            'headers' => ['Accept' => 'application/json'],
        ])->toArray();

        $this->assertSame(['Taught course', 'Coached course'], array_column($report['items'], 'title'));
        $this->assertSame([0, $coachedSession->getId()], array_column($report['meta']['subscriptions'], 'sessionId'));

        $client->request('GET', '/api/global-reporting/report', [
            'query' => [
                'section' => 'learner-detail',
                'userId' => $learner->getId(),
                'courseId' => $otherCourse->getId(),
                'sessionId' => 0,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheLearnerClassesAreListed(): void
    {
        $learner = $this->createUser('report_learner');
        $class = $this->createUserGroup('Evening class');
        $class->addUser($learner);
        $this->getEntityManager()->flush();

        $this->assertSame(['Evening class'], $this->getLearnerReport($learner)['meta']['classes']);
    }

    public function testMessagesAboutTheLearnerAreHiddenWhenTheSettingIsOff(): void
    {
        $this->setMessagesAboutUsers('false');
        $learner = $this->createUser('report_learner');

        $this->assertNull($this->getLearnerReport($learner)['meta']['messages']);

        $this->sendMessage($learner, 'Follow-up');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOnlyTheAuthorDeletesAMessageAboutTheLearner(): void
    {
        $this->setMessagesAboutUsers('true');
        $learner = $this->createUser('report_learner');

        $this->sendMessage($learner, 'Follow-up');
        $this->assertResponseIsSuccessful();

        $messages = $this->getLearnerReport($learner)['meta']['messages'];
        $this->assertSame(['Follow-up'], array_column($messages, 'title'));
        $this->assertTrue($messages[0]['canDelete']);

        $otherAdmin = $this->createUser('other_admin', '', '', 'ROLE_ADMIN');
        $this->createClientWithCredentials($this->getUserTokenFromUser($otherAdmin))->request(
            'DELETE',
            '/global-reporting/learners/'.$learner->getId().'/messages/'.$messages[0]['id']
        );
        $this->assertResponseStatusCodeSame(404);

        $this->client()->request(
            'DELETE',
            '/global-reporting/learners/'.$learner->getId().'/messages/'.$messages[0]['id']
        );
        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->getLearnerReport($learner)['meta']['messages']);

        // The deletion is traced, as the legacy page did.
        $this->assertSame(1, (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM track_e_default WHERE default_event_type = 'msg_deleted' AND default_value LIKE :value",
            ['value' => '%'.$messages[0]['id'].' - Follow-up%']
        ));
    }

    private function subscribeToSession(User $learner, Course $course, string $title): Session
    {
        $session = $this->createSession($title);
        $session
            ->addUserInSession(Session::STUDENT, $learner)
            ->addCourse($course)
        ;
        $session->addUserInCourse(Session::STUDENT, $learner, $course);
        self::getContainer()->get(SessionRepository::class)->update($session);

        return $session;
    }

    private function setMessagesAboutUsers(string $value): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            "UPDATE settings SET selected_value = :value WHERE variable = 'private_messages_about_user'",
            ['value' => $value]
        );
    }

    private function sendMessage(User $learner, string $subject): void
    {
        $this->client()->request('POST', '/global-reporting/learners/'.$learner->getId().'/messages', [
            'json' => ['subject' => $subject, 'content' => '<p>Needs help with the exercises</p>'],
        ]);
    }

    private function getLearnerReport(User $learner): array
    {
        return $this->client()->request('GET', '/api/global-reporting/report', [
            'query' => ['section' => 'learner-detail', 'userId' => $learner->getId()],
            'headers' => ['Accept' => 'application/json'],
        ])->toArray();
    }

    private function client()
    {
        $this->admin ??= $this->createUser('report_admin', '', '', 'ROLE_ADMIN');

        return $this->createClientWithCredentials($this->getUserTokenFromUser($this->admin));
    }
}
