<?php

namespace ChamiloLMS\InstallerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use ChamiloLMS\InstallerBundle\Validator\Constraints as Assert;

class ConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'database',
                'chamilo_installer_configuration_database',
                array(
                    'label'       => 'form.configuration.database.header',
                    'constraints' => array(
                        new Assert\DatabaseConnection(),
                    ),
                )
            )
            ->add(
                'mailer',
                'chamilo_installer_configuration_mailer',
                array(
                    'label' => 'form.configuration.mailer.header'
                )
            )
            /*->add(
                'websocket',
                'chamilo_installer_configuration_websocket',
                array(
                    'label' => 'form.configuration.websocket.header'
                )
            )*/
            ->add(
                'system',
                'chamilo_installer_configuration_system',
                array(
                    'label' => 'form.configuration.system.header'
                )
            );
    }

    public function getName()
    {
        return 'chamilo_installer_configuration';
    }
}
