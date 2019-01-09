<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ForumSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class ForumSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'default_forum_view' => 'flat',
                    'display_groups_forum_in_general_tool' => 'true',
                ]
            )
        ;

        $allowedTypes = [
            'default_forum_view' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'default_forum_view',
                ChoiceType::class,
                [
                    'choices' => [
                        'Flat' => 'flat',
                        'Threaded' => 'threaded',
                        'Nested' => 'nested',
                    ],
                ]
            )
            ->add('display_groups_forum_in_general_tool', YesNoType::class);
    }
}
