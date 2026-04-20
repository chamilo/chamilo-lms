<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\TagRepository;
use DateTime;
use ExtraFieldValue;
use GoogleMapsPlugin;
use Oh\GoogleMapFormTypeBundle\Form\Type\GoogleMapType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<array>
 */
class ExtraFieldType extends AbstractType
{
    private const PAUSE_TRAINING_LABELS = [
        'pause_formation' => 'Pause training',
        'start_pause_date' => 'Start pause date',
        'end_pause_date' => 'End pause date',
        'disable_emails' => 'Disable automatic emails',
    ];

    private const PAUSE_TRAINING_HELP = [
        'pause_formation' => 'Temporarily pause inactivity follow-up for your account.',
        'start_pause_date' => 'Use your local date and time.',
        'end_pause_date' => 'Use your local date and time.',
        'disable_emails' => 'Stop automatic inactivity emails for your account.',
    ];

    public function __construct(
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
        private readonly ExtraFieldRepository $extraFieldRepository,
        private readonly TagRepository $tagRepository,
        private readonly Security $security,
        private readonly PluginHelper $pluginHelper
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $item */
        $item = $options['item'] instanceof User
            ? $options['item']
            : ($this->security->getUser() instanceof User ? $this->security->getUser() : null);

        $extraFieldType = ExtraField::USER_FIELD_TYPE;
        $extraFields = $this->extraFieldRepository->getExtraFields($extraFieldType);

        /** @var string[] $allowlist */
        $allowlist = $options['visibility_allowlist'] ?? [];

        /** @var array<string, bool> $editableMap */
        $editableMap = $options['visibility_editable_map'] ?? [];

        /** @var string[] $forcedVisibleVariables */
        $forcedVisibleVariables = $options['forced_visible_variables'] ?? [];

        /** @var array<string, bool> $forcedEditableMap */
        $forcedEditableMap = $options['forced_editable_map'] ?? [];

        /** @var string[] $excludedVariables */
        $excludedVariables = $options['excluded_variables'] ?? [];

        $strict = (bool) ($options['visibility_strict'] ?? false);

        $editableMap = array_merge($editableMap, $forcedEditableMap);

        $hasAllowlistFilter = $strict || !empty($allowlist) || !empty($forcedVisibleVariables);
        $effectiveAllowlist = $allowlist;

        if ($hasAllowlistFilter) {
            $effectiveAllowlist = array_values(array_unique(array_map(
                static fn ($value) => (string) $value,
                array_merge($allowlist, $forcedVisibleVariables)
            )));

            if (!empty($excludedVariables)) {
                $effectiveAllowlist = array_values(array_diff($effectiveAllowlist, $excludedVariables));
            }

            if (empty($effectiveAllowlist)) {
                return;
            }
        }

        $pluginEnabled = $this->pluginHelper->isPluginEnabled('google_maps');
        $gMapsPlugin = GoogleMapsPlugin::create();
        $apiEnabled = 'true' === $gMapsPlugin->get('enable_api');

        $existingVariables = array_map(
            static fn ($extraField) => $extraField->getVariable(),
            $extraFields
        );

        foreach ($forcedVisibleVariables as $variable) {
            if (\in_array($variable, $existingVariables, true)) {
                continue;
            }

            $forced = $this->extraFieldRepository->findOneBy([
                'variable' => $variable,
                'itemType' => $extraFieldType,
            ]);

            if ($forced) {
                $extraFields[] = $forced;
                $existingVariables[] = $variable;
            }
        }

        if ($pluginEnabled && $apiEnabled) {
            $forceVars = ['terms_villedustage', 'terms_ville'];

            foreach ($forceVars as $variable) {
                if (\in_array($variable, $existingVariables, true)) {
                    continue;
                }

                if ($hasAllowlistFilter && !\in_array($variable, $effectiveAllowlist, true)) {
                    continue;
                }

                $forced = $this->extraFieldRepository->findOneBy([
                    'variable' => $variable,
                    'itemType' => $extraFieldType,
                ]);

                if ($forced) {
                    $extraFields[] = $forced;
                    $existingVariables[] = $variable;
                }
            }
        }

        $data = [];
        $legacyExtraFieldValue = null;
        $legacyItemId = null;

        if ($item instanceof User) {
            $legacyExtraFieldValue = new ExtraFieldValue('user');
            $legacyItemId = (int) $item->getId();

            $values = $this->extraFieldValuesRepository->getExtraFieldValuesFromItem($item, $extraFieldType);
            foreach ($values as $value) {
                $data[$value->getField()->getVariable()] = $value->getFieldValue();
            }
        }

        foreach ($extraFields as $extraField) {
            $variable = $extraField->getVariable();

            if (\in_array($variable, $excludedVariables, true)) {
                continue;
            }

            if ($hasAllowlistFilter && !\in_array($variable, $effectiveAllowlist, true)) {
                continue;
            }

            $text = self::PAUSE_TRAINING_LABELS[$variable] ?? $extraField->getDisplayText();
            $value = $data[$variable] ?? null;

            if (null === $value && null !== $legacyExtraFieldValue && null !== $legacyItemId) {
                $legacyRow = $legacyExtraFieldValue->get_values_by_handler_and_field_variable($legacyItemId, $variable);

                if (\is_array($legacyRow)) {
                    $value = $legacyRow['value'] ?? $legacyRow['field_value'] ?? null;
                }
            }

            $editable = \array_key_exists($variable, $editableMap)
                ? (bool) $editableMap[$variable]
                : (bool) $extraField->isChangeable();

            $defaultOptions = [
                'label' => $text,
                'required' => false,
                'by_reference' => false,
                'mapped' => false,
                'data' => $value,
                'disabled' => !$editable,
            ];

            if (\array_key_exists($variable, self::PAUSE_TRAINING_LABELS)) {
                $defaultOptions['translation_domain'] = 'messages';
            }

            if (\array_key_exists($variable, self::PAUSE_TRAINING_HELP)) {
                $defaultOptions['help'] = self::PAUSE_TRAINING_HELP[$variable];
            }

            $helperText = trim((string) $extraField->getHelperText());
            if ('' !== $helperText && !\array_key_exists($variable, self::PAUSE_TRAINING_HELP)) {
                $defaultOptions['help'] = $helperText;
            }

            if (\in_array($variable, ['start_pause_date', 'end_pause_date'], true)) {
                $defaultOptions['attr'] = array_merge($defaultOptions['attr'] ?? [], [
                    'class' => trim((string) (($defaultOptions['attr']['class'] ?? '').' js-pause-training-datetime')),
                    'placeholder' => 'YYYY-MM-DD HH:mm',
                    'autocomplete' => 'off',
                ]);
            }

            switch ($extraField->getValueType()) {
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                    if ($pluginEnabled && $apiEnabled) {
                        $defaultOptions['data'] = [];
                        if (!empty($value)) {
                            $parts = explode('::', (string) $value);
                            $coordinates = isset($parts[1]) ? explode(',', $parts[1]) : [];
                            $defaultOptions['data'] = [
                                'address' => $parts[0] ?? '',
                                'latitude' => $coordinates[0] ?? '',
                                'longitude' => $coordinates[1] ?? '',
                            ];
                        }
                        $builder->add($variable, GoogleMapType::class, $defaultOptions);
                    } else {
                        if (!empty($value) && \is_string($value)) {
                            $defaultOptions['data'] = $value;
                        }
                        $defaultOptions['attr']['placeholder'] = 'address::lat,lng or lat,lng';
                        $builder->add($variable, TextType::class, $defaultOptions);
                    }

                    break;

                case \ExtraField::FIELD_TYPE_TAG:
                    $defaultOptions['expanded'] = false;
                    $defaultOptions['multiple'] = true;

                    $class = 'select2_user_rel_tag';
                    $choices = [];
                    $choicesAttributes = [];

                    if ($item instanceof User) {
                        $tags = $this->tagRepository->getTagsByUser($extraField, $item);
                        foreach ($tags as $tag) {
                            $stringTag = $tag->getTag();
                            if ('' === $stringTag) {
                                continue;
                            }
                            $choices[$stringTag] = $stringTag;
                            $choicesAttributes[$stringTag] = ['data-id' => $tag->getId()];
                        }
                    }

                    $defaultOptions['choices'] = $choices;
                    $defaultOptions['choice_attr'] = $choicesAttributes;
                    $defaultOptions['data'] = array_values($choices);
                    $defaultOptions['attr'] = array_merge($defaultOptions['attr'] ?? [], [
                        'class' => $class,
                        'style' => 'width:500px',
                        'data.field_id' => (string) $extraField->getId(),
                    ]);

                    $builder->add($variable, ChoiceType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_VIDEO_URL:
                case \ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                    $builder->add($variable, UrlType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER:
                    $builder->add($variable, TelType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_DATE:
                    $defaultOptions['data'] = !empty($value) ? new DateTime((string) $value) : null;
                    $defaultOptions['widget'] = 'single_text';
                    $builder->add($variable, DateType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_DATETIME:
                    $defaultOptions['data'] = !empty($value) ? new DateTime((string) $value) : null;
                    $defaultOptions['widget'] = 'single_text';

                    if (\in_array($variable, ['start_pause_date', 'end_pause_date'], true)) {
                        $defaultOptions['html5'] = false;
                        $defaultOptions['format'] = 'yyyy-MM-dd HH:mm';
                    }

                    $builder->add($variable, DateTimeType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_TEXTAREA:
                    $builder->add($variable, TextareaType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_FLOAT:
                    $builder->add($variable, NumberType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_INTEGER:
                    $builder->add($variable, IntegerType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_LETTERS_ONLY:
                case \ExtraField::FIELD_TYPE_ALPHANUMERIC:
                case \ExtraField::FIELD_TYPE_TEXT:
                    $builder->add($variable, TextType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_CHECKBOX:
                    $defaultOptions['data'] = '1' === (string) $value || 1 === (int) $value;
                    $builder->add($variable, CheckboxType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_RADIO:
                case \ExtraField::FIELD_TYPE_SELECT:
                    $defaultOptions['attr']['class'] = 'p-select p-component p-inputwrapper p-inputwrapper-filled';

                    // no break
                case \ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    if (empty($value)) {
                        $defaultOptions['data'] = null;
                    }

                    $fieldOptions = $extraField->getOptions();
                    $choices = [];
                    foreach ($fieldOptions as $option) {
                        $choices[$option->getDisplayText()] = $option->getValue();
                    }
                    $defaultOptions['choices'] = $choices;

                    if (\ExtraField::FIELD_TYPE_SELECT === $extraField->getValueType()) {
                        $defaultOptions['expanded'] = false;
                        $defaultOptions['multiple'] = false;
                    }

                    if (\ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $extraField->getValueType()) {
                        $defaultOptions['expanded'] = false;
                        $defaultOptions['multiple'] = true;
                    }

                    $builder->add($variable, ChoiceType::class, $defaultOptions);

                    break;

                default:
                    break;
            }
        }

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($item, $extraFields, $excludedVariables): void {
                if (!$item instanceof User) {
                    return;
                }

                $submittedData = $event->getData() ?? [];
                if (!\is_array($submittedData)) {
                    $submittedData = [];
                }

                foreach (['pause_formation', 'disable_emails'] as $checkboxVariable) {
                    if (!$event->getForm()->has($checkboxVariable)) {
                        continue;
                    }

                    if (!\array_key_exists($checkboxVariable, $submittedData)) {
                        $submittedData[$checkboxVariable] = '0';
                    } else {
                        $submittedData[$checkboxVariable] = empty($submittedData[$checkboxVariable]) ? '0' : '1';
                    }
                }

                foreach (['start_pause_date', 'end_pause_date'] as $dateVariable) {
                    if (\array_key_exists($dateVariable, $submittedData)) {
                        $value = $submittedData[$dateVariable];

                        if (null === $value || '' === $value) {
                            $submittedData[$dateVariable] = null;
                        } else {
                            $submittedData[$dateVariable] = (string) $value;
                        }
                    }
                }

                $event->setData($submittedData);

                foreach ($extraFields as $extraField) {
                    $variable = $extraField->getVariable();

                    if (\in_array($variable, $excludedVariables, true)) {
                        continue;
                    }

                    if (!\array_key_exists($variable, $submittedData)) {
                        continue;
                    }

                    $newValue = $submittedData[$variable];

                    switch ($extraField->getValueType()) {
                        case \ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                        case \ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                            if (!empty($newValue) && \is_array($newValue)) {
                                $newValue = ($newValue['address'] ?? '').'::'.($newValue['latitude'] ?? '').','.($newValue['longitude'] ?? '');
                            }
                            $this->extraFieldValuesRepository->updateItemData($extraField, $item, $newValue);

                            break;

                        case \ExtraField::FIELD_TYPE_TAG:
                            $formItem = $event->getForm()->get($variable);
                            $fieldOptions = $formItem->getConfig()->getOptions();
                            $fieldOptions['choices'] = $newValue;
                            $event->getForm()->add($variable, ChoiceType::class, $fieldOptions);

                            if (!empty($newValue)) {
                                foreach ((array) $newValue as $tag) {
                                    $this->tagRepository->addTagToUser($extraField, $item, $tag);
                                }
                            }

                            break;

                        default:
                            if (null !== $newValue && !\is_string($newValue)) {
                                $newValue = (string) $newValue;
                            }

                            $this->extraFieldValuesRepository->updateItemData($extraField, $item, $newValue);

                            break;
                    }
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'visibility_allowlist' => [],
            'visibility_editable_map' => [],
            'visibility_strict' => false,
            'forced_visible_variables' => [],
            'forced_editable_map' => [],
            'excluded_variables' => [],
            'item' => null,
        ]);

        $resolver->setAllowedTypes('visibility_allowlist', ['array']);
        $resolver->setAllowedTypes('visibility_editable_map', ['array']);
        $resolver->setAllowedTypes('visibility_strict', ['bool']);
        $resolver->setAllowedTypes('forced_visible_variables', ['array']);
        $resolver->setAllowedTypes('forced_editable_map', ['array']);
        $resolver->setAllowedTypes('excluded_variables', ['array']);
        $resolver->setAllowedTypes('item', ['null', User::class]);
    }
}
