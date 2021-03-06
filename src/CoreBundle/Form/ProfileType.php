<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\Type\IllustrationType;
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
    /**
     * @todo replace hardcode values of locale.preferred_choices
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'firstname',
                null,
                [
                    'label' => 'Firstname',
                    'required' => true,
                ]
            )
            ->add(
                'lastname',
                null,
                [
                    'label' => 'Lastname',
                    'required' => true,
                ]
            )
            //->add('official_code', TextType::class)
            //->add('groups')
            ->add(
                'locale',
                LocaleType::class,
                [
                    'preferred_choices' => [
                        'en',
                        'fr',
                        'es',
                        'pt',
                        'nl',
                    ],
                ]
            )
            /*->add(
                'dateOfBirth',
                BirthdayType::class,
                [
                    'label' => 'form.label_date_of_birth',
                    'required' => false,
                    'widget' => 'single_text',
                ]
            )*/
            /*->add(
                'website',
                UrlType::class,
                [
                    'label' => 'Website',
                    'required' => false,
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
            ->add(
                'timezone',
                TimezoneType::class,
                [
                    'label' => 'Timezone',
                    'required' => true,
                    //'preferred_choices' => array('Europe/Paris', 'America/Lima'),
                ]
            )
            ->add(
                'phone',
                null,
                [
                    'label' => 'Phone number',
                    'required' => false,
                ]
            )
            ->add(
                'illustration',
                IllustrationType::class,
                [
                    'label' => 'Picture',
                    'required' => false,
                    'mapped' => false,
                ]
            )
            /*->add(
                'picture',
                'sonata_media_type',
                [
                    'provider' => 'sonata.media.provider.image',
                    'context' => 'user',
                    'required' => false,
                    'data_class' => 'Chamilo\MediaBundle\Entity\Media',
                ]
            )*/
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
        return 'chamilo_sonata_user_profile';
    }
}
