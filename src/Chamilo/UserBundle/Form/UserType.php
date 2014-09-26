<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Chamilo\CoreBundle\Entity\Session;

class UserType extends AbstractType
{
    /**
     * @inheritdoc
     **/
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', 'text')
            ->add('lastname', 'text')
            ->add('official_code', 'text')
            ->add('email', 'email')
            ->add('username', 'text')
            ->add('phone', 'text')
            ->add('timezone', 'timezone')
            ->add('locale', 'locale', array('preferred_choices' => array('en', 'fr', 'es')))
            ->add('picture_uri', 'sonata_media_type', array(
                'provider' => 'sonata.media.provider.image',
                'context'  => 'user_image',
                    'required' => false
            ))
            ->add('save', 'submit', array('label' => 'Update'))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\UserBundle\Entity\User'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }
}

