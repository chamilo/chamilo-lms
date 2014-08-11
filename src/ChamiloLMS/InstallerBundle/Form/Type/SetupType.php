<?php

namespace ChamiloLMS\InstallerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'admin',
                'chamilo_installer_setup_admin',
                array(
                    'label'       => 'form.setup.admin.header',
                )
            )
            ->add(
                'portal',
                'chamilo_installer_setup_portal',
                array(
                    'label' => 'form.setup.portal.header'
                )
            );
    }

    public function getName()
    {
        return 'chamilo_installer_setup';
    }
}
