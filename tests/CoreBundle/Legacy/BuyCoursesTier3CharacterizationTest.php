<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;
use ReflectionMethod;

/**
 * Tier 3 characterization of the benefit / frozen-enrollment / course-retention queries of BuyCoursesPlugin,
 * written to pass on the Database::query() implementation before it is moved to the DBAL QueryBuilder.
 * Fixtures prefix bc_ and are rolled back by DAMA.
 */
final class BuyCoursesTier3CharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    private function callPrivate(string $method, array $args = []): mixed
    {
        $m = new ReflectionMethod(BuyCoursesPlugin::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->plugin, $args);
    }

    private function frozenRow(int $courseId, int $userId): void
    {
        $this->conn->insert('plugin_buycourses_frozen_enrollment', [
            'course_id' => $courseId,
            'user_id' => $userId,
            'frozen_since' => '2026-01-01 00:00:00',
        ]);
    }

    private function frozenCourseIds(): array
    {
        $ids = array_map('intval', $this->conn->fetchFirstColumn(
            'SELECT course_id FROM plugin_buycourses_frozen_enrollment ORDER BY course_id, user_id'
        ));

        return $ids;
    }

    private function benefitFieldId(string $variable): int
    {
        $this->callPrivate('ensureUserExtraField', [$variable, $variable]);

        return (int) $this->conn->fetchOne("SELECT id FROM extra_field WHERE variable = ? AND item_type = 1", [$variable]);
    }

    private function makeBenefitServiceSale(int $fieldId, int $granted, array $sale): int
    {
        $this->conn->insert('plugin_buycourses_services', [
            'name' => 'bc_service', 'description' => 'bc_desc', 'price' => '100.00', 'duration_days' => 30,
            'applies_to' => 1, 'owner_id' => 1, 'active' => 1, 'visibility' => 1, 'video_url' => '',
            'image' => '', 'service_information' => '',
        ]);
        $serviceId = (int) $this->conn->lastInsertId();
        $this->conn->insert('plugin_buycourses_service_rel_extra_field', [
            'service_id' => $serviceId, 'extra_field_id' => $fieldId, 'granted_value' => $granted,
        ]);
        $this->conn->insert('plugin_buycourses_service_sale', array_merge([
            'service_id' => $serviceId,
            'reference' => 'bc_sref_'.uniqid('', true),
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'price' => '100.00', 'node_type' => 1, 'node_id' => 2, 'buyer_id' => 2,
            'buy_date' => '2026-01-10 08:00:00', 'date_start' => '2026-01-10 08:00:00',
            'date_end' => '2099-02-09 08:00:00', 'status' => 1, 'payment_type' => 2, 'invoice' => 0,
        ], $sale));

        return (int) $this->conn->lastInsertId();
    }

    public function testRestoreFrozenEnrollmentsDeletesOnlyTheFrozenRowsOfCoursesTaughtByTheUser(): void
    {
        // Admin (id 1) teaches courses 1 and 2; user 3 teaches none of them.
        $this->conn->insert('course_rel_user', ['user_id' => 3, 'c_id' => 2, 'relation_type' => 0, 'status' => 5, 'progress' => 0]);
        $this->conn->executeStatement('DELETE FROM course_rel_user WHERE user_id = 1 AND c_id = 2');
        $this->frozenRow(1, 2);
        $this->frozenRow(1, 3);
        $this->frozenRow(2, 3);

        $this->plugin->restoreFrozenEnrollmentsForUser(1);

        // Course 1 (taught by user 1) is cleared for every user; course 2 (only a student there) is untouched.
        $this->assertSame([2], $this->frozenCourseIds());

        $this->plugin->restoreFrozenEnrollmentsForUser(3);
        $this->assertSame([2], $this->frozenCourseIds(), 'a student of a course does not restore it');

        $this->plugin->restoreFrozenEnrollmentsForUser(0);
        $this->assertSame([2], $this->frozenCourseIds());
    }

    public function testLatestActiveBenefitPayloadPicksTheLatestUnexpiredCompletedSaleOfTheBuyer(): void
    {
        $variable = BuyCoursesPlugin::EXTRA_FIELD_MAX_COURSES;
        $fieldId = $this->benefitFieldId($variable);
        $otherFieldId = $this->benefitFieldId(BuyCoursesPlugin::EXTRA_FIELD_HOSTING_LIMIT);
        $future = '2099-03-01 00:00:00';

        // Not completed, expired, other buyer and other field are all ignored.
        $this->makeBenefitServiceSale($fieldId, 99, ['status' => 0]);
        $this->makeBenefitServiceSale($fieldId, 98, ['date_end' => '2000-01-01 00:00:00']);
        $this->makeBenefitServiceSale($fieldId, 97, ['buyer_id' => 3]);
        $this->makeBenefitServiceSale($otherFieldId, 96, []);
        $this->assertNull($this->callPrivate('getLatestActiveBenefitPayloadFromSales', [2, $variable]));

        $this->makeBenefitServiceSale($fieldId, 5, ['date_end' => '2099-02-01 00:00:00']);
        $this->makeBenefitServiceSale($fieldId, 7, ['date_end' => $future]);
        // Same end date: the highest sale id wins.
        $this->makeBenefitServiceSale($fieldId, 9, ['date_end' => $future]);

        $this->assertSame(
            ['limit' => 9, 'expiry' => $future],
            $this->callPrivate('getLatestActiveBenefitPayloadFromSales', [2, $variable])
        );
        $this->assertNull($this->callPrivate('getLatestActiveBenefitPayloadFromSales', [2, 'unknown_variable']));
        $this->assertNull($this->callPrivate('getLatestActiveBenefitPayloadFromSales', [3, BuyCoursesPlugin::EXTRA_FIELD_DOCUMENT_QUOTA]));
    }

    public function testLockUserExtraFieldVisibilityZeroesTheVisibilityFlagsOfOneField(): void
    {
        $fieldId = $this->benefitFieldId(BuyCoursesPlugin::EXTRA_FIELD_MAX_COURSES);
        $otherId = $this->benefitFieldId(BuyCoursesPlugin::EXTRA_FIELD_HOSTING_LIMIT);
        $this->conn->executeStatement(
            'UPDATE extra_field SET visible_to_self = 1, visible_to_others = 1, changeable = 1, filter = 1 WHERE id IN (?, ?)',
            [$fieldId, $otherId]
        );

        $this->callPrivate('lockUserExtraFieldVisibility', [$fieldId]);

        $locked = $this->conn->fetchAssociative('SELECT visible_to_self, visible_to_others, changeable, filter FROM extra_field WHERE id = ?', [$fieldId]);
        $this->assertSame(['0', '0', '0', '0'], array_map('strval', array_values($locked)));
        $other = $this->conn->fetchAssociative('SELECT visible_to_self, visible_to_others, changeable, filter FROM extra_field WHERE id = ?', [$otherId]);
        $this->assertSame(['1', '1', '1', '1'], array_map('strval', array_values($other)));

        $this->callPrivate('lockUserExtraFieldVisibility', [0]);
        $this->assertSame('1', (string) $this->conn->fetchOne('SELECT changeable FROM extra_field WHERE id = ?', [$otherId]));
    }

    private function makeCatalogService(string $name, string $price, int $days, int $appliesTo, array $override = []): int
    {
        $this->conn->insert('plugin_buycourses_services', array_merge([
            'name' => $name, 'description' => 'bc_desc', 'price' => $price, 'duration_days' => $days,
            'applies_to' => $appliesTo, 'owner_id' => 1, 'active' => 1, 'visibility' => 1, 'video_url' => '',
            'image' => '', 'service_information' => '',
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    public function testGetCatalogServiceListFiltersCountsAndPaginates(): void
    {
        $this->selectCurrency('EUR');
        $this->setGlobalConfig(0, 0);
        $this->setPluginSettings(['tax_enable' => 'false']);
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_services');

        $a = $this->makeCatalogService('bc_alpha', '10.00', 30, 1);
        $b = $this->makeCatalogService('bc_beta', '50.00', 31, 2);
        $c = $this->makeCatalogService('bc_gamma', '90.00', 365, 3);
        $this->makeCatalogService('bc_inactive', '20.00', 30, 1, ['active' => 0]);
        $this->makeCatalogService('bc_hidden', '20.00', 30, 1, ['visibility' => 0]);
        $this->makeCatalogService('bc_noowner', '20.00', 30, 1, ['owner_id' => 999999]);
        $d = $this->makeCatalogService('bc_zero_days', '30.00', 0, 1);

        $ids = fn (array $list): array => array_map(static fn (array $s): int => (int) $s['id'], $list);

        $this->assertSame(4 + 0, $this->plugin->getCatalogServiceList(0, 100, null, 0, 0, '', 'count'));
        $this->assertSame([$a, $b, $c, $d], $ids($this->plugin->getCatalogServiceList(0, 100)));
        // LIMIT offset, count semantic of (start, end)
        $this->assertSame([$b, $c], $ids($this->plugin->getCatalogServiceList(1, 2)));
        $this->assertSame([$a], $ids($this->plugin->getCatalogServiceList(-5, 0)), 'start floors at 0, end at 1');
        $this->assertSame([$b], $ids($this->plugin->getCatalogServiceList(0, 100, 'beta')));
        $this->assertSame(1, $this->plugin->getCatalogServiceList(0, 100, 'beta', 0, 0, '', 'count'));
        $this->assertSame([$b, $c, $d], $ids($this->plugin->getCatalogServiceList(0, 100, null, 30, 0)), 'min is inclusive');
        $this->assertSame([$a, $b, $d], $ids($this->plugin->getCatalogServiceList(0, 100, null, 0, 50)), 'max is inclusive');
        $this->assertSame([$b], $ids($this->plugin->getCatalogServiceList(0, 100, null, 0, 0, 2)));
        $this->assertSame([$b], $ids($this->plugin->getCatalogServiceList(0, 100, null, 0, 0, '2')));
        $this->assertSame([$a, $b], $ids($this->plugin->getCatalogServiceList(0, 100, null, 0, 0, '', 'all', 'monthly')), 'monthly: 0 < days <= 31');
        $this->assertSame([$c], $ids($this->plugin->getCatalogServiceList(0, 100, null, 0, 0, '', 'all', 'yearly')));
        $this->assertSame(2, $this->plugin->getCatalogServiceList(0, 100, null, 0, 0, '', 'count', 'monthly'));
        $this->assertSame([], $this->plugin->getCatalogServiceList(0, 100, "x' OR '1'='1"));
    }

    public function testFilterSessionListFiltersByTitlePriceAndCategoryAndSortsByTitle(): void
    {
        $this->conn->insert('session_category', ['title' => 'bc_cat']);
        $cat = (int) $this->conn->lastInsertId();
        $make = function (string $title, ?int $category, ?string $price, int $type = BuyCoursesPlugin::PRODUCT_TYPE_SESSION): int {
            $this->conn->insert('session', [
                'title' => $title, 'session_category_id' => $category, 'nbr_courses' => 0, 'nbr_users' => 0,
                'nbr_classes' => 0, 'visibility' => 1, 'status' => 0,
            ]);
            $id = (int) $this->conn->lastInsertId();
            if (null !== $price) {
                $this->makeItem($id, (float) $price, null, $type);
            }

            return $id;
        };
        $make('bc_s_zeta', null, '10.00');
        $make('bc_s_alpha', $cat, '60.00');
        $mid = $make('bc_s_mid', $cat, '30.00');
        $make('bc_s_noitem', null, null);
        $make('bc_s_course_item', null, '20.00', BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
        // a second item of the same session must not duplicate it
        $this->makeItem($mid, 31.0, null, BuyCoursesPlugin::PRODUCT_TYPE_SESSION);

        $call = fn (...$args): array|int => $this->callPrivate('filterSessionList', $args);
        $titles = fn (array $sessions): array => array_map(static fn ($s): string => $s->getTitle(), $sessions);

        $this->assertSame(['bc_s_alpha', 'bc_s_mid', 'bc_s_zeta'], $titles($call(0, 100, 'bc_s_')));
        $this->assertSame(3, $call(0, 100, 'bc_s_', 0, 0, 'count'), 'count is DISTINCT');
        $this->assertSame(['bc_s_mid', 'bc_s_zeta'], $titles($call(1, 2, 'bc_s_')));
        $this->assertSame(['bc_s_alpha'], $titles($call(0, 100, 'alpha')));
        $this->assertSame(['bc_s_alpha', 'bc_s_mid'], $titles($call(0, 100, 'bc_s_', 30, 0)));
        $this->assertSame(['bc_s_zeta'], $titles($call(0, 100, 'bc_s_', 0, 10)));
        $this->assertSame(['bc_s_alpha'], $titles($call(0, 100, 'bc_s_', 60, 0)), 'min is inclusive');
        $this->assertSame(['bc_s_alpha', 'bc_s_mid'], $titles($call(0, 100, 'bc_s_', 0, 0, 'all', $cat)));
        $this->assertSame(2, $call(0, 100, 'bc_s_', 0, 0, 'count', $cat));
        $this->assertSame([], $call(0, 100, "x' OR '1'='1"));
    }
}
