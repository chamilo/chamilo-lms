<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AnnouncementCourseSettingsSchema.
 *
 * @package Chamilo\CourseBundle\Settings
 */
class AnnouncementCourseSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults([
                'enabled' => '',
                'allow_user_edit_announcement' => '',
            ])
        ;
        $allowedTypes = [
            'enabled' => ['string'],
            'allow_user_edit_announcement' => ['string'],
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
            ->add('allow_user_edit_announcement', 'yes_no')
        ;
    }
}
