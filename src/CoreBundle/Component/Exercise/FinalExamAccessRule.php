<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Exercise;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Closure;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Tracking;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Conditions a learner must meet before starting a test, typically a final exam: a minimum time
 * spent in the course and a required user extra field (such as a student ID). The conditions are
 * stored as JSON in the final_exam_access_rule extra field of the exercise and only apply while
 * the exercise.allow_final_exam_access_rule setting is enabled.
 */
final class FinalExamAccessRule
{
    public const string EXERCISE_RULE_FIELD_VARIABLE = 'final_exam_access_rule';
    public const string SETTING = 'exercise.allow_final_exam_access_rule';
    public const string USER_IDENTIFIER_OPT_OUT_VALUE = 'NONE';

    public static function isEnabled(): bool
    {
        return 'true' === (string) Container::getSettingsManager()->getSetting(self::SETTING, true);
    }

    /**
     * @param Closure(): int|null $spentSeconds the time spent in the course, read only when the
     *                                          exercise has a rule; the course reports by default
     *
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
        int $exerciseId,
        ?Closure $spentSeconds = null,
    ): array {
        if (!self::isEnabled()) {
            return self::notApplicable();
        }

        $rule = self::getRuleForExercise($exerciseId);
        if (null === $rule) {
            return self::notApplicable();
        }

        $requiredMinutes = (int) $rule['minimum_minutes_before_exam'];
        $spentSeconds = null !== $spentSeconds
            ? $spentSeconds()
            : (int) Tracking::get_time_spent_on_the_course($userId, $courseId, $sessionId);
        $spentMinutes = (int) floor(max(0, $spentSeconds) / 60);
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
        if (!self::isEnabled()) {
            return false;
        }

        $rule = self::getRuleForExercise($exerciseId);
        if (null === $rule || $userId <= 0) {
            return false;
        }

        $value = trim($value);
        if (!self::isValidUserIdentifier($value, (bool) ($rule['allow_user_identifier_opt_out'] ?? false))) {
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

    /**
     * A user identifier is made of digits only, at least 3. When the rule allows it, the learner
     * can decline to give one.
     */
    public static function isValidUserIdentifier(string $value, bool $allowOptOut): bool
    {
        if (self::USER_IDENTIFIER_OPT_OUT_VALUE === $value) {
            return $allowOptOut;
        }

        return 1 === preg_match('/^\d{3,}$/', $value);
    }

    /**
     * The rule to store from the values of the exercise settings form, or null when it sets no
     * condition. Keys the form does not edit (for example the source of a migrated rule) are kept.
     *
     * @param array<string, mixed>|null $existingRule
     *
     * @return array<string, mixed>|null
     */
    public static function buildRule(
        ?array $existingRule,
        int $minimumMinutes,
        string $userIdentifierFieldVariable,
        bool $allowUserIdentifierOptOut,
        string $userIdentifierOptOutPrompt,
    ): ?array {
        $minimumMinutes = max(0, $minimumMinutes);
        $userIdentifierFieldVariable = trim($userIdentifierFieldVariable);
        if (0 === $minimumMinutes && '' === $userIdentifierFieldVariable) {
            return null;
        }

        $hasIdentifier = '' !== $userIdentifierFieldVariable;
        $allowOptOut = $hasIdentifier && $allowUserIdentifierOptOut;

        return array_merge($existingRule ?? [], [
            'minimum_minutes_before_exam' => $minimumMinutes,
            'user_identifier_field_variable' => $userIdentifierFieldVariable,
            'allow_user_identifier_opt_out' => $allowOptOut,
            'user_identifier_opt_out_prompt' => $allowOptOut ? trim($userIdentifierOptOutPrompt) : '',
        ]);
    }

    /**
     * @param array<string, mixed> $rule
     */
    public static function encodeRule(array $rule): string
    {
        return json_encode($rule, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);

        return \sprintf('%02d hours %02d minutes', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getRuleForExercise(int $exerciseId): ?array
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

        try {
            return self::decodeRule((string) $value->getFieldValue());
        } catch (InvalidArgumentException $exception) {
            throw new RuntimeException(\sprintf('Invalid final exam access rule configured for exercise %d: %s', $exerciseId, $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * Decodes the JSON of a rule, as stored in the extra field: null for an empty value.
     *
     * @return array<string, mixed>|null
     *
     * @throws InvalidArgumentException when the JSON is not a valid rule
     */
    public static function decodeRule(string $rawRule): ?array
    {
        $rawRule = trim($rawRule);
        if ('' === $rawRule) {
            return null;
        }

        try {
            $rule = json_decode($rawRule, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('the value is not valid JSON.', 0, $exception);
        }

        if (!\is_array($rule) || array_is_list($rule)) {
            throw new InvalidArgumentException('the value must be a JSON object.');
        }

        foreach (['minimum_minutes_before_exam', 'user_identifier_field_variable'] as $key) {
            if (!\array_key_exists($key, $rule)) {
                throw new InvalidArgumentException(\sprintf('"%s" is missing.', $key));
            }
        }

        if ((int) $rule['minimum_minutes_before_exam'] < 0) {
            throw new InvalidArgumentException('"minimum_minutes_before_exam" cannot be negative.');
        }

        return $rule;
    }

    /**
     * Stricter than decodeRule(), which accepts the rules already stored: checks the types of the
     * keys for a rule a teacher writes by hand.
     *
     * @param array<string, mixed> $rule
     *
     * @throws InvalidArgumentException
     */
    public static function assertRuleTypes(array $rule): void
    {
        $minutes = $rule['minimum_minutes_before_exam'] ?? null;
        if (!\is_int($minutes) || $minutes < 0) {
            throw new InvalidArgumentException('"minimum_minutes_before_exam" must be a whole number of minutes, 0 or more.');
        }
        if (!\is_string($rule['user_identifier_field_variable'] ?? null)) {
            throw new InvalidArgumentException('"user_identifier_field_variable" must be a string.');
        }
        if (isset($rule['allow_user_identifier_opt_out']) && !\is_bool($rule['allow_user_identifier_opt_out'])) {
            throw new InvalidArgumentException('"allow_user_identifier_opt_out" must be true or false.');
        }
        if (isset($rule['user_identifier_opt_out_prompt']) && !\is_string($rule['user_identifier_opt_out_prompt'])) {
            throw new InvalidArgumentException('"user_identifier_opt_out_prompt" must be a string.');
        }
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
