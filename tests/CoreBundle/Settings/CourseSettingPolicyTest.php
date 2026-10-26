<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Settings;

use Chamilo\CoreBundle\Settings\CourseSettingPolicy;
use PHPUnit\Framework\TestCase;

final class CourseSettingPolicyTest extends TestCase
{
    public function testMissingPolicyPreservesLegacyValues(): void
    {
        $this->assertSame('1', CourseSettingPolicy::effectiveValue([], 'student_validate_own_attendance', '1', -1));
        $this->assertSame(-1, CourseSettingPolicy::effectiveValue([], 'student_validate_own_attendance', null, -1));
        $this->assertTrue(CourseSettingPolicy::isEditable([], 'student_validate_own_attendance'));
    }

    public function testLockedPolicyOverridesExistingCourseValueWithoutDeletingIt(): void
    {
        $policies = ['student_validate_own_attendance' => ['editable' => false, 'default' => '0']];
        $this->assertSame('0', CourseSettingPolicy::effectiveValue($policies, 'student_validate_own_attendance', '1', -1));
        $this->assertFalse(CourseSettingPolicy::isEditable($policies, 'student_validate_own_attendance'));
    }

    public function testEditableSettingUsesCourseValueOrPlatformDefault(): void
    {
        $policies = ['student_validate_own_attendance' => ['editable' => true, 'default' => '1']];
        $this->assertSame('0', CourseSettingPolicy::effectiveValue($policies, 'student_validate_own_attendance', '0', -1));
        $this->assertSame('1', CourseSettingPolicy::effectiveValue($policies, 'student_validate_own_attendance', null, -1));
        $this->assertSame('1', CourseSettingPolicy::effectiveValue($policies, 'student_validate_own_attendance', '-1', -1));
    }

    public function testInvalidJsonDoesNotCreateImplicitLockdown(): void
    {
        $this->assertSame([], CourseSettingPolicy::parse('not json'));
        $this->assertSame([], CourseSettingPolicy::parse('{"attendance":{"editable":"false","default":"1"}}'));
        $this->assertSame([], CourseSettingPolicy::parse('{"../bad":{"editable":false,"default":"1"}}'));
    }

    public function testEmptyLegacyCategoryDoesNotMakeSettingAmbiguous(): void
    {
        // Historically imported courses have blank categories alongside the
        // modern named category, but the setting is the same one.
        $this->assertFalse(CourseSettingPolicy::hasConflictingCategories(['', 'attendance']));
        $this->assertFalse(CourseSettingPolicy::hasConflictingCategories(['', 'agenda', 'agenda']));
        $this->assertFalse(CourseSettingPolicy::hasConflictingCategories(['', '']));
        $this->assertFalse(CourseSettingPolicy::hasConflictingCategories(['  ', 'learning_path']));
    }

    public function testDistinctNamedCategoriesRequireScopedPolicies(): void
    {
        $this->assertTrue(CourseSettingPolicy::hasConflictingCategories(['forum', 'attendance']));
        $this->assertTrue(CourseSettingPolicy::hasConflictingCategories(['', 'forum', 'attendance']));
    }

    public function testLegacyListValuesRemainArraysWhenAllowed(): void
    {
        $policies = ['email_alert_manager_on_new_quiz' => ['editable' => true, 'default' => '1,2']];
        $this->assertSame(['1', '4'], CourseSettingPolicy::effectiveValue($policies, 'email_alert_manager_on_new_quiz', ['1', '4'], -1));
    }
    public function testCategoryPoliciesNeverLeakBetweenCourseTools(): void
    {
        $policies = CourseSettingPolicy::parse('{"forum.enabled":{"editable":false,"default":"0"},"wiki.enabled":{"editable":true,"default":"1"}}');
        $this->assertCount(2, $policies);
        $this->assertFalse(CourseSettingPolicy::isEditable($policies, 'enabled', 'forum'));
        $this->assertTrue(CourseSettingPolicy::isEditable($policies, 'enabled', 'wiki'));
        $this->assertTrue(CourseSettingPolicy::isEditable($policies, 'enabled', 'document'));
        $this->assertSame('0', CourseSettingPolicy::effectiveValue($policies, 'enabled', '1', '1', 'forum'));
        $this->assertSame('0', CourseSettingPolicy::effectiveValue($policies, 'enabled', '0', '1', 'wiki'));
        $this->assertSame('1', CourseSettingPolicy::effectiveValue($policies, 'enabled', null, '1', 'wiki'));
        $this->assertSame('1', CourseSettingPolicy::effectiveValue($policies, 'enabled', '1', '1', 'document'));
    }

    public function testUnqualifiedExistingPolicyStillWorksWithCategory(): void
    {
        $policies = ['student_validate_own_attendance' => ['editable' => false, 'default' => '1']];
        $this->assertFalse(CourseSettingPolicy::isEditable($policies, 'student_validate_own_attendance', 'attendance'));
        $this->assertSame('1', CourseSettingPolicy::effectiveValue($policies, 'student_validate_own_attendance', '0', '0', 'attendance'));
    }

    public function testInvalidNestedPolicyKeyIsRejected(): void
    {
        $this->assertSame([], CourseSettingPolicy::parse('{"forum.enabled.extra":{"editable":false,"default":"0"}}'));
        $this->assertSame([], CourseSettingPolicy::parse('{".enabled":{"editable":false,"default":"0"}}'));
    }
}
