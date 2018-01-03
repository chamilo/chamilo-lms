<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;

/**
 * Class AnnouncementCourseSettingsSchema
 * @package Chamilo\CourseBundle\Settings
 */
class AnnouncementCourseSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'enabled' => '',
                'allow_user_edit_announcement' => '',
            ))
        ;
        $allowedTypes = array(
            'enabled' => array('string'),
            'allow_user_edit_announcement' => array('string'),
        );
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
