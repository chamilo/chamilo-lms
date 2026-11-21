<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class CasSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'cas_activate' => '',
                    'cas_server' => '',
                    'cas_server_uri' => '',
                    'cas_port' => '',
                    'cas_protocol' => '',
                    'cas_add_user_activate' => '',
                    'update_user_info_cas_with_ldap' => '',
                ]
            )
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('cas_activate', YesNoType::class)
            ->add('cas_server')
            ->add('cas_server_uri')
            ->add('cas_port')
            ->add('cas_protocol')
            ->add('cas_add_user_activate')
            ->add('update_user_info_cas_with_ldap', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);

        // Existing database rows may still describe removed Chamilo 1.x scripts.
        // Keep stored values intact and show the current supported modes instead.
        foreach ([
            'cas_activate' => 'Enable the CAS sign-in button when the server and portal URL are correctly configured.',
            'cas_add_user_activate' => 'Use false to disable automatic creation, platform to use CAS attributes, or extldap to use the configured LDAP directory.',
            'cas_protocol' => 'Select CAS1, CAS2 (default), or CAS3. TLS is always required. The legacy SAML 1.1 protocol is not supported.',
            'update_user_info_cas_with_ldap' => 'Update the first name, last name and email from the configured LDAP directory over verified LDAPS. Roles and passwords are never synchronized by CAS.',
        ] as $name => $help) {
            $field = $builder->get($name);
            $options = $field->getOptions();
            $options['help'] = $this->translator->trans($help);
            $builder->add($name, \get_class($field->getType()->getInnerType()), $options);
        }
    }
}
