<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ForumSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'default_forum_view' => 'flat',
                    'display_groups_forum_in_general_tool' => 'true',
                    'global_forums_course_id' => '0',
                    'hide_forum_post_revision_language' => 'false',
                    'allow_forum_post_revisions' => 'false',
                    'forum_fold_categories' => 'false',
                    'allow_forum_category_language_filter' => 'false',
                    'subscribe_users_to_forum_notifications_also_in_base_course' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'default_forum_view' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
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
            ->add('display_groups_forum_in_general_tool', YesNoType::class)
            ->add('global_forums_course_id', TextType::class)
            ->add('hide_forum_post_revision_language', YesNoType::class)
            ->add('allow_forum_post_revisions', YesNoType::class)
            ->add('forum_fold_categories', YesNoType::class)
            ->add('allow_forum_category_language_filter', YesNoType::class)
            ->add('subscribe_users_to_forum_notifications_also_in_base_course', YesNoType::class)
        ;
    }
}
