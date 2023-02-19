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
                    'allow_private_skills' => 'false',
                    'allow_teacher_access_student_skills' => 'false',
                    'skills_teachers_can_assign_skills' => 'false',
                    'hide_skill_levels' => 'false',
                    'table_of_hierarchical_skill_presentation' => 'false',
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
            ->add('allow_private_skills', YesNoType::class)
            ->add('allow_teacher_access_student_skills', YesNoType::class)
            ->add('skills_teachers_can_assign_skills', YesNoType::class)
            ->add('hide_skill_levels', YesNoType::class)
            ->add('table_of_hierarchical_skill_presentation', YesNoType::class)
        ;
    }
}
