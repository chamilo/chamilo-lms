<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CSurvey;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const FILTER_VALIDATE_EMAIL;
use const JSON_THROW_ON_ERROR;

/**
 * Shared helpers for the legacy survey profile form.
 *
 * The legacy form stores selected fields in c_survey.form_fields using keys like
 * "profile_firstname:1@profile_email:1@". This trait keeps that format intact.
 */
trait SurveyProfileFieldsTrait
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableSurveyProfileFieldOptions(): array
    {
        return array_map(
            static fn (array $field): array => [
                'value' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'readOnly' => $field['readOnly'],
            ],
            $this->getAvailableSurveyProfileFields()
        );
    }

    /**
     * @return string[]
     */
    private function getSelectedSurveyProfileFields(CSurvey $survey): array
    {
        if (1 !== (int) $survey->getShowFormProfile()) {
            return [];
        }

        $available = [];
        foreach ($this->getAvailableSurveyProfileFields() as $field) {
            $available[$field['key']] = true;
        }

        $selected = [];
        foreach (explode('@', (string) $survey->getFormFields()) as $item) {
            if ('' === trim($item)) {
                continue;
            }

            [$key, $enabled] = array_pad(explode(':', $item, 2), 2, '0');
            $key = trim($key);
            if ('1' === (string) $enabled && isset($available[$key])) {
                $selected[] = $key;
            }
        }

        return array_values(array_unique($selected));
    }

    /**
     * @param string[] $selectedFields
     */
    private function buildSurveyProfileFormFieldsString(array $selectedFields): string
    {
        $available = [];
        foreach ($this->getAvailableSurveyProfileFields() as $field) {
            $available[$field['key']] = true;
        }

        $value = '';
        foreach (array_values(array_unique($selectedFields)) as $key) {
            $key = trim((string) $key);
            if ('' !== $key && isset($available[$key])) {
                $value .= $key.':1@';
            }
        }

        return $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSurveyAnswerProfileFields(CSurvey $survey, User $user): array
    {
        if ('1' === (string) $survey->getAnonymous() || 1 !== (int) $survey->getShowFormProfile()) {
            return [];
        }

        $selectedFields = $this->getSelectedSurveyProfileFields($survey);
        if ([] === $selectedFields) {
            return [];
        }

        $definitions = [];
        foreach ($this->getAvailableSurveyProfileFields() as $field) {
            $definitions[$field['key']] = $field;
        }

        $items = [];
        foreach ($selectedFields as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }

            $field = $definitions[$key];
            $editable = !$field['readOnly'];
            $items[] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'inputType' => $field['inputType'] ?? 'text',
                'value' => $this->getSurveyProfileFieldValue($field, $user),
                'required' => $editable && true === ($field['required'] ?? false),
                'readOnly' => !$editable,
                'options' => $field['options'] ?? [],
                'helpText' => $field['helpText'] ?? '',
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $profileValues
     */
    private function applySurveyProfileValues(CSurvey $survey, User $user, array $profileValues): void
    {
        if ('1' === (string) $survey->getAnonymous() || 1 !== (int) $survey->getShowFormProfile()) {
            return;
        }

        $selectedFields = $this->getSelectedSurveyProfileFields($survey);
        if ([] === $selectedFields) {
            return;
        }

        $definitions = [];
        foreach ($this->getAvailableSurveyProfileFields() as $field) {
            $definitions[$field['key']] = $field;
        }

        foreach ($selectedFields as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }

            $field = $definitions[$key];
            if (true === $field['readOnly']) {
                continue;
            }

            $value = $profileValues[$key] ?? $this->getSurveyProfileFieldValue($field, $user);
            $value = \is_array($value) ? array_map('strval', $value) : trim((string) $value);

            if (true === ($field['required'] ?? false) && (\is_array($value) ? [] === $value : '' === $value)) {
                throw new BadRequestHttpException('Please complete the required profile fields.');
            }

            if ('email' === $field['field'] && '' !== (string) $value && false === filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new BadRequestHttpException('The email address is not valid.');
            }

            if ('language' === $field['field'] && '' !== (string) $value && !$this->isAvailableSurveyProfileLanguage((string) $value)) {
                throw new BadRequestHttpException('The selected language is not available.');
            }

            if ('extra' === $field['source']) {
                $this->saveSurveyExtraProfileValue($field, $user, $value);

                continue;
            }

            $this->setSurveyProfileBaseValue($field['field'], $user, (string) $value);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableSurveyProfileFields(): array
    {
        $visibleOptions = $this->getSurveyProfileSettingList('profile.visible_options', [
            'name',
            'officialcode',
            'email',
            'language',
            'phone',
        ]);
        $changeableOptions = $this->getSurveyProfileSettingList('profile.changeable_options', [
            'name',
            'officialcode',
            'email',
            'language',
            'phone',
        ]);
        $requiredOptions = $this->getSurveyProfileSettingList('registration.required_profile_fields', []);

        $items = [];
        if (\in_array('name', $visibleOptions, true)) {
            $nameEditable = \in_array('name', $changeableOptions, true);
            $items[] = $this->createBaseSurveyProfileField('profile_firstname', 'firstname', 'First name', 'text', $nameEditable, true);
            $items[] = $this->createBaseSurveyProfileField('profile_lastname', 'lastname', 'Last name', 'text', $nameEditable, true);
        }

        if (\in_array('officialcode', $visibleOptions, true)) {
            $items[] = $this->createBaseSurveyProfileField(
                'profile_official_code',
                'official_code',
                'Official code',
                'text',
                \in_array('officialcode', $changeableOptions, true),
                \in_array('officialcode', $requiredOptions, true)
            );
        }

        if (\in_array('email', $visibleOptions, true)) {
            $items[] = $this->createBaseSurveyProfileField(
                'profile_email',
                'email',
                'E-mail',
                'email',
                \in_array('email', $changeableOptions, true),
                \in_array('email', $requiredOptions, true)
            );
        }

        if (\in_array('phone', $visibleOptions, true)) {
            $items[] = $this->createBaseSurveyProfileField(
                'profile_phone',
                'phone',
                'Phone',
                'text',
                \in_array('phone', $changeableOptions, true),
                \in_array('phone', $requiredOptions, true)
            );
        }

        if (\in_array('language', $visibleOptions, true)) {
            $items[] = $this->createBaseSurveyProfileField(
                'profile_language',
                'language',
                'Language',
                'select',
                \in_array('language', $changeableOptions, true),
                \in_array('language', $requiredOptions, true),
                $this->getSurveyProfileLanguageOptions()
            );
        }

        foreach ($this->getAvailableSurveyExtraProfileFields() as $field) {
            $items[] = $field;
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableSurveyExtraProfileFields(): array
    {
        $extraFields = $this->entityManager->getRepository(ExtraField::class)->findBy(
            ['itemType' => ExtraField::USER_FIELD_TYPE],
            ['fieldOrder' => 'ASC', 'id' => 'ASC']
        );

        $items = [];
        foreach ($extraFields as $extraField) {
            if (!$extraField instanceof ExtraField || !$extraField->isVisibleToSelf()) {
                continue;
            }

            $type = $this->mapSurveyExtraFieldType($extraField->getValueType());
            if (null === $type) {
                continue;
            }

            $items[] = [
                'key' => 'extra_'.$extraField->getVariable(),
                'field' => 'extra_'.$extraField->getVariable(),
                'source' => 'extra',
                'extraFieldId' => (int) $extraField->getId(),
                'label' => $extraField->getDisplayText() ?: $extraField->getVariable(),
                'type' => $type['type'],
                'inputType' => $type['inputType'],
                'readOnly' => !$extraField->isChangeable(),
                'required' => false,
                'options' => $this->getSurveyExtraFieldOptions($extraField),
                'helpText' => $extraField->getHelperText() ?? '',
            ];
        }

        return $items;
    }

    /**
     * @return array{type: string, inputType: string}|null
     */
    private function mapSurveyExtraFieldType(int $type): ?array
    {
        return match ($type) {
            ExtraField::FIELD_TYPE_TEXT, ExtraField::FIELD_TYPE_INTEGER, ExtraField::FIELD_TYPE_FLOAT => [
                'type' => 'text',
                'inputType' => 'text',
            ],
            ExtraField::FIELD_TYPE_TEXTAREA => [
                'type' => 'textarea',
                'inputType' => 'text',
            ],
            ExtraField::FIELD_TYPE_RADIO, ExtraField::FIELD_TYPE_SELECT => [
                'type' => 'select',
                'inputType' => 'text',
            ],
            ExtraField::FIELD_TYPE_SELECT_MULTIPLE => [
                'type' => 'multiselect',
                'inputType' => 'text',
            ],
            ExtraField::FIELD_TYPE_DATE, ExtraField::FIELD_TYPE_DATETIME => [
                'type' => 'text',
                'inputType' => 'text',
            ],
            default => null,
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getSurveyExtraFieldOptions(ExtraField $extraField): array
    {
        $options = [];
        foreach ($extraField->getOptions() as $option) {
            if (!$option instanceof ExtraFieldOptions) {
                continue;
            }

            $value = (string) ($option->getValue() ?? '');
            if ('' === $value) {
                continue;
            }

            $options[] = [
                'value' => $value,
                'label' => $option->getDisplayText() ?: $value,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSurveyProfileLanguageOptions(): array
    {
        $items = [];
        $languages = $this->entityManager->getRepository(Language::class)->findBy(['available' => true], ['englishName' => 'ASC']);
        foreach ($languages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $items[] = [
                'value' => $language->getIsocode(),
                'label' => $language->getOriginalName() ?: $language->getEnglishName(),
            ];
        }

        return $items;
    }

    private function isAvailableSurveyProfileLanguage(string $language): bool
    {
        foreach ($this->getSurveyProfileLanguageOptions() as $option) {
            if ($language === $option['value']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $default
     *
     * @return string[]
     */
    private function getSurveyProfileSettingList(string $name, array $default): array
    {
        $value = $this->settingsManager->getSetting($name, true);
        if (null === $value || '' === $value || [] === $value) {
            return $default;
        }

        if (\is_array($value)) {
            return array_values(array_unique(array_map('strval', $value)));
        }

        $decoded = json_decode((string) $value, true);
        if (\is_array($decoded)) {
            return array_values(array_unique(array_map('strval', $decoded)));
        }

        $items = preg_split('/[\s,;|]+/', trim((string) $value)) ?: [];
        $items = array_values(array_filter(array_map('strval', $items), static fn (string $item): bool => '' !== $item));

        return [] !== $items ? array_values(array_unique($items)) : $default;
    }

    /**
     * @param array<int, array<string, mixed>> $options
     *
     * @return array<string, mixed>
     */
    private function createBaseSurveyProfileField(
        string $key,
        string $field,
        string $label,
        string $type,
        bool $editable,
        bool $required,
        array $options = []
    ): array {
        return [
            'key' => $key,
            'field' => $field,
            'source' => 'base',
            'label' => $label,
            'type' => 'select' === $type ? 'select' : 'text',
            'inputType' => 'email' === $type ? 'email' : 'text',
            'readOnly' => !$editable,
            'required' => $required,
            'options' => $options,
            'helpText' => '',
        ];
    }

    /**
     * @param array<string, mixed> $field
     */
    private function getSurveyProfileFieldValue(array $field, User $user): array|string
    {
        if ('extra' === $field['source']) {
            return $this->getSurveyExtraProfileValue((int) $field['extraFieldId'], (int) $user->getId(), 'multiselect' === $field['type']);
        }

        return match ($field['field']) {
            'firstname' => (string) $user->getFirstname(),
            'lastname' => (string) $user->getLastname(),
            'official_code' => (string) $user->getOfficialCode(),
            'email' => (string) $user->getEmail(),
            'phone' => (string) $user->getPhone(),
            'language' => (string) $user->getLocale(),
            default => '',
        };
    }

    private function getSurveyExtraProfileValue(int $fieldId, int $userId, bool $multiple): array|string
    {
        $extraField = $this->entityManager->getRepository(ExtraField::class)->find($fieldId);
        if (!$extraField instanceof ExtraField) {
            return $multiple ? [] : '';
        }

        $value = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $extraField,
            'itemId' => $userId,
        ]);

        if (!$value instanceof ExtraFieldValues) {
            return $multiple ? [] : '';
        }

        $fieldValue = (string) $value->getFieldValue();
        if (!$multiple) {
            return $fieldValue;
        }

        $decoded = json_decode($fieldValue, true);
        if (\is_array($decoded)) {
            return array_values(array_map('strval', $decoded));
        }

        return '' !== $fieldValue ? preg_split('/[,;]+/', $fieldValue) ?: [] : [];
    }

    /**
     * @param array<string, mixed>      $field
     * @param string|array<int, string> $value
     */
    private function saveSurveyExtraProfileValue(array $field, User $user, array|string $value): void
    {
        $extraField = $this->entityManager->getRepository(ExtraField::class)->find((int) $field['extraFieldId']);
        if (!$extraField instanceof ExtraField) {
            return;
        }

        $extraValue = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $extraField,
            'itemId' => (int) $user->getId(),
        ]);

        if (!$extraValue instanceof ExtraFieldValues) {
            $extraValue = new ExtraFieldValues();
            $extraValue
                ->setField($extraField)
                ->setItemId((int) $user->getId())
                ->setComment('')
            ;
            $this->entityManager->persist($extraValue);
        }

        $extraValue->setFieldValue(\is_array($value) ? json_encode(array_values($value), JSON_THROW_ON_ERROR) : $value);
    }

    private function setSurveyProfileBaseValue(string $field, User $user, string $value): void
    {
        match ($field) {
            'firstname' => $user->setFirstname($value),
            'lastname' => $user->setLastname($value),
            'official_code' => $user->setOfficialCode($value),
            'email' => $user->setEmail($value)->setEmailCanonical(mb_strtolower($value)),
            'phone' => $user->setPhone($value),
            'language' => $user->setLocale($value),
            default => null,
        };
    }
}
