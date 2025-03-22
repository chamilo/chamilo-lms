<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
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
        $changeableOptions = $this->settingsManager->getSetting('profile.changeable_options', true) ?? [];
        $visibleOptions = $this->settingsManager->getSetting('profile.visible_options', true) ?? [];
        $languages = array_flip($this->languageRepository->getAllAvailableToArray(true));

        $fieldsMap = [
            'name' => ['field' => 'firstname', 'type' => TextType::class, 'label' => 'Firstname'],
            'officialcode' => ['field' => 'official_code', 'type' => TextType::class, 'label' => 'Official Code'],
            'email' => ['field' => 'email', 'type' => EmailType::class, 'label' => 'Email'],
            'picture' => [
                'field' => 'illustration',
                'type' => IllustrationType::class,
                'label' => 'Picture',
                'mapped' => false,
            ],
            'login' => ['field' => 'login', 'type' => TextType::class, 'label' => 'Login'],
            'password' => [
                'field' => 'password',
                'type' => PasswordType::class,
                'label' => 'Password',
                'mapped' => false,
                'required' => false,
            ],
            'language' => [
                'field' => 'locale',
                'type' => ChoiceType::class,
                'label' => 'Language',
                'choices' => $languages,
            ],
            'phone' => ['field' => 'phone', 'type' => TextType::class, 'label' => 'Phone Number'],
            'theme' => ['field' => 'theme', 'type' => TextType::class, 'label' => 'Theme'],
        ];

        foreach ($fieldsMap as $key => $fieldConfig) {
            if (\in_array($key, $visibleOptions)) {
                $isEditable = \in_array($key, $changeableOptions);
                $builder->add(
                    $fieldConfig['field'],
                    $fieldConfig['type'],
                    array_merge(
                        [
                            'label' => $fieldConfig['label'],
                            'required' => $fieldConfig['required'] ?? false,
                            'mapped' => $fieldConfig['mapped'] ?? true,
                            'attr' => !$isEditable ? ['readonly' => true] : [],
                        ],
                        isset($fieldConfig['choices']) ? ['choices' => $fieldConfig['choices']] : []
                    )
                );
            }
        }

        if ('true' === $this->settingsManager->getSetting('use_users_timezone') && \in_array('timezone', $visibleOptions)) {
            $builder->add(
                'timezone',
                TimezoneType::class,
                [
                    'label' => 'Timezone',
                    'required' => true,
                    'attr' => !\in_array('timezone', $changeableOptions) ? ['readonly' => true] : [],
                ]
            );
        }

        $builder->add('extra_fields', ExtraFieldType::class, ['mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }
}
