<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
                    'skills_hierarchical_view_in_user_tracking' => 'false',
                    'skill_levels_names' => '',
                    'allow_skill_rel_items' => 'false',
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
            ->add('skills_hierarchical_view_in_user_tracking', YesNoType::class)
            ->add(
                'skill_levels_names',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Set skill levels name, then later it will be parsed using get_lang BT#13586').
                        $this->settingArrayHelpValue('skill_levels_names'),
                ]
            )
            ->add('allow_skill_rel_items', YesNoType::class)
        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'skill_levels_names' => "<pre>
                [
                    'levels' => [
                        1 => 'Skills',
                        2 => 'Capability',
                        3 => 'Dimension',
                    ]
                ]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
