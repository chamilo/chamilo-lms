<?php

namespace ChamiloLMS\InstallerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SetupType extends AbstractType
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
                'company_name',
                'text',
                array(
                    'label'    => 'form.setup.company_name',
                    'mapped'   => false,
                    'constraints'   => array(
                        new Assert\NotBlank(),
                        new Assert\Length(array('max' => 15))
                    ),
                )
            )
            ->add(
                'company_title',
                'text',
                array(
                    'label'    => 'form.setup.company_title',
                    'mapped'   => false,
                    'required' => false,
                )
            )
            ->add(
                'username',
                'text',
                array(
                    'label' => 'form.setup.username',
                )
            )
            ->add(
                'plainPassword',
                'repeated',
                array(
                    'type'           => 'password',
                    'invalid_message' => 'The password fields must match.',
                    'first_options'  => array('label' => 'form.setup.password'),
                    'second_options' => array('label' => 'form.setup.password_re'),
                )
            )
            ->add(
                'email',
                'email',
                array(
                    'label' => 'form.setup.email',
                )
            )
            ->add(
                'firstName',
                'text',
                array(
                    'label' => 'form.setup.firstname',
                )
            )
            ->add(
                'lastName',
                'text',
                array(
                    'label' => 'form.setup.lastname',
                )
            )
            ->add(
                'loadFixtures',
                'checkbox',
                array(
                    'label'    => 'form.setup.load_fixtures',
                    'required' => false,
                    'mapped'   => false,
                )
            );
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
        return 'chamilo_installer_setup';
    }
}
