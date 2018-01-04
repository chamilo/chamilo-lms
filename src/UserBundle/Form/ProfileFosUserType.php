<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle\Form;

use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Class ProfileType
 * @deprecated is not being used
 * @package Chamilo\UserBundle\Form
 */
class ProfileFosUserType extends AbstractType
{
    /**
     * @inheritdoc
     **/
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
            array(
                'data_class' => 'Chamilo\UserBundle\Entity\User',
            )
        );
    }

    public function getName()
    {
        return 'chamilo_fos_user_profile';
    }
}

