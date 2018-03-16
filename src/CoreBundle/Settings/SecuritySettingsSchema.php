<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SecuritySettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class SecuritySettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder->setDefaults(
            [
                'filter_terms' => '',
                'allow_browser_sniffer' => 'false',
                'admins_can_set_users_pass' => '', // ?
                'allow_strength_pass_checker' => 'true',
                'allow_captcha' => 'false',
                'user_reset_password' => 'false',
                'user_reset_password_token_limit' => '3600',
                'captcha_number_mistakes_to_block_account' => '',
                'captcha_time_to_block' => '',
                'prevent_multiple_simultaneous_login' => 'false',
            ]
        );
        $allowedTypes = [
            'allow_browser_sniffer' => ['string'],
            'allow_strength_pass_checker' => ['string'],
            'captcha_number_mistakes_to_block_account' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('filter_terms', 'textarea')
            ->add('allow_browser_sniffer', YesNoType::class)
            ->add('admins_can_set_users_pass', YesNoType::class)
            ->add('allow_strength_pass_checker', YesNoType::class)
            ->add('allow_captcha', YesNoType::class)
            ->add('user_reset_password', YesNoType::class)
            ->add('user_reset_password_token_limit', 'text')
            ->add('captcha_number_mistakes_to_block_account', 'text')
            ->add('captcha_time_to_block', 'text')
            ->add('prevent_multiple_simultaneous_login', YesNoType::class)

        ;
    }
}
