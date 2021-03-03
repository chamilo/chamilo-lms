<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoNumericType;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class DocumentCourseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults([
                'enabled' => 1,
                'documents_default_visibility' => '',
            ])
        ;
        $allowedTypes = [
            'documents_default_visibility' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enabled', YesNoNumericType::class)
            ->add('documents_default_visibility')
        ;
    }
}
