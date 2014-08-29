<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SystemType
 * @package Chamilo\InstallerBundle\Form\Type\Configuration
 */
class SystemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'chamilo_installer_locale',
                'locale',
                array(
                    'label'             => 'form.configuration.system.locale',
                    'preferred_choices' => array('en'),
                    'constraints'       => array(
                        new Assert\NotBlank(),
                        new Assert\Locale(),
                    ),
                   //'client_validation'  => false,
                )
            )
            ->add(
                'chamilo_installer_secret',
                'text',
                array(
                    'label'             => 'form.configuration.system.secret',
                    'data'              => md5(uniqid()),
                    'constraints'       => array(
                        new Assert\NotBlank(),
                    )
                )
            );
    }

    public function getName()
    {
        return 'chamilo_installer_configuration_system';
    }
}
