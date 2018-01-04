<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form\Type;

use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RegistrationFormType
 * Form located in web/app_dev.php/register/
 * @package Chamilo\UserBundle\Form\Type
 */
class RegistrationFormType extends AbstractType
{
    /**
     * @inheritdoc
     **/
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                null,
                array(
                    'label' => 'form.username',
                    'translation_domain' => 'FOSUserBundle',
                )
            )
            ->add('firstname', 'text')
            ->add('lastname', 'text')
            ->add(
                'email',
                'email',
                array(
                    'label' => 'form.email',
                    'translation_domain' => 'FOSUserBundle',
                )
            )
            ->add('captcha', 'Gregwar\CaptchaBundle\Type\CaptchaType');
        ;

        //$builder
        /*->add('official_code', 'text')
        ->add('email', 'email')
        ->add('username', 'text')
        ->add('phone', 'text')
        ->add('password', 'password')
        ->add('groups')
        ->add('timezone', 'timezone')
        ->add(
            'locale',
            'locale',
            array('preferred_choices' => array('en', 'fr', 'es'))
        )
        ->add(
            'picture_uri',
            'sonata_media_type',
            array(
                'provider' => 'sonata.media.provider.image',
                'context' => 'user_image',
                'required' => false,
            )
        )
        ->add(
            'extraFields',
            'collection',
            array(
                'required' => false,
                'type' => 'chamilo_user.form.type.attribute_value_type',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            )
        )
        ->add('save', 'submit', array('label' => 'Update'))*/
        // ;

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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => 'Chamilo\UserBundle\Entity\User',
            )
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'fos_user_registration';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chamilo_sonata_user_registration';
    }
}

