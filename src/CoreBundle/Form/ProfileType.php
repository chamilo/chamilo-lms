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

        // Fine-grained (category + key): profile.profile_fields_visibility (JSON)
        $visibilitySetting = $this->settingsManager->getSetting('profile.profile_fields_visibility', true) ?? [];
        if (\is_string($visibilitySetting)) {
            try {
                $decoded = \json_decode($visibilitySetting, true, 512, \JSON_THROW_ON_ERROR);
                if (\is_array($decoded)) {
                    $visibilitySetting = $decoded;
                }
            } catch (\Throwable) {
                $visibilitySetting = [];
            }
        }
        $fieldsVisibility = [];
        if (\is_array($visibilitySetting)) {
            $fieldsVisibility = $visibilitySetting['options'] ?? $visibilitySetting;
            if (!\is_array($fieldsVisibility)) {
                $fieldsVisibility = [];
            }
        }

        // Expand aliases used by high-level settings
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

        $languages = array_flip($this->languageRepository->getAllAvailableToArray(true));

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
                    // Plain input + flatpickr in Twig (better UX) - html5 off to avoid native picker
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
        ];

        // Priority rules (core):
        // - If key exists in fine-grained map: boolean means editable=true/false; presence means visible
        // - If not present: visible/editable fall back to high-level lists
        $isCoreVisible = function (string $key) use ($fieldsVisibility, $visibleHigh): bool {
            if (\array_key_exists($key, $fieldsVisibility)) {
                return true; // listed → visible
            }
            return \in_array($key, $visibleHigh, true);
        };
        $isCoreEditable = function (string $key) use ($fieldsVisibility, $editableHigh): bool {
            if (\array_key_exists($key, $fieldsVisibility)) {
                return (bool) $fieldsVisibility[$key]; // json boolean drives editability
            }
            return \in_array($key, $editableHigh, true);
        };

        // Build core fields
        foreach ($fieldsMap as $key => $fieldConfig) {
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

            if (isset($fieldConfig['form_options']) && \is_array($fieldConfig['form_options'])) {
                $opts = array_merge($opts, $fieldConfig['form_options']);
            }

            if (!$isCoreEditable($key)) {
                $opts['disabled'] = true;
            }

            $builder->add($fieldConfig['field'], $fieldConfig['type'], $opts);
        }

        // Timezone (optional, independent from the visibility settings above)
        if ('true' === $this->settingsManager->getSetting('profile.use_users_timezone', true)) {
            $timezones = DateTimeZone::listIdentifiers();
            sort($timezones);
            $timezoneChoices = array_combine($timezones, $timezones);

            $builder->add(
                'timezone',
                ChoiceType::class,
                [
                    'label' => 'Timezone',
                    'choices' => $timezoneChoices,
                    'required' => false,
                    'placeholder' => '',
                    'choice_translation_domain' => false,
                ]
            );
        }

        // Build ExtraFieldType with allowlist + editable map derived from JSON
        // Consider as "core" the keys present in $fieldsMap; the rest of JSON keys affect "extra" fields.
        $coreKeys = array_keys($fieldsMap);

        $extraAllowlist = [];
        $extraEditableMap = [];
        foreach ($fieldsVisibility as $key => $bool) {
            if (!\in_array($key, $coreKeys, true)) {
                $extraAllowlist[] = $key;          // presence in JSON ⇒ visible
                $extraEditableMap[$key] = (bool) $bool; // boolean ⇒ editable
            }
        }

        $builder->add('extra_fields', ExtraFieldType::class, [
            'mapped'                  => false,
            'label'                   => false,
            'visibility_allowlist'    => $extraAllowlist,    // empty → show all; non-empty → filter
            'visibility_editable_map' => $extraEditableMap,  // editable control per extra
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
