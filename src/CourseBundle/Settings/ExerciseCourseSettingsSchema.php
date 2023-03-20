<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoNumericType;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class ExerciseCourseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults([
                'enabled' => 1,
                'email_alert_manager_on_new_quiz' => 0,
            ])
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enabled', YesNoNumericType::class)
            ->add('email_alert_manager_on_new_quiz', YesNoNumericType::class)
        ;
    }
}
