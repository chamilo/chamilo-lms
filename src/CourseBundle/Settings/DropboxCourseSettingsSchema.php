<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DropboxCourseSettingsSchema.
 *
 * @package Chamilo\CourseBundle\Settings
 */
class DropboxCourseSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('enabled', 'yes_no')
            ->add('email_alert_on_new_doc_dropbox', 'yes_no')
        ;
    }
}
