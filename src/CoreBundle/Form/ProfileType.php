<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    private LanguageRepository $languageRepository;

    public function __construct(LanguageRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = array_flip($this->languageRepository->getAllAvailableToArray());

        $builder
            ->add('firstname', TextType::class, ['label' => 'Firstname', 'required' => true])
            ->add('lastname', TextType::class, ['label' => 'Lastname', 'required' => true])
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

        // Update Author id
        /*$builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($currentUser) {
                // @var User $user
                $user = $event->getData();
                $extraFields = $user->getExtrafields();
                foreach ($extraFields as $extraField) {
                    $extraField->setAuthor($currentUser);
                }
            }
        );*/
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
