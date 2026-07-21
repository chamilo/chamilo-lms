<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Exercise;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use JsonException;
use RuntimeException;
use Tracking;

use const JSON_THROW_ON_ERROR;

final class FinalExamAccessRule
{
    public const EXERCISE_RULE_FIELD_VARIABLE = 'final_exam_access_rule';

    /**
     * @return array{
     *     applies: bool,
     *     allowed: bool,
     *     time_requirement_met: bool,
     *     required_minutes: int,
     *     spent_minutes: int,
     *     remaining_minutes: int,
     *     user_identifier_present: bool,
     *     user_identifier_label: string,
     *     allow_user_identifier_opt_out: bool,
     *     user_identifier_opt_out_prompt: string
     * }
     */
    public static function evaluate(
        int $userId,
        int $courseId,
        int $sessionId,
        int $exerciseId
    ): array {
        $rule = self::getRuleForExercise($exerciseId);
        if (null === $rule) {
            return self::notApplicable();
        }

        $requiredMinutes = (int) $rule['minimum_minutes_before_exam'];
        $spentSeconds = (int) Tracking::get_time_spent_on_the_course($userId, $courseId, $sessionId);
        $spentMinutes = (int) floor($spentSeconds / 60);
        $timeRequirementMet = $spentMinutes >= $requiredMinutes;

        $fieldVariable = trim((string) $rule['user_identifier_field_variable']);
        $userIdentifierPresent = '' === $fieldVariable
            || '' !== self::getUserIdentifierValue($fieldVariable, $userId);

        return [
            'applies' => true,
            'allowed' => $timeRequirementMet && $userIdentifierPresent,
            'time_requirement_met' => $timeRequirementMet,
            'required_minutes' => $requiredMinutes,
            'spent_minutes' => $spentMinutes,
            'remaining_minutes' => max(0, $requiredMinutes - $spentMinutes),
            'user_identifier_present' => $userIdentifierPresent,
            'user_identifier_label' => self::getUserIdentifierLabel($fieldVariable),
            'allow_user_identifier_opt_out' => (bool) ($rule['allow_user_identifier_opt_out'] ?? false),
            'user_identifier_opt_out_prompt' => trim((string) ($rule['user_identifier_opt_out_prompt'] ?? '')),
        ];
    }

    public static function saveUserIdentifier(int $userId, int $exerciseId, string $value): bool
    {
        $rule = self::getRuleForExercise($exerciseId);
        if (null === $rule || $userId <= 0) {
            return false;
        }

        $value = trim($value);
        $allowOptOut = (bool) ($rule['allow_user_identifier_opt_out'] ?? false);
        if ('NONE' === $value) {
            if (!$allowOptOut) {
                return false;
            }
        } elseif (!preg_match('/^\d{3,}$/', $value)) {
            return false;
        }

        $fieldVariable = trim((string) $rule['user_identifier_field_variable']);
        if ('' === $fieldVariable) {
            return false;
        }

        $extraField = Container::getExtraFieldRepository()->findByVariable(
            ExtraField::USER_FIELD_TYPE,
            $fieldVariable
        );
        $user = Container::getEntityManager()->find(User::class, $userId);

        if (!$extraField instanceof ExtraField || !$user instanceof User) {
            return false;
        }

        self::getExtraFieldValuesRepository()->updateItemData($extraField, $user, $value);

        return true;
    }

    public static function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);

        return \sprintf('%02d hours %02d minutes', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function getRuleForExercise(int $exerciseId): ?array
    {
        if ($exerciseId <= 0) {
            return null;
        }

        $value = self::getExtraFieldValuesRepository()->getValueByVariableAndItem(
            self::EXERCISE_RULE_FIELD_VARIABLE,
            $exerciseId,
            ExtraField::EXERCISE_FIELD_TYPE
        );
        if (!$value instanceof ExtraFieldValues) {
            return null;
        }

        $rawRule = trim((string) $value->getFieldValue());
        if ('' === $rawRule) {
            return null;
        }

        try {
            $rule = json_decode($rawRule, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(\sprintf('Invalid final exam access rule configured for exercise %d.', $exerciseId), 0, $exception);
        }

        if (!\is_array($rule)) {
            throw new RuntimeException(\sprintf('Invalid final exam access rule configured for exercise %d.', $exerciseId));
        }

        foreach (['minimum_minutes_before_exam', 'user_identifier_field_variable'] as $key) {
            if (!\array_key_exists($key, $rule)) {
                throw new RuntimeException(\sprintf('Final exam access rule for exercise %d is missing "%s".', $exerciseId, $key));
            }
        }

        if ((int) $rule['minimum_minutes_before_exam'] < 0) {
            throw new RuntimeException(\sprintf('Invalid final exam access rule configured for exercise %d.', $exerciseId));
        }

        return $rule;
    }

    private static function getUserIdentifierValue(string $fieldVariable, int $userId): string
    {
        if ('' === $fieldVariable || $userId <= 0) {
            return '';
        }

        $value = self::getExtraFieldValuesRepository()->getValueByVariableAndItem(
            $fieldVariable,
            $userId,
            ExtraField::USER_FIELD_TYPE
        );

        return $value instanceof ExtraFieldValues ? trim((string) $value->getFieldValue()) : '';
    }

    private static function getUserIdentifierLabel(string $fieldVariable): string
    {
        if ('' === $fieldVariable) {
            return 'Student ID';
        }

        $field = Container::getExtraFieldRepository()->findByVariable(
            ExtraField::USER_FIELD_TYPE,
            $fieldVariable
        );

        if (!$field instanceof ExtraField) {
            return 'Student ID';
        }

        $label = trim((string) $field->getDisplayText());

        return '' !== $label ? $label : 'Student ID';
    }

    private static function getExtraFieldValuesRepository(): ExtraFieldValuesRepository
    {
        /** @var ExtraFieldValuesRepository $repository */
        return Container::getEntityManager()->getRepository(ExtraFieldValues::class);
    }

    /**
     * @return array{
     *     applies: bool,
     *     allowed: bool,
     *     time_requirement_met: bool,
     *     required_minutes: int,
     *     spent_minutes: int,
     *     remaining_minutes: int,
     *     user_identifier_present: bool,
     *     user_identifier_label: string,
     *     allow_user_identifier_opt_out: bool,
     *     user_identifier_opt_out_prompt: string
     * }
     */
    private static function notApplicable(): array
    {
        return [
            'applies' => false,
            'allowed' => true,
            'time_requirement_met' => true,
            'required_minutes' => 0,
            'spent_minutes' => 0,
            'remaining_minutes' => 0,
            'user_identifier_present' => true,
            'user_identifier_label' => 'Student ID',
            'allow_user_identifier_opt_out' => false,
            'user_identifier_opt_out_prompt' => '',
        ];
    }
}
