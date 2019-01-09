<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GroupSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class GroupSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'allow_group_categories' => 'false',
                    'hide_course_group_if_no_tools_available' => 'false',
                ]
            )
        ;
        $allowedTypes = [
            'allow_group_categories' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_group_categories', YesNoType::class)
            ->add('hide_course_group_if_no_tools_available', YesNoType::class);
    }
}
