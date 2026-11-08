<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Component\Gradebook;

use Chamilo\CoreBundle\Component\Gradebook\AchievementCertificateBatchService;
use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

/**
 * The SQL building blocks of the achievement certificate commands (chamilo:migration:process-achievement-
 * certificates and chamilo:gradebook:process-achievement-certificates): which categories are eligible, which
 * students are pending, how an ambiguous category is resolved, and the course extra fields that carry the
 * notification and the legacy confirmation. The service is built by hand, like the commands do (Component classes
 * are not autowired). Raw rows only, FIXED dates; the foreign keys are off for the test (session variable) since
 * categories and certificates need no parents here. Tag sv3_.
 */
final class AchievementCertificateBatchServiceTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private const SUBJECT = 'plugin_gradingelectronic_certificate_notification_subject';
    private const MESSAGE = 'plugin_gradingelectronic_certificate_notification_message';
    private const LEGACY = 'plugin_gradingelectronic_course_id';

    private AchievementCertificateBatchService $service;
    /** @var int[] */
    private array $nodes = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = new AchievementCertificateBatchService(
            $this->conn(),
            $this->getEntityManager(),
            new CourseCompletionRuleEvaluator($this->conn()),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(Environment::class),
        );
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->nodes = array_map('intval', $this->conn()->fetchFirstColumn('SELECT id FROM resource_node ORDER BY id LIMIT 12 OFFSET 5'));
    }

    protected function tearDown(): void
    {
        $this->conn()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        parent::tearDown();
    }

    private function category(string $title, ?int $session = null, ?int $parent = null, int $generate = 1, float $min = 50.0, int $course = 1): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO gradebook_category (title, weight, visible, locked, generate_certificates, is_requirement, calculation_mode, c_id, session_id, parent_id, certif_min_score)
             VALUES (?, 1, 1, 0, ?, 0, 'weighted_average', ?, ?, ?, ?)",
            ['sv3_'.$title, $generate, $course, $session, $parent, $min]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function certificate(int $categoryId, int $userId, ?int $nodeIndex): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, publish, resource_node_id) VALUES (?, ?, 80, '2020-01-01 00:00:00', 0, ?)",
            [$categoryId, $userId, null === $nodeIndex ? null : $this->nodes[$nodeIndex]]
        );
    }

    private function field(string $variable): int
    {
        $existing = $this->conn()->fetchOne('SELECT id FROM extra_field WHERE variable = ? AND item_type = ?', [$variable, ExtraField::COURSE_FIELD_TYPE]);
        if (false !== $existing) {
            $this->markTestSkipped('The extra field '.$variable.' already exists on this installation.');
        }
        $this->conn()->executeStatement(
            "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove) VALUES (?, 1, ?, 'x', 0, 0, 0, 0, '2020-01-01 00:00:00', 0)",
            [ExtraField::COURSE_FIELD_TYPE, $variable]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function value(int $fieldId, int $courseId, string $value): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO extra_field_values (field_id, item_id, field_value, created_at, updated_at) VALUES (?, ?, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
            [$fieldId, $courseId, $value]
        );
    }

    private function enrol(int $userId, int $status = 5): void
    {
        $this->courseUser($userId, 1, $status);
    }

    private function ids(array $rows): array
    {
        return array_map(static fn (array $r): int => (int) $r['id'], $rows);
    }

    public function testEligibleCategoriesAreRootGenerateCertificatesOnesOrderedBySessionThenId(): void
    {
        $base2 = $this->category('base2');
        $base1 = $this->category('base1', 0);   // session 0 and NULL are the same context
        $s7 = $this->category('s7', 7);
        $sub = $this->category('sub', null, $base1);
        $off = $this->category('off', null, null, 0);
        $zeroParent = $this->category('zeroParent', null, 0);
        $other = $this->category('othercourse', null, null, 1, 50.0, 2);

        $all = $this->service->loadEligibleCategories(1, null);
        $mine = array_values(array_filter($this->ids($all), static fn (int $id): bool => \in_array($id, [$base1, $base2, $s7, $sub, $off, $zeroParent, $other], true)));

        $this->assertSame([$base2, $base1, $zeroParent, $s7], $mine, 'Sub-categories, disabled and other-course ones are excluded; order is session then id');
        $this->assertSame([$base2, $base1, $zeroParent], array_values(array_intersect($this->ids($this->service->loadEligibleCategories(1, 0)), [$base1, $base2, $zeroParent, $s7])));
        $this->assertSame([$s7], array_values(array_intersect($this->ids($this->service->loadEligibleCategories(1, 7)), [$base1, $base2, $zeroParent, $s7])));
        $this->assertSame([$base1], $this->ids($this->service->loadEligibleCategories(1, 0, $base1)));
        $this->assertSame([], $this->service->loadEligibleCategories(1, 0, $s7), 'Category filter and session filter combine');
        $row = $this->service->loadEligibleCategories(1, 7, $s7)[0];
        $this->assertSame('sv3_s7', $row['title']);
        $this->assertSame(7, (int) $row['session_id']);
        $this->assertSame(50.0, (float) $row['certif_min_score']);
        $this->assertSame(0, (int) $this->service->loadEligibleCategories(1, 0, $base2)[0]['session_id'], 'NULL session reads as 0');
    }

    public function testPendingStudentsOfTheBaseCourse(): void
    {
        $cat = $this->category('c');
        $a = $this->rawUser('a');
        $b = $this->rawUser('b');
        $c = $this->rawUser('c');
        $inactive = $this->rawUser('inactive', 5, 0);
        $teacher = $this->rawUser('teacher');
        $done = $this->rawUser('done');
        $unenrolled = $this->rawUser('unenrolled');
        foreach ([$a, $b, $c, $inactive, $done] as $u) {
            $this->enrol($u);
        }
        $this->enrol($teacher, 1);
        $this->certificate($cat, $done, 0);
        $after = $a - 1;

        $this->assertSame([$a, $b, $c], $this->service->loadPendingUserIds(1, $cat, 0, $after, 50, null), 'Students only, active only, without certificate, ascending');
        $this->assertSame([$b, $c], $this->service->loadPendingUserIds(1, $cat, 0, $a, 50, null), 'Cursor is exclusive');
        $this->assertSame([$a, $b], $this->service->loadPendingUserIds(1, $cat, 0, $after, 2, null), 'Batch size');
        $this->assertSame([$b], $this->service->loadPendingUserIds(1, $cat, 0, $after, 50, $b));
        $this->assertSame([], $this->service->loadPendingUserIds(1, $cat, 0, $after, 50, $done), 'Requested user already certified');
        $this->assertSame([], $this->service->loadPendingUserIds(1, $cat, 0, $after, 50, $unenrolled));
        $this->assertSame([$a, $b, $c, $done], array_values(array_diff($this->service->loadPendingUserIds(1, $this->category('other'), 0, $after, 50, null), [$inactive])), 'The certificate is per category');
    }

    public function testAnEmptyCertificateRowIsPendingOnlyWhenIncludeEmptyIsSet(): void
    {
        $cat = $this->category('c');
        $empty = $this->rawUser('empty');
        $full = $this->rawUser('full');
        $none = $this->rawUser('none');
        foreach ([$empty, $full, $none] as $u) {
            $this->enrol($u);
        }
        $this->certificate($cat, $empty, null);  // registered but no stored file
        $this->certificate($cat, $full, 1);
        $after = $empty - 1;

        $this->assertSame([$none], $this->service->loadPendingUserIds(1, $cat, 0, $after, 50, null));
        $this->assertSame([$empty, $none], $this->service->loadPendingUserIds(1, $cat, 0, $after, 50, null, true));
    }

    public function testPendingStudentsOfASessionUseTheSessionSubscriptionsOnly(): void
    {
        $cat = $this->category('s', 5);
        $s5 = $this->rawSession('s5');
        $s6 = $this->rawSession('s6');
        $a = $this->rawUser('a');
        $b = $this->rawUser('b');
        $coach = $this->rawUser('coach');
        $baseOnly = $this->rawUser('baseonly');
        $this->sessionCourseUser($s5, 1, $a, 0);
        $this->sessionCourseUser($s5, 1, $b, 0);
        $this->sessionCourseUser($s5, 1, $coach, 2);
        $this->sessionCourseUser($s6, 1, $baseOnly, 0);
        $this->enrol($baseOnly);
        $this->certificate($cat, $b, 0);
        $after = $a - 1;

        $this->assertSame([$a], $this->service->loadPendingUserIds(1, $cat, $s5, $after, 50, null));
        $this->assertSame([$a], $this->service->loadPendingUserIds(1, $cat, $s5, $after, 50, $a));
        $this->assertSame([], $this->service->loadPendingUserIds(1, $cat, $s5, $after, 50, $coach));
        $this->assertSame([$baseOnly], $this->service->loadPendingUserIds(1, $cat, $s6, $after, 50, null));
        $this->assertSame([], $this->service->loadPendingUserIds(1, $cat, $s5, $after, 50, $baseOnly));
    }

    public function testLegacyGradebookConfirmationNeedsExactlyOneNonBlankValue(): void
    {
        $this->assertFalse($this->service->hasLegacyGradebookConfirmation(1), 'No extra field');
        $field = $this->field(self::LEGACY);
        $this->assertFalse($this->service->hasLegacyGradebookConfirmation(1), 'No value');
        $this->value($field, 1, '   ');
        $this->assertFalse($this->service->hasLegacyGradebookConfirmation(1), 'Blank value');
        $this->value($field, 2, '42');
        $this->assertTrue($this->service->hasLegacyGradebookConfirmation(2));
        $this->assertFalse($this->service->hasLegacyGradebookConfirmation(1), 'Values are per course');
        $this->value($field, 2, '43');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Course 2 has multiple values');
        $this->service->hasLegacyGradebookConfirmation(2);
    }

    public function testNotificationNeedsBothFieldsAndASingleValueEach(): void
    {
        $this->assertSame([], $this->service->resolveNotification(1), 'Fields missing');
        $subject = $this->field(self::SUBJECT);
        $this->assertSame([], $this->service->resolveNotification(1), 'Only one field exists');
        $message = $this->field(self::MESSAGE);
        $this->assertSame([], $this->service->resolveNotification(1), 'Both empty');

        $this->value($subject, 1, '  Well done ');
        $this->assertSame(['subject' => 'Well done', 'message' => ''], $this->service->resolveNotification(1), 'Values are trimmed, one side may be empty');

        $this->value($message, 1, 'Your certificate');
        $this->assertSame(['subject' => 'Well done', 'message' => 'Your certificate'], $this->service->resolveNotification(1));

        $this->value($message, 1, 'second');
        $this->assertSame([], $this->service->resolveNotification(1), 'Two values for one field are ambiguous');
    }

    public function testAmbiguousCategoryIsResolvedByCertificateHistoryOnly(): void
    {
        $a = $this->category('a');
        $b = $this->category('b');
        $rows = $this->service->loadEligibleCategories(1, 0);
        $pair = array_values(array_filter($rows, static fn (array $r): bool => \in_array((int) $r['id'], [$a, $b], true)));
        $u1 = $this->rawUser('u1');
        $u2 = $this->rawUser('u2');
        $u3 = $this->rawUser('u3');

        $this->assertNull($this->service->resolveAmbiguousCategory(1, 0, [], 'standard'));
        $this->assertSame((int) $pair[0]['id'], (int) $this->service->resolveAmbiguousCategory(1, 0, [$pair[0]], 'standard')['id'], 'A single category is returned as is');
        $this->assertNull($this->service->resolveAmbiguousCategory(1, 0, $pair, 'standard'), 'No history: unresolved, never "the lowest id"');

        $this->certificate($a, $u1, 0);
        $this->certificate($b, $u2, 1);
        $this->assertNull($this->service->resolveAmbiguousCategory(1, 0, $pair, 'standard'), 'Tie: unresolved');

        $this->certificate($b, $u3, 2);
        $this->assertSame($b, (int) $this->service->resolveAmbiguousCategory(1, 0, $pair, 'standard')['id'], 'The category that issued most certificates wins');
    }

    public function testGenerationLimitAndNumberFormatting(): void
    {
        $this->assertFalse($this->service->generationLimitReached(['would_generate' => 99, 'generated' => 99], 0, false), '0 means no limit');
        $this->assertTrue($this->service->generationLimitReached(['would_generate' => 0, 'generated' => 3], 3, false));
        $this->assertFalse($this->service->generationLimitReached(['would_generate' => 3, 'generated' => 2], 3, false), 'A real run counts generated');
        $this->assertTrue($this->service->generationLimitReached(['would_generate' => 3, 'generated' => 0], 3, true), 'A dry run counts would_generate');
        $this->assertSame('100', $this->service->formatNumber(100.0));
        $this->assertSame('0', $this->service->formatNumber(0.0));
        $this->assertSame('12.5', $this->service->formatNumber(12.5));
        $this->assertSame('0.3333', $this->service->formatNumber(1 / 3));
        $this->assertSame('10', $this->service->formatNumber(10.00001));
    }
}
