<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\UserMergeHelper;
use Chamilo\CoreBundle\Helpers\UserMergeRefusedException;
use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsMaintenanceActionService;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\GenericRowInsertTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use ReflectionClassConstant;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Hardening of the account merge: who may merge whom (actor scope), the teacher-into-student refusal, and the
 * tables that follow the learner. Users are raw "rp_" rows; foreign keys are switched off for the table tests
 * (a session variable, not DDL). Every statement runs inside the DAMA transaction. Tag sv4_.
 */
final class UserMergeHelperHardeningTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use GenericRowInsertTrait;
    use RawReportingFixturesTrait;

    private UserMergeHelper $helper;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->helper = self::getContainer()->get(UserMergeHelper::class);
    }

    private function roleUser(string $suffix, array $roles, ?int $urlId = 1): int
    {
        $id = $this->rawUser($suffix, 5, 1, $urlId);
        $this->conn()->executeStatement('UPDATE user SET roles = ? WHERE id = ?', [json_encode($roles), $id]);
        $this->fresh();

        return $id;
    }

    private function active(int $id): int
    {
        return (int) $this->conn()->fetchOne('SELECT active FROM user WHERE id = ?', [$id]);
    }

    private function rows(string $table, string $field, int|string $value): int
    {
        return (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM `{$table}` WHERE `{$field}` = ?", [$value]);
    }

    // ------------------------------------------------------------------ teacher into student

    /**
     * @dataProvider teacherDirectionProvider
     */
    public function testATeacherIsNeverMergedIntoAStudent(array $sourceRoles, array $targetRoles, bool $allowed): void
    {
        $keep = $this->roleUser('keep', $targetRoles);
        $merge = $this->roleUser('merge', $sourceRoles);

        $this->assertSame($allowed, $this->helper->mergeUsers($keep, $merge));
        $this->assertSame($allowed ? -2 : 1, $this->active($merge));
    }

    public static function teacherDirectionProvider(): iterable
    {
        yield 'teacher into student: refused' => [['ROLE_TEACHER'], [], false];

        yield 'teacher into explicit student: refused' => [['ROLE_TEACHER'], ['ROLE_STUDENT'], false];

        yield 'HR manager (a teacher by hierarchy) into student: refused' => [['ROLE_HR'], [], false];

        yield 'teacher into session manager: refused' => [['ROLE_TEACHER'], ['ROLE_SESSION_MANAGER'], false];

        yield 'student into teacher: allowed' => [[], ['ROLE_TEACHER'], true];

        yield 'teacher into teacher: allowed' => [['ROLE_TEACHER'], ['ROLE_TEACHER'], true];

        yield 'student into student: allowed' => [[], [], true];

        yield 'teacher into admin: allowed' => [['ROLE_TEACHER'], ['ROLE_ADMIN'], true];

        yield 'teacher into HR: allowed' => [['ROLE_TEACHER'], ['ROLE_HR'], true];
    }

    public function testBatchRefusesATeacherIntoStudentWithAClearMessageAndMergesNothing(): void
    {
        $keep = $this->roleUser('keep', []);
        $student = $this->roleUser('stud', []);
        $teacher = $this->roleUser('teach', ['ROLE_TEACHER']);
        $this->conn()->executeStatement("INSERT INTO track_e_login (login_user_id, login_date, user_ip, logout_date) VALUES (?, '2020-01-01 00:00:00', '', '2020-01-01 00:00:00')", [$student]);

        try {
            $this->helper->mergeUsersBatch($keep, [$student, $teacher]);
            $this->fail('A teacher may not be merged into a student');
        } catch (UserMergeRefusedException $e) {
            $this->assertStringContainsString('teacher', $e->getMessage());
            $this->assertStringContainsString('#'.$teacher, $e->getMessage());
        }

        $this->assertSame(1, $this->active($student), 'The whole batch is rolled back');
        $this->assertSame(1, $this->active($teacher));
        $this->assertSame(0, $this->rows('track_e_login', 'login_user_id', $keep));
        $this->assertSame(1, $this->rows('track_e_login', 'login_user_id', $student));
    }

    // ------------------------------------------------------------------ who may merge whom

    public function testASessionAdministratorCannotMergeAnyone(): void
    {
        $actor = $this->roleUser('sess', ['ROLE_SESSION_MANAGER']);
        $keep = $this->roleUser('keep', []);
        $merge = $this->roleUser('merge', []);

        $this->assertFalse($this->helper->mergeUsers($keep, $merge, null, false, $this->entityUser($actor)));
        $this->assertSame(1, $this->active($merge));
    }

    public function testAnAdministratorOfOneUrlCannotMergeUsersOfAnotherUrl(): void
    {
        $other = $this->secondUrl(true);
        $actor = $this->roleUser('adm', ['ROLE_ADMIN'], $other);
        $inKeep = $this->roleUser('keep', [], $other);
        $inMerge = $this->roleUser('merge', [], $other);
        $foreignKeep = $this->roleUser('fkeep', [], 1);
        $foreignMerge = $this->roleUser('fmerge', [], 1);
        $a = $this->entityUser($actor);

        $this->assertFalse($this->helper->mergeUsers($foreignKeep, $foreignMerge, null, false, $a), 'both outside');
        $this->assertFalse($this->helper->mergeUsers($inKeep, $foreignMerge, null, false, $a), 'merged account outside');
        $this->assertFalse($this->helper->mergeUsers($foreignKeep, $inMerge, null, false, $a), 'surviving account outside');
        $this->assertSame(1, $this->active($foreignMerge));
        $this->assertSame(1, $this->active($inMerge));

        $this->assertTrue($this->helper->mergeUsers($inKeep, $inMerge, null, false, $a), 'inside the managed URL');
        $this->assertSame(-2, $this->active($inMerge));
    }

    public function testAGlobalAdministratorOfASubtreeIsConfinedToIt(): void
    {
        $child = $this->secondUrl(true);
        $actor = $this->roleUser('gadm', ['ROLE_GLOBAL_ADMIN'], $child);
        $keep = $this->roleUser('keep', [], $child);
        $merge = $this->roleUser('merge', [], $child);
        $rootUser = $this->roleUser('root', [], 1);
        $a = $this->entityUser($actor);

        $this->assertFalse($this->helper->mergeUsers($keep, $rootUser, null, false, $a));
        $this->assertTrue($this->helper->mergeUsers($keep, $merge, null, false, $a));
    }

    public function testAnUnrestrictedAdministratorMayMergeAnywhere(): void
    {
        $other = $this->secondUrl(true);
        $actor = $this->roleUser('root_adm', ['ROLE_GLOBAL_ADMIN'], 1);
        $keep = $this->roleUser('keep', [], $other);
        $merge = $this->roleUser('merge', [], 1);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge, null, false, $this->entityUser($actor)));
    }

    public function testAnActorCannotMergeAwayTheirOwnAccount(): void
    {
        $actor = $this->roleUser('adm', ['ROLE_ADMIN']);
        $keep = $this->roleUser('keep', ['ROLE_ADMIN']);

        $this->assertFalse($this->helper->mergeUsers($keep, $actor, null, false, $this->entityUser($actor)));
        $this->assertSame(1, $this->active($actor));
    }

    public function testBatchRefusesAnOutOfScopeAccountAndRollsBack(): void
    {
        $other = $this->secondUrl(true);
        $actor = $this->roleUser('adm', ['ROLE_ADMIN'], $other);
        $keep = $this->roleUser('keep', [], $other);
        $inside = $this->roleUser('in', [], $other);
        $outside = $this->roleUser('out', [], 1);

        try {
            $this->helper->mergeUsersBatch($keep, [$inside, $outside], null, false, $this->entityUser($actor));
            $this->fail('Out-of-scope account must be refused');
        } catch (UserMergeRefusedException $e) {
            $this->assertStringContainsString('#'.$outside, $e->getMessage());
        }
        $this->assertSame(1, $this->active($inside), 'rolled back');
    }

    public function testTheMaintenanceActionRefusesWhenThereIsNoAuthenticatedAdministrator(): void
    {
        $a = $this->rawUser('dupa');
        $b = $this->rawUser('dupb');
        foreach ([$a, $b] as $id) {
            $this->conn()->executeStatement("UPDATE user SET firstname = 'Hard-sv4', lastname = 'Pair' WHERE id = ?", [$id]);
        }
        $this->fresh();
        $service = self::getContainer()->get(AdminStatisticsMaintenanceActionService::class);
        self::getContainer()->get('security.token_storage')->setToken(null);

        try {
            $service->runDuplicateAction('unify', 0, $a, 'name', 0);
            $this->fail('Unifying without an authenticated administrator must be refused');
        } catch (AccessDeniedHttpException) {
            $this->assertSame(1, $this->active($b));
        }
    }

    public function testTheMaintenanceActionDoesNotTouchAccountsOutsideTheActorScopeAndSurfacesTheRefusal(): void
    {
        $actor = $this->roleUser('adm', ['ROLE_ADMIN'], 1);
        $keep = $this->roleUser('keep', [], 1);
        $teacher = $this->roleUser('teach', ['ROLE_TEACHER'], 1);
        foreach ([$keep, $teacher] as $id) {
            $this->conn()->executeStatement("UPDATE user SET firstname = 'Hard2-sv4', lastname = 'Pair' WHERE id = ?", [$id]);
        }
        $this->fresh();
        $user = $this->entityUser($actor);
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $service = self::getContainer()->get(AdminStatisticsMaintenanceActionService::class);

        try {
            $service->runDuplicateAction('unify', 0, $keep, 'name', 0);
            $this->fail('A teacher may not be unified into a student');
        } catch (HttpExceptionInterface $e) {
            $this->assertStringContainsString('teacher', strtolower($e->getMessage()));
        }
        $this->assertSame(1, $this->active($teacher));
    }

    // ------------------------------------------------------------------ the tables

    /**
     * Every column that references a user must be decided: merged by a rule, or declared as intentionally left on the
     * merged account. A new table with a foreign key to user fails here until somebody decides.
     */
    public function testEveryForeignKeyToUserIsEitherMergedOrIntentionallyLeft(): void
    {
        $covered = [];
        foreach (['getUpdateRules' => 'field', 'getDeleteRules' => 'field', 'getPivotMergeRules' => 'userField', 'getMoveKeepingTargetRules' => 'userField'] as $method => $key) {
            foreach ((new ReflectionMethod(UserMergeHelper::class, $method))->invoke($this->helper) as $rule) {
                $covered[$rule['table'].'.'.$rule[$key]] = true;
            }
        }
        foreach ((new ReflectionMethod(UserMergeHelper::class, 'getSpecialRules'))->invoke($this->helper) as $entry) {
            $covered[$entry] = true;
        }
        $left = (new ReflectionClassConstant(UserMergeHelper::class, 'LEFT_ON_MERGED_ACCOUNT'))->getValue();
        $this->assertGreaterThan(10, \count($left));
        $this->assertSame([], array_values(array_intersect(array_keys($covered), $left)), 'A column cannot be both merged and left');

        $columns = $this->conn()->fetchFirstColumn(
            "SELECT CONCAT(table_name, '.', column_name) FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE() AND referenced_table_name = 'user'
             ORDER BY 1"
        );
        $undecided = array_values(array_filter($columns, static fn (string $c): bool => !isset($covered[$c]) && !\in_array($c, $left, true)));
        $this->assertSame([], $undecided, 'Columns referencing user that the merge neither moves nor declares left');
    }

    public function testCredentialsAndPrivateConversationsAreNeverMerged(): void
    {
        $left = (new ReflectionClassConstant(UserMergeHelper::class, 'LEFT_ON_MERGED_ACCOUNT'))->getValue();
        foreach (['user_auth_source.user_id', 'oauth_access_token.user_id', 'oauth_refresh_token.user_id', 'oauth_authorization_code.user_id', 'reset_password_request.user_id', 'push_subscription.user_id', 'user_api_key.user_id', 'ai_tutor_conversation.user_id', 'templates.user_id'] as $c) {
            $this->assertContains($c, $left, $c);
        }
    }

    public function testNewlyMergedTablesAreDeclared(): void
    {
        $update = array_map(static fn (array $r): string => $r['table'].'.'.$r['field'], (new ReflectionMethod(UserMergeHelper::class, 'getUpdateRules'))->invoke($this->helper));
        foreach ([
            'resource_tag.author_id', 'resource_user_tag.user_id', 'c_survey_template.created_by_id', 'c_toolbox_version.created_by_id',
            'mail_template.author_id', 'page_layout.created_by', 'page_layout.updated_by', 'support_bot_tree.created_by_id',
            'transcript.user_id', 'transcript.issued_by_id', 'user_career.user_id', 'justification_document_rel_users.user_id',
            'gradebook_certificate_expiry_notification.sent_by_user_id', 'gradebook_linkeval_log.user_id_log', 'c_attendance_sheet_log.lastedit_user_id',
            'skill_rel_user_comment.feedback_giver_id', 'skill_rel_user.last_status_update_user_id', 'conference_activity.participant_id',
            'conference_meeting.user_id', 'xapi_internal_log.user_id', 'automatic_unregistration_log.user_id', 'user_activity_status_archive.user_id',
            'third_party_data_exchange_user.user_id', 'c_peer_assessment_log.user_id', 'c_peer_autogroup_rel_student_publication.user_id',
            'c_attendance_result_comment.user_id', 'c_attendance_result_comment.author_user_id', 'c_dropbox_category.user_id',
            'c_dropbox_feedback.author_user_id', 'c_dropbox_file.uploader_id', 'c_dropbox_person.user_id', 'c_dropbox_post.dest_user_id',
            'c_forum_mailcue.user_id', 'c_forum_notification.user_id', 'c_wiki.user_id', 'c_wiki_mailcue.user_id', 'c_chat_connected.user_id',
            'exercise_attempt_recycle_bin.user_id', 'learning_path_tracking_recycle_bin.user_id', 'hotpotatoes_attempt.user_id',
            'hotpotatoes_attempt_review.reviewed_by_user_id', 'track_e_attempt_qualify.author', 'usergroup.author_id', 'user.creator_id',
            'social_post_attachments.sys_insert_user_id', 'social_post_attachments.sys_lastedit_user_id', 'ticket_ticket.sys_insert_user_id',
            'ticket_ticket.sys_lastedit_user_id', 'ai_requests.user_id', 'welfare_report.target_user_id',
        ] as $entry) {
            $this->assertContains($entry, $update);
        }
    }

    public function testResourceLinkMovesKeepingItsIdAndALinkTheTargetAlreadyHasStaysBehind(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $this->insert('resource_link', ['resource_node_id' => 900001, 'user_id' => $keep, 'c_id' => 1, 'session_id' => null, 'group_id' => null, 'usergroup_id' => null]);
        $this->insert('resource_link', ['resource_node_id' => 900001, 'user_id' => $merge, 'c_id' => 1, 'session_id' => null, 'group_id' => null, 'usergroup_id' => null]);
        $this->insert('resource_link', ['resource_node_id' => 900002, 'user_id' => $merge, 'c_id' => 1, 'session_id' => null, 'group_id' => null, 'usergroup_id' => null]);
        $free = (int) $this->conn()->fetchOne('SELECT id FROM resource_link WHERE resource_node_id = 900002');

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame($keep, (int) $this->conn()->fetchOne('SELECT user_id FROM resource_link WHERE id = ?', [$free]), 'moves, keeping its id (resource_right rows reference it)');
        $this->assertSame(2, $this->rows('resource_link', 'user_id', $keep));
        $this->assertSame(1, $this->rows('resource_link', 'user_id', $merge), 'the duplicate of a link the target has stays on the merged account');
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testBlogTaskAssignmentsAndCourseVotesKeepTheTargetsOnConflict(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $same = ['task_id' => 5, 'blog_id' => 6, 'target_date' => '2020-02-02'];
        $this->insert('c_blog_task_rel_user', ['user_id' => $keep] + $same);
        $this->insert('c_blog_task_rel_user', ['user_id' => $merge] + $same);
        $this->insert('c_blog_task_rel_user', ['user_id' => $merge, 'task_id' => 5, 'blog_id' => 6, 'target_date' => '2020-03-03']);
        $this->insert('user_rel_course_vote', ['user_id' => $keep, 'c_id' => 1, 'session_id' => 0, 'url_id' => 1, 'vote' => 4]);
        $this->insert('user_rel_course_vote', ['user_id' => $merge, 'c_id' => 1, 'session_id' => 0, 'url_id' => 1, 'vote' => 1]);
        $this->insert('user_rel_course_vote', ['user_id' => $merge, 'c_id' => 2, 'session_id' => 0, 'url_id' => 1, 'vote' => 2]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame(2, $this->rows('c_blog_task_rel_user', 'user_id', $keep));
        $this->assertSame(0, $this->rows('c_blog_task_rel_user', 'user_id', $merge));
        $this->assertSame([1 => 4, 2 => 2], array_map('intval', $this->conn()->fetchAllKeyValue('SELECT c_id, vote FROM user_rel_course_vote WHERE user_id = ? ORDER BY c_id', [$keep])), 'the target vote wins');
        $this->assertSame(0, $this->rows('user_rel_course_vote', 'user_id', $merge));
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testSurveyAnswersFollowTheLearnerOnlyWhereTheTargetHasNotAnsweredTheSameSurvey(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $anonymous = md5('anonymous-sv4');
        foreach ([[701, $keep], [701, $merge], [702, $merge], [703, $anonymous]] as [$survey, $who]) {
            $this->insert('c_survey_answer', ['survey_id' => $survey, 'question_id' => 1, 'user' => (string) $who, 'option_id' => '1', 'value' => 1, 'c_lp_item_id' => 0]);
        }

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame([701, 702], array_map('intval', $this->conn()->fetchFirstColumn('SELECT survey_id FROM c_survey_answer WHERE user = ? ORDER BY survey_id', [(string) $keep])), 'answers of the surveys the target did not answer follow the learner');
        $this->assertSame([701], array_map('intval', $this->conn()->fetchFirstColumn('SELECT survey_id FROM c_survey_answer WHERE user = ?', [(string) $merge])), 'a survey both answered keeps the target answers only');
        $this->assertSame(1, $this->rows('c_survey_answer', 'user', $anonymous), 'anonymous respondents (a hash, not an id) are never touched');
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * End to end on a fixture pair: every update rule (new and old) re-pointed, the row count of every table involved
     * is unchanged (nothing deleted or created by an update), and a bystander keeps every one of its rows.
     */
    public function testEndToEndEveryUpdateRuleMovesOnlyTheMergedAccountAndNoRowCountChanges(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $bystander = $this->rawUser('bystander');
        $rules = (new ReflectionMethod(UserMergeHelper::class, 'getUpdateRules'))->invoke($this->helper);

        $n = 0;
        foreach ($rules as $rule) {
            // A unique key that does not involve the user column: give each row its own value.
            $unique = 'c_toolbox_version' === $rule['table'] ? ['version_number' => ++$n] : [];
            $extra = 'user' === $rule['table'] ? ['roles' => '[]', 'username_canonical' => 'sv4u'.(++$n), 'username' => 'sv4u'.$n, 'email' => 'sv4u'.$n.'@x.invalid', 'email_canonical' => 'sv4u'.$n.'@x.invalid'] : [];
            $this->insert($rule['table'], [$rule['field'] => $merge] + $unique + $extra);
            $extra2 = $extra ? ['username_canonical' => 'sv4u'.(++$n), 'username' => 'sv4u'.$n, 'email' => 'sv4u'.$n.'@x.invalid', 'email_canonical' => 'sv4u'.$n.'@x.invalid'] + $extra : [];
            $this->insert($rule['table'], [$rule['field'] => $bystander] + ($unique ? ['version_number' => ++$n] : []) + $extra2);
        }
        $count = fn (): array => array_map(
            fn (string $t): int => (int) $this->conn()->fetchOne("SELECT COUNT(*) FROM `{$t}`"),
            array_combine($tables = array_values(array_unique(array_column($rules, 'table'))), $tables)
        );
        $bystanderRows = fn (): array => array_map(
            fn (array $r): int => $this->rows($r['table'], $r['field'], $bystander),
            array_combine(array_map(static fn (array $r): string => $r['table'].'.'.$r['field'], $rules), $rules)
        );
        $before = $count();
        $bystanderBefore = $bystanderRows();

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame($before, $count());
        $this->assertSame($bystanderBefore, $bystanderRows());
        foreach ($rules as $rule) {
            $this->assertSame(0, $this->rows($rule['table'], $rule['field'], $merge), $rule['table'].'.'.$rule['field']);
        }
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testTheSurvivorsCanonicalIdentityIsUntouched(): void
    {
        $keep = $this->rawUser('keep');
        $merge = $this->rawUser('merge');
        $before = $this->conn()->fetchAssociative('SELECT username, username_canonical, email, email_canonical, password, roles FROM user WHERE id = ?', [$keep]);

        $this->assertTrue($this->helper->mergeUsers($keep, $merge));

        $this->assertSame($before, $this->conn()->fetchAssociative('SELECT username, username_canonical, email, email_canonical, password, roles FROM user WHERE id = ?', [$keep]));
    }
}
