<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class GroupSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'allow_group_categories' => 'false',
                    'hide_course_group_if_no_tools_available' => 'false',
                    'show_groups_to_users' => 'false',
                ]
            )
        ;
        $allowedTypes = [
            'allow_group_categories' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_group_categories', YesNoType::class)
            ->add('hide_course_group_if_no_tools_available', YesNoType::class)
            ->add('show_groups_to_users', YesNoType::class);
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
