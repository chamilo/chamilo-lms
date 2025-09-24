<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeZone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<User>
 */
class ProfileType extends AbstractType
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly SettingsManager $settingsManager,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // High-level lists (fallback behavior)
        $changeableOptions = $this->settingsManager->getSetting('profile.changeable_options', true) ?? [];
        $visibleOptions    = $this->settingsManager->getSetting('profile.visible_options', true) ?? [];

        // Fine-grained JSON (authoritative if present)
        $rawFine = $this->settingsManager->getSetting('profile.profile_fields_visibility', true) ?? [];
        if (\is_string($rawFine)) {
            try {
                $decoded = \json_decode($rawFine, true, 512, \JSON_THROW_ON_ERROR);
                $rawFine = \is_array($decoded) ? $decoded : [];
            } catch (\Throwable) {
                $rawFine = [];
            }
        }
        $fieldsVisibility = [];
        if (\is_array($rawFine)) {
            $fieldsVisibility = $rawFine['options'] ?? $rawFine;
            if (!\is_array($fieldsVisibility)) {
                $fieldsVisibility = [];
            }
        }
        $hasFine = !empty($fieldsVisibility); // strict mode if true

        // Expand aliases used by high-level settings (fallbacks only)
        $expandMap = [
            'name'    => ['firstname', 'lastname'],
            'surname' => ['lastname'],
        ];
        $expand = static function (array $keys) use ($expandMap): array {
            $out = [];
            foreach ($keys as $k) {
                $out = array_merge($out, $expandMap[$k] ?? [$k]);
            }

            return array_values(array_unique($out));
        };

        $visibleHigh  = $expand(\is_array($visibleOptions) ? $visibleOptions : []);
        $editableHigh = $expand(\is_array($changeableOptions) ? $changeableOptions : []);

        $languages = array_flip($this->languageRepository->getAllAvailableToArray(true, true));

        // Core fields map (keys must align with settings keys)
        $fieldsMap = [
            'firstname'    => ['field' => 'firstname',     'type' => TextType::class,         'label' => 'Firstname'],
            'lastname'     => ['field' => 'lastname',      'type' => TextType::class,         'label' => 'Lastname'],
            'officialcode' => ['field' => 'official_code', 'type' => TextType::class,         'label' => 'Official Code'],
            'email'        => ['field' => 'email',         'type' => EmailType::class,        'label' => 'Email'],
            'picture'      => ['field' => 'illustration',  'type' => IllustrationType::class, 'label' => 'Picture', 'mapped' => false],
            'login'        => ['field' => 'login',         'type' => TextType::class,         'label' => 'Login'],
            'password'     => ['field' => 'password',      'type' => PasswordType::class,     'label' => 'Password', 'mapped' => false, 'required' => false],
            'language'     => [
                'field' => 'locale',
                'type'  => ChoiceType::class,
                'label' => 'Language',
                'choices' => $languages,
                'required' => true,
                'placeholder' => null,
                'choice_translation_domain' => false,
            ],
            'phone'        => ['field' => 'phone',         'type' => TextType::class,         'label' => 'Phone Number'],
            'theme'        => ['field' => 'theme',         'type' => TextType::class,         'label' => 'Theme'],

            // Core date_of_birth → entity property dateOfBirth
            'date_of_birth' => [
                'field' => 'date_of_birth',
                'type'  => DateType::class,
                'label' => 'Date of birth',
                'required' => false,
                'form_options' => [
                    'widget'        => 'single_text',
                    'html5'         => false,
                    'format'        => 'yyyy-MM-dd',
                    'property_path' => 'dateOfBirth',
                    'attr'          => [
                        'class'        => 'js-date-of-birth',
                        'placeholder'  => 'YYYY-MM-DD',
                        'autocomplete' => 'bday',
                        'inputmode'    => 'numeric',
                    ],
                ],
            ],
            // Timezone will be added below if visible (fine JSON or fallback)
            'timezone'     => [
                'field' => 'timezone',
                'type'  => ChoiceType::class,
                'label' => 'Timezone',
                'required' => false,
                'form_options' => static function (): array {
                    $timezones = DateTimeZone::listIdentifiers();
                    sort($timezones);
                    $choices = array_combine($timezones, $timezones);
                    return [
                        'choices' => $choices,
                        'placeholder' => '',
                        'choice_translation_domain' => false,
                    ];
                },
            ],
        ];

        // Visibility (core):
        // Strict when $hasFine: only keys present in $fieldsVisibility are visible.
        // Otherwise, fallback to visible_options.
        $isCoreVisible = function (string $key) use ($fieldsVisibility, $visibleHigh, $hasFine): bool {
            if ($hasFine) {
                return \array_key_exists($key, $fieldsVisibility);
            }
            return \in_array($key, $visibleHigh, true);
        };

        // Editability (core):
        // If key is in fine JSON, its boolean decides; otherwise fallback to changeable_options.
        $isCoreEditable = function (string $key) use ($fieldsVisibility, $editableHigh): bool {
            if (\array_key_exists($key, $fieldsVisibility)) {
                return (bool) $fieldsVisibility[$key];
            }
            return \in_array($key, $editableHigh, true);
        };

        // Build core fields (except timezone; decide after)
        foreach ($fieldsMap as $key => $fieldConfig) {
            if ($key === 'timezone') {
                continue;
            }
            if (!$isCoreVisible($key)) {
                continue;
            }

            $opts = [
                'label'    => $fieldConfig['label'],
                'required' => $fieldConfig['required'] ?? false,
                'mapped'   => $fieldConfig['mapped']   ?? true,
            ];

            if (isset($fieldConfig['choices'])) {
                $opts['choices'] = $fieldConfig['choices'];
                if (isset($fieldConfig['placeholder'])) {
                    $opts['placeholder'] = $fieldConfig['placeholder'];
                }
                if (isset($fieldConfig['choice_translation_domain'])) {
                    $opts['choice_translation_domain'] = $fieldConfig['choice_translation_domain'];
                }
            }

            if (isset($fieldConfig['form_options'])) {
                $extra = \is_callable($fieldConfig['form_options'])
                    ? ($fieldConfig['form_options'])()
                    : (array) $fieldConfig['form_options'];
                $opts = array_merge($opts, $extra);
            }

            if (!$isCoreEditable($key)) {
                $opts['disabled'] = true;
            }

            $builder->add($fieldConfig['field'], $fieldConfig['type'], $opts);
        }

        // Timezone: only show if visible (fine JSON present with key, or fallback says visible)
        if ($isCoreVisible('timezone')) {
            $tzCfg = $fieldsMap['timezone'];
            $opts = [
                'label'    => $tzCfg['label'],
                'required' => $tzCfg['required'],
                'mapped'   => true,
            ];
            $extra = ($tzCfg['form_options'])();
            $opts = array_merge($opts, $extra);
            if (!$isCoreEditable('timezone')) {
                $opts['disabled'] = true;
            }
            $builder->add($tzCfg['field'], $tzCfg['type'], $opts);
        }

        // Build ExtraFieldType with allowlist + editable map derived from fine JSON (strict when present)
        $coreKeys = array_keys($fieldsMap);
        $extraAllowlist = [];
        $extraEditableMap = [];

        if ($hasFine) {
            // Strict: only extras listed in fine JSON
            foreach ($fieldsVisibility as $key => $bool) {
                if (!\in_array($key, $coreKeys, true)) {
                    $extraAllowlist[] = $key;               // visible
                    $extraEditableMap[$key] = (bool) $bool; // editable
                }
            }
        } else {
            // Fallback: show all extras (no allowlist) and let ExtraField configuration drive editability
            $extraAllowlist = []; // empty = render all extras
            $extraEditableMap = []; // let EF config decide
        }

        $builder->add('extra_fields', ExtraFieldType::class, [
            'mapped'                  => false,
            'label'                   => false,
            'visibility_allowlist'    => $extraAllowlist,
            'visibility_editable_map' => $extraEditableMap,
            'visibility_strict'       => $hasFine,
            'item'                    => $builder->getData(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
