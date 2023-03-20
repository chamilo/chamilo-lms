<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class LearningPathSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'fixed_encoding' => 'false',
                    'show_invisible_exercise_in_lp_toc' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'fixed_encoding' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('fixed_encoding', YesNoType::class)
            ->add('show_invisible_exercise_in_lp_toc', YesNoType::class)
        ;
    }
}
