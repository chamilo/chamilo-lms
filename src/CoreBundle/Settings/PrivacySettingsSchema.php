<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class PrivacySettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'disable_change_user_visibility_for_public_courses' => 'true',
            'disable_gdpr' => 'true',
            'data_protection_officer_name' => '',
            'data_protection_officer_role' => '',
            'data_protection_officer_email' => '',
            'hide_user_field_from_list' => '',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('disable_change_user_visibility_for_public_courses', YesNoType::class)
            ->add('disable_gdpr', YesNoType::class)
            ->add('data_protection_officer_name', TextType::class)
            ->add('data_protection_officer_role', TextType::class)
            ->add('data_protection_officer_email', TextType::class)
            ->add('hide_user_field_from_list', TextareaType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
