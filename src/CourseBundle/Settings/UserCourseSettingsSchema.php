<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Settings;

use Chamilo\CoreBundle\Settings\AbstractSettingsSchema;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class UserCourseSettingsSchema.
 *
 * @package Chamilo\CourseBundle\Settings
 */
class UserCourseSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults([
                'enabled' => '',
                'allow_user_view_user_list' => '',
            ])
        ;
        $allowedTypes = [
            'enabled' => ['string'],
            'allow_user_view_user_list' => ['string'],
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
            ->add('allow_user_view_user_list', 'yes_no')
        ;
    }
}
