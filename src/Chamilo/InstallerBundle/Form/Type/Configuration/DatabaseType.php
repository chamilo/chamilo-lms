<?php

namespace Chamilo\InstallerBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DatabaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'chamilo_installer_database_host',
                'text',
                array(
                    'label'       => 'form.configuration.database.host',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'chamilo_installer_database_port',
                'integer',
                array(
                    'label'       => 'form.configuration.database.port',
                    'required'    => false,
                    'constraints' => array(
                        new Assert\Type(array('type' => 'integer')),
                    ),
                )
            )
            ->add(
                'chamilo_installer_database_name',
                'text',
                array(
                    'label'       => 'form.configuration.database.name',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'chamilo_installer_database_user',
                'text',
                array(
                    'label'       => 'form.configuration.database.user',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    ),
                )
            )
            ->add(
                'chamilo_installer_database_password',
                'password',
                array(
                    'label'    => 'form.configuration.database.password',
                    'required' => false,
                )
            )
            ->add(
                'chamilo_installer_database_drop_full',
                'checkbox',
                array(
                    'label'    => 'form.configuration.database.drop_full',
                    'required' => false,
                    'data'     => false,
                )
            );
    }

    public function getName()
    {
        return 'chamilo_installer_configuration_database';
    }
}
