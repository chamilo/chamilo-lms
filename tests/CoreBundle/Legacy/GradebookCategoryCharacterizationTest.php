<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Category;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Evaluation;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\UuidV4;

/**
 * Characterization of the raw-SQL half of the legacy gradebook Category class
 * (public/main/gradebook/lib/be/category.class.php) and of the SQL of Evaluation::load()/delete*, which the
 * category tree walks through.
 *
 * LIVE (called from public/main/gradebook/*.php, certificate.lib.php, userportal.lib.php, plugins): load,
 * loadSessionCategories, add, save, delete, delete_all, show_message_resource_delete, showAllCategoryInfo,
 * getAllowSkillBySubCategory, get_subcategories (+ get_root_categories_for_student/_teacher it delegates to),
 * get_evaluations, get_links, get_target_categories, move_to_cat, get_tree, get_not_created_course_categories,
 * get_all_courses, apply_visibility_to_children, lock/lockAllItems, getCurrentScore/registerCurrentScore.
 * DEAD (no caller anywhere): updateChildrenWeight, findByCertificate, getCategories($catId) (marked @deprecated).
 *
 * Fixtures (title prefix "gc_") are raw INSERTs, courses 1/2, users 1 (admin) and 2 (anonymous); the table may
 * hold rows of other suites, so every assertion filters on the ids it created. Not covered: certificate
 * generation (PDF/Twig), calc_score() (needs real exercises/links) and the Doctrine-only getters.
 */
final class GradebookCategoryCharacterizationTest extends KernelTestCase
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
        $httpSession = new HttpSession(new MockArraySessionStorage());
        Container::setSession($httpSession);
        $request = Request::create('/main/gradebook/index.php');
        $request->setSession($httpSession);
        Container::setRequest($request);
        $this->conn = $container->get('doctrine')->getConnection();
        $_GET = [];
        $_POST = [];
        $this->context(1, 1);
    }

    protected function tearDown(): void
    {
        ChamiloSession::erase('_user');
        ChamiloSession::erase('sid');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('_course');
        $_GET = [];
        $_POST = [];
        self::getContainer()->get('security.token_storage')->setToken(null);
        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function context(int $userId, ?int $courseId, int $sessionId = 0): void
    {
        ChamiloSession::write('_user', ['id' => $userId, 'user_id' => $userId]);
        if (null !== $courseId) {
            $code = 1 === $courseId ? 'AIACT' : 'USINGCHAMILO';
            ChamiloSession::write('_real_cid', $courseId);
            ChamiloSession::write('_cid', $code);
            ChamiloSession::write('_course', api_get_course_info($code));
        } else {
            ChamiloSession::erase('_real_cid');
            ChamiloSession::erase('_cid');
            ChamiloSession::erase('_course');
        }
        ChamiloSession::write('sid', $sessionId);
        ChamiloSession::write('gid', 0);
        ChamiloSession::erase('studentview');
        Container::getEntityManager()->clear();
        $user = $this->getUser(1 === $userId ? 'admin' : 'anon');
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', $user->getRoles())
        );
    }

    private function setSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    private function session(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO session (title, nbr_courses, nbr_users, nbr_classes, visibility, status, position)
             VALUES (?, 0, 0, 0, 1, 0, 0)',
            ['gc_session_'.(++$this->seq)]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function cat(array $o = []): int
    {
        $this->conn->insert('gradebook_category', $o + [
            'title' => 'gc_cat_'.(++$this->seq),
            'description' => 'desc',
            'user_id' => 1,
            'c_id' => 1,
            'parent_id' => null,
            'session_id' => null,
            'weight' => 100,
            'visible' => 1,
            'locked' => 0,
            'generate_certificates' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    private function evaluation(int $categoryId, array $o = []): int
    {
        $this->conn->insert('gradebook_evaluation', $o + [
            'c_id' => 1,
            'category_id' => $categoryId,
            'title' => 'gc_eval_'.(++$this->seq),
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

    private function user(int $status = 5): int
    {
        $name = 'gc_user_'.(++$this->seq).'_'.substr(md5((string) microtime(true)), 0, 6);
        $this->conn->executeStatement(
            "INSERT INTO user (username, username_canonical, locale, password, timezone, email, email_canonical,
                locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at,
                firstname, lastname, official_code)
             VALUES (?, ?, 'en_US', 'x', 'UTC', ?, ?, 0, 0, 0, 'x', '[]', ?, 1, ?,
                '2001-01-01 00:00:00', '2001-01-01 00:00:00', 'F', 'L', ?)",
            [$name, $name, $name.'@example.com', $name.'@example.com', $status, UuidV4::v4()->toBinary(), 'GC'.$this->seq]
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

    /**
     * @param Category[] $cats
     *
     * @return int[]
     */
    private function ids(array $cats): array
    {
        return array_map(static fn (Category $c): int => (int) $c->get_id(), $cats);
    }

    private function row(int $id): array|false
    {
        return $this->conn->fetchAssociative('SELECT * FROM gradebook_category WHERE id = ?', [$id]);
    }

    /**
     * What a student/anonymous user sees: no token user rights, not a course editor.
     */
    private function asStudent(): void
    {
        $this->context(2, 1);
    }

    // ---------------------------------------------------------------- load()

    public function testLoadByIdMapsEveryColumnOntoTheObject(): void
    {
        $id = $this->cat([
            'title' => 'gc_full',
            'description' => 'gc_description',
            'weight' => 42.5,
            'visible' => 0,
            'locked' => 1,
            'certif_min_score' => 60,
            'generate_certificates' => 1,
            'is_requirement' => 1,
            'allow_skills_by_subcategory' => 0,
            'calculation_mode' => 'weighted_average',
        ]);

        $cats = Category::load($id);

        $this->assertCount(1, $cats);
        $c = $cats[0];
        $this->assertSame($id, (int) $c->get_id());
        $this->assertSame('gc_full', $c->get_name());
        $this->assertSame('gc_description', $c->get_description());
        $this->assertSame(1, (int) $c->get_user_id());
        $this->assertSame(1, (int) $c->getCourseId());
        $this->assertSame('AIACT', $c->get_course_code());
        $this->assertEquals(42.5, $c->get_weight());
        $this->assertSame(0, (int) $c->is_visible());
        $this->assertSame(1, (int) $c->is_locked());
        $this->assertSame(60, (int) $c->getCertificateMinScore());
        $this->assertSame(1, (int) $c->getGenerateCertificates());
        $this->assertSame(1, (int) $c->getIsRequirement());
        $this->assertFalse($c->getAllowSkillBySubCategory($id), 'column value 0 means "not allowed"');
        // PINNED QUIRK: parent NULL (how add() stores a root category) is seen as parent 0.
        $this->assertSame(0, (int) $c->get_parent_id());
    }

    public function testLoadWithIdZeroBuildsTheInMemoryRootAndNeverTouchesTheDatabase(): void
    {
        $cats = Category::load(0);

        $this->assertCount(1, $cats);
        $this->assertSame(0, $cats[0]->get_id());
        $this->assertSame(0, (int) $cats[0]->get_weight());
        $this->assertSame(1, (int) $cats[0]->is_visible());
        $this->assertSame([], Category::load(2000000000));
    }

    public function testLoadFiltersByCourseUserParentAndVisibility(): void
    {
        $root = $this->cat();
        $child = $this->cat(['parent_id' => $root, 'visible' => 0]);
        $childVisible = $this->cat(['parent_id' => $root]);
        $otherCourse = $this->cat(['c_id' => 2, 'user_id' => 1]);
        $independent = $this->cat(['c_id' => null]);

        $inCourse1 = $this->ids(Category::load(null, null, 1, null, null, 0));
        $this->assertContains($root, $inCourse1);
        $this->assertContains($child, $inCourse1);
        $this->assertNotContains($otherCourse, $inCourse1, 'c_id filter');
        $this->assertNotContains($independent, $inCourse1, 'a course-independent category has c_id NULL');

        $this->assertSame([$child, $childVisible], $this->ids(Category::load(null, null, 1, $root, null, 0)));
        $this->assertSame([$childVisible], $this->ids(Category::load(null, null, 1, $root, 1, 0)));
        $this->assertSame([$child], $this->ids(Category::load(null, null, 1, $root, 0, 0)), 'visible = 0 is a real filter');
        $this->assertContains($otherCourse, $this->ids(Category::load(null, 1, 2, null, null, 0)));
        $this->assertNotContains($root, $this->ids(Category::load(null, 999999, 0, null, null, 0)));
    }

    public function testLoadWithoutIdOnlyReturnsBaseCourseRowsUnlessASessionIsGiven(): void
    {
        $sid = $this->session();
        $base = $this->cat();
        $inSession = $this->cat(['session_id' => $sid]);
        $zeroSession = $this->cat(['session_id' => null]);

        $withoutSession = $this->ids(Category::load(null, null, 1, null, null, 0));
        $this->assertContains($base, $withoutSession);
        $this->assertContains($zeroSession, $withoutSession);
        $this->assertNotContains($inSession, $withoutSession);

        $this->assertSame([$inSession], $this->ids(Category::load(null, null, 1, null, null, $sid)));

        // session_id = null falls back to the session of the current request
        $this->context(1, 1, $sid);
        $this->assertSame([$inSession], $this->ids(Category::load(null, null, 1)));
    }

    public function testLoadByIdIgnoresTheSessionOnlyWhenNoSessionIsActive(): void
    {
        $sid = $this->session();
        $inSession = $this->cat(['session_id' => $sid]);
        $base = $this->cat();

        $this->assertSame([$inSession], $this->ids(Category::load($inSession, null, 0, null, null, 0)), 'no session: id is enough');
        // PINNED QUIRK: inside a session a base-course category cannot be loaded by its id (session_id = X is forced).
        $this->assertSame([], Category::load($base, null, 0, null, null, $sid));
        $this->assertSame([$inSession], $this->ids(Category::load($inSession, null, 0, null, null, $sid)));
    }

    public function testLoadAppliesTheOrderByTail(): void
    {
        $root = $this->cat();
        $b = $this->cat(['parent_id' => $root, 'title' => 'gc_b']);
        $a = $this->cat(['parent_id' => $root, 'title' => 'gc_a']);

        $this->assertSame([$a, $b], $this->ids(Category::load(null, null, 1, $root, null, 0, 'ORDER BY title ASC')));
        $this->assertSame([$b, $a], $this->ids(Category::load(null, null, 1, $root, null, 0, 'ORDER BY title DESC')));
    }

    public function testLoadSessionCategoriesKeepsTheSessionCategoriesOfTheCurrentCourse(): void
    {
        $sid = $this->session();
        $mine = $this->cat(['session_id' => $sid, 'c_id' => 1]);
        $theirs = $this->cat(['session_id' => $sid, 'c_id' => 2]);

        $found = $this->ids(Category::loadSessionCategories(null, $sid));

        $this->assertContains($mine, $found);
        $this->assertNotContains($theirs, $found, 'only the course of the request');
        $this->assertSame(0, Category::loadSessionCategories(0)[0]->get_id(), 'explicit 0 is the root');
        $this->assertNull(Category::loadSessionCategories(null, 0), 'no session: nothing (null, not [])');
    }

    // ---------------------------------------------------------------- add / save / delete

    public function testAddPersistsARootCategoryWithANullParent(): void
    {
        $c = new Category();
        $c->set_name('gc_added');
        $c->set_description('gc_added_description');
        $c->set_user_id(1);
        $c->setCourseId(1);
        $c->set_parent_id(0);
        $c->set_weight(37);
        $c->set_visible(1);
        $c->set_session_id(0);
        $c->setGenerateCertificates(1);
        $c->set_certificate_min_score(55);
        $c->setIsRequirement(0);

        $id = $c->add();

        $this->assertGreaterThan(0, $id);
        $this->assertSame($id, (int) $c->get_id());
        $row = $this->row($id);
        $this->assertSame('gc_added', $row['title']);
        $this->assertSame('gc_added_description', $row['description']);
        $this->assertSame('1', (string) $row['c_id']);
        $this->assertNull($row['parent_id'], 'a root is stored with parent NULL, never 0');
        $this->assertEquals(37, $row['weight']);
        $this->assertSame('1', (string) $row['visible']);
        $this->assertSame('1', (string) $row['generate_certificates']);
        $this->assertSame('55', (string) $row['certif_min_score']);
        $this->assertSame('0', (string) $row['locked']);
        $this->assertSame('weighted_average', $row['calculation_mode']);
    }

    public function testAddRefusesTheMinusOneNameAndANameWithoutUser(): void
    {
        $c = new Category();
        $c->set_name('-1');
        $c->set_user_id(1);
        $this->assertFalse($c->add());

        // PINNED QUIRK: without set_user_id() the category is still inserted (the owner falls back to the current user).
        $d = new Category();
        $d->set_name('gc_no_user');
        $d->setCourseId(1);
        $d->set_weight(1);
        $d->set_visible(1);
        $d->set_session_id(0);
        $d->setGenerateCertificates(0);
        $d->setIsRequirement(0);
        $dId = $d->add();
        $this->assertGreaterThan(0, (int) $dId);
        $this->assertSame('1', (string) $this->row((int) $dId)['user_id']);
    }

    public function testSaveUpdatesTheRowAndReturnsFalseForAnUnknownId(): void
    {
        $root = $this->cat();
        $id = $this->cat(['parent_id' => $root, 'title' => 'gc_before', 'weight' => 10, 'visible' => 1]);

        $c = Category::load($id)[0];
        $c->set_name('gc_after');
        $c->set_description('gc_after_description');
        $c->set_weight(75.5);
        $c->set_visible(0);
        $c->set_certificate_min_score(80);
        $c->setGenerateCertificates(1);
        $c->setCourseId(1);
        $this->context(1, 1);
        $c->save();

        $row = $this->row($id);
        $this->assertSame('gc_after', $row['title']);
        $this->assertSame('gc_after_description', $row['description']);
        $this->assertEquals(75.5, $row['weight']);
        $this->assertSame('0', (string) $row['visible']);
        $this->assertSame('80', (string) $row['certif_min_score']);
        $this->assertSame('1', (string) $row['generate_certificates']);
        $this->assertSame((string) $root, (string) $row['parent_id'], 'parent kept');

        $ghost = new Category();
        $ghost->set_id(2000000000);
        $this->assertFalse($ghost->save());
    }

    public function testDeleteRemovesOnlyThatRowAndCascadesToItsChildrenAndScoreLog(): void
    {
        $root = $this->cat();
        $child = $this->cat(['parent_id' => $root]);
        $sibling = $this->cat();
        Category::registerCurrentScore(12.5, 1, $root);

        Category::load($root)[0]->delete();

        $this->assertFalse($this->row($root));
        $this->assertFalse($this->row($child), 'ON DELETE CASCADE on parent_id');
        $this->assertNotFalse($this->row($sibling));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_score_log WHERE category_id = ?', [$root]));
    }

    public function testDeleteAllRemovesSubcategoriesEvaluationsAndTheirResults(): void
    {
        $root = $this->cat();
        $child = $this->cat(['parent_id' => $root]);
        $evalRoot = $this->evaluation($root);
        $evalChild = $this->evaluation($child);
        $keep = $this->cat();
        $evalKeep = $this->evaluation($keep);
        $this->conn->insert('gradebook_result', ['evaluation_id' => $evalChild, 'user_id' => 1, 'score' => 5, 'created_at' => '2020-01-01 00:00:00']);
        $this->conn->insert('gradebook_result', ['evaluation_id' => $evalKeep, 'user_id' => 1, 'score' => 6, 'created_at' => '2020-01-01 00:00:00']);

        Category::load($root)[0]->delete_all();

        $this->assertFalse($this->row($root));
        $this->assertFalse($this->row($child));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_evaluation WHERE id IN (?, ?)', [$evalRoot, $evalChild]));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_result WHERE evaluation_id = ?', [$evalChild]));
        $this->assertNotFalse($this->row($keep));
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM gradebook_result WHERE evaluation_id = ?', [$evalKeep]));
    }

    // ---------------------------------------------------------------- single-row readers

    public function testShowMessageResourceDeleteWarnsOnlyForCategoriesFlaggedWithVisible3(): void
    {
        $this->assertSame('', Category::show_message_resource_delete(null));
        $this->assertSame('', Category::show_message_resource_delete(0));
        $before = Category::show_message_resource_delete(2);
        $this->cat(['c_id' => 2, 'visible' => 3]);

        $after = Category::show_message_resource_delete(2);

        $this->assertStringContainsString('resource-deleted', $after);
        $this->assertNotSame($before, '' === $after ? 'x' : $after);
        $this->assertSame('', Category::show_message_resource_delete(2000000000));
    }

    public function testShowAllCategoryInfoReturnsTheRawRowOrFalse(): void
    {
        $id = $this->cat(['title' => 'gc_info', 'weight' => 7]);

        $info = (new Category())->showAllCategoryInfo($id);

        $this->assertSame('gc_info', $info['title']);
        $this->assertEquals(7, $info['weight']);
        $this->assertSame([], (new Category())->showAllCategoryInfo(0));
        // PINNED QUIRK: an unknown id gives an empty array here (the code path of fetch_array on no row), not false.
        $this->assertSame([], (new Category())->showAllCategoryInfo(2000000000));
    }

    public function testGetAllowSkillBySubCategoryTreatsOnlyTheValueOneAsAllowed(): void
    {
        $allowed = $this->cat(['allow_skills_by_subcategory' => 1]);
        $denied = $this->cat(['allow_skills_by_subcategory' => 0]);
        $other = $this->cat(['allow_skills_by_subcategory' => 2]);
        $nullable = $this->cat(['allow_skills_by_subcategory' => null]);
        $c = new Category();

        $this->assertTrue($c->getAllowSkillBySubCategory($allowed));
        $this->assertFalse($c->getAllowSkillBySubCategory($denied));
        $this->assertFalse($c->getAllowSkillBySubCategory($other));
        $this->assertTrue($c->getAllowSkillBySubCategory($nullable), 'NULL falls back to allowed');
        $this->assertTrue($c->getAllowSkillBySubCategory(), 'no id at all: allowed');
        // PINNED QUIRK: an id that does not exist behaves like NULL (allowed) because Database::result() gives null.
        $this->assertTrue($c->getAllowSkillBySubCategory(2000000000));
    }

    // ---------------------------------------------------------------- tree walkers

    public function testGetSubcategoriesAsAdminListsTheChildrenOfTheCourseAndSession(): void
    {
        $sid = $this->session();
        $root = $this->cat();
        $a = $this->cat(['parent_id' => $root, 'title' => 'gc_a']);
        $hidden = $this->cat(['parent_id' => $root, 'title' => 'gc_b', 'visible' => 0]);
        $inSession = $this->cat(['parent_id' => $root, 'session_id' => $sid]);

        $parent = Category::load($root)[0];

        $this->assertSame([$a, $hidden], $this->ids($parent->get_subcategories(null, 1, 0)), 'an admin sees hidden ones too');
        $this->assertSame([$inSession], $this->ids($parent->get_subcategories(null, 1, $sid)));
        $this->assertSame([$hidden, $a], $this->ids($parent->get_subcategories(null, 1, 0, 'ORDER BY title DESC')));
    }

    public function testGetSubcategoriesForAStudentHidesInvisibleCategoriesOnceAStudentIdIsGiven(): void
    {
        $root = $this->cat();
        $visible = $this->cat(['parent_id' => $root]);
        $this->cat(['parent_id' => $root, 'visible' => 0]);
        $this->asStudent();

        $parent = Category::load($root)[0];

        $this->assertSame([$visible], $this->ids($parent->get_subcategories(2, 1, 0)));
        // PINNED QUIRK: without a student id a non-admin, non-editor gets nothing at all (not even the visible ones).
        $this->assertSame([], $parent->get_subcategories(null, 1, 0));
    }

    public function testRootCategoriesForAStudentAreOnlyTheVisibleOnesOfThatCourseAndSession(): void
    {
        $visible = $this->cat(['title' => 'gc_root_visible']);
        $hidden = $this->cat(['title' => 'gc_root_hidden', 'visible' => 0]);
        $otherCourse = $this->cat(['c_id' => 2]);
        $this->asStudent();

        $found = $this->ids((new Category())->get_root_categories_for_student(2, 1, 0));

        // A root category is stored with parent_id NULL (0 cannot even be stored: FK to gradebook_category.id); the
        // query used to say "parent_id = 0" and so never found any.
        $this->assertContains($visible, $found);
        $this->assertNotContains($hidden, $found);
        $this->assertNotContains($otherCourse, $found);
        $child = $this->cat(['parent_id' => $visible]);
        $this->assertNotContains($child, $this->ids((new Category())->get_root_categories_for_student(2, 1, 0)), 'only roots');
    }

    public function testRootCategoriesForAStudentWithoutCourseFollowTheirSubscriptions(): void
    {
        $student = $this->user();
        $this->subscribe($student, 2, 5);
        $mine = $this->cat(['c_id' => 2]);
        $notMine = $this->cat(['c_id' => 1]);
        $this->asStudent();

        $found = $this->ids((new Category())->get_root_categories_for_student($student));

        $this->assertContains($mine, $found);
        $this->assertNotContains($notMine, $found);
    }

    public function testRootCategoriesForATeacherAreScopedToTheirCourses(): void
    {
        $teacher = $this->user(1);
        $this->subscribe($teacher, 2, 1);
        $inCourse2 = $this->cat(['c_id' => 2]);
        $inCourse1 = $this->cat(['c_id' => 1]);

        $withCourse = $this->ids((new Category())->get_root_categories_for_teacher($teacher, 2, 0));
        $this->assertContains($inCourse2, $withCourse);
        $this->assertNotContains($inCourse1, $withCourse);

        $anyCourse = $this->ids((new Category())->get_root_categories_for_teacher($teacher));
        $this->assertContains($inCourse2, $anyCourse);
        $this->assertNotContains($inCourse1, $anyCourse);
    }

    public function testRootCategoriesForATeacherIgnoreTheCoursesWhereTheUserIsOnlyAStudent(): void
    {
        // The teacher listing used to follow any course_rel_user row, so a plain student subscription exposed the
        // root categories (hidden ones included) of that course through the teacher listing; the student listing
        // always filtered on the STUDENT status.
        $student = $this->user();
        $this->subscribe($student, 2, 5);
        $teacher = $this->user(1);
        $this->subscribe($teacher, 2, 1);
        $hidden = $this->cat(['c_id' => 2, 'visible' => 0]);

        $this->assertNotContains($hidden, $this->ids((new Category())->get_root_categories_for_teacher($student)));
        $this->assertContains($hidden, $this->ids((new Category())->get_root_categories_for_teacher($teacher)));
    }

    public function testGetNotCreatedCourseCategoriesListsTheTeachersCoursesWithoutAGradebookRoot(): void
    {
        $teacher = $this->user(1);
        $this->subscribe($teacher, 1, 1);
        $this->subscribe($teacher, 2, 1);
        $this->cat(['c_id' => 1]);
        $this->context(2, 1);

        $codes = array_column(Category::get_not_created_course_categories($teacher), 0);
        sort($codes);

        // course 1 has a root category now, so only course 2 is still offered as "not created" (the NOT IN subquery
        // used to test "parent_id = 0" instead of NULL and so excluded nothing).
        $this->assertContains('USINGCHAMILO', $codes);
        $this->assertNotContains('AIACT', $codes);
        $this->assertSame([], Category::get_not_created_course_categories($this->user()), 'a user who manages nothing');
        $all = array_column(Category::get_all_courses($teacher), 0);
        sort($all);
        $this->assertSame(['AIACT', 'USINGCHAMILO'], $all, 'every course the teacher manages');
        $this->assertSame([], Category::get_all_courses($this->user()), 'a user who manages nothing');
    }

    public function testGetEvaluationsReadsTheCategoryEvaluationsOfTheCourse(): void
    {
        $root = $this->cat();
        $child = $this->cat(['parent_id' => $root]);
        $e1 = $this->evaluation($root, ['title' => 'gc_e1']);
        $e2 = $this->evaluation($root, ['title' => 'gc_e2', 'visible' => 0]);
        $e3 = $this->evaluation($child, ['title' => 'gc_e3']);
        $parent = Category::load($root)[0];

        $direct = array_map(static fn (Evaluation $e): int => (int) $e->get_id(), $parent->get_evaluations(null, false, 1));
        $recursive = array_map(static fn (Evaluation $e): int => (int) $e->get_id(), $parent->get_evaluations(null, true, 1));

        $this->assertSame([$e1, $e2], $direct);
        $this->assertSame([$e1, $e2, $e3], $recursive, 'the recursion walks the subcategories');
    }

    public function testGetLinksOfARootOrAnEmptyCategoryIsEmpty(): void
    {
        $root = $this->cat();
        $this->assertSame([], Category::load($root)[0]->get_links(null, true, 1));
        $this->assertSame([], Category::load(0)[0]->get_links(), 'no links in the virtual root');
    }

    public function testEvaluationLoadWithAUserSelectsTheEvaluationsOfTheCoursesTheUserTeaches(): void
    {
        // gradebook_evaluation has no user_id column any more: the old creator filter is replaced by "the
        // courses the user teaches" (see GradebookUtils::taughtCoursesCondition). It used to throw.
        $cat = $this->cat();
        $inCourse1 = $this->evaluation($cat, ['title' => 'gc_taught', 'c_id' => 1]);
        $inCourse2 = $this->evaluation($cat, ['title' => 'gc_not_taught', 'c_id' => 2]);
        $teacher = $this->user(1);
        $this->subscribe($teacher, 1, 1);
        $student = $this->user(5);
        $this->subscribe($student, 2, 5);
        $ids = static fn (array $l): array => array_map(static fn (Evaluation $e): int => (int) $e->get_id(), $l);

        $this->assertSame([$inCourse1], $ids(Evaluation::load(null, $teacher, null, $cat, null)));
        $this->assertSame([], $ids(Evaluation::load(null, $student, null, $cat, null)), 'a learner teaches nothing');
        $this->assertSame([], $ids(Evaluation::load(null, $this->user(1), null, $cat, null)), 'no course at all');
    }

    // ---------------------------------------------------------------- Evaluation::load()

    public function testEvaluationLoadFiltersByCourseCategoryVisibilityAndLock(): void
    {
        $cat = $this->cat();
        $visible = $this->evaluation($cat, ['title' => 'gc_ev_visible']);
        $hidden = $this->evaluation($cat, ['title' => 'gc_ev_hidden', 'visible' => 0]);
        $locked = $this->evaluation($cat, ['title' => 'gc_ev_locked', 'locked' => 1]);
        $other = $this->evaluation($cat, ['title' => 'gc_ev_other', 'c_id' => 2]);
        $ids = static fn (array $l): array => array_map(static fn (Evaluation $e): int => (int) $e->get_id(), $l);

        $this->assertSame([$visible, $hidden, $locked, $other], $ids(Evaluation::load(null, null, null, $cat)));
        $this->assertSame([$visible, $hidden, $locked], $ids(Evaluation::load(null, null, 1, $cat)));
        $this->assertSame([$visible, $locked, $other], $ids(Evaluation::load(null, null, null, $cat, 1)));
        $this->assertSame([$hidden], $ids(Evaluation::load(null, null, null, $cat, 0)));
        $this->assertSame([$locked], $ids(Evaluation::load(null, null, null, $cat, null, 1)));
        $this->assertSame([$visible], $ids(Evaluation::load($visible)));
        $this->assertSame([], Evaluation::load(2000000000));

        $e = Evaluation::load($visible)[0];
        $this->assertSame('gc_ev_visible', $e->get_name());
        $this->assertEquals(20, $e->get_max());
        $this->assertEquals(10, $e->get_weight());
        $this->assertSame($cat, (int) $e->get_category_id());
    }

    public function testEvaluationSaveUpdatesTheRowAndLogsTheOldWeight(): void
    {
        $cat = $this->cat();
        $other = $this->cat();
        $id = $this->evaluation($cat, ['title' => 'gc_before', 'weight' => 10, 'max' => 20, 'visible' => 1]);
        $untouched = $this->evaluation($cat, ['title' => 'gc_untouched']);
        $this->context(1, 1);

        $e = Evaluation::load($id)[0];
        $e->set_name('gc_after');
        $e->set_description('gc_new_description');
        $e->set_weight(33);
        $e->set_max(50);
        $e->set_visible(0);
        $e->save();

        $row = $this->conn->fetchAssociative('SELECT * FROM gradebook_evaluation WHERE id = ?', [$id]);
        $this->assertSame('gc_after', $row['title']);
        $this->assertSame('gc_new_description', $row['description']);
        $this->assertEquals(33, $row['weight']);
        $this->assertEquals(50, $row['max']);
        $this->assertSame('0', (string) $row['visible']);
        $this->assertSame((string) $cat, (string) $row['category_id'], 'category kept');
        $this->assertSame('1', (string) $row['c_id']);
        $this->assertSame('gc_untouched', $this->conn->fetchOne('SELECT title FROM gradebook_evaluation WHERE id = ?', [$untouched]));
        // history: the weight BEFORE the update is logged
        $log = $this->conn->fetchAssociative('SELECT * FROM gradebook_linkeval_log WHERE id_linkeval_log = ?', [$id]);
        $this->assertEquals(10, $log['weight']);
        $this->assertSame('gc_before', $log['title']);
        $this->assertSame('evaluation', $log['type']);
    }

    public function testApplyVisibilityToChildrenAlsoUpdatesTheEvaluations(): void
    {
        $root = $this->cat(['visible' => 0]);
        $evalRoot = $this->evaluation($root, ['visible' => 1]);
        $outsider = $this->evaluation($this->cat(), ['visible' => 1]);
        $this->context(1, 1);

        Category::load($root)[0]->apply_visibility_to_children();

        $this->assertSame('0', (string) $this->conn->fetchOne('SELECT visible FROM gradebook_evaluation WHERE id = ?', [$evalRoot]));
        $this->assertSame('1', (string) $this->conn->fetchOne('SELECT visible FROM gradebook_evaluation WHERE id = ?', [$outsider]));
    }

    // ---------------------------------------------------------------- lock / visibility / move

    public function testLockSetsTheFlagOfThatCategoryOnly(): void
    {
        $a = $this->cat();
        $b = $this->cat();

        Category::load($a)[0]->lock(1);
        $this->assertSame('1', (string) $this->row($a)['locked']);
        $this->assertSame('0', (string) $this->row($b)['locked']);

        Category::load($a)[0]->lock(0);
        $this->assertSame('0', (string) $this->row($a)['locked']);
    }

    public function testLockAllItemsLocksTheCategoryAndItsEvaluationsOnlyWhenTheSettingIsOn(): void
    {
        $cat = $this->cat();
        $eval = $this->evaluation($cat);

        $this->setSetting('gradebook_locking_enabled', 'false');
        Category::load($cat)[0]->lockAllItems(1);
        $this->assertSame('0', (string) $this->row($cat)['locked'], 'setting off: nothing happens');

        $this->setSetting('gradebook_locking_enabled', 'true');
        Category::load($cat)[0]->lockAllItems(1);
        $this->assertSame('1', (string) $this->row($cat)['locked']);
        $this->assertSame('1', (string) $this->conn->fetchOne('SELECT locked FROM gradebook_evaluation WHERE id = ?', [$eval]));
    }

    public function testApplyVisibilityToChildrenPushesTheParentVisibilityDownTheSubcategories(): void
    {
        $root = $this->cat(['visible' => 0]);
        $child = $this->cat(['parent_id' => $root, 'visible' => 1]);
        $grandChild = $this->cat(['parent_id' => $child, 'visible' => 1]);
        $outsider = $this->cat(['visible' => 1]);

        $this->context(1, 1);
        Category::load($root)[0]->apply_visibility_to_children();

        $this->assertSame('0', (string) $this->row($child)['visible']);
        $this->assertSame('0', (string) $this->row($grandChild)['visible'], 'recursive');
        $this->assertSame('1', (string) $this->row($outsider)['visible']);
    }

    public function testTargetCategoriesListTheRootAndTheCourseTreeButNeverTheCategoryItself(): void
    {
        $root = $this->cat(['title' => 'gc_t_root']);
        $moving = $this->cat(['parent_id' => $root, 'title' => 'gc_t_moving']);
        $sibling = $this->cat(['parent_id' => $root, 'title' => 'gc_t_sibling']);
        $this->context(1, 1);

        $targets = Category::load($moving)[0]->get_target_categories();

        $byId = [];
        foreach ($targets as $t) {
            $byId[$t[0]] = $t;
        }
        $this->assertSame(0, $targets[0][0], 'the main folder comes first');
        $this->assertArrayHasKey($root, $byId);
        $this->assertArrayHasKey($sibling, $byId);
        $this->assertArrayNotHasKey($moving, $byId, 'a category cannot be moved into itself');
        $this->assertSame(1, $byId[$root][2]);
        // PINNED QUIRK: load(..., parent_id 0) ignores the parent filter, so every category of the course is a
        // "root" target at level 1, including the child of $root listed again beneath its parent.
        $this->assertSame(1, $byId[$sibling][2]);
    }

    public function testTargetCategoriesOfTheRootAreNotMovable(): void
    {
        $this->assertNull(Category::load(0)[0]->get_target_categories());
    }

    public function testMoveToCatReparentsTheCategory(): void
    {
        $a = $this->cat(['title' => 'gc_mv_a']);
        $b = $this->cat(['title' => 'gc_mv_b']);
        $moving = $this->cat(['parent_id' => $a, 'title' => 'gc_mv_moving']);
        $this->context(1, 1);

        $c = Category::load($moving)[0];
        $c->move_to_cat(Category::load($b)[0]);

        $this->assertSame((string) $b, (string) $this->row($moving)['parent_id']);
    }

    // ---------------------------------------------------------------- score log

    public function testRegisterCurrentScoreInsertsALogRowAndGetCurrentScoreReadsTheLatestOne(): void
    {
        $cat = $this->cat();
        $entity = Container::getEntityManager()->find(GradebookCategory::class, $cat);
        $this->assertSame(0, Category::getCurrentScore(1, $entity), 'no log: 0');

        $first = Category::registerCurrentScore(40.5, 1, $cat);
        $second = Category::registerCurrentScore('77.25', 1, $cat);
        $this->assertGreaterThan($first, $second);
        $this->assertEquals(40.5, $this->conn->fetchOne('SELECT score FROM gradebook_score_log WHERE id = ?', [$first]));
        $this->assertEquals(77.25, $this->conn->fetchOne('SELECT score FROM gradebook_score_log WHERE id = ?', [$second]));
        $this->assertEquals(1, (int) $this->conn->fetchOne('SELECT user_id FROM gradebook_score_log WHERE id = ?', [$second]));

        // The latest row wins by registered_at (two registrations in the same second tie: order undefined).
        $this->conn->executeStatement('UPDATE gradebook_score_log SET registered_at = ? WHERE id = ?', ['2020-01-01 10:00:00', $first]);
        $this->conn->executeStatement('UPDATE gradebook_score_log SET registered_at = ? WHERE id = ?', ['2020-01-01 09:00:00', $second]);
        $this->assertEquals(40.5, Category::getCurrentScore(1, $entity));
        Category::registerCurrentScore(5, 2, $cat);
        $this->assertEquals(5, Category::getCurrentScore(2, $entity), 'scoped per user');
    }

    public function testGetCurrentScoreRecalculatedIsZeroWithoutAnyEvaluationOrLink(): void
    {
        $cat = $this->cat();
        $entity = Container::getEntityManager()->find(GradebookCategory::class, $cat);
        Category::registerCurrentScore(90, 1, $cat);

        $this->assertSame(0, Category::getCurrentScore(1, $entity, true, 1, 0), 'recalculate ignores the log');
    }
}
