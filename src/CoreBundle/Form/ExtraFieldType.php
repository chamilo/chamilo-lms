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
        $item = $this->security->getUser();
        if (null === $item) {
            return;
        }

        $extraFieldType = ExtraField::USER_FIELD_TYPE;

        // Load all extra fields for user
        $extraFields = $this->extraFieldRepository->getExtraFields($extraFieldType);

        // Optional allowlist/editable map provided by parent form
        /** @var string[] $allowlist */
        $allowlist = $options['visibility_allowlist'] ?? [];
        /** @var array<string,bool> $editableMap */
        $editableMap = $options['visibility_editable_map'] ?? [];

        // Determine Google Maps plugin state (enabled + API on)
        $pluginEnabled = $this->pluginHelper->isPluginEnabled('google_maps');
        $gMapsPlugin = GoogleMapsPlugin::create();
        $apiEnabled = ('true' === $gMapsPlugin->get('enable_api'));

        // If allowlist is provided and plugin is active, we may add forced fields only if included in allowlist
        if ($pluginEnabled && $apiEnabled) {
            $forceVars = ['terms_villedustage', 'terms_ville'];
            $existing = array_map(static fn ($ef) => $ef->getVariable(), $extraFields);
            foreach ($forceVars as $v) {
                if (!\in_array($v, $existing, true)) {
                    // Only inject if parent explicitly allowed it
                    if (!empty($allowlist) && !\in_array($v, $allowlist, true)) {
                        continue;
                    }
                    $forced = $this->extraFieldRepository->findOneBy([
                        'variable' => $v,
                        'itemType' => $extraFieldType,
                    ]);
                    if ($forced) {
                        $extraFields[] = $forced;
                    }
                }
            }
        }

        // Fetch current values for the logged user
        $values = $this->extraFieldValuesRepository->getExtraFieldValuesFromItem($item, $extraFieldType);
        $data = [];
        foreach ($values as $value) {
            $data[$value->getField()->getVariable()] = $value->getFieldValue();
        }

        // Build form fields
        foreach ($extraFields as $extraField) {
            $variable = $extraField->getVariable();

            // If allowlist provided, skip not-listed extras
            if (!empty($allowlist) && !\in_array($variable, $allowlist, true)) {
                continue;
            }

            $text     = $extraField->getDisplayText();
            $value    = $data[$variable] ?? null;

            // If editable map provided, use it; otherwise fallback to field config
            $editable = \array_key_exists($variable, $editableMap)
                ? (bool) $editableMap[$variable]
                : (bool) $extraField->isChangeable();

            $defaultOptions = [
                'label'        => $text,
                'required'     => false,
                'by_reference' => false,
                'mapped'       => false,
                'data'         => $value,
                'disabled'     => !$editable,
            ];

            switch ($extraField->getValueType()) {
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES:
                case \ExtraField::FIELD_TYPE_GEOLOCALIZATION:
                    if ($pluginEnabled && $apiEnabled) {
                        $defaultOptions['data'] = [];
                        if (!empty($value)) {
                            $parts = explode('::', (string) $value);
                            $coordinates = isset($parts[1]) ? explode(',', $parts[1]) : [];
                            $defaultOptions['data'] = [
                                'address'   => $parts[0] ?? '',
                                'latitude'  => $coordinates[0] ?? '',
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

                    // Preload existing user tags as choices (if any)
                    $class = 'select2_extra_rel_tag';
                    $tags = $this->tagRepository->getTagsByUser($extraField, $item);
                    $choices = [];
                    $choicesAttributes = [];
                    foreach ($tags as $tag) {
                        $stringTag = $tag->getTag();
                        if ($stringTag === '') {
                            continue;
                        }
                        $choices[$stringTag] = $stringTag;
                        $choicesAttributes[$stringTag] = ['data-id' => $tag->getId()];
                    }
                    $defaultOptions['choices'] = $choices;
                    $defaultOptions['choice_attr'] = $choicesAttributes;
                    $defaultOptions['data'] = $choices;

                    $defaultOptions['attr'] = [
                        'class' => $class,
                        'style' => 'width:500px',
                        'data.field_id' => $extraField->getId(),
                    ];
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
                    $defaultOptions['data'] = null;
                    if (!empty($value)) {
                        $defaultOptions['data'] = new DateTime((string) $value);
                    }
                    $defaultOptions['widget'] = 'single_text';
                    $builder->add($variable, DateType::class, $defaultOptions);

                    break;

                case \ExtraField::FIELD_TYPE_DATETIME:
                    $defaultOptions['data'] = null;
                    if (!empty($value)) {
                        $defaultOptions['data'] = new DateTime((string) $value);
                    }
                    $defaultOptions['widget'] = 'single_text';
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
                    $defaultOptions['data'] = (1 === (int) $value);
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
                    $options = $extraField->getOptions();
                    $choices = [];
                    foreach ($options as $option) {
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
                    // Safely skip unsupported types
                    break;
            }
        }

        // Persist new values on submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($item, $extraFields): void {
                $data = $event->getData() ?? [];
                foreach ($extraFields as $extraField) {
                    $variable = $extraField->getVariable();

                    // If field wasn't built (e.g., filtered by allowlist), skip
                    if (!\array_key_exists($variable, $data)) {
                        continue;
                    }

                    $newValue = $data[$variable];

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
                            $options = $formItem->getConfig()->getOptions();
                            $options['choices'] = $newValue;
                            $event->getForm()->add($variable, ChoiceType::class, $options);

                            if (!empty($newValue)) {
                                foreach ((array) $newValue as $tag) {
                                    $this->tagRepository->addTagToUser($extraField, $item, $tag);
                                }
                            }

                            break;

                        default:
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
            // Optional: list of extra variables to render. Empty = render all.
            'visibility_allowlist'    => [],
            // Optional: control editable per variable. Empty = fallback to ExtraField::isChangeable()
            'visibility_editable_map' => [],
        ]);

        $resolver->setAllowedTypes('visibility_allowlist', ['array']);
        $resolver->setAllowedTypes('visibility_editable_map', ['array']);
    }
}
