<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ExerciseCourseSettingsSchema.
 *
 * @package Chamilo\CourseBundle\Settings
 */
class ExerciseCourseSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('enabled', 'yes_no')
            ->add('email_alert_manager_on_new_quiz', 'yes_no')
        ;
    }
}
