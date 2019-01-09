<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class LearningPathSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class LearningPathSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'fixed_encoding' => 'false',
                    'show_invisible_exercise_in_lp_toc' => 'false',
                ]
            );

        $allowedTypes = [
            'fixed_encoding' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('fixed_encoding', YesNoType::class)
            ->add('show_invisible_exercise_in_lp_toc', YesNoType::class)
        ;
    }
}
