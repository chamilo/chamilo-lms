<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class ProfileType extends AbstractType
{
    private LanguageRepository $languageRepository;
    private ExtraFieldValuesRepository $extraFieldValuesRepository;
    private Security $security;
    private ExtraFieldRepository $extraFieldRepository;

    public function __construct(
        LanguageRepository $languageRepository,
        ExtraFieldValuesRepository $extraFieldValuesRepository,
        ExtraFieldRepository $extraFieldRepository,
        Security $security
    ) {
        $this->languageRepository = $languageRepository;
        $this->extraFieldValuesRepository = $extraFieldValuesRepository;
        $this->extraFieldRepository = $extraFieldRepository;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = array_flip($this->languageRepository->getAllAvailableToArray());

        $builder
            ->add('firstname', TextType::class, ['label' => 'Firstname', 'required' => true])
            ->add('lastname', TextType::class, ['label' => 'Lastname', 'required' => true])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => true])
            //->add('official_code', TextType::class)
            //->add('groups')
            ->add('locale', LocaleType::class, [
                //'preferred_choices' => ['en', 'fr_FR', 'es_ES', 'pt', 'nl'],
                'choices' => $languages,
                'choice_loader' => null,
            ])
            /*->add(                'dateOfBirth',
                BirthdayType::class,
                [
                    'label' => 'form.label_date_of_birth',
                    'required' => false,
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'biography',
                TextareaType::class,
                [
                    'label' => 'form.label_biography',
                    'required' => false,
                ]
            )*/
            /*->add('locale', 'locale', array(
                'label'    => 'form.label_locale',
                'required' => false,
            ))*/
            ->add('timezone', TimezoneType::class, ['label' => 'Timezone', 'required' => true])
            ->add('phone', TextType::class, ['label' => 'Phone number', 'required' => false])
            ->add(
                'illustration',
                IllustrationType::class,
                ['label' => 'Picture', 'required' => false, 'mapped' => false]
            )
            //->add('website', UrlType::class, ['label' => 'Website', 'required' => false])
            /*->add(
                'extraFieldValues',
                CollectionType::class,
                array(
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'type' => 'chamilo_user_extra_field_value',
                    'by_reference' => false,
                    'prototype' => true,
                    'widget_add_btn' => ['label' => 'Add'],
                    'options' => array( // options for collection fields
                        'widget_remove_btn' => array('label' => 'Remove'),
                        'label_render' => false,
                    )
                )
            )*/
            //->add('save', 'submit', array('label' => 'Update')            )
        ;

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (null === $user) {
            return;
        }

        $extraFields = $this->extraFieldRepository->getExtraFields(ExtraField::USER_FIELD_TYPE);
        $values = $this->extraFieldValuesRepository->getExtraFieldValuesFromItem($user, ExtraField::USER_FIELD_TYPE);

        $data = [];
        foreach ($values as $value) {
            $data[$value->getField()->getVariable()] = $value->getValue();
        }

        foreach ($extraFields as $extraField) {
            $text = $extraField->getDisplayText();
            $variable = $extraField->getVariable();
            $value = $data[$extraField->getVariable()] ?? '';

            // @todo
            switch ($extraField->getFieldType()) {
                case \ExtraField::FIELD_TYPE_DATETIME:
                    $builder->add($variable, DateTimeType::class, [
                        'label' => $text,
                        'required' => false,
                        'by_reference' => false,
                        'mapped' => false,
                        'data' => $value,
                    ]);

                    break;
                case \ExtraField::FIELD_TYPE_DATE:
                    $builder->add($variable, DateType::class, [
                        'label' => $text,
                        'required' => false,
                        'by_reference' => false,
                        'mapped' => false,
                        'data' => $value,
                    ]);

                    break;
                case \ExtraField::FIELD_TYPE_TEXTAREA:
                case \ExtraField::FIELD_TYPE_TEXT:
                    $builder->add($variable, TextType::class, [
                        'label' => $text,
                        'required' => false,
                        'by_reference' => false,
                        'mapped' => false,
                        'data' => $value,
                    ]);

                    break;
                case \ExtraField::FIELD_TYPE_CHECKBOX:
                case \ExtraField::FIELD_TYPE_RADIO:
                case \ExtraField::FIELD_TYPE_SELECT:
                case \ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    $options = $extraField->getOptions();
                    $choices = [];
                    foreach ($options as $option) {
                        $choices[$option->getDisplayText()] = $option->getValue();
                    }

                    $params = [
                        'label' => $text,
                        'required' => false,
                        'by_reference' => false,
                        'mapped' => false,
                        'data' => $value,
                        'choices' => $choices,
                    ];

                    if (\ExtraField::FIELD_TYPE_CHECKBOX === $extraField->getFieldType()) {
                        $params['expanded'] = true;
                        $params['multiple'] = true;
                    }
                    if (\ExtraField::FIELD_TYPE_SELECT === $extraField->getFieldType()) {
                        $params['expanded'] = false;
                        $params['multiple'] = false;
                    }
                    if (\ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $extraField->getFieldType()) {
                        $params['expanded'] = false;
                        $params['multiple'] = true;
                    }
                    $builder->add($variable, ChoiceType::class, $params);

                    break;
            }
        }

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($user, $extraFields): void {
                $data = $event->getData();

                /** @var ExtraField $extraField */
                foreach ($extraFields as $extraField) {
                    $newValue = $data[$extraField->getVariable()] ?? '';
                    if (empty($newValue)) {
                        continue;
                    }
                    $this->extraFieldValuesRepository->updateItemData($extraField, $user, $newValue);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => User::class,
            ]
        );
    }

    public function getName(): string
    {
        return 'chamilo_user_profile';
    }
}
