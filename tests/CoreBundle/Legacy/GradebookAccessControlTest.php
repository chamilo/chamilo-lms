<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use AbstractLink;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use GradebookUtils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Uid\UuidV4;

/**
 * Access control of the legacy gradebook pages (public/main/gradebook, inc/ajax/gradebook.ajax.php).
 *
 * The pages take category/evaluation/link/result/certificate ids from the request and used to read or write
 * them whatever their course. GradebookUtils::{category,evaluation,link,result,certificate}BelongsToCourse()
 * are the guard predicates (tested here for behaviour); the pages call them through
 * protectElementInCurrentCourse(), which ends the request (api_not_allowed) and therefore is pinned at source
 * level. Fixtures (prefix "ga_") are prepared inserts on courses 1/2.
 */
final class GradebookAccessControlTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private const GRADEBOOK_DIR = __DIR__.'/../../../public/main/gradebook/';

    private Connection $conn;
    private int $seq = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/gradebook/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $_GET = [];
        $_POST = [];
        ChamiloSession::write('_user', ['id' => 1, 'user_id' => 1]);
        Container::getEntityManager()->clear();
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    private function cat(int $courseId): int
    {
        $this->conn->insert('gradebook_category', [
            'title' => 'ga_cat_'.(++$this->seq),
            'description' => '',
            'user_id' => 1,
            'c_id' => $courseId,
            'parent_id' => null,
            'session_id' => null,
            'weight' => 100,
            'visible' => 1,
            'locked' => 0,
            'generate_certificates' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function evaluation(int $categoryId, int $courseId): int
    {
        $this->conn->insert('gradebook_evaluation', [
            'c_id' => $courseId,
            'category_id' => $categoryId,
            'title' => 'ga_eval_'.(++$this->seq),
            'description' => '',
            'created_at' => '2020-01-01 00:00:00',
            'weight' => 10,
            'max' => 20,
            'visible' => 1,
            'type' => 'evaluation',
            'locked' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $categoryId, int $courseId): int
    {
        $this->conn->insert('gradebook_link', [
            'ref_id' => 5, // an existing resource node (offset 5+), the table has a foreign key on it
            'c_id' => $courseId,
            'category_id' => $categoryId,
            'type' => 1,
            'created_at' => '2020-01-01 00:00:00',
            'weight' => 10,
            'visible' => 1,
            'locked' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function user(int $status = 5): int
    {
        $name = 'ga_user_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname, official_code)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', ?, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', 'F', 'L', ?)",
            [$name, $name, $name.'@example.com', $name.'@example.com', $status, UuidV4::v4()->toBinary(), 'GA'.$this->seq]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function subscribe(int $userId, int $courseId, int $status): void
    {
        $this->conn->executeStatement(
            'INSERT INTO course_rel_user (user_id, c_id, status, relation_type, sort, user_course_cat, is_tutor, progress)
             VALUES (?, ?, ?, 0, 0, 0, 0, 0)',
            [$userId, $courseId, $status]
        );
    }

    public function testCategoryBelongsToCourseOnlyForItsOwnCourse(): void
    {
        $inOne = $this->cat(1);
        $inTwo = $this->cat(2);

        $this->assertTrue(GradebookUtils::categoryBelongsToCourse($inOne, 1));
        $this->assertFalse(GradebookUtils::categoryBelongsToCourse($inOne, 2), 'a teacher of course 2 must not reach it');
        $this->assertTrue(GradebookUtils::categoryBelongsToCourse($inTwo, 2));
        $this->assertFalse(GradebookUtils::categoryBelongsToCourse($inTwo, 1));
    }

    public function testNoGuardAcceptsAnEmptyIdOrAnEmptyCourse(): void
    {
        $cat = $this->cat(1);
        $eval = $this->evaluation($cat, 1);
        $link = $this->link($cat, 1);

        $this->assertFalse(GradebookUtils::categoryBelongsToCourse(0, 1));
        $this->assertFalse(GradebookUtils::categoryBelongsToCourse($cat, 0), 'no course context is never a match');
        $this->assertFalse(GradebookUtils::evaluationBelongsToCourse($eval, 0));
        $this->assertFalse(GradebookUtils::linkBelongsToCourse($link, 0));
        $this->assertFalse(GradebookUtils::categoryBelongsToCourse(2000000000, 1));
        $this->assertFalse(GradebookUtils::evaluationBelongsToCourse(-3, 1));
    }

    public function testEvaluationAndLinkBelongToTheCourseTheyWereCreatedIn(): void
    {
        $cat = $this->cat(1);
        $eval = $this->evaluation($cat, 1);
        $foreignEval = $this->evaluation($cat, 2);
        $link = $this->link($cat, 1);
        $foreignLink = $this->link($cat, 2);

        $this->assertTrue(GradebookUtils::evaluationBelongsToCourse($eval, 1));
        $this->assertFalse(GradebookUtils::evaluationBelongsToCourse($eval, 2));
        $this->assertTrue(GradebookUtils::evaluationBelongsToCourse($foreignEval, 2));
        $this->assertFalse(GradebookUtils::evaluationBelongsToCourse($foreignEval, 1));
        $this->assertTrue(GradebookUtils::linkBelongsToCourse($link, 1));
        $this->assertFalse(GradebookUtils::linkBelongsToCourse($link, 2));
        $this->assertTrue(GradebookUtils::linkBelongsToCourse($foreignLink, 2));
        $this->assertFalse(GradebookUtils::linkBelongsToCourse($foreignLink, 1));
    }

    public function testResultBelongsToTheCourseOfItsEvaluation(): void
    {
        $cat = $this->cat(1);
        $eval = $this->evaluation($cat, 1);
        $this->conn->insert('gradebook_result', [
            'evaluation_id' => $eval,
            'user_id' => 1,
            'score' => 12,
            'created_at' => '2020-01-01 00:00:00',
        ]);
        $resultId = (int) $this->conn->lastInsertId();

        $this->assertTrue(GradebookUtils::resultBelongsToCourse($resultId, 1));
        $this->assertFalse(GradebookUtils::resultBelongsToCourse($resultId, 2));
        $this->assertFalse(GradebookUtils::resultBelongsToCourse(0, 1));
    }

    public function testCertificateBelongsToTheCourseOfItsCategory(): void
    {
        $cat = $this->cat(1);
        $this->conn->insert('gradebook_certificate', [
            'cat_id' => $cat,
            'user_id' => 1,
            'score_certificate' => 50,
            'created_at' => '2020-01-01 00:00:00',
            'publish' => 0,
        ]);
        $certificateId = (int) $this->conn->lastInsertId();

        $this->assertTrue(GradebookUtils::certificateBelongsToCourse($certificateId, 1));
        $this->assertFalse(GradebookUtils::certificateBelongsToCourse($certificateId, 2), 'teacher of another course cannot delete it');
        $this->assertFalse(GradebookUtils::certificateBelongsToCourse($certificateId, 0));
    }

    public function testTaughtCoursesConditionKeepsOnlyTaughtOrCoachedCourses(): void
    {
        $cat = $this->cat(1);
        $this->evaluation($cat, 1);
        $teacher = $this->user(1);
        $this->subscribe($teacher, 1, 1);
        $learner = $this->user(5);
        $this->subscribe($learner, 1, 5);
        $coach = $this->user(1);
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['ga_session']
        );
        $sessionId = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            'INSERT INTO session_rel_course_rel_user (session_id, c_id, user_id, status, visibility, legal_agreement, progress)
             VALUES (?, 2, ?, 2, 1, 0, 0)',
            [$sessionId, $coach]
        );
        $count = fn (int $userId, int $courseId): int => (int) $this->conn->fetchOne(
            'SELECT COUNT(*) FROM gradebook_evaluation WHERE c_id = ? AND '.GradebookUtils::taughtCoursesCondition($userId),
            [$courseId]
        );

        $this->assertSame(1, $count($teacher, 1));
        $this->assertSame(0, $count($teacher, 2), 'a course the user does not teach');
        $this->assertSame(0, $count($learner, 1), 'a learner teaches nothing');
        $this->assertSame(0, $count($coach, 1));
        $this->evaluation($cat, 2);
        $this->assertSame(1, $count($coach, 2), 'the coach of a session course');
        $this->assertSame(0, $count($this->user(1), 1));
    }

    public function testLinkLoadWithAUserSelectsTheLinksOfTheCoursesTheUserTeaches(): void
    {
        // AbstractLink::load() filtered on a user_id column that gradebook_link no longer has and threw.
        $cat = $this->cat(1);
        $taught = $this->link($cat, 1);
        $other = $this->link($cat, 2);
        $teacher = $this->user(1);
        $this->subscribe($teacher, 1, 1);

        $ids = array_map(static fn (AbstractLink $l): int => (int) $l->get_id(), AbstractLink::load(null, null, null, $teacher, null, $cat));
        $this->assertSame([$taught], $ids);
        $this->assertNotContains($other, $ids);
        $this->assertSame([], AbstractLink::load(null, null, null, $this->user(5), null, $cat));
    }

    /**
     * @dataProvider pageGuards
     *
     * @param string[] $needles
     */
    public function testPagesGuardTheIdsOfTheRequest(string $page, array $needles): void
    {
        $source = (string) file_get_contents(self::GRADEBOOK_DIR.$page);
        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $source, $page.' must contain '.$needle);
        }
    }

    /**
     * The pages are procedural and end the request on denial: pin the guards at source level.
     *
     * @return iterable<string, array{string, string[]}>
     */
    public static function pageGuards(): iterable
    {
        $cat = 'protectElementInCurrentCourse(\'CATE\'';
        $eval = 'protectElementInCurrentCourse(\'EVAL\'';
        $link = 'protectElementInCurrentCourse(\'LINK\'';

        yield 'index lock/unlock/delete/move/stats' => ['index.php', [$cat, $eval, $link, 'GradebookUtils::block_students();']];

        yield 'edit category' => ['gradebook_edit_cat.php', [$cat, "\$values['hid_id']"]];

        yield 'add category' => ['gradebook_add_cat.php', [$cat]];

        yield 'add evaluation' => ['gradebook_add_eval.php', [$cat, 'categoryBelongsToCourse']];

        yield 'edit evaluation' => ['gradebook_edit_eval.php', [$eval, $cat]];

        yield 'add link' => ['gradebook_add_link.php', [$cat]];

        yield 'edit link' => ['gradebook_edit_link.php', [$link, $cat]];

        yield 'add result' => ['gradebook_add_result.php', [$eval, "\$values['evaluation_id'] = \$selectEval"]];

        yield 'edit result' => ['gradebook_edit_result.php', [$eval]];

        yield 'edit all weights' => ['gradebook_edit_all.php', [$cat]];

        yield 'scoring system' => ['gradebook_scoring_system.php', [$cat]];

        yield 'log evaluation' => ['gradebook_showlog_eval.php', [$eval]];

        yield 'log link' => ['gradebook_showlog_link.php', [$link]];

        yield 'flat view' => ['gradebook_flatview.php', [$cat]];

        yield 'view result' => ['gradebook_view_result.php', [$eval, 'check_token(\'get\')', 'GradebookUtils::block_students();']];

        yield 'statistics' => ['gradebook_statistics.php', [$eval, 'api_protect_course_script(true)']];

        yield 'user stats' => ['user_stats.php', [$cat, 'api_protect_course_script(true)']];

        yield 'summary' => ['gradebook_display_summary.php', [$cat]];

        yield 'personal stats' => ['personal_stats.php', [$cat, 'api_protect_course_script(true)', 'api_block_anonymous_users']];

        yield 'certificates' => ['gradebook_display_certificate.php', [$cat, 'certificateBelongsToCourse', '$isBossOnly']];
    }

    public function testAjaxActionsGuardTheCategoryAndTheArchiveLookupIsRestricted(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../public/main/inc/ajax/gradebook.ajax.php');
        $this->assertSame(4, substr_count($source, "protectElementInCurrentCourse('CATE'"), 'comment, weight, export, verify');
        $verify = substr($source, (int) strpos($source, "case 'verify_export_all_certificates'"));
        $this->assertStringContainsString('api_is_allowed_to_edit', $verify);
        $this->assertStringNotContainsString("\$_GET['cidReq']", $verify, 'the archive of another course cannot be asked for');
    }

    public function testDestructiveGetLinksCarryAToken(): void
    {
        $this->assertStringContainsString('sec_token=', (string) file_get_contents(self::GRADEBOOK_DIR.'lib/fe/resulttable.class.php'));
        $this->assertStringContainsString('sec_token=', (string) file_get_contents(self::GRADEBOOK_DIR.'lib/fe/displaygradebook.php'));
    }
}
