<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ProfileType.
 *
 * @deprecated is not being used
 *
 * @package Chamilo\UserBundle\Form
 */
class ProfileFosUserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('email', 'email')
        ;
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
            [
                'data_class' => 'Chamilo\UserBundle\Entity\User',
            ]
        );
    }

    public function getName()
    {
        return 'chamilo_fos_user_profile';
    }
}
