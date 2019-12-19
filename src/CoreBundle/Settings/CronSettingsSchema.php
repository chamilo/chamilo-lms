<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PlatformSettingsSchema.
 */
class CronSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'cron_remind_course_finished_activate' => 'false',
                    'cron_remind_course_expiration_frequency' => '',
                    'cron_remind_course_expiration_activate' => 'false',
                ]
            );
        $allowedTypes = [
            'cron_remind_course_finished_activate' => ['string'],
            'cron_remind_course_expiration_frequency' => ['string'],
            'cron_remind_course_expiration_activate' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('cron_remind_course_finished_activate', YesNoType::class)
            ->add('cron_remind_course_expiration_frequency')
            ->add('cron_remind_course_expiration_activate', YesNoType::class)
        ;
    }
}
