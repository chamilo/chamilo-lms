<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The legacy survey pages take survey and question ids from the request and the survey helpers do not scope
 * by course, so pages must first prove the id belongs to the current course/session (and the question to the
 * survey). These tests pin the guards doing that. Fixtures: prepared inserts, tag "sa_".
 */
final class SurveyAccessControlTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/survey/survey.php');
        $request->setSession($session);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
    }

    private function survey(): int
    {
        $this->conn->insert('c_survey', [
            'code' => 'sa_code_'.(++$this->seq).uniqid(),
            'title' => 'sa_survey '.$this->seq,
            'lang' => 'english',
            'avail_from' => '2026-01-01 00:00:00',
            'avail_till' => '2026-12-31 00:00:00',
            'is_shared' => '0',
            'template' => 'template',
            'creation_date' => '2026-01-01 00:00:00',
            'invited' => 0,
            'answered' => 0,
            'invite_mail' => '',
            'reminder_mail' => '',
            'mail_subject' => '',
            'anonymous' => '0',
            'shuffle' => 0,
            'one_question_per_page' => 0,
            'survey_version' => '',
            'survey_type' => 0,
            'show_form_profile' => 0,
            'form_fields' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function question(int $surveyId): int
    {
        $this->conn->insert('c_survey_question', [
            'survey_id' => $surveyId,
            'survey_question' => 'sa_question',
            'survey_question_comment' => '',
            'type' => 'yesno',
            'display' => 'vertical',
            'sort' => 1,
            'shared_question_id' => 0,
            'max_value' => 0,
            'survey_group_pri' => 0,
            'survey_group_sec1' => 0,
            'survey_group_sec2' => 0,
            'is_required' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function publish(int $surveyId, int $courseId, ?int $sessionId = null): void
    {
        $typeId = (int) $this->conn->fetchOne("SELECT id FROM resource_type WHERE title = 'surveys'");
        $now = '2020-01-01 00:00:00';
        $this->conn->executeStatement(
            'INSERT INTO resource_node (resource_type_id, creator_id, title, slug, level, path, created_at, updated_at, public, uuid)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?, 0, UNHEX(REPLACE(UUID(), \'-\', \'\')))',
            [$typeId, $this->getUser('admin')->getId(), 'sa_node_'.$surveyId, 'sa-node-'.$surveyId, 'sa-node-'.$surveyId.'/', $now, $now]
        );
        $nodeId = (int) $this->conn->lastInsertId();
        $this->conn->update('c_survey', ['resource_node_id' => $nodeId], ['iid' => $surveyId]);
        $this->conn->insert('resource_link', [
            'resource_node_id' => $nodeId,
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'visibility' => 2,
            'display_order' => 0,
            'resource_type_group' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function session(): int
    {
        $this->conn->insert('session', [
            'title' => 'sa_session_'.(++$this->seq).'_'.uniqid(),
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function testSurveyOfTheSameCourseIsAccepted(): void
    {
        $id = $this->survey();
        $this->publish($id, 1);

        $this->assertTrue(\SurveyManager::surveyBelongsToCourse($id, 1, 0));
    }

    public function testSurveyOfAnotherCourseIsRejected(): void
    {
        $id = $this->survey();
        $this->publish($id, 2);

        $this->assertFalse(\SurveyManager::surveyBelongsToCourse($id, 1, 0), 'a teacher of course 1 must not reach a course 2 survey');
        $this->assertTrue(\SurveyManager::surveyBelongsToCourse($id, 2, 0));
    }

    public function testUnknownEmptyOrUnlinkedIdIsRejected(): void
    {
        $this->assertFalse(\SurveyManager::surveyBelongsToCourse(999999999, 1, 0));
        $this->assertFalse(\SurveyManager::surveyBelongsToCourse(0, 1, 0));
        $this->assertFalse(\SurveyManager::surveyBelongsToCourse($this->survey(), 1, 0), 'no resource link');
        $id = $this->survey();
        $this->publish($id, 1);
        $this->assertFalse(\SurveyManager::surveyBelongsToCourse($id, 0, 0));
    }

    public function testBaseCourseSurveyIsVisibleInASessionButSessionSurveyIsNotInTheBaseCourse(): void
    {
        $sid = $this->session();
        $other = $this->session();
        $base = $this->survey();
        $this->publish($base, 1);
        $inSession = $this->survey();
        $this->publish($inSession, 1, $sid);

        $this->assertTrue(\SurveyManager::surveyBelongsToCourse($base, 1, $sid));
        $this->assertTrue(\SurveyManager::surveyBelongsToCourse($inSession, 1, $sid));
        $this->assertFalse(\SurveyManager::surveyBelongsToCourse($inSession, 1, 0));
        $this->assertFalse(\SurveyManager::surveyBelongsToCourse($inSession, 1, $other));
    }

    public function testQuestionMustBelongToTheSurvey(): void
    {
        $a = $this->survey();
        $b = $this->survey();
        $q = $this->question($a);

        $this->assertTrue(\SurveyManager::surveyQuestionBelongsToSurvey($q, $a));
        $this->assertFalse(\SurveyManager::surveyQuestionBelongsToSurvey($q, $b));
        $this->assertFalse(\SurveyManager::surveyQuestionBelongsToSurvey(0, $a));
        $this->assertFalse(\SurveyManager::surveyQuestionBelongsToSurvey($q, 0));
    }

    private const PLAIN_USER = 2;

    private function teacherOf(int $userId, int $courseId, int $status = 1): void
    {
        $this->conn->insert('course_rel_user', [
            'user_id' => $userId,
            'c_id' => $courseId,
            'relation_type' => 0,
            'status' => $status,
            'progress' => 0,
        ]);
    }

    public function testCopyTargetNeedsTeacherRightsOnTheTargetCourse(): void
    {
        // Course 2 has no teacher row for user 2: the posted target must be refused.
        $this->assertFalse(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 2, 0, false));
        $this->teacherOf(self::PLAIN_USER, 2);
        $this->assertTrue(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 2, 0, false));
        $this->assertFalse(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 1, 0, false), 'teacher of 2 only');
    }

    public function testCopyTargetStudentOfTheCourseIsRefused(): void
    {
        $this->teacherOf(self::PLAIN_USER, 2, 5);

        $this->assertFalse(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 2, 0, false));
    }

    public function testCopyTargetAdminMayCopyAnywhereButNotToUnknownTargets(): void
    {
        $this->assertTrue(\SurveyManager::canCopySurveyTo(1, 2, 0, true));
        $this->assertFalse(\SurveyManager::canCopySurveyTo(1, 999999, 0, true), 'unknown course');
        $this->assertFalse(\SurveyManager::canCopySurveyTo(1, 2, 999999, true), 'session without the course');
        $this->assertFalse(\SurveyManager::canCopySurveyTo(1, 0, 0, true));
    }

    public function testCopyTargetSessionCoachMayCopyIntoHisSessionOnly(): void
    {
        $sid = $this->session();
        $other = $this->session();
        foreach ([$sid, $other] as $id) {
            $this->conn->insert('session_rel_course', ['session_id' => $id, 'c_id' => 2, 'position' => 0, 'nbr_users' => 0]);
        }
        $this->conn->insert('session_rel_course_rel_user', [
            'user_id' => self::PLAIN_USER, 'session_id' => $sid, 'c_id' => 2, 'status' => 2,
            'visibility' => 1, 'legal_agreement' => 0, 'progress' => 0,
        ]);

        $this->assertTrue(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 2, $sid, false));
        $this->assertFalse(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 2, $other, false));
        $this->assertFalse(\SurveyManager::canCopySurveyTo(self::PLAIN_USER, 2, 0, false), 'coach is not course teacher');
    }
}
