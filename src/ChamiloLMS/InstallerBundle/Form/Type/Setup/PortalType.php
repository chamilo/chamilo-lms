<?php

namespace ChamiloLMS\InstallerBundle\Form\Type\Setup;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PortalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'portal_name',
                'text',
                array(
                    'label'    => 'form.setup.portal.portal_name',
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
                    'label'    => 'form.setup.portal.company_title',
                    'mapped'   => false,
                    'required' => false,
                )
            )
            ->add(
                'company_url',
                'url',
                array(
                    'label'    => 'form.setup.portal.company_url',
                    'mapped'   => false,
                    'required' => false,
                )
            )
            ->add(
                'allow_self_registration',
                'choice',
                array(
                    'label'    => 'form.setup.portal.allow_self_registration',
                    'mapped'   => false,
                    'required' => false,
                    'preferred_choices' => array(),
                    'choices'       => array(
                        '1'       => 'Yes',
                        '0'       => 'No',
                    ),
                )
            )
            ->add(
                'allow_self_registration_as_trainer',
                'choice',
                array(
                    'label'    => 'form.setup.portal.allow_self_registration_as_trainer',
                    'mapped'   => false,
                    'required' => false,
                    'preferred_choices' => array(),
                    'choices'       => array(
                        '1'       => 'Yes',
                        '0'       => 'No',
                    ),
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'allow_self_registration_as_trainer' => '1',
            'allow_self_registration' => 'No'
            )
        );
    }

    public function getName()
    {
        return 'chamilo_installer_setup_portal';
    }
}
