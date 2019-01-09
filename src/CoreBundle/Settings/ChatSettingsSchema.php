<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChatSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class ChatSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'show_chat_folder' => 'true',
                    'allow_global_chat' => 'true',
                ]
            );
        $allowedTypes = [
            'show_chat_folder' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_global_chat', YesNoType::class)
            ->add('show_chat_folder', YesNoType::class)
        ;
    }
}
