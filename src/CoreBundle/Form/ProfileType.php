<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeZone;
use PauseTraining;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Throwable;

use const JSON_THROW_ON_ERROR;

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
        $changeableOptions = $this->normalizeSettingList(
            $this->settingsManager->getSetting('profile.changeable_options', true) ?? []
        );
        $visibleOptions = $this->normalizeSettingList(
            $this->settingsManager->getSetting('profile.visible_options', true) ?? []
        );

        $requiredOptions = $this->normalizeSettingList(
            $this->settingsManager->getSetting('registration.required_profile_fields', true)
            ?? $this->settingsManager->getSetting('registration.required_fields', true)
            ?? []
        );

        $usersTimezonesEnabled = 'true' === (string) $this->settingsManager->getSetting('profile.use_users_timezone', true);

        $rawFine = $this->settingsManager->getSetting('profile.profile_fields_visibility', true) ?? [];
        if (\is_string($rawFine)) {
            try {
                $decoded = json_decode($rawFine, true, 512, JSON_THROW_ON_ERROR);
                $rawFine = \is_array($decoded) ? $decoded : [];
            } catch (Throwable) {
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

        $hasFine = !empty($fieldsVisibility);

        $expandMap = [
            'name' => ['firstname', 'lastname'],
            'surname' => ['lastname'],
        ];

        $expand = static function (array $keys) use ($expandMap): array {
            $out = [];
            foreach ($keys as $key) {
                $out = array_merge($out, $expandMap[$key] ?? [$key]);
            }

            return array_values(array_unique($out));
        };

        $visibleHigh = $expand($visibleOptions);
        $editableHigh = $expand($changeableOptions);
        $requiredHigh = $expand($requiredOptions);

        $languages = array_flip($this->languageRepository->getAllAvailableToArray(true, true));
        $ignoredKeys = ['theme'];

        $pauseTrainingFields = [
            'pause_formation',
            'start_pause_date',
            'end_pause_date',
            'disable_emails',
        ];
        $showPauseTrainingFields = $this->shouldShowPauseTrainingFields();

        $fieldsMap = [
            'firstname' => ['field' => 'firstname', 'type' => TextType::class, 'label' => 'First name'],
            'lastname' => ['field' => 'lastname', 'type' => TextType::class, 'label' => 'Last name'],
            'officialcode' => ['field' => 'official_code', 'type' => TextType::class, 'label' => 'Official code'],
            'email' => ['field' => 'email', 'type' => EmailType::class, 'label' => 'E-mail'],
            'picture' => ['field' => 'illustration', 'type' => IllustrationType::class, 'label' => 'Picture', 'mapped' => false],
            'login' => ['field' => 'login', 'type' => TextType::class, 'label' => 'Username'],
            'password' => ['field' => 'password', 'type' => PasswordType::class, 'label' => 'Password', 'mapped' => false, 'required' => false],
            'language' => [
                'field' => 'locale',
                'type' => ChoiceType::class,
                'label' => 'Language',
                'choices' => $languages,
                'required' => true,
                'placeholder' => null,
                'choice_translation_domain' => false,
            ],
            'phone' => ['field' => 'phone', 'type' => TextType::class, 'label' => 'Phone number'],
            'theme' => ['field' => 'theme', 'type' => TextType::class, 'label' => 'Theme (stylesheet)'],
            'date_of_birth' => [
                'field' => 'date_of_birth',
                'type' => DateType::class,
                'label' => 'Date of birth',
                'required' => false,
                'form_options' => [
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'yyyy-MM-dd',
                    'property_path' => 'dateOfBirth',
                    'attr' => [
                        'class' => 'js-date-of-birth',
                        'placeholder' => 'YYYY-MM-DD',
                        'autocomplete' => 'bday',
                        'inputmode' => 'numeric',
                    ],
                ],
            ],
            'timezone' => [
                'field' => 'timezone',
                'type' => ChoiceType::class,
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

        $isCoreVisible = function (string $key) use ($fieldsVisibility, $visibleHigh, $hasFine, $ignoredKeys, $usersTimezonesEnabled): bool {
            if (\in_array($key, $ignoredKeys, true)) {
                return false;
            }

            if ('timezone' === $key) {
                return $usersTimezonesEnabled;
            }

            if ($hasFine) {
                return \array_key_exists($key, $fieldsVisibility);
            }

            return \in_array($key, $visibleHigh, true);
        };

        $isCoreEditable = function (string $key) use ($fieldsVisibility, $editableHigh, $ignoredKeys, $usersTimezonesEnabled): bool {
            if (\in_array($key, $ignoredKeys, true)) {
                return false;
            }

            if ('timezone' === $key) {
                return $usersTimezonesEnabled;
            }

            if (\array_key_exists($key, $fieldsVisibility)) {
                return (bool) $fieldsVisibility[$key];
            }

            return \in_array($key, $editableHigh, true);
        };

        $isCoreRequired = static function (string $key) use ($requiredHigh): bool {
            return \in_array($key, $requiredHigh, true);
        };

        foreach ($fieldsMap as $key => $fieldConfig) {
            if ('timezone' === $key) {
                continue;
            }

            if ('password' === $key && !$options['include_password_field']) {
                continue;
            }

            if (!$isCoreVisible($key)) {
                continue;
            }

            $required = (bool) ($fieldConfig['required'] ?? false);
            if (!$required && $isCoreRequired($key)) {
                $required = true;
            }

            $isEditable = $isCoreEditable($key);
            $opts = [
                'label' => $fieldConfig['label'],
                'required' => $required,
                'mapped' => $fieldConfig['mapped'] ?? true,
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

            if (\in_array($fieldConfig['type'], [TextType::class, EmailType::class], true)) {
                $opts['empty_data'] = '';
                $opts['trim'] = true;
            }

            if (!$isEditable) {
                $opts['disabled'] = true;
                $opts['required'] = false;
            }

            if ($opts['required']) {
                $existingLabelAttr = $opts['label_attr'] ?? [];
                $existingClass = (string) ($existingLabelAttr['class'] ?? '');
                $existingLabelAttr['class'] = trim($existingClass.' required');
                $opts['label_attr'] = $existingLabelAttr;
            }

            if ('email' === $key) {
                $constraints = $opts['constraints'] ?? [];
                $constraints = $this->addConstraintIfMissing(
                    $constraints,
                    EmailConstraint::class,
                    new EmailConstraint([
                        'mode' => EmailConstraint::VALIDATION_MODE_HTML5,
                        'message' => 'Please enter a valid email address.',
                    ])
                );
                $opts['constraints'] = $constraints;
                $opts['invalid_message'] = 'Please enter a valid email address.';
                $opts['empty_data'] = '';
            } elseif ($opts['required'] && 'picture' !== $key && 'password' !== $key) {
                $constraints = $opts['constraints'] ?? [];
                $constraints = $this->addConstraintIfMissing(
                    $constraints,
                    NotBlank::class,
                    new NotBlank([
                        'message' => 'This value should not be blank.',
                    ])
                );
                $opts['constraints'] = $constraints;
            }

            $builder->add($fieldConfig['field'], $fieldConfig['type'], $opts);
        }

        if ($isCoreVisible('timezone')) {
            $tzCfg = $fieldsMap['timezone'];
            $isEditable = $isCoreEditable('timezone');

            $opts = [
                'label' => $tzCfg['label'],
                'required' => $tzCfg['required'],
                'mapped' => true,
            ];
            $extra = ($tzCfg['form_options'])();
            $opts = array_merge($opts, $extra);

            if (!$isEditable) {
                $opts['disabled'] = true;
                $opts['required'] = false;
            }

            if ($opts['required']) {
                $existingLabelAttr = $opts['label_attr'] ?? [];
                $existingClass = (string) ($existingLabelAttr['class'] ?? '');
                $existingLabelAttr['class'] = trim($existingClass.' required');
                $opts['label_attr'] = $existingLabelAttr;

                $constraints = $opts['constraints'] ?? [];
                $constraints[] = new NotBlank([
                    'message' => 'This value should not be blank.',
                ]);
                $opts['constraints'] = $constraints;
            }

            $builder->add($tzCfg['field'], $tzCfg['type'], $opts);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }

            if (\array_key_exists('email', $data) && null === $data['email']) {
                $data['email'] = '';
            }

            $event->setData($data);
        });

        $coreKeys = array_keys($fieldsMap);
        $extraAllowlist = [];
        $extraEditableMap = [];

        if ($hasFine) {
            foreach ($fieldsVisibility as $key => $bool) {
                if (\in_array($key, $ignoredKeys, true)) {
                    continue;
                }
                if (!\in_array($key, $coreKeys, true)) {
                    $extraAllowlist[] = $key;
                    $extraEditableMap[$key] = (bool) $bool;
                }
            }
        }

        $showPauseTrainingFields = $this->shouldShowPauseTrainingFields();

        $builder->add('extra_fields', ExtraFieldType::class, [
            'mapped' => false,
            'label' => false,
            'visibility_allowlist' => $extraAllowlist,
            'visibility_editable_map' => $extraEditableMap,
            'visibility_strict' => $hasFine,
            'forced_visible_variables' => $showPauseTrainingFields ? $pauseTrainingFields : [],
            'forced_editable_map' => $showPauseTrainingFields ? array_fill_keys($pauseTrainingFields, true) : [],
            'excluded_variables' => $showPauseTrainingFields ? [] : $pauseTrainingFields,
            'item' => $builder->getData(),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_password_field' => false,
        ]);

        $resolver->setAllowedTypes('include_password_field', 'bool');
    }

    private function addConstraintIfMissing(array $constraints, string $constraintClass, object $constraint): array
    {
        foreach ($constraints as $existingConstraint) {
            if ($existingConstraint instanceof $constraintClass) {
                return $constraints;
            }
        }

        $constraints[] = $constraint;

        return $constraints;
    }

    private function normalizeSettingList(mixed $value): array
    {
        if (\is_array($value)) {
            return array_values(array_filter(array_map(
                static fn ($item) => \is_string($item) ? trim($item) : '',
                $value
            )));
        }

        if (\is_string($value)) {
            $value = trim($value);
            if ('' === $value) {
                return [];
            }

            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    private function shouldShowPauseTrainingFields(): bool
    {
        if (!$this->loadPauseTrainingPlugin()) {
            return false;
        }

        try {
            $plugin = PauseTraining::create();

            return 'true' === (string) $plugin->get('tool_enable')
                && 'true' === (string) $plugin->get('allow_users_to_edit_pause_formation');
        } catch (Throwable) {
            return false;
        }
    }

    private function loadPauseTrainingPlugin(): bool
    {
        if (class_exists(PauseTraining::class, false)) {
            return true;
        }

        if (!\function_exists('api_get_path') || !\defined('SYS_PLUGIN_PATH')) {
            return false;
        }

        $pluginBasePath = rtrim((string) api_get_path(SYS_PLUGIN_PATH), '/\\');
        $candidateFiles = [
            $pluginBasePath.'/PauseTraining/PauseTraining.php',
            $pluginBasePath.'/pausetraining/PauseTraining.php',
        ];

        foreach ($candidateFiles as $candidateFile) {
            if (is_file($candidateFile)) {
                require_once $candidateFile;

                break;
            }
        }

        return class_exists(PauseTraining::class, false);
    }
}
