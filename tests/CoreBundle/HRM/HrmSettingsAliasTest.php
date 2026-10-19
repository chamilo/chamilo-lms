<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\HRM;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class HrmSettingsAliasTest extends TestCase
{
    public function testLegacyAndCanonicalNamesResolveToCanonicalSetting(): void
    {
        $manager = (new ReflectionClass(SettingsManager::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(SettingsManager::class, 'validateSetting');

        foreach ([
            'registration.drh_autosubscribe' => 'registration.hrm_autosubscribe',
            'registration.hrm_autosubscribe' => 'registration.hrm_autosubscribe',
            'drh_autosubscribe' => 'registration.hrm_autosubscribe',
            'hrm_autosubscribe' => 'registration.hrm_autosubscribe',
            'session.drh_can_access_all_session_content' => 'session.hrm_can_access_all_session_content',
            'drh_can_access_all_session_content' => 'session.hrm_can_access_all_session_content',
            'session.hrm_can_access_all_session_content' => 'session.hrm_can_access_all_session_content',
            'workflows.drh_allow_access_to_all_students' => 'workflows.hrm_allow_access_to_all_students',
            'workflows.hrm_allow_access_to_all_students' => 'workflows.hrm_allow_access_to_all_students',
        ] as $original => $expected) {
            self::assertSame($expected, $method->invoke($manager, $original), $original);
        }
    }

    public function testLegacyValueIsAvailableUntilDatabaseMigrationRuns(): void
    {
        $manager = (new ReflectionClass(SettingsManager::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(SettingsManager::class, 'normalizeHrmParameters');

        self::assertSame(
            ['drh_can_access_all_session_content' => 'false', 'hrm_can_access_all_session_content' => 'false'],
            $method->invoke($manager, ['drh_can_access_all_session_content' => 'false'], 'session'),
        );
        self::assertSame(
            ['drh_autosubscribe' => 'COURSE01', 'hrm_autosubscribe' => ''],
            $method->invoke($manager, ['drh_autosubscribe' => 'COURSE01', 'hrm_autosubscribe' => ''], 'registration'),
        );
    }

    public function testDefaultsAndFixtureUseCanonicalNames(): void
    {
        $byCategory = [];
        foreach ([
            SettingsCurrentFixtures::getExistingSettings(),
            SettingsCurrentFixtures::getNewConfigurationSettings(),
        ] as $fixtures) {
            foreach ($fixtures as $category => $settings) {
                foreach ($settings as $setting) {
                    $byCategory[$category][$setting['name']] = true;
                }
            }
        }

        self::assertArrayHasKey('hrm_autosubscribe', $byCategory['registration']);
        self::assertArrayHasKey('hrm_can_access_all_session_content', $byCategory['session']);
        self::assertArrayHasKey('hrm_allow_access_to_all_students', $byCategory['workflows']);
        self::assertArrayNotHasKey('drh_autosubscribe', $byCategory['registration']);
        self::assertArrayNotHasKey('drh_can_access_all_session_content', $byCategory['session']);
        self::assertArrayNotHasKey('drh_allow_access_to_all_students', $byCategory['workflows']);
    }
}
