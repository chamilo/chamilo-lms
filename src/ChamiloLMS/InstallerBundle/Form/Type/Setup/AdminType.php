<?php

namespace ChamiloLMS\InstallerBundle\Form\Type\Setup;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AdminType extends AbstractType
{
    protected $dataClass;

    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                'text',
                array(
                    'label' => 'form.setup.admin.username',
                )
            )
            ->add(
                'plainPassword',
                'repeated',
                array(
                    'type'           => 'password',
                    'invalid_message' => 'The password fields must match.',
                    'first_options'  => array('label' => 'form.setup.admin.password'),
                    'second_options' => array('label' => 'form.setup.admin.password_re'),
                )
            )
            ->add(
                'email',
                'email',
                array(
                    'label' => 'form.setup.admin.email',
                )
            )
            ->add(
                'firstName',
                'text',
                array(
                    'label' => 'form.setup.admin.firstname',
                )
            )
            ->add(
                'lastName',
                'text',
                array(
                    'label' => 'form.setup.admin.lastname',
                )
            )
            ->add(
                'phone',
                'text',
                array(
                    'label' => 'form.setup.admin.phone',
                )
            );
        /*
            ->add(
                'loadFixtures',
                'checkbox',
                array(
                    'label'    => 'form.setup.load_fixtures',
                    'required' => false,
                    'mapped'   => false,
                )
            );*/
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class'        => $this->dataClass,
                'validation_groups' => array('Registration', 'Default'),
            )
        );
    }


    public function getName()
    {
        return 'chamilo_installer_setup_admin';
    }
}
