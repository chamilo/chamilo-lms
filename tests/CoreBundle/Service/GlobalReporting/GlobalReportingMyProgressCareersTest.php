<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContext;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Chamilo\Tests\CoreBundle\Service\RawReportingFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The careers block of /reporting/my-progress, which replaced the one of the legacy main/auth/my_progress.php:
 * the learner sees his careers, oldest subscription first, each with a link to its diagram. When the platform
 * identifies diagrams by the external career id, a career without one has no diagram, because
 * career_diagram.php would not find it.
 */
final class GlobalReportingMyProgressCareersTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    private GlobalReportingSectionQueryService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(GlobalReportingSectionQueryService::class);
    }

    public function testCareersAreHiddenWhileTheCareerUsersSettingIsOff(): void
    {
        $me = $this->rawUser('car1');
        $this->userCareer($me, $this->career('rp_car1 career'), '2026-01-01 10:00:00');
        $this->setting('allow_career_users', 'false');

        self::assertSame([], $this->careers($me));
    }

    public function testCareersAreListedOldestSubscriptionFirstWithTheirDiagram(): void
    {
        $me = $this->rawUser('car2');
        $other = $this->rawUser('car2b');
        $late = $this->career('rp_car2 late');
        $early = $this->career('rp_car2 early');
        $this->userCareer($me, $late, '2026-03-01 10:00:00');
        $this->userCareer($me, $early, '2026-01-01 10:00:00');
        $this->userCareer($other, $this->career('rp_car2 foreign'), '2026-01-01 10:00:00');
        $this->setting('allow_career_users', 'true');
        $this->setting('use_career_external_id_as_identifier_in_diagrams', 'false');

        self::assertSame(
            [
                ['id' => $early, 'title' => 'rp_car2 early', 'diagramUrl' => '/main/user/career_diagram.php?career_id='.$early],
                ['id' => $late, 'title' => 'rp_car2 late', 'diagramUrl' => '/main/user/career_diagram.php?career_id='.$late],
            ],
            $this->careers($me),
        );
    }

    public function testExternalIdentifiersReplaceTheInternalOneAndTheirAbsenceRemovesTheDiagram(): void
    {
        $me = $this->rawUser('car3');
        $withExternal = $this->career('rp_car3 with');
        $withoutExternal = $this->career('rp_car3 without');
        $this->userCareer($me, $withExternal, '2026-01-01 10:00:00');
        $this->userCareer($me, $withoutExternal, '2026-02-01 10:00:00');
        $this->externalCareerId($withExternal, 'EXT 1&2');
        $this->setting('allow_career_users', 'true');
        $this->setting('use_career_external_id_as_identifier_in_diagrams', 'true');

        $careers = $this->careers($me);

        self::assertSame('/main/user/career_diagram.php?career_id=EXT+1%262', $careers[0]['diagramUrl']);
        self::assertNull($careers[1]['diagramUrl']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function careers(int $userId): array
    {
        $this->fresh();
        $context = new GlobalReportingContext(
            $this->entityUser($userId),
            1,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
        );

        return $this->service->getSection($context, 'my-progress', [])['meta']['careers'];
    }

    private function career(string $title): int
    {
        $this->conn()->insert('career', [
            'title' => $title,
            'description' => '',
            'status' => 1,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        return (int) $this->conn()->lastInsertId();
    }

    private function userCareer(int $userId, int $careerId, string $createdAt): void
    {
        $this->conn()->insert('user_career', [
            'user_id' => $userId,
            'career_id' => $careerId,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function externalCareerId(int $careerId, string $value): void
    {
        $fieldId = $this->conn()->fetchOne(
            'SELECT id FROM extra_field WHERE variable = ? AND item_type = ?',
            ['external_career_id', ExtraField::CAREER_FIELD_TYPE],
        );
        if (false === $fieldId) {
            $this->conn()->insert('extra_field', [
                'item_type' => ExtraField::CAREER_FIELD_TYPE,
                'value_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => 'external_career_id',
                'created_at' => '2026-01-01 00:00:00',
            ]);
            $fieldId = $this->conn()->lastInsertId();
        }

        $this->conn()->insert('extra_field_values', [
            'field_id' => (int) $fieldId,
            'item_id' => $careerId,
            'field_value' => $value,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }

    private function setting(string $variable, string $value): void
    {
        $this->conn()->executeStatement(
            'UPDATE settings SET selected_value = ? WHERE variable = ?',
            [$value, $variable],
        );
    }
}
