<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class SkillSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'allow_skills_tool' => 'true',
                    'allow_hr_skills_management' => 'true',
                    'show_full_skill_name_on_skill_wheel' => 'false',
                    'badge_assignation_notification' => 'false',
                ]
            )
        ;
        $allowedTypes = [
            'allow_skills_tool' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_skills_tool', YesNoType::class)
            ->add('allow_hr_skills_management', YesNoType::class)
            ->add('show_full_skill_name_on_skill_wheel', YesNoType::class)
            ->add('badge_assignation_notification', YesNoType::class)
        ;
    }
}
