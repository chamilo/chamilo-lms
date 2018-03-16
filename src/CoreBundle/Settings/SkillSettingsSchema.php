<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
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
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                [
                    'allow_skills_tool' => 'true',
                    'allow_hr_skills_management' => 'true',
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
        ;
    }
}
