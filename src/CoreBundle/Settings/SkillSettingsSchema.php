<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SkillSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class SkillSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'allow_skills_tool' => 'true',
                    'allow_hr_skills_management' => 'true',
                    'show_full_skill_name_on_skill_wheel' => 'false',
                ]
            );
        $allowedTypes = [
            'allow_skills_tool' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_skills_tool', YesNoType::class)
            ->add('allow_hr_skills_management', YesNoType::class)
            ->add('show_full_skill_name_on_skill_wheel', YesNoType::class)
        ;
    }
}
