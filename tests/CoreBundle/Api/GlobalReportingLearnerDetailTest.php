<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelSkill;
use Chamilo\CoreBundle\Entity\SkillRelUser;
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

    public function testSkillsWithoutAPortalAreListedButNotThoseOfAnotherPortal(): void
    {
        $learner = $this->createUser('report_learner');
        // Skills migrated from 1.11 may carry no portal (0): the legacy report showed them, so must this one.
        $this->acquireSkill($learner, 'Skill without portal', 0);
        $this->acquireSkill($learner, 'Skill of this portal', $this->getAccessUrl()->getId());
        $this->acquireSkill($learner, 'Skill of another portal', $this->getAccessUrl()->getId() + 1);

        $titles = array_column($this->getLearnerReport($learner)['meta']['skills'], 'title');

        $this->assertSame(['Skill of this portal', 'Skill without portal'], $titles);
    }

    public function testWithoutSkillLevelNamesTheSkillsAreListedWithoutTheirTree(): void
    {
        $learner = $this->createUser('report_learner');
        $this->acquire($learner, $this->createSkill('Listening', $this->createSkill('Communication')));
        $this->setSetting('skill_levels_names', '');

        $report = $this->getLearnerReport($learner);

        // The legacy page then lists the acquired skills only, as the flat list already does
        $this->assertSame(['Listening'], array_column($report['meta']['skills'], 'title'));
        $this->assertNull($report['meta']['skillTree']);
    }

    public function testTheSkillTreeShowsTheNotAcquiredAncestorsOfTheAcquiredSkills(): void
    {
        $learner = $this->createUser('report_learner');
        $communication = $this->createSkill('Communication');
        $this->acquire($learner, $this->createSkill('Listening', $this->createSkill('Oral', $communication)));
        // A branch without any acquired skill has nothing to place, so the legacy page does not draw it
        $this->createSkill('Unrelated', $this->createSkill('Leadership'));
        $this->setSetting('skill_levels_names', '{"1":"Basic"}');
        $this->setSetting('skills_hierarchical_view_in_user_tracking', 'false');

        $tree = $this->getLearnerReport($learner)['meta']['skillTree'];

        $this->assertSame('categories', $tree['mode']);
        $this->assertSame(
            [['Communication', false, [['Oral', false, [['Listening', true, []]]]]]],
            $this->summarizeSkillNodes($tree['nodes'])
        );

        $this->setSetting('skills_hierarchical_view_in_user_tracking', 'true');

        $this->assertSame('hierarchical', $this->getLearnerReport($learner)['meta']['skillTree']['mode']);
    }

    public function testTheCourseReportPlacesOnlyTheSkillsOfThatCourseInTheSkillTree(): void
    {
        $learner = $this->createUser('report_learner');
        $course = $this->createCourse('Skills course');
        $course->addUserAsStudent($learner);
        $communication = $this->createSkill('Communication');
        $this->acquire($learner, $this->createSkill('Listening', $communication), $course);
        // Acquired outside the course: the legacy course report leaves it out, so must its tree
        $this->acquire($learner, $this->createSkill('Writing', $communication));
        $this->setSetting('skill_levels_names', '{"1":"Basic"}');

        $sections = $this->client()->request('GET', '/api/global-reporting/report', [
            'query' => [
                'section' => 'learner-detail',
                'userId' => $learner->getId(),
                'courseId' => $course->getId(),
                'sessionId' => 0,
            ],
            'headers' => ['Accept' => 'application/json'],
        ])->toArray()['sections'];
        $skillsSection = array_values(array_filter($sections, static fn (array $section): bool => 'course-skills' === $section['key']))[0];

        $this->assertSame(
            [['Communication', false, [['Listening', true, []]]]],
            $this->summarizeSkillNodes($skillsSection['tree']['nodes'])
        );
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

    private function acquireSkill(User $learner, string $title, int $accessUrlId): void
    {
        $em = $this->getEntityManager();
        $skill = (new Skill())
            ->setTitle($title)
            ->setShortCode($title)
            ->setAccessUrlId($accessUrlId)
        ;
        $em->persist($skill);
        $this->acquire($learner, $skill);
    }

    /**
     * A skill placed in the skill tree. Without a parent, its relation has none, which hangs it from the root.
     */
    private function createSkill(string $title, ?Skill $parent = null): Skill
    {
        $em = $this->getEntityManager();
        $skill = (new Skill())
            ->setTitle($title)
            ->setShortCode($title)
            ->setAccessUrlId($this->getAccessUrl()->getId())
        ;
        $em->persist($skill);
        $em->persist(
            (new SkillRelSkill())
                ->setSkill($skill)
                ->setParent($parent)
                ->setRelationType(0)
                ->setLevel(0)
        );
        $em->flush();

        return $skill;
    }

    private function acquire(User $learner, Skill $skill, ?Course $course = null): void
    {
        $em = $this->getEntityManager();
        $acquisition = (new SkillRelUser())
            ->setSkill($skill)
            ->setUser($learner)
            ->setArgumentation('')
            ->setArgumentationAuthorId((int) $learner->getId())
        ;
        if (null !== $course) {
            $acquisition->setCourse($course);
        }
        $em->persist($acquisition);
        $em->flush();
    }

    /**
     * @return list<array{0: string, 1: bool, 2: array}> [title, acquired, children] of every node
     */
    private function summarizeSkillNodes(array $nodes): array
    {
        return array_map(
            fn (array $node): array => [$node['title'], $node['acquired'], $this->summarizeSkillNodes($node['children'])],
            $nodes
        );
    }

    private function setSetting(string $variable, string $value): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE settings SET selected_value = :value WHERE variable = :variable',
            ['value' => $value, 'variable' => $variable]
        );
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
