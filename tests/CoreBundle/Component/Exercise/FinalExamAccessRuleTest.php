<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Component\Exercise;

use Chamilo\CoreBundle\Component\Exercise\FinalExamAccessRule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FinalExamAccessRuleTest extends TestCase
{
    /**
     * Saving the exercise form without any condition must leave the test open.
     */
    public function testNoConditionMeansNoRule(): void
    {
        $this->assertNull(FinalExamAccessRule::buildRule(null, 0, '', true, 'Prompt'));
        $this->assertNull(FinalExamAccessRule::buildRule(null, -5, '  ', false, ''));
    }

    public function testMinimumTimeOnly(): void
    {
        $this->assertSame([
            'minimum_minutes_before_exam' => 90,
            'user_identifier_field_variable' => '',
            'allow_user_identifier_opt_out' => false,
            'user_identifier_opt_out_prompt' => '',
        ], FinalExamAccessRule::buildRule(null, 90, '', true, 'Ignored without a field'));
    }

    /**
     * A rule written by chamilo:migration:migrate-final-exam-access keeps the keys the form does
     * not edit, so its origin stays traceable.
     */
    public function testMigratedRuleKeepsKeysTheFormDoesNotEdit(): void
    {
        $migrated = [
            'course_duration_minutes' => 2400,
            'final_exam_minutes' => 60,
            'minimum_minutes_before_exam' => 2340,
            'user_identifier_field_variable' => 'student_id',
            'allow_user_identifier_opt_out' => true,
            'user_identifier_opt_out_prompt' => 'Submit your completion?',
            'source' => 'legacy_final_exam_access_rule',
        ];

        $rule = FinalExamAccessRule::buildRule($migrated, 2000, 'student_id', true, ' Submit your completion? ');

        $this->assertSame(2000, $rule['minimum_minutes_before_exam']);
        $this->assertSame('Submit your completion?', $rule['user_identifier_opt_out_prompt']);
        $this->assertSame('legacy_final_exam_access_rule', $rule['source']);
        $this->assertSame(2400, $rule['course_duration_minutes']);
    }

    public function testOptOutNeedsARequiredField(): void
    {
        $rule = FinalExamAccessRule::buildRule(null, 30, '', true, 'Prompt');

        $this->assertFalse($rule['allow_user_identifier_opt_out']);
        $this->assertSame('', $rule['user_identifier_opt_out_prompt']);
    }

    public function testUserIdentifierFormat(): void
    {
        $this->assertTrue(FinalExamAccessRule::isValidUserIdentifier('12345', false));
        $this->assertFalse(FinalExamAccessRule::isValidUserIdentifier('12', false));
        $this->assertFalse(FinalExamAccessRule::isValidUserIdentifier('12a45', false));
        $this->assertFalse(FinalExamAccessRule::isValidUserIdentifier('NONE', false));
        $this->assertTrue(FinalExamAccessRule::isValidUserIdentifier('NONE', true));
    }

    public function testRuleIsStoredAsReadableJson(): void
    {
        $this->assertSame(
            '{"user_identifier_opt_out_prompt":"Soumettre à l\'organisme ?/oui"}',
            FinalExamAccessRule::encodeRule(['user_identifier_opt_out_prompt' => "Soumettre à l'organisme ?/oui"]),
        );
    }

    public function testDecodeRuleAcceptsAStoredRule(): void
    {
        $this->assertNull(FinalExamAccessRule::decodeRule('  '));

        $rule = FinalExamAccessRule::decodeRule('{"minimum_minutes_before_exam": "600", "user_identifier_field_variable": "", "source": "x"}');

        $this->assertSame('600', $rule['minimum_minutes_before_exam']);
        $this->assertSame('x', $rule['source']);
    }

    /**
     * @dataProvider invalidJsonProvider
     */
    public function testDecodeRuleRejectsInvalidJson(string $json): void
    {
        $this->expectException(InvalidArgumentException::class);
        FinalExamAccessRule::decodeRule($json);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidJsonProvider(): iterable
    {
        yield 'not JSON' => ['{minimum'];

        yield 'list' => ['[1, 2]'];

        yield 'scalar' => ['600'];

        yield 'missing field key' => ['{"minimum_minutes_before_exam": 10}'];

        yield 'negative minutes' => ['{"minimum_minutes_before_exam": -1, "user_identifier_field_variable": ""}'];
    }

    public function testHandWrittenRuleNeedsTheRightTypes(): void
    {
        FinalExamAccessRule::assertRuleTypes([
            'minimum_minutes_before_exam' => 600,
            'user_identifier_field_variable' => 'student_id',
            'allow_user_identifier_opt_out' => true,
            'user_identifier_opt_out_prompt' => 'Prompt',
            'source' => 'manual',
        ]);

        foreach ([
            ['minimum_minutes_before_exam' => '600', 'user_identifier_field_variable' => ''],
            ['minimum_minutes_before_exam' => 10.5, 'user_identifier_field_variable' => ''],
            ['minimum_minutes_before_exam' => 10, 'user_identifier_field_variable' => 5],
            ['minimum_minutes_before_exam' => 10, 'user_identifier_field_variable' => '', 'allow_user_identifier_opt_out' => 'yes'],
            ['minimum_minutes_before_exam' => 10, 'user_identifier_field_variable' => '', 'user_identifier_opt_out_prompt' => ['a']],
        ] as $rule) {
            try {
                FinalExamAccessRule::assertRuleTypes($rule);
                $this->fail('Accepted '.json_encode($rule));
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
