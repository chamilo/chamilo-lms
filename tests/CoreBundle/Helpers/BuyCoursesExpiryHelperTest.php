<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Helpers\BuyCoursesExpiryHelper;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Legacy\BuyCoursesCharacterizationTestCase;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Plugin;
use ReflectionProperty;

/**
 * Expiry of the BuyCourses service benefits (app:buycourses:expiry cron and the renewal path of the plugin): closes
 * the managed courses above the course-creation limit, freezes the students above the hosting limit and reopens
 * closed courses on renewal. Gated by the plugin being enabled on the current access URL AND its tables existing.
 * The plugin counts as enabled when a payment method is on (BuyCoursesPlugin::isEnabled()), forced on the plugin
singleton inside each test and restored afterwards; the platform limits are the
 * max_courses_per_user / hosting_limit_users_per_course settings rows, the service grants are the installer
 * extra fields buycourses_max_courses / buycourses_hosting_limit. Users are raw "rp_" rows, courses are 1 and 2
 * (their visibility is changed and rolled back). Dates are fixed in 2001 (expired) or 2999 (active).
 */
final class BuyCoursesExpiryHelperTest extends BuyCoursesCharacterizationTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private BuyCoursesExpiryHelper $helper;
    private mixed $previousSettings = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSettings = $this->singletonSettings()->getValue(BuyCoursesPlugin::create());
        $this->singletonSettings()->setValue(BuyCoursesPlugin::create(), []);
        $this->helper = new BuyCoursesExpiryHelper($this->conn());
    }

    /**
     * BuyCoursesPlugin::isEnabled() is "any payment method is switched on" and the helper reads it from the plugin
     * singleton, so the singleton's cached settings are replaced for the test (and restored in tearDown()).
     */
    private function enablePlugin(): void
    {
        $this->singletonSettings()->setValue(BuyCoursesPlugin::create(), ['paypal_enable' => 'true']);
        $this->helper = new BuyCoursesExpiryHelper($this->conn());
    }

    private function singletonSettings(): ReflectionProperty
    {
        $prop = new ReflectionProperty(Plugin::class, 'settings');
        $prop->setAccessible(true);

        return $prop;
    }

    protected function tearDown(): void
    {
        $this->singletonSettings()->setValue(BuyCoursesPlugin::create(), $this->previousSettings);
        parent::tearDown();
    }

    private function setSetting(string $variable, string $value): void
    {
        $this->conn()->executeStatement('UPDATE settings SET selected_value = ? WHERE variable = ?', [$value, $variable]);
    }

    private function ensureService(int $serviceId): void
    {
        $this->conn()->executeStatement(
            "INSERT IGNORE INTO plugin_buycourses_services (id, name, description, price, duration_days, renewable, total_charges, allow_trial, trial_frequency, trial_total_charges, max_subscribers, display_on_course_creation_page, applies_to, owner_id, active, visibility, video_url, image, service_information)
             VALUES (?, ?, '', 1.00, 30, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, '', '', '')",
            [$serviceId, 'sv2_service_'.$serviceId]
        );
    }

    private function sale(int $buyerId, int $serviceId, string $dateEnd, int $status = 1): int
    {
        $this->ensureService($serviceId);
        $currency = (int) $this->conn()->fetchOne('SELECT MIN(id) FROM plugin_buycourses_currency');
        $this->conn()->executeStatement(
            "INSERT INTO plugin_buycourses_service_sale (service_id, reference, currency_id, price, node_type, node_id, buyer_id, buy_date, date_end, status, trial, payment_type, recurring_payment, invoice)
             VALUES (?, ?, ?, 1.00, 1, 1, ?, '2000-01-01 00:00:00', ?, ?, 0, 1, 0, 0)",
            [$serviceId, 'sv2_'.getmypid().'_'.(++$this->rpSeq), $currency, $buyerId, $dateEnd, $status]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function grant(int $serviceId, string $variable, int $value): void
    {
        $fieldId = (int) $this->conn()->fetchOne('SELECT id FROM extra_field WHERE variable = ? AND item_type = 1', [$variable]);
        $this->conn()->executeStatement('INSERT INTO plugin_buycourses_service_rel_extra_field (service_id, extra_field_id, granted_value) VALUES (?, ?, ?)', [$serviceId, $fieldId, $value]);
    }

    private function visibility(int $courseId): int
    {
        return (int) $this->conn()->fetchOne('SELECT visibility FROM course WHERE id = ?', [$courseId]);
    }

    private function frozen(int $courseId): array
    {
        return array_map('intval', $this->conn()->fetchFirstColumn('SELECT user_id FROM plugin_buycourses_frozen_enrollment WHERE course_id = ? ORDER BY user_id', [$courseId]));
    }

    private function subscriptionCourse(int $saleId, int $serviceId, int $courseId, int $userId, string $status, ?string $context = null): int
    {
        $this->conn()->executeStatement(
            "INSERT INTO plugin_buycourses_subscription_course (service_sale_id, service_id, course_id, user_id, status, created_at, context_json)
             VALUES (?, ?, ?, ?, ?, '2000-01-01 00:00:00', ?)",
            [$saleId, $serviceId, $courseId, $userId, $status, $context]
        );

        return (int) $this->conn()->lastInsertId();
    }

    public function testNothingHappensWhileThePluginIsDisabled(): void
    {
        $teacher = $this->rawUser('t');
        $student = $this->rawUser('s');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $this->sale($teacher, 910001, '2001-01-01 00:00:00');
        $this->conn()->executeStatement("INSERT INTO plugin_buycourses_frozen_enrollment (course_id, user_id, frozen_since) VALUES (1, ?, '2001-01-01 00:00:00')", [$student]);
        $before = [$this->visibility(1), $this->visibility(2)];

        $this->assertSame(0, $this->helper->processExpiredServiceBenefits());
        $this->assertFalse($this->helper->isFrozenEnrollment(1, $student), 'A frozen row is ignored when the plugin is off');
        $this->assertSame(0, $this->helper->reactivateClosedCoursesForUser($teacher));
        $this->assertSame($before, [$this->visibility(1), $this->visibility(2)]);
    }

    public function testIsFrozenEnrollmentReadsTheFrozenTableForThatCourseAndUser(): void
    {
        $this->enablePlugin();
        $a = $this->rawUser('a');
        $b = $this->rawUser('b');
        $this->conn()->executeStatement("INSERT INTO plugin_buycourses_frozen_enrollment (course_id, user_id, frozen_since) VALUES (1, ?, '2001-01-01 00:00:00')", [$a]);

        $this->assertTrue($this->helper->isFrozenEnrollment(1, $a));
        $this->assertFalse($this->helper->isFrozenEnrollment(2, $a), 'Frozen in course 1 only');
        $this->assertFalse($this->helper->isFrozenEnrollment(1, $b));
        $this->assertFalse($this->helper->isFrozenEnrollment(0, $a));
        $this->assertFalse($this->helper->isFrozenEnrollment(1, 0));
        $this->assertFalse($this->helper->isFrozenEnrollment(-1, -1));
    }

    public function testCoursesAboveThePlatformLimitAreClosedLowestIdsKept(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $this->sale($teacher, 910002, '2001-01-01 00:00:00');
        $keptBefore = $this->visibility(1);

        $this->assertSame(1, $this->helper->processExpiredServiceBenefits());

        $this->assertSame($keptBefore, $this->visibility(1), 'The first course by id stays');
        $this->assertSame(0, $this->visibility(2), 'The excess course is closed');
    }

    public function testUnexpiredOrNonCompletedSalesDoNotTriggerTheSweep(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $this->sale($teacher, 910003, '2999-01-01 00:00:00');    // still running
        $this->sale($teacher, 910004, '2001-01-01 00:00:00', 0); // not completed
        $before = $this->visibility(2);

        $this->assertSame(0, $this->helper->processExpiredServiceBenefits());
        $this->assertSame($before, $this->visibility(2));
    }

    public function testALimitStoredForAnotherAccessUrlIsNeverUsedInsteadOfTheCurrentOne(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        // The row of another URL ("no limit") sorts BEFORE the URL 1 row (its access URL id is lower): without
        // ordering by the access URL, "LIMIT 1" returned that row and the limit silently vanished.
        $this->conn()->executeStatement(
            "INSERT INTO access_url (id, lft, lvl, rgt, url, description, active, created_by) VALUES (-5, 1, 0, 2, 'http://bp-other.test/', '', 1, 1)"
        );
        $this->conn()->executeStatement(
            "INSERT INTO settings (id, access_url, variable, type, category, selected_value, title, access_url_changeable)
             VALUES (?, -5, 'max_courses_per_user', 'textfield', 'platform', '0', 'bp_other_url', 1)",
            [1 + (int) $this->conn()->fetchOne('SELECT MAX(id) FROM settings')]
        );
        $this->sale($teacher, 910006, '2001-01-01 00:00:00');

        $this->assertSame(1, $this->helper->processExpiredServiceBenefits());

        $this->assertSame(0, $this->visibility(2), 'The limit of the current URL (1 course) is applied');
    }

    public function testLimitZeroMeansNoLimit(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '0');
        $this->sale($teacher, 910005, '2001-01-01 00:00:00');
        $before = $this->visibility(2);

        $this->assertSame(1, $this->helper->processExpiredServiceBenefits(), 'The buyer is still counted as processed');
        $this->assertSame($before, $this->visibility(2));
    }

    public function testAnActiveServiceGrantOverridesThePlatformLimit(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $this->sale($teacher, 910006, '2001-01-01 00:00:00');  // expired: this is what triggers the sweep
        $this->sale($teacher, 910007, '2999-01-01 00:00:00');  // active, grants two courses
        $this->grant(910007, 'buycourses_max_courses', 2);
        $before = $this->visibility(2);

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame($before, $this->visibility(2), 'The grant of 2 keeps both courses open');
    }

    public function testAnExpiredServiceGrantIsIgnored(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $this->sale($teacher, 910008, '2001-01-01 00:00:00');
        $this->grant(910008, 'buycourses_max_courses', 5);

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame(0, $this->visibility(2), 'The expired sale cannot extend the limit');
    }

    public function testCoursesAsHrManagerOrAsStudentAreNotCounted(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1, 1); // status teacher but HRM relation
        $this->courseUser($teacher, 2, 5);    // student
        $this->setSetting('max_courses_per_user', '1');
        $this->sale($teacher, 910009, '2001-01-01 00:00:00');
        $before = [$this->visibility(1), $this->visibility(2)];

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame($before, [$this->visibility(1), $this->visibility(2)]);
    }

    public function testClosingATrackedCourseRecordsThePreviousVisibilityForRenewal(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $saleId = $this->sale($teacher, 910010, '2001-01-01 00:00:00');
        $this->conn()->executeStatement('UPDATE course SET visibility = 3 WHERE id = 2');
        $sc = $this->subscriptionCourse($saleId, 910010, 2, $teacher, 'active');

        $this->helper->processExpiredServiceBenefits();

        $row = $this->conn()->fetchAssociative('SELECT status, context_json, last_action, closed_at FROM plugin_buycourses_subscription_course WHERE id = ?', [$sc]);
        $this->assertSame('closed', $row['status']);
        $this->assertSame('closed', $row['last_action']);
        $this->assertNotNull($row['closed_at']);
        $context = json_decode($row['context_json'], true);
        $this->assertSame(3, $context['previous_visibility']);
        $this->assertSame('closed', $context['last_action']);
        $this->assertSame(0, $this->visibility(2));
    }

    public function testAlreadyClosedTrackedCourseKeepsItsRecordedVisibility(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($teacher, 2, 1);
        $this->setSetting('max_courses_per_user', '1');
        $saleId = $this->sale($teacher, 910011, '2001-01-01 00:00:00');
        $sc = $this->subscriptionCourse($saleId, 910011, 2, $teacher, 'closed', '{"previous_visibility":2}');
        $this->conn()->executeStatement('UPDATE course SET visibility = 0 WHERE id = 2');

        $this->helper->processExpiredServiceBenefits();

        $context = json_decode((string) $this->conn()->fetchOne('SELECT context_json FROM plugin_buycourses_subscription_course WHERE id = ?', [$sc]), true);
        $this->assertSame(2, $context['previous_visibility'], 'A second sweep must not overwrite the visibility with the closed value 0');
    }

    public function testStudentsAboveTheHostingLimitAreFrozenByIdAndTheListIsRecomputed(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $s1 = $this->rawUser('s1');
        $s2 = $this->rawUser('s2');
        $s3 = $this->rawUser('s3');
        $hr = $this->rawUser('hr');
        $this->courseUser($teacher, 1, 1);
        foreach ([$s1, $s2, $s3] as $s) {
            $this->courseUser($s, 1, 5);
        }
        $this->courseUser($hr, 1, 5, 1); // HRM relation is not a student of the quota
        $this->setSetting('hosting_limit_users_per_course', '2');
        $this->sale($teacher, 910012, '2001-01-01 00:00:00');
        $this->conn()->executeStatement("INSERT INTO plugin_buycourses_frozen_enrollment (course_id, user_id, frozen_since) VALUES (1, ?, '2001-01-01 00:00:00')", [$s1]);

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame([$s3], $this->frozen(1), 'Stale rows are cleared, only the students beyond the 2 first ids are frozen');
        $this->assertTrue($this->helper->isFrozenEnrollment(1, $s3));
        $this->assertFalse($this->helper->isFrozenEnrollment(1, $s1));
        $this->assertNotNull($this->conn()->fetchOne('SELECT frozen_since FROM plugin_buycourses_frozen_enrollment WHERE user_id = ?', [$s3]));
    }

    public function testHostingLimitZeroUnfreezesEveryone(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $s1 = $this->rawUser('s1');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($s1, 1, 5);
        $this->setSetting('hosting_limit_users_per_course', '0');
        $this->sale($teacher, 910013, '2001-01-01 00:00:00');
        $this->conn()->executeStatement("INSERT INTO plugin_buycourses_frozen_enrollment (course_id, user_id, frozen_since) VALUES (1, ?, '2001-01-01 00:00:00')", [$s1]);

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame([], $this->frozen(1));
    }

    public function testTheBestHostingGrantOfTheCourseTeachersRaisesTheQuota(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $coTeacher = $this->rawUser('t2');
        $s1 = $this->rawUser('s1');
        $s2 = $this->rawUser('s2');
        $this->courseUser($teacher, 1, 1);
        $this->courseUser($coTeacher, 1, 1);
        $this->courseUser($s1, 1, 5);
        $this->courseUser($s2, 1, 5);
        $this->setSetting('hosting_limit_users_per_course', '1');
        $this->sale($teacher, 910014, '2001-01-01 00:00:00');
        $this->sale($coTeacher, 910015, '2999-01-01 00:00:00');
        $this->grant(910015, 'buycourses_hosting_limit', 2);

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame([], $this->frozen(1), 'The co-teacher active grant of 2 covers both students');
    }

    public function testHostingLimitOfTheBuyerOnlyAppliesToCoursesTheyManage(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $other = $this->rawUser('o');
        $s1 = $this->rawUser('s1');
        $this->courseUser($other, 1, 1);
        $this->courseUser($s1, 1, 5);
        $this->setSetting('hosting_limit_users_per_course', '0');
        $this->setSetting('max_courses_per_user', '0');
        $this->sale($teacher, 910016, '2001-01-01 00:00:00');
        $this->conn()->executeStatement("INSERT INTO plugin_buycourses_frozen_enrollment (course_id, user_id, frozen_since) VALUES (1, ?, '2001-01-01 00:00:00')", [$s1]);

        $this->helper->processExpiredServiceBenefits();

        $this->assertSame([$s1], $this->frozen(1), 'The buyer manages no course: the frozen list of another teacher is untouched');
    }

    public function testReactivateRestoresVisibilityAndRepointsOnlyTheMatchingService(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $this->conn()->executeStatement('UPDATE course SET visibility = 0 WHERE id IN (1, 2)');
        $sameService = $this->subscriptionCourse(500001, 910020, 1, $teacher, 'closed', '{"previous_visibility":3}');
        $otherService = $this->subscriptionCourse(500002, 910021, 2, $teacher, 'hidden', '{}');

        $count = $this->helper->reactivateClosedCoursesForUser($teacher, 500099, 910020);

        $this->assertSame(2, $count);
        $this->assertSame(3, $this->visibility(1), 'previous_visibility restored');
        $this->assertSame(1, $this->visibility(2), 'No recorded visibility falls back to 1 (registered users)');
        $rows = $this->conn()->fetchAllAssociative('SELECT id, status, service_sale_id, last_action FROM plugin_buycourses_subscription_course WHERE id IN (?, ?) ORDER BY id', [$sameService, $otherService]);
        $byId = array_column($rows, null, 'id');
        $this->assertSame('active', $byId[$sameService]['status']);
        $this->assertSame('500099', (string) $byId[$sameService]['service_sale_id'], 'Same service: repointed to the renewal sale');
        $this->assertSame('active', $byId[$otherService]['status']);
        $this->assertSame('500002', (string) $byId[$otherService]['service_sale_id'], 'Other service: not repointed');
        $this->assertSame('reactivated', $byId[$sameService]['last_action']);
        $this->assertNull($this->conn()->fetchOne('SELECT closed_at FROM plugin_buycourses_subscription_course WHERE id = ?', [$sameService]));
    }

    public function testReactivateOnlyTouchesTheCoursesOfThatBuyerThatAreClosedOrHidden(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $other = $this->rawUser('o');
        $this->conn()->executeStatement('UPDATE course SET visibility = 0 WHERE id = 1');
        $closed = $this->subscriptionCourse(500003, 910020, 1, $teacher, 'closed', '{"previous_visibility":2}');
        $activeCourse = $this->subscriptionCourse(500004, 910020, 2, $teacher, 'active', '{"previous_visibility":3}');
        $before2 = $this->visibility(2);

        $this->assertSame(0, $this->helper->reactivateClosedCoursesForUser($other, 500099, 910020), 'Another buyer cannot reopen it');
        $this->assertSame(0, $this->visibility(1));
        $this->assertSame('closed', $this->conn()->fetchOne('SELECT status FROM plugin_buycourses_subscription_course WHERE id = ?', [$closed]));

        $this->assertSame(1, $this->helper->reactivateClosedCoursesForUser($teacher));
        $this->assertSame(2, $this->visibility(1));
        $this->assertSame($before2, $this->visibility(2), 'An active row is left alone');
        $this->assertSame('active', $this->conn()->fetchOne('SELECT status FROM plugin_buycourses_subscription_course WHERE id = ?', [$activeCourse]));
    }

    public function testReactivateWithoutRenewalSaleKeepsTheStaleSaleId(): void
    {
        $this->enablePlugin();
        $teacher = $this->rawUser('t');
        $sc = $this->subscriptionCourse(500010, 910022, 1, $teacher, 'closed', null);

        $this->assertSame(1, $this->helper->reactivateClosedCoursesForUser($teacher));
        $this->assertSame('500010', (string) $this->conn()->fetchOne('SELECT service_sale_id FROM plugin_buycourses_subscription_course WHERE id = ?', [$sc]));
        $this->assertSame(0, $this->helper->reactivateClosedCoursesForUser($teacher), 'Nothing left to reactivate');
        $this->assertSame(0, $this->helper->reactivateClosedCoursesForUser(0));
    }
}
