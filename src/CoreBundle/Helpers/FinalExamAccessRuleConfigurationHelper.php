<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Component\Exercise\FinalExamAccessRule;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Reads and writes the access conditions of a test (FinalExamAccessRule) for the exercise settings
 * form, which edits them as fields instead of the JSON stored in the extra field.
 */
final readonly class FinalExamAccessRuleConfigurationHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExtraFieldRepository $extraFieldRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {}

    /**
     * @return array{
     *     minimumMinutes: int,
     *     userIdentifierFieldVariable: string,
     *     allowUserIdentifierOptOut: bool,
     *     userIdentifierOptOutPrompt: string,
     *     json: string,
     *     editJson: bool
     * }
     */
    public function getFormValues(?CQuiz $quiz): array
    {
        $rawRule = trim((string) $this->getStoredValue($quiz)?->getFieldValue());
        $json = $rawRule;
        $rule = null;

        try {
            $rule = FinalExamAccessRule::decodeRule($rawRule);
            if (null !== $rule) {
                $json = json_encode($rule, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        } catch (InvalidArgumentException) {
            // An invalid rule shows as no condition in the fields, and as stored in the JSON, so
            // that the teacher can fix it or replace it by saving the fields.
            $rule = null;
        }

        return [
            'minimumMinutes' => max(0, (int) ($rule['minimum_minutes_before_exam'] ?? 0)),
            'userIdentifierFieldVariable' => trim((string) ($rule['user_identifier_field_variable'] ?? '')),
            'allowUserIdentifierOptOut' => (bool) ($rule['allow_user_identifier_opt_out'] ?? false),
            'userIdentifierOptOutPrompt' => trim((string) ($rule['user_identifier_opt_out_prompt'] ?? '')),
            'json' => $json,
            'editJson' => false,
        ];
    }

    /**
     * The user profile fields a test can require: the text fields users can see and change on
     * their own profile, since the learner fills it in, plus the one the test already requires.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getUserIdentifierFieldOptions(?CQuiz $quiz): array
    {
        $currentVariable = $this->getFormValues($quiz)['userIdentifierFieldVariable'];
        $options = [];
        foreach ($this->extraFieldRepository->findBy(
            ['itemType' => ExtraField::USER_FIELD_TYPE, 'valueType' => ExtraField::FIELD_TYPE_TEXT],
            ['fieldOrder' => 'ASC'],
        ) as $field) {
            $isCurrent = '' !== $currentVariable && $currentVariable === $field->getVariable();
            if (!$isCurrent && (!$field->isChangeable() || !$field->isVisibleToSelf())) {
                continue;
            }

            $label = trim((string) $field->getDisplayText());
            $options[] = ['value' => $field->getVariable(), 'label' => '' !== $label ? $label : $field->getVariable()];
        }

        return $options;
    }

    /**
     * Called before anything is saved, so that an invalid choice does not leave a test half saved.
     *
     * @param CQuiz|null           $quiz   the test being edited, null for a new one
     * @param array<string, mixed> $values the finalExamAccessRule values sent by the form
     */
    public function assertValid(?CQuiz $quiz, array $values): void
    {
        if ($this->isJsonEdited($values)) {
            $rule = $this->decodeEditedRule($values);
            $fieldVariable = trim((string) ($rule['user_identifier_field_variable'] ?? ''));
        } else {
            $fieldVariable = trim((string) ($values['userIdentifierFieldVariable'] ?? ''));
        }

        if ('' !== $fieldVariable && !\in_array($fieldVariable, array_column($this->getUserIdentifierFieldOptions($quiz), 'value'), true)) {
            throw new BadRequestHttpException('The selected profile field is invalid.');
        }
    }

    /**
     * @param array<string, mixed> $values the finalExamAccessRule values sent by the form, checked
     *                                     by assertValid()
     */
    public function save(CQuiz $quiz, array $values): void
    {
        $exerciseId = (int) ($quiz->getIid() ?? 0);
        if ($exerciseId <= 0) {
            return;
        }

        $existingValue = $this->getStoredValue($quiz);

        if ($this->isJsonEdited($values)) {
            // Saved as written, keys the fields do not edit included
            $rule = $this->decodeEditedRule($values);
        } else {
            try {
                $existingRule = FinalExamAccessRule::decodeRule((string) $existingValue?->getFieldValue());
            } catch (InvalidArgumentException) {
                $existingRule = null;
            }

            $rule = FinalExamAccessRule::buildRule(
                $existingRule,
                (int) ($values['minimumMinutes'] ?? 0),
                trim((string) ($values['userIdentifierFieldVariable'] ?? '')),
                (bool) ($values['allowUserIdentifierOptOut'] ?? false),
                (string) ($values['userIdentifierOptOutPrompt'] ?? ''),
            );
        }

        if (null === $rule) {
            if ($existingValue instanceof ExtraFieldValues) {
                $this->entityManager->remove($existingValue);
            }

            return;
        }

        $field = $existingValue?->getField() ?? $this->getOrCreateRuleField();
        $extraValue = $existingValue ?? (new ExtraFieldValues())
            ->setField($field)
            ->setItemId($exerciseId)
            ->setComment('')
        ;
        $extraValue->setFieldValue(FinalExamAccessRule::encodeRule($rule));
        $this->entityManager->persist($extraValue);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function isJsonEdited(array $values): bool
    {
        return true === ($values['editJson'] ?? false);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>|null null when the JSON is empty, which removes the conditions
     */
    private function decodeEditedRule(array $values): ?array
    {
        try {
            $rule = FinalExamAccessRule::decodeRule((string) ($values['json'] ?? ''));
            if (null !== $rule) {
                FinalExamAccessRule::assertRuleTypes($rule);
            }
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The JSON of the access conditions is invalid: '.$exception->getMessage(), $exception);
        }

        return $rule;
    }

    private function getStoredValue(?CQuiz $quiz): ?ExtraFieldValues
    {
        $exerciseId = (int) ($quiz?->getIid() ?? 0);
        if ($exerciseId <= 0) {
            return null;
        }

        return $this->extraFieldValuesRepository->getValueByVariableAndItem(
            FinalExamAccessRule::EXERCISE_RULE_FIELD_VARIABLE,
            $exerciseId,
            ExtraField::EXERCISE_FIELD_TYPE,
        );
    }

    /**
     * Same field as the one chamilo:migration:migrate-final-exam-access creates.
     */
    private function getOrCreateRuleField(): ExtraField
    {
        $field = $this->extraFieldRepository->findByVariable(
            ExtraField::EXERCISE_FIELD_TYPE,
            FinalExamAccessRule::EXERCISE_RULE_FIELD_VARIABLE,
        );
        if ($field instanceof ExtraField) {
            return $field;
        }

        $field = (new ExtraField())
            ->setItemType(ExtraField::EXERCISE_FIELD_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_TEXTAREA)
            ->setVariable(FinalExamAccessRule::EXERCISE_RULE_FIELD_VARIABLE)
            ->setDisplayText('Final exam access rule')
            ->setDescription('JSON configuration for legal final-exam timing and user identifier requirements.')
            ->setVisibleToSelf(false)
            ->setVisibleToOthers(false)
            ->setChangeable(false)
            ->setFilter(false)
        ;
        $this->entityManager->persist($field);

        return $field;
    }
}
