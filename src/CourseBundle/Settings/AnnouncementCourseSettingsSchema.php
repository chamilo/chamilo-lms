<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoNumericType;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Chamilo\CoreBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

class AnnouncementCourseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'enabled' => 1,
                    'allow_user_edit_announcement' => 0,
                ]
            );
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enabled', YesNoNumericType::class)
            ->add('allow_user_edit_announcement', YesNoNumericType::class)
        ;
    }
}
