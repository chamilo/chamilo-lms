<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;
use ReflectionMethod;

/**
 * Tier 3 (batch b) characterization of the course-creation service-sale, hosting-limit, frozen-enrollment and
 * expiry queries of BuyCoursesPlugin, written to pass on the Database::query() implementation before it is moved
 * to the DQL / DBAL QueryBuilder. Fixtures prefix bc_ and are rolled back by DAMA.
 */
final class BuyCoursesTier3bCharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    private function callPrivate(string $method, array $args = []): mixed
    {
        $m = new ReflectionMethod(BuyCoursesPlugin::class, $method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->plugin, $args);
    }

    private function fieldId(string $variable): int
    {
        $this->callPrivate('ensureUserExtraField', [$variable, $variable]);

        return (int) $this->conn->fetchOne('SELECT id FROM extra_field WHERE variable = ? AND item_type = 1', [$variable]);
    }

    private function makeService(array $override = [], ?int $maxCoursesGranted = null): int
    {
        $this->conn->insert('plugin_buycourses_services', array_merge([
            'name' => 'bc_service', 'description' => 'bc_desc', 'price' => '100.00', 'duration_days' => 30,
            'applies_to' => 1, 'owner_id' => 1, 'active' => 1, 'visibility' => 1, 'video_url' => '',
            'image' => '', 'service_information' => '', 'display_on_course_creation_page' => 1,
        ], $override));
        $id = (int) $this->conn->lastInsertId();
        if (null !== $maxCoursesGranted) {
            $this->conn->insert('plugin_buycourses_service_rel_extra_field', [
                'service_id' => $id,
                'extra_field_id' => $this->fieldId(BuyCoursesPlugin::EXTRA_FIELD_MAX_COURSES),
                'granted_value' => $maxCoursesGranted,
            ]);
        }

        return $id;
    }

    private function makeServiceSale(int $serviceId, array $override = []): int
    {
        $this->conn->insert('plugin_buycourses_service_sale', array_merge([
            'service_id' => $serviceId,
            'reference' => 'bc_sref_'.uniqid('', true),
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'price' => '100.00', 'node_type' => 1, 'node_id' => 2, 'buyer_id' => 2,
            'buy_date' => '2026-01-10 08:00:00', 'date_start' => '2026-01-10 08:00:00',
            'date_end' => '2099-02-09 08:00:00', 'status' => 1, 'payment_type' => 2, 'invoice' => 0,
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    private function link(int $saleId, int $serviceId, int $courseId, array $override = []): int
    {
        $this->conn->insert('plugin_buycourses_subscription_course', array_merge([
            'service_sale_id' => $saleId, 'service_id' => $serviceId, 'course_id' => $courseId, 'user_id' => 2,
            'status' => 'active', 'created_at' => '2026-01-01 00:00:00',
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    private function clearLinks(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_subscription_course');
    }

    public function testDisplayedCourseCreationServiceBenefitNeedsAnActiveVisibleUserServiceGrantingMoreThanTheThreshold(): void
    {
        $this->setPluginSettings(['include_services' => 'true']);
        $variable = BuyCoursesPlugin::EXTRA_FIELD_MAX_COURSES;
        $fieldId = $this->fieldId($variable);
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_services');

        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable));

        $good = $this->makeService([], 5);
        $this->assertTrue($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable));
        $this->assertTrue($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable, 4));
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable, 5), 'strictly greater');
        $this->assertTrue($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable, -7), 'negative threshold floors at 0');
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit(BuyCoursesPlugin::EXTRA_FIELD_HOSTING_LIMIT));
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit('unknown_variable'));

        $this->setPluginSettings(['include_services' => 'false']);
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable));
        $this->setPluginSettings(['include_services' => 'true']);

        foreach (['display_on_course_creation_page' => 0, 'applies_to' => 2, 'visibility' => 0, 'active' => 0] as $column => $value) {
            $this->conn->executeStatement("UPDATE plugin_buycourses_services SET $column = ? WHERE id = ?", [$value, $good]);
            $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable), $column);
            $this->conn->executeStatement("UPDATE plugin_buycourses_services SET $column = 1 WHERE id = ?", [$good]);
            $this->conn->executeStatement('UPDATE plugin_buycourses_services SET applies_to = 1 WHERE id = ?', [$good]);
        }
        $this->assertTrue($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable));

        // A service that grants the value on another field does not count.
        $this->conn->executeStatement('UPDATE plugin_buycourses_service_rel_extra_field SET extra_field_id = ? WHERE service_id = ?', [$fieldId + 100000, $good]);
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceBenefit($variable));
    }

    public function testDisplayedCourseCreationServiceAiFeatureReadsTheFeatureListOfEligibleServices(): void
    {
        $this->setPluginSettings(['include_services' => 'true']);
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_services');
        $tutor = BuyCoursesPlugin::AI_COURSE_FEATURE_TUTOR_CHATBOT;

        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature($tutor));

        $this->makeService(['ai_course_features_json' => 'not json']);
        $this->makeService(['ai_course_features_json' => null]);
        $this->makeService(['ai_course_features_json' => '["toolbox"]', 'display_on_course_creation_page' => 0]);
        $this->makeService(['ai_course_features_json' => '["toolbox"]', 'active' => 0]);
        $this->makeService(['ai_course_features_json' => '["toolbox"]', 'visibility' => 0]);
        $this->makeService(['ai_course_features_json' => '["toolbox"]', 'applies_to' => 2]);
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature('toolbox'));

        $this->makeService(['ai_course_features_json' => '["exercise_generator","tutor_chatbot","bogus"]']);
        $this->assertTrue($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature($tutor));
        $this->assertTrue($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature('exercise_generator'));
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature('toolbox'));
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature('bogus'));
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature(''));

        $this->setPluginSettings(['include_services' => 'false']);
        $this->assertFalse($this->plugin->hasActiveDisplayedCourseCreationServiceAiFeature($tutor));
    }

    public function testCourseCreationServiceSaleSelectionStatusValidatesTheSaleAndTheCourseAllowance(): void
    {
        $this->setPluginSettings(['include_services' => 'true']);
        $this->clearLinks();
        $service = $this->makeService(['name' => 'bc_selected', 'renewable' => 1], 2);
        $sale = $this->makeServiceSale($service);

        $status = $this->plugin->getCourseCreationServiceSaleSelectionStatus(2, $sale);
        $this->assertSame(['valid', 'message', 'reason', 'sale', 'benefits', 'usedCourses', 'maxCourses'], array_keys($status));
        $this->assertTrue($status['valid']);
        $this->assertSame('', $status['message']);
        $this->assertNull($status['reason']);
        $this->assertSame(0, $status['usedCourses']);
        $this->assertSame(2, $status['maxCourses']);
        $this->assertSame(
            ['maxCourses' => 2, 'hostingLimit' => 0, 'documentQuotaMb' => 0, 'aiFeatures' => []],
            $status['benefits']
        );
        $row = $status['sale'];
        $this->assertSame((string) $sale, (string) $row['id']);
        $this->assertSame((string) $service, (string) $row['service_id']);
        $this->assertSame('2', (string) $row['buyer_id']);
        $this->assertSame('100.00', $row['price']);
        $this->assertSame('2099-02-09 08:00:00', $row['date_end']);
        $this->assertSame('bc_selected', $row['service_name']);
        $this->assertSame('1', (string) $row['display_on_course_creation_page']);
        $this->assertSame('1', (string) $row['applies_to']);
        $this->assertSame('1', (string) $row['visibility']);
        $this->assertSame('1', (string) $row['renewable']);
        $this->assertSame(2, $row['max_courses_with_benefits']);
        $this->assertSame(0, $row['hosting_limit']);
        $this->assertSame(0, $row['document_quota_mb']);
        $this->assertSame([], $row['ai_features']);
        $named = array_values(array_filter(array_keys($row), 'is_string'));
        $this->assertSame(['service_name', 'display_on_course_creation_page', 'applies_to', 'visibility', 'renewable', 'max_courses_with_benefits', 'hosting_limit', 'document_quota_mb', 'ai_features'], array_slice($named, -9));

        $this->link($sale, $service, 1);
        $status = $this->plugin->getCourseCreationServiceSaleSelectionStatus(2, $sale);
        $this->assertTrue($status['valid']);
        $this->assertSame(1, $status['usedCourses']);

        $this->link($sale, $service, 2);
        $status = $this->plugin->getCourseCreationServiceSaleSelectionStatus(2, $sale);
        $this->assertFalse($status['valid']);
        $this->assertSame('service_course_limit_reached', $status['reason']);
        $this->assertSame(2, $status['sale']['max_courses_with_benefits']);
        $this->assertArrayNotHasKey('benefits', $status);

        $unavailable = fn (int $user, int $saleId): ?string => $this->plugin->getCourseCreationServiceSaleSelectionStatus($user, $saleId)['reason'];
        $this->assertSame('invalid_selection', $unavailable(0, $sale));
        $this->assertSame('invalid_selection', $unavailable(2, 0));
        $this->assertSame('service_sale_unavailable', $unavailable(3, $sale), 'other buyer');
        $this->assertSame('service_sale_unavailable', $unavailable(2, $sale + 1000));

        $this->setPluginSettings(['include_services' => 'false']);
        $this->assertSame('services_disabled', $unavailable(2, $sale));
        $this->setPluginSettings(['include_services' => 'true']);

        $this->clearLinks();
        $this->conn->executeStatement('UPDATE plugin_buycourses_service_sale SET status = 0 WHERE id = ?', [$sale]);
        $this->assertSame('service_sale_unavailable', $unavailable(2, $sale));
        $this->conn->executeStatement("UPDATE plugin_buycourses_service_sale SET status = 1, date_end = '2000-01-01 00:00:00' WHERE id = ?", [$sale]);
        $this->assertSame('service_sale_unavailable', $unavailable(2, $sale), 'expired');
    }

    public function testCourseCreationServiceSaleSelectionStatusRefusesServicesNotOfferedOnCourseCreation(): void
    {
        $this->setPluginSettings(['include_services' => 'true']);
        $this->clearLinks();
        foreach ([['display_on_course_creation_page' => 0], ['applies_to' => 2], ['visibility' => 0]] as $override) {
            $service = $this->makeService($override);
            $sale = $this->makeServiceSale($service);
            $this->assertSame(
                'service_sale_unavailable',
                $this->plugin->getCourseCreationServiceSaleSelectionStatus(2, $sale)['reason'],
                json_encode($override)
            );
        }

        // An inactive service (active = 0) is still accepted for a sale already bought; no max benefit means unlimited.
        $service = $this->makeService(['active' => 0]);
        $sale = $this->makeServiceSale($service);
        $status = $this->plugin->getCourseCreationServiceSaleSelectionStatus(2, $sale);
        $this->assertTrue($status['valid']);
        $this->assertSame(0, $status['maxCourses']);
    }

    public function testBestActiveServiceSaleForCourseCreationPrefersTheSaleWithMostRemainingSlots(): void
    {
        $this->clearLinks();
        $service = $this->makeService([], 2);
        $otherService = $this->makeService();
        $latest = $this->makeServiceSale($service, ['date_end' => '2099-05-01 00:00:00']);
        $older = $this->makeServiceSale($service, ['date_end' => '2099-03-01 00:00:00']);
        $sameEnd = $this->makeServiceSale($service, ['date_end' => '2099-03-01 00:00:00']);
        $this->makeServiceSale($service, ['date_end' => '2000-03-01 00:00:00']);
        $this->makeServiceSale($service, ['status' => 0, 'date_end' => '2099-09-01 00:00:00']);
        $this->makeServiceSale($service, ['buyer_id' => 3, 'date_end' => '2099-09-01 00:00:00']);
        $this->makeServiceSale($otherService, ['date_end' => '2099-09-01 00:00:00']);

        // Unlimited: the first row of the ordering (date_end DESC, id DESC) wins ties.
        $best = $this->plugin->getBestActiveServiceSaleForCourseCreation(2, $service, 0);
        $this->assertSame($latest, (int) $best['id']);
        $this->assertSame('100.00', $best['price']);
        $this->assertSame('2099-05-01 00:00:00', $best['date_end']);

        // With a cap of 2 and one course on the latest sale, the sale ordered next (highest id of the tied ones) has more slots.
        $this->link($latest, $service, 1);
        $best = $this->plugin->getBestActiveServiceSaleForCourseCreation(2, $service, 2);
        $this->assertSame($sameEnd, (int) $best['id']);

        $this->link($sameEnd, $service, 2);
        $best = $this->plugin->getBestActiveServiceSaleForCourseCreation(2, $service, 2);
        $this->assertSame($older, (int) $best['id']);

        $this->assertNull($this->plugin->getBestActiveServiceSaleForCourseCreation(0, $service));
        $this->assertNull($this->plugin->getBestActiveServiceSaleForCourseCreation(2, 0));
        $this->assertNull($this->plugin->getBestActiveServiceSaleForCourseCreation(3 + 1000, $service));
        $this->assertNull($this->plugin->getBestActiveServiceSaleForCourseCreation(2, $service + 9999));
    }

    public function testCountCoursesLinkedToServiceSaleCountsDistinctExistingNotDeletedCourses(): void
    {
        $this->clearLinks();
        $service = $this->makeService();
        $sale = $this->makeServiceSale($service);
        $other = $this->makeServiceSale($service);

        $this->assertSame(0, $this->plugin->countCoursesLinkedToServiceSale($sale));
        $this->assertSame(0, $this->plugin->countCoursesLinkedToServiceSale(0));
        $this->link($sale, $service, 1);
        $this->link($other, $service, 2);
        $this->assertSame(1, $this->plugin->countCoursesLinkedToServiceSale($sale));
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET status = 'closed' WHERE course_id = 1");
        $this->assertSame(1, $this->plugin->countCoursesLinkedToServiceSale($sale), 'closed still counts');
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET status = 'deleted' WHERE course_id = 1");
        $this->assertSame(0, $this->plugin->countCoursesLinkedToServiceSale($sale));
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET status = 'active', deleted_at = '2026-01-02 00:00:00' WHERE course_id = 1");
        $this->assertSame(0, $this->plugin->countCoursesLinkedToServiceSale($sale));
        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_course SET deleted_at = NULL WHERE course_id = 1');
        $this->assertSame(1, $this->plugin->countCoursesLinkedToServiceSale($sale));
        // A link to a course that does not exist is ignored.
        $this->link($sale, $service, 987654);
        $this->assertSame(1, $this->plugin->countCoursesLinkedToServiceSale($sale));
    }

    public function testReassignExpiredSubscriptionCoursesMovesCoursesToActiveSalesWithinTheAllowance(): void
    {
        $this->clearLinks();
        $service = $this->makeService([], 1);
        $expired = $this->makeServiceSale($service, ['date_end' => '2000-01-01 00:00:00']);
        $empty = ['found' => 0, 'reassigned' => 0, 'remaining' => 0, 'errors' => 0, 'reassigned_subscription_course_ids' => []];

        $this->assertSame($empty, $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales(0));
        $this->assertSame(array_replace($empty, ['errors' => 1]), $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired + 5000));
        $this->assertSame($empty, $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired));

        $l1 = $this->link($expired, $service, 1, ['context_json' => '{"keep":"me"}']);
        $l2 = $this->link($expired, $service, 2, ['context_json' => 'broken']);
        $this->assertSame(
            array_replace($empty, ['found' => 2, 'remaining' => 2]),
            $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired),
            'no replacement sale'
        );

        // Not replacements: another buyer, not completed, not started yet, expired, another service.
        $this->makeServiceSale($service, ['buyer_id' => 3]);
        $this->makeServiceSale($service, ['status' => 0]);
        $this->makeServiceSale($service, ['date_start' => '2099-01-01 00:00:00']);
        $this->makeServiceSale($service, ['date_end' => '2001-01-01 00:00:00']);
        $this->makeServiceSale($this->makeService(), []);
        $this->assertSame(
            array_replace($empty, ['found' => 2, 'remaining' => 2]),
            $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired)
        );

        $replacement = $this->makeServiceSale($service, [
            'date_start' => null, 'date_end' => '2099-07-01 00:00:00',
            'recurring_profile_id' => 'rp_1', 'gateway_subscription_id' => 'gw_1',
        ]);

        // Allowance 1: only the first course (lowest link id) fits; a dry run changes nothing.
        $dry = $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired, true);
        $this->assertSame(['found' => 2, 'reassigned' => 1, 'remaining' => 1, 'errors' => 0, 'reassigned_subscription_course_ids' => [$l1]], $dry);
        $this->assertSame((string) $expired, (string) $this->conn->fetchOne('SELECT service_sale_id FROM plugin_buycourses_subscription_course WHERE id = ?', [$l1]));

        $real = $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired);
        $this->assertSame($dry, $real);
        $row = $this->conn->fetchAssociative('SELECT * FROM plugin_buycourses_subscription_course WHERE id = ?', [$l1]);
        $this->assertSame((string) $replacement, (string) $row['service_sale_id']);
        $this->assertSame('active', $row['status']);
        $this->assertSame('reassigned', $row['last_action']);
        $context = json_decode($row['context_json'], true);
        unset($context['reassigned_at']);
        $this->assertSame([
            'keep' => 'me',
            'service_sale_id' => $replacement,
            'service_id' => $service,
            'date_start' => '',
            'date_end' => '2099-07-01 00:00:00',
            'recurring_profile_id' => 'gw_1',
            'status' => 'active',
            'reassigned_from_service_sale_id' => $expired,
            'last_action' => 'reassigned',
        ], $context);
        $this->assertSame((string) $expired, (string) $this->conn->fetchOne('SELECT service_sale_id FROM plugin_buycourses_subscription_course WHERE id = ?', [$l2]));

        // A sale that is itself still running is never its own replacement: the only other sale (the one above) is
        // full, and the running sale has a free slot (its link is on a course that does not exist) that must not be used.
        $running = $this->makeServiceSale($service, ['date_end' => '2099-08-01 00:00:00']);
        $this->link($running, $service, 987655);
        $this->assertSame(
            array_replace($empty, ['found' => 1, 'remaining' => 1]),
            $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($running, true)
        );
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_subscription_course WHERE course_id = 987655');
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_service_sale WHERE id = ?', [$running]);

        // The replacement is now full: nothing else moves.
        $again = $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired);
        $this->assertSame(array_replace($empty, ['found' => 1, 'remaining' => 1]), $again);

        // Unlimited allowance (no benefit row): the rest moves.
        $this->conn->executeStatement('UPDATE plugin_buycourses_service_rel_extra_field SET granted_value = 0 WHERE service_id = ?', [$service]);
        $rest = $this->plugin->reassignExpiredSubscriptionCoursesToActiveServiceSales($expired);
        $this->assertSame(['found' => 1, 'reassigned' => 1, 'remaining' => 0, 'errors' => 0, 'reassigned_subscription_course_ids' => [$l2]], $rest);
        $context = json_decode((string) $this->conn->fetchOne('SELECT context_json FROM plugin_buycourses_subscription_course WHERE id = ?', [$l2]), true);
        $this->assertSame($replacement, $context['service_sale_id']);
    }

    /**
     * @return int[] ids of the first 8 users of the test database in id order (fixture users are completed with
     *               throwaway ones, rolled back by DAMA)
     */
    private function userIds(int $minimum): array
    {
        $count = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM user');
        for ($i = $count; $i < 8; ++$i) {
            $username = 'bc_t3b_'.$i.'_'.getmypid();
            $this->conn->executeStatement(
                "INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale, password,
                    timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'bc', 'bc', 'en_US', 'x', 'UTC', 0, 0, 0, '', '[]', 5, 1, ?, '2020-01-01 00:00:00', '2020-01-01 00:00:00')",
                [$username, $username, $username.'@example.com', $username.'@example.com', random_bytes(16)]
            );
        }
        $ids = array_map('intval', $this->conn->fetchFirstColumn('SELECT id FROM user ORDER BY id LIMIT 8'));
        $this->assertGreaterThanOrEqual($minimum, count($ids));

        return $ids;
    }

    private function subscribe(int $userId, int $courseId, int $status = 5, int $relation = 0): void
    {
        $this->conn->insert('course_rel_user', [
            'user_id' => $userId, 'c_id' => $courseId, 'relation_type' => $relation, 'status' => $status, 'progress' => 0,
        ]);
    }

    private function frozenUserIds(int $courseId): array
    {
        return array_map('intval', $this->conn->fetchFirstColumn(
            'SELECT user_id FROM plugin_buycourses_frozen_enrollment WHERE course_id = ? ORDER BY user_id',
            [$courseId]
        ));
    }

    public function testManagedCourseIdsAndCourseManagerIdsIgnoreHrmAndStudentRelations(): void
    {
        [$a, $b, $c] = $this->userIds(3);
        $this->conn->executeStatement('DELETE FROM course_rel_user WHERE c_id IN (1, 2)');
        $this->subscribe($b, 2, 1);
        $this->subscribe($b, 1, 1);
        $this->subscribe($b, 1, 1);
        $this->subscribe($b, 2, 1, 1); // HRM link: not managed
        $this->subscribe($c, 1, 5);    // student: not a manager
        $this->subscribe($a, 1, 1);
        $this->subscribe($c, 2, 1, 1);

        $this->assertSame([1, 2], $this->plugin->getManagedCourseIdsByUser($b), 'distinct, ascending');
        $this->assertSame([1], $this->plugin->getManagedCourseIdsByUser($a));
        $this->assertSame([], $this->plugin->getManagedCourseIdsByUser($c));
        $this->assertSame([], $this->plugin->getManagedCourseIdsByUser(0));

        $this->assertSame([min($a, $b), max($a, $b)], $this->plugin->getCourseManagerIdsForCourse(1));
        $this->assertSame([$b], $this->plugin->getCourseManagerIdsForCourse(2));
        $this->assertSame([], $this->plugin->getCourseManagerIdsForCourse(0));
        $this->assertSame([], $this->plugin->getCourseManagerIdsForCourse(987654));
    }

    public function testHasActiveSubscriptionCourseNeedsAnActiveLinkOnARunningCompletedSale(): void
    {
        $this->clearLinks();
        $service = $this->makeService();
        $sale = $this->makeServiceSale($service);
        $link = $this->link($sale, $service, 1);

        $this->assertTrue($this->plugin->hasActiveSubscriptionCourse(1));
        $this->assertFalse($this->plugin->hasActiveSubscriptionCourse(2));
        $this->assertFalse($this->plugin->hasActiveSubscriptionCourse(0));

        $break = function (string $table, string $column, string $value, int $id): void {
            $this->assertFalse($this->plugin->hasActiveSubscriptionCourse(1), "$column $value");
        };
        $steps = [
            ['plugin_buycourses_service_sale', $sale, 'status = 0'],
            ['plugin_buycourses_service_sale', $sale, "date_end = '2000-01-01 00:00:00'"],
            ['plugin_buycourses_service_sale', $sale, "date_start = '2099-01-01 00:00:00'"],
            ['plugin_buycourses_subscription_course', $link, "status = 'closed'"],
            ['plugin_buycourses_subscription_course', $link, "deleted_at = '2026-01-02 00:00:00'"],
        ];
        foreach ($steps as [$table, $id, $set]) {
            $saved = $this->conn->fetchAssociative("SELECT * FROM $table WHERE id = ?", [$id]);
            $this->conn->executeStatement("UPDATE $table SET $set WHERE id = ?", [$id]);
            $this->assertFalse($this->plugin->hasActiveSubscriptionCourse(1), $set);
            $this->conn->update($table, $saved, ['id' => $id]);
            $this->assertTrue($this->plugin->hasActiveSubscriptionCourse(1), 'restored after '.$set);
        }

        // A sale without a start date is running.
        $this->conn->executeStatement('UPDATE plugin_buycourses_service_sale SET date_start = NULL WHERE id = ?', [$sale]);
        $this->assertTrue($this->plugin->hasActiveSubscriptionCourse(1));
        unset($break);
    }

    public function testHasActiveAiCourseFeatureReadsTheContextOfTheLinkBeforeTheServiceFeatures(): void
    {
        $this->clearLinks();
        $service = $this->makeService(['ai_course_features_json' => '["tutor_chatbot","toolbox"]']);
        $sale = $this->makeServiceSale($service);
        $link = $this->link($sale, $service, 1);
        $tutor = BuyCoursesPlugin::AI_COURSE_FEATURE_TUTOR_CHATBOT;

        // No stored feature list in the context: the service's list applies.
        $this->assertTrue($this->plugin->hasActiveAiCourseFeature(1, $tutor));
        $this->assertTrue($this->plugin->hasActiveAiCourseFeature(1, 'toolbox'));
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, 'task_grader'));
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, 'bogus'));
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(2, $tutor));
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(0, $tutor));

        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_course SET context_json = ? WHERE id = ?', ['{"ai_features":["task_grader"]}', $link]);
        $this->assertTrue($this->plugin->hasActiveAiCourseFeature(1, 'task_grader'));
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, $tutor), 'the stored list replaces the service list');
        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_course SET context_json = ? WHERE id = ?', ['{"ai_features":[]}', $link]);
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, $tutor));
        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_course SET context_json = ? WHERE id = ?', ['garbage', $link]);
        $this->assertTrue($this->plugin->hasActiveAiCourseFeature(1, $tutor));

        foreach (['status = 0', "date_end = '2000-01-01 00:00:00'", "date_start = '2099-01-01 00:00:00'"] as $set) {
            $this->conn->executeStatement("UPDATE plugin_buycourses_service_sale SET $set WHERE id = ?", [$sale]);
            $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, $tutor), $set);
            $this->conn->executeStatement("UPDATE plugin_buycourses_service_sale SET status = 1, date_start = '2026-01-10 08:00:00', date_end = '2099-02-09 08:00:00' WHERE id = ?", [$sale]);
        }
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET status = 'closed' WHERE id = ?", [$link]);
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, $tutor));
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET status = 'active', deleted_at = '2026-01-02 00:00:00' WHERE id = ?", [$link]);
        $this->assertFalse($this->plugin->hasActiveAiCourseFeature(1, $tutor));
    }

    public function testActiveSubscriptionCourseHostingLimitReadsTheLinkContext(): void
    {
        $this->clearLinks();
        $service = $this->makeService();
        $sale = $this->makeServiceSale($service);
        $link = $this->link($sale, $service, 1, ['context_json' => '{"hosting_limit":25}']);

        $this->assertSame(25, $this->plugin->getActiveSubscriptionCourseHostingLimit(1));
        $this->assertNull($this->plugin->getActiveSubscriptionCourseHostingLimit(2));
        $this->assertNull($this->plugin->getActiveSubscriptionCourseHostingLimit(0));
        $this->assertSame(25, $this->plugin->getEffectiveUsersPerCourseLimitForCourse(1));

        // Unlike hasActiveSubscriptionCourse(), neither deleted_at nor date_start is looked at.
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET deleted_at = '2026-01-02 00:00:00' WHERE id = ?", [$link]);
        $this->conn->executeStatement("UPDATE plugin_buycourses_service_sale SET date_start = '2099-01-01 00:00:00' WHERE id = ?", [$sale]);
        $this->assertSame(25, $this->plugin->getActiveSubscriptionCourseHostingLimit(1));

        foreach (['{"hosting_limit":0}', '{"hosting_limit":-3}', '{}', 'garbage', '[1]'] as $context) {
            $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_course SET context_json = ? WHERE id = ?', [$context, $link]);
            $this->assertNull($this->plugin->getActiveSubscriptionCourseHostingLimit(1), $context);
        }
        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_course SET context_json = ? WHERE id = ?', ['{"hosting_limit":"7"}', $link]);
        $this->assertSame(7, $this->plugin->getActiveSubscriptionCourseHostingLimit(1));

        foreach (['status = 0', "date_end = '2000-01-01 00:00:00'"] as $set) {
            $this->conn->executeStatement("UPDATE plugin_buycourses_service_sale SET $set WHERE id = ?", [$sale]);
            $this->assertNull($this->plugin->getActiveSubscriptionCourseHostingLimit(1), $set);
            $this->conn->executeStatement("UPDATE plugin_buycourses_service_sale SET status = 1, date_end = '2099-02-09 08:00:00' WHERE id = ?", [$sale]);
        }
        $this->conn->executeStatement("UPDATE plugin_buycourses_subscription_course SET status = 'closed' WHERE id = ?", [$link]);
        $this->assertNull($this->plugin->getActiveSubscriptionCourseHostingLimit(1));
    }

    public function testStudentCountAndHostingLimitCheckCountDirectAndSessionStudentsOnce(): void
    {
        [$a, $b, $c, $d, $e] = $this->userIds(5);
        $this->conn->executeStatement('DELETE FROM course_rel_user WHERE c_id IN (1, 2)');
        $this->conn->insert('session', [
            'title' => 'bc_hosting_session', 'nbr_courses' => 0, 'nbr_users' => 0, 'nbr_classes' => 0, 'visibility' => 1, 'status' => 0,
        ]);
        $sessionId = (int) $this->conn->lastInsertId();
        $sessionStudent = function (int $user, int $course, int $status = 5) use ($sessionId): void {
            $this->conn->insert('session_rel_course_rel_user', [
                'session_id' => $sessionId, 'c_id' => $course, 'user_id' => $user, 'status' => $status,
                'visibility' => 0, 'legal_agreement' => 0, 'progress' => 0,
            ]);
        };

        $this->assertSame(0, $this->plugin->countStudentsForCourseHostingLimit(1));
        $this->assertSame(0, $this->plugin->countStudentsForCourseHostingLimit(0));

        $this->subscribe($a, 1, 5);
        $this->subscribe($b, 1, 5, 1); // HRM relation
        $this->subscribe($c, 1, 1);    // teacher
        $this->subscribe($d, 2, 5);    // other course
        $this->assertSame(1, $this->plugin->countStudentsForCourseHostingLimit(1));
        $sessionStudent($a, 1);        // same student in a session: counted once
        $sessionStudent($e, 1);
        $sessionStudent($b, 1, 2);     // session coach status: not a student
        $sessionStudent($c, 1, 0);     // status 0 is not STUDENT (5) either
        $this->assertSame(2, $this->plugin->countStudentsForCourseHostingLimit(1));
        $this->assertSame(1, $this->plugin->countStudentsForCourseHostingLimit(2));

        // Effective limit 3 through a service linked to course 1.
        $this->clearLinks();
        $service = $this->makeService();
        $sale = $this->makeServiceSale($service);
        $this->link($sale, $service, 1, ['context_json' => '{"hosting_limit":3}']);

        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$d]), '2 + 1 = 3 is not above the limit');
        $this->assertTrue($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$d, $this->userIds(6)[5]]), '2 + 2 = 4');
        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$a, $e, $d]), 'two are already subscribed, only one is new');
        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$a, $a, 0, -4]));
        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, []));
        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(0, [$d]));
        // An HRM link, a teacher link or a session coach row all count as "already there", so those users are not new.
        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$b, $c, $d, $a]), 'only $d is new');
        $this->subscribe($d, 1, 5);
        $this->assertSame(3, $this->plugin->countStudentsForCourseHostingLimit(1));
        $this->assertTrue($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$this->userIds(6)[5]]), '3 + 1 = 4');
        $this->assertFalse($this->plugin->wouldCourseUserSubscriptionExceedHostingLimit(1, [$d]));
    }

    public function testFrozenEnrollmentChecksAndFreezeExcessKeepsTheLowestUserIdsOfTheStudents(): void
    {
        [$a, $b, $c, $d] = $this->userIds(4);
        $this->conn->executeStatement('DELETE FROM course_rel_user WHERE c_id IN (1, 2)');
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_frozen_enrollment');
        foreach ([$d, $b, $a, $c] as $student) {
            $this->subscribe($student, 1, 5);
        }
        $teacher = $this->userIds(5)[4];
        $this->subscribe($teacher, 1, 1);
        $hrm = $this->userIds(6)[5];
        $this->subscribe($hrm, 1, 5, 1);
        $this->subscribe($a, 2, 5);
        $sorted = [$a, $b, $c, $d];
        sort($sorted);

        $this->assertFalse($this->plugin->isFrozenEnrollment(1, $a));
        $this->plugin->freezeExcessEnrollmentsForCourse(1, 2);
        $this->assertSame([$sorted[2], $sorted[3]], $this->frozenUserIds(1), 'students beyond the limit, highest ids; no teacher, no HRM');
        $this->assertTrue($this->plugin->isFrozenEnrollment(1, $sorted[3]));
        $this->assertFalse($this->plugin->isFrozenEnrollment(1, $sorted[0]));
        $this->assertFalse($this->plugin->isFrozenEnrollment(2, $sorted[3]));
        $this->assertFalse($this->plugin->isFrozenEnrollment(0, $sorted[3]));
        $this->assertFalse($this->plugin->isFrozenEnrollment(1, 0));

        // A new run rebuilds the list from scratch.
        $this->plugin->freezeExcessEnrollmentsForCourse(1, 3);
        $this->assertSame([$sorted[3]], $this->frozenUserIds(1));
        // Not above the limit: the list is cleared.
        $this->plugin->freezeExcessEnrollmentsForCourse(1, 4);
        $this->assertSame([], $this->frozenUserIds(1));
        $this->plugin->freezeExcessEnrollmentsForCourse(1, 1);
        $this->assertCount(3, $this->frozenUserIds(1));
        // A limit of zero or less clears.
        $this->plugin->freezeExcessEnrollmentsForCourse(1, 0);
        $this->assertSame([], $this->frozenUserIds(1));

        $this->conn->insert('plugin_buycourses_frozen_enrollment', ['course_id' => 2, 'user_id' => $a, 'frozen_since' => '2026-01-01 00:00:00']);
        $this->conn->insert('plugin_buycourses_frozen_enrollment', ['course_id' => 1, 'user_id' => $a, 'frozen_since' => '2026-01-01 00:00:00']);
        $this->plugin->clearFrozenEnrollmentsForCourse(1);
        $this->assertSame([], $this->frozenUserIds(1));
        $this->assertSame([$a], $this->frozenUserIds(2), 'only the requested course is cleared');
        $this->plugin->clearFrozenEnrollmentsForCourse(0);
        $this->assertSame([$a], $this->frozenUserIds(2));
        $this->plugin->freezeExcessEnrollmentsForCourse(0, 1);
        $this->assertSame([$a], $this->frozenUserIds(2));
    }

    public function testApplyAiCourseFeatureSettingsWritesOneSettingRowPerFeature(): void
    {
        $definitions = array_keys($this->plugin->getAiCourseFeatureDefinitions());
        $this->conn->executeStatement('DELETE FROM c_course_setting WHERE c_id = 1');
        $rows = fn (): array => $this->conn->fetchAllAssociative(
            'SELECT variable, value, category, title, subkey, type, comment FROM c_course_setting WHERE c_id = 1 ORDER BY variable'
        );

        $this->plugin->applyAiCourseFeatureSettingsToCourse(1, ['toolbox', 'tutor_chatbot', 'bogus']);
        $expected = [];
        foreach ($definitions as $feature) {
            $expected[$feature] = [
                'variable' => $feature, 'value' => in_array($feature, ['toolbox', 'tutor_chatbot'], true) ? 'true' : 'false',
                'category' => 'ai_helpers', 'title' => '', 'subkey' => null, 'type' => null, 'comment' => null,
            ];
        }
        ksort($expected);
        $this->assertSame(array_values($expected), $rows());
        $this->assertGreaterThanOrEqual(10, count($expected));

        // A second call updates the existing rows in place.
        $this->plugin->applyAiCourseFeatureSettingsToCourse(1, ['task_grader']);
        $after = $rows();
        $this->assertCount(count($definitions), $after);
        $values = array_column($after, 'value', 'variable');
        $this->assertSame('true', $values['task_grader']);
        $this->assertSame('false', $values['toolbox']);

        // A setting of the same name in another category is taken over; other courses are not touched.
        $this->conn->executeStatement("UPDATE c_course_setting SET category = 'other', title = 'kept' WHERE c_id = 1 AND variable = 'toolbox'");
        $this->plugin->applyAiCourseFeatureSettingsToCourse(1, ['toolbox']);
        $toolbox = $this->conn->fetchAssociative("SELECT value, category, title FROM c_course_setting WHERE c_id = 1 AND variable = 'toolbox'");
        $this->assertSame(['value' => 'true', 'category' => 'ai_helpers', 'title' => 'kept'], $toolbox);
        $this->assertSame(0, (int) $this->conn->fetchOne("SELECT COUNT(*) FROM c_course_setting WHERE c_id = 0 AND category = 'ai_helpers'"));

        $this->plugin->applyAiCourseFeatureSettingsToCourse(0, ['toolbox']);
        $this->assertSame(0, (int) $this->conn->fetchOne("SELECT COUNT(*) FROM c_course_setting WHERE c_id = 0 AND category = 'ai_helpers'"));
    }
}
