<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Category;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Tests\ChamiloTestTrait;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\UuidV4;

/**
 * Second characterization pass over public/main/gradebook/lib/be/category.class.php: what the mutation pass of the
 * DQL migration found unpinned by GradebookCategoryCharacterizationTest (the raw row shape of
 * showAllCategoryInfo(), the default order of load(), the session and teacher branches of the root category
 * listings).
 */
final class GradebookCategoryTier2CharacterizationTest extends KernelTestCase
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

    private function gradeModel(): int
    {
        $this->conn->executeStatement("INSERT INTO grade_model (title, description) VALUES ('gc2_model', 'x')");

        return (int) $this->conn->lastInsertId();
    }

    private function document(): int
    {
        $this->conn->executeStatement(
            "INSERT INTO resource_node (resource_type_id, title, slug, created_at, updated_at, public, uuid)
             VALUES (32, ?, ?, NOW(), NOW(), 0, UNHEX(REPLACE(UUID(), '-', '')))",
            ['gc2_doc_'.(++$this->seq), 'gc2-doc-'.getmypid().'-'.$this->seq]
        );
        $node = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement(
            "INSERT INTO c_document (resource_node_id, title, filetype, readonly, template) VALUES (?, 'gc2_doc', 'file', 0, 0)",
            [$node]
        );

        return (int) $this->conn->lastInsertId();
    }

    public function testLoadOrdersAscendingWhenTheOrderByTailHasNoDirection(): void
    {
        $root = $this->cat();
        $b = $this->cat(['parent_id' => $root, 'title' => 'gc2_b']);
        $a = $this->cat(['parent_id' => $root, 'title' => 'gc2_a']);
        $c = $this->cat(['parent_id' => $root, 'title' => 'gc2_c']);

        $this->assertSame([$a, $b, $c], $this->ids(Category::load(null, null, 1, $root, null, 0, 'ORDER BY title')));
        $this->assertSame([$c, $a, $b], $this->ids(Category::load(null, null, 1, $root, null, 0, 'ORDER BY id DESC')));
        $this->assertSame([$b, $a, $c], $this->ids(Category::load(null, null, 1, $root, null, 0, 'order by id')));
    }

    public function testShowAllCategoryInfoReturnsEveryColumnWithTheRawTypesAndTheNumericKeys(): void
    {
        $model = $this->gradeModel();
        $document = $this->document();
        $session = $this->session();
        $root = $this->cat();
        $id = $this->cat([
            'title' => 'gc2_full',
            'description' => 'gc2 description',
            'parent_id' => $root,
            'session_id' => $session,
            'weight' => 12.5,
            'visible' => 1,
            'locked' => 1,
            'certif_min_score' => 55,
            'grade_model_id' => $model,
            'document_id' => $document,
            'default_lowest_eval_exclude' => 1,
            'generate_certificates' => 1,
            'certificate_validity_period' => 30,
            'is_requirement' => 1,
            'depends' => 'a:0:{}',
            'minimum_to_validate' => 3,
            'gradebooks_to_validate_in_dependence' => 2,
            'allow_skills_by_subcategory' => 0,
            'calculation_mode' => 'weighted_average',
        ]);

        $info = (new Category())->showAllCategoryInfo($id);

        $columns = [
            'id', 'user_id', 'c_id', 'parent_id', 'session_id', 'grade_model_id', 'document_id', 'title', 'description',
            'weight', 'visible', 'certif_min_score', 'locked', 'default_lowest_eval_exclude', 'generate_certificates',
            'certificate_validity_period', 'is_requirement', 'depends', 'minimum_to_validate',
            'gradebooks_to_validate_in_dependence', 'allow_skills_by_subcategory', 'calculation_mode',
        ];
        $raw = $this->row($id);
        foreach ($columns as $column) {
            $this->assertArrayHasKey($column, $info);
            $this->assertEquals($raw[$column], $info[$column], $column);
        }
        $this->assertSame(
            ['id' => $id, 'user_id' => 1, 'c_id' => 1, 'parent_id' => $root, 'session_id' => $session, 'grade_model_id' => $model, 'document_id' => $document],
            array_intersect_key($info, array_flip(['id', 'user_id', 'c_id', 'parent_id', 'session_id', 'grade_model_id', 'document_id']))
        );
        $this->assertSame(1, $info['visible']);
        $this->assertSame(1, $info['locked']);
        $this->assertSame(1, $info['default_lowest_eval_exclude']);
        $this->assertSame(1, $info['generate_certificates']);
        $this->assertSame(1, $info['is_requirement']);
        $this->assertSame(0, $info['allow_skills_by_subcategory']);
        $this->assertSame(55, $info['certif_min_score']);
        $this->assertSame('weighted_average', $info['calculation_mode']);
        $this->assertSame('gc2_full', $info['title']);
        $this->assertSame($info['id'], $info[0]);
        $this->assertSame($info['calculation_mode'], $info[\count($columns) - 1]);
        $this->assertCount(2 * \count($columns), $info);

        $loaded = Category::load($id)[0];
        $this->assertSame($model, (int) $loaded->get_grade_model_id());
        $this->assertSame($document, (int) $loaded->getDocumentId());
        $this->assertSame(3, (int) $loaded->getMinimumToValidate());
    }

    public function testShowAllCategoryInfoKeepsNullColumnsAsNull(): void
    {
        $id = $this->cat();

        $info = (new Category())->showAllCategoryInfo($id);

        $this->assertNull($info['parent_id']);
        $this->assertNull($info['session_id']);
        $this->assertNull($info['grade_model_id']);
        $this->assertNull($info['document_id']);
        $this->assertNull($info['certif_min_score']);
    }

    public function testRootCategoriesOfAPlatformAdminFollowTheGivenSessionOrNoSessionAcrossCourses(): void
    {
        $sid = $this->session();
        $inSession = $this->cat(['session_id' => $sid]);
        $inSessionOtherCourse = $this->cat(['session_id' => $sid, 'c_id' => 2]);
        $noSession = $this->cat();
        $this->context(1, 1);

        $withSession = $this->ids((new Category())->get_root_categories_for_student(1, 0, $sid));
        $this->assertContains($inSession, $withSession);
        $this->assertContains($inSessionOtherCourse, $withSession);
        $this->assertNotContains($noSession, $withSession);

        $withoutSession = $this->ids((new Category())->get_root_categories_for_student(1, 0, 0));
        $this->assertContains($noSession, $withoutSession);
        $this->assertNotContains($inSession, $withoutSession);
    }

    public function testRootCategoriesForATeacherInASessionKeepTheSessionOnes(): void
    {
        $teacher = $this->user(1);
        $this->subscribe($teacher, 1, 1);
        $sid = $this->session();
        $inSession = $this->cat(['session_id' => $sid]);
        $otherSession = $this->cat(['session_id' => $this->session()]);
        $baseCourse = $this->cat();

        $found = $this->ids((new Category())->get_root_categories_for_teacher($teacher, 1, $sid));

        $this->assertContains($inSession, $found);
        $this->assertNotContains($otherSession, $found);
        $this->assertNotContains($baseCourse, $found);
    }
}
