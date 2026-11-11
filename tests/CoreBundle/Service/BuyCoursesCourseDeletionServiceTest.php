<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\BuyCoursesCourseDeletionService;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class BuyCoursesCourseDeletionServiceTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function hiddenCourse(array $overrides = []): array
    {
        return array_replace([
            'course_id' => 345,
            'user_id' => 12,
            'buyer_id' => 12,
            'hidden_at' => '2026-10-01 00:00:00',
            'subscription_behavior_json' => '{"delete_after_days":30}',
            'course_visibility' => 4,
            'recurring_payment' => -1,
            'has_other_active_sale' => 0,
            'has_session_course' => 0,
        ], $overrides);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-11 12:00:00', new DateTimeZone('UTC'));
    }

    public function testDeletionIsDisabledByDefault(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['subscription_behavior_json' => '{}']),
            $this->now()
        );

        self::assertSame('disabled', $decision['action']);
    }

    public function testDeletionWaitsForRetentionPeriod(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['subscription_behavior_json' => '{"delete_after_days":60}']),
            $this->now()
        );

        self::assertSame('pending', $decision['action']);
        self::assertSame('2026-11-30 00:00:00', $decision['due_at']);
    }

    public function testDeletionIsEligibleAfterRetentionPeriod(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow($this->hiddenCourse(), $this->now());

        self::assertSame('delete', $decision['action']);
        self::assertSame('2026-10-31 00:00:00', $decision['due_at']);
    }

    public function testEnabledRenewalCannotDeleteExpiredCourse(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['recurring_payment' => 1]),
            $this->now()
        );

        self::assertSame('blocked', $decision['action']);
        self::assertStringContainsString('cancelled', $decision['reason']);
    }

    public function testSuspendedRenewalCannotDeleteExpiredCourse(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['recurring_payment' => 2]),
            $this->now()
        );

        self::assertSame('blocked', $decision['action']);
    }

    public function testMissingRenewalStatusCannotDeleteExpiredCourse(): void
    {
        $row = $this->hiddenCourse();
        unset($row['recurring_payment']);

        $decision = BuyCoursesCourseDeletionService::decisionForRow($row, $this->now());

        self::assertSame('blocked', $decision['action']);
    }

    public function testActiveReplacementSaleBlocksDeletion(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['has_other_active_sale' => 1]),
            $this->now()
        );

        self::assertSame('blocked', $decision['action']);
    }

    public function testCoursesSharedWithSessionsAreProtected(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['has_session_course' => 1]),
            $this->now()
        );

        self::assertSame('blocked', $decision['action']);
    }

    public function testSubscriptionOwnerMismatchBlocksDeletion(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['buyer_id' => 99]),
            $this->now()
        );

        self::assertSame('blocked', $decision['action']);
    }

    public function testChangedVisibilityBlocksDeletion(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow(
            $this->hiddenCourse(['course_visibility' => 2]),
            $this->now()
        );

        self::assertSame('blocked', $decision['action']);
    }

    public function testMultiUrlInstallationBlocksDeletion(): void
    {
        $decision = BuyCoursesCourseDeletionService::decisionForRow($this->hiddenCourse(), $this->now(), true);

        self::assertSame('blocked', $decision['action']);
    }

    public function testMissingOrZeroDeleteDelayNeverDeletes(): void
    {
        foreach (['null', '0', '-1', '"30"', '{}', '[]'] as $value) {
            $config = '{"delete_after_days":'.$value.'}';
            $decision = BuyCoursesCourseDeletionService::decisionForRow(
                $this->hiddenCourse(['subscription_behavior_json' => $config]),
                $this->now()
            );

            self::assertNotSame('delete', $decision['action'], 'Unsafe deletion delay accepted: '.$value);
        }
    }
}
