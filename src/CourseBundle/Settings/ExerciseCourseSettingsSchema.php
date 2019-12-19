<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ExerciseCourseSettingsSchema.
 */
class ExerciseCourseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults([
                'enabled' => '',
                'email_alert_manager_on_new_quiz' => '',
            ])
        ;
        $allowedTypes = [
            'enabled' => ['string'],
            'email_alert_manager_on_new_quiz' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('enabled', YesNoType::class)
            ->add('email_alert_manager_on_new_quiz', YesNoType::class)
        ;
    }
}
