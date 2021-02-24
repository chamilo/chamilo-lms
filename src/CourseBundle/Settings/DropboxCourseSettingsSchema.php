<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DropboxCourseSettingsSchema.
 */
class DropboxCourseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults([
                'enabled' => '',
                'email_alert_on_new_doc_dropbox' => '',
            ])
        ;

        $allowedTypes = [
            'enabled' => ['string'],
            'email_alert_on_new_doc_dropbox' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enabled', YesNoType::class)
            ->add('email_alert_on_new_doc_dropbox', YesNoType::class)
        ;
    }
}
