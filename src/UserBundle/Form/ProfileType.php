<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ProfileType
 * Located in web/app_dev.php/profile/edit-profile.
 *
 * @package Chamilo\UserBundle\Form
 */
class ProfileType extends AbstractType
{
    /**
     * @todo replace hardcode values of locale.preferred_choices
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstname',
                null,
                [
                    'label' => 'form.label_firstname',
                    'required' => false,
                ]
            )
            ->add(
                'lastname',
                null,
                [
                    'label' => 'form.label_lastname',
                    'required' => false,
                ]
            )
            ->add('official_code', 'text')
            //->add('groups')
            ->add(
                'locale',
                'locale',
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
            ->add(
                'dateOfBirth',
                'birthday',
                [
                    'label' => 'form.label_date_of_birth',
                    'required' => false,
                    'widget' => 'single_text',
                ]
            )
            ->add(
                'website',
                'url',
                [
                    'label' => 'form.label_website',
                    'required' => false,
                ]
            )
            ->add(
                'biography',
                'textarea',
                [
                    'label' => 'form.label_biography',
                    'required' => false,
                ]
            )
            /*->add('locale', 'locale', array(
                'label'    => 'form.label_locale',
                'required' => false,
            ))*/
            ->add(
                'timezone',
                'timezone',
                [
                    'label' => 'form.label_timezone',
                    'required' => false,
                    //'preferred_choices' => array('Europe/Paris', 'America/Lima'),
                ]
            )
            ->add(
                'phone',
                null,
                [
                    'label' => 'form.label_phone',
                    'required' => false,
                ]
            )
            ->add(
                'picture',
                'sonata_media_type',
                [
                    'provider' => 'sonata.media.provider.image',
                    'context' => 'user',
                    'required' => false,
                    'data_class' => 'Chamilo\MediaBundle\Entity\Media',
                ]
            )
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

    /**
     * {@inheritdoc}
     *
     * @deprecated Remove it when bumping requirements to Symfony 2.7+
     */
    /*public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }*/

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\UserBundle\Entity\User',
            ]
        );
    }

    public function getName()
    {
        return 'chamilo_sonata_user_profile';
    }
}
