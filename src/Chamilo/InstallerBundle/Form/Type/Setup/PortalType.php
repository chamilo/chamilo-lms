<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\InstallerBundle\Form\Type\Setup;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PortalType
 * @package Chamilo\InstallerBundle\Form\Type\Setup
 */
class PortalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'institution',
                'text',
                array(
                    'label'    => 'form.setup.portal.institution',
                    'mapped'   => false,
                    'constraints'   => array(
                        new Assert\NotBlank(),
                        new Assert\Length(array('max' => 15))
                    ),
                )
            )
            ->add(
                'site_name',
                'text',
                array(
                    'label'    => 'form.setup.portal.site_name',
                    'mapped'   => false,
                    'required' => false,
                )
            )
            ->add(
                'institution_url',
                'url',
                array(
                    'label'    => 'form.setup.portal.institution_url',
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
                    'required' => true,
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
                    'required' => true,
                    'choices'       => array(
                        '1'       => 'Yes',
                        '0'       => 'No',
                    ),
                )
            )
            ->add(
                'timezone',
                'timezone',
                array(
                    'label'    => 'form.setup.portal.timezone',
                    'mapped'   => false,
                    'required' => false,
                    'preferred_choices' => array('Europe/Paris'),
                )
            );

    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'allow_self_registration_as_trainer' => '0',
            'allow_self_registration' => '1'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'chamilo_installer_setup_portal';
    }
}
