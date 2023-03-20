<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoNumericType;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class CourseProgressCourseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults([
                'enabled' => 1,
            ])
        ;
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enabled', YesNoNumericType::class)
        ;
    }
}
