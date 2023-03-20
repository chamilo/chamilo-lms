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
        ;
    }
}
