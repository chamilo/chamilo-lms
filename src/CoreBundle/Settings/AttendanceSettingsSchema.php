<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class AttendanceSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'allow_delete_attendance' => 'true',
                    'enable_sign_attendance_sheet' => 'false',
                    'attendance_calendar_set_duration' => 'false',
                    'attendance_allow_comments' => 'false',
                ]
            )
//            ->setAllowedTypes(
//                array()
//            )
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add(
                'allow_delete_attendance',
                YesNoType::class,
                [
                    'label' => 'AttendanceDeletionEnableTitle',
                    'help' => 'AttendanceDeletionEnableComment',
                ]
            )
            ->add('enable_sign_attendance_sheet', YesNoType::class)
            ->add('attendance_calendar_set_duration', YesNoType::class)
            ->add('attendance_allow_comments', YesNoType::class)
        ;
    }
}
