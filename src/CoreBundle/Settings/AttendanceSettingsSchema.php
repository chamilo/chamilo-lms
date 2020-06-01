<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AttendanceSettingsSchema.
 */
class AttendanceSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder)
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

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'allow_delete_attendance',
                YesNoType::class,
                ['label' => 'AttendanceDeletionEnableTitle', 'help' => 'AttendanceDeletionEnableComment']
            )
        ;
    }
}
