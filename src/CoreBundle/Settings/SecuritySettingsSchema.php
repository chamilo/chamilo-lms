<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SecuritySettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults(
            [
                'filter_terms' => '',
                'allow_browser_sniffer' => 'false',
                'admins_can_set_users_pass' => '',
                'allow_strength_pass_checker' => 'true',
                'allow_captcha' => 'false',
                'user_reset_password' => 'false',
                'user_reset_password_token_limit' => '3600',
                'captcha_number_mistakes_to_block_account' => '',
                'captcha_time_to_block' => '',
                'prevent_multiple_simultaneous_login' => 'false',
                'check_password' => 'false',
                'security_strict_transport' => 'strict-transport-security: max-age=31536000; includeSubDomains',
                'security_content_policy' => "default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; child-src 'self' *.youtube.com yt.be *.vimeo.com *.slideshare.com;",
                'security_content_policy_report_only' => "default-src 'self'; script-src *://*.google.com:*",
                'security_public_key_pins' => '',
                'security_public_key_pins_report_only' => '',
                'security_x_frame_options' => 'SAMEORIGIN',
                'security_xss_protection' => '1; mode=block',
                'security_x_content_type_options' => 'nosniff',
                'security_referrer_policy' => 'origin-when-cross-origin',
                'security_block_inactive_users_immediately' => 'false',
                'password_requirements' => '',
                'allow_online_users_by_status' => '',
            ]
        );
        $allowedTypes = [
            'allow_browser_sniffer' => ['string'],
            'allow_strength_pass_checker' => ['string'],
            'captcha_number_mistakes_to_block_account' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('filter_terms', TextareaType::class)
            ->add('allow_browser_sniffer', YesNoType::class)
            ->add('admins_can_set_users_pass', YesNoType::class)
            ->add('allow_strength_pass_checker', YesNoType::class)
            ->add('allow_captcha', YesNoType::class)
            ->add('user_reset_password', YesNoType::class)
            ->add('user_reset_password_token_limit')
            ->add('captcha_number_mistakes_to_block_account')
            ->add('captcha_time_to_block')
            ->add('prevent_multiple_simultaneous_login', YesNoType::class)
            ->add('check_password', YesNoType::class)
            ->add(
                'security_strict_transport',
                TextType::class,
                [
                    'label' => 'SecurityStrictTransportTitle',
                    'help' => 'SecurityStrictTransportComment',
                ]
            )
            ->add(
                'security_content_policy',
                TextType::class,
                [
                    'label' => 'SecurityContentPolicyTitle',
                    'help' => 'SecurityContentPolicyComment',
                ]
            )
            ->add(
                'security_content_policy_report_only',
                TextType::class,
                [
                    'label' => 'SecurityContentPolicyReportOnlyTitle',
                    'help' => 'SecurityContentPolicyReportOnlyComment',
                ]
            )
            ->add(
                'security_public_key_pins',
                TextType::class,
                [
                    'label' => 'SecurityPublicKeyPinsTitle',
                    'help' => 'SecurityPublicKeyPinsComment',
                ]
            )
            ->add(
                'security_public_key_pins_report_only',
                TextType::class,
                [
                    'label' => 'SecurityPublicKeyPinsReportOnlyTitle',
                    'help' => 'SecurityPublicKeyPinsReportOnlyComment',
                ]
            )
            ->add(
                'security_x_frame_options',
                TextType::class,
                [
                    'label' => 'SecurityXFrameOptionsTitle',
                    'help' => 'SecurityXFrameOptionsComment',
                ]
            )
            ->add(
                'security_xss_protection',
                TextType::class,
                [
                    'label' => 'SecurityXssProtectionTitle',
                    'help' => 'SecurityXssProtectionComment',
                ]
            )
            ->add(
                'security_x_content_type_options',
                TextType::class,
                [
                    'label' => 'SecurityXContentTypeOptionsTitle',
                    'help' => 'SecurityXContentTypeOptionsComment',
                ]
            )
            ->add(
                'security_referrer_policy',
                TextType::class,
                [
                    'label' => 'SecurityReferrerPolicyTitle',
                    'help' => 'SecurityReferrerPolicyComment',
                ]
            )
            ->add('security_block_inactive_users_immediately', YesNoType::class)
            ->add(
                'password_requirements',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Customize password generation and verification').
                        $this->settingArrayHelpValue('password_requirements'),
                ]
            )
            ->add(
                'allow_online_users_by_status',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow online users by user profile 1 = COURSEMANAGER (teacher) 5 = STUDENT 11 = PLATFORM_ADMIN Example: The online users will be available only for teachers and students').
                        $this->settingArrayHelpValue('allow_online_users_by_status'),
                ]
            )
        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'password_requirements' => "<pre>
                [
                    'min' => [
                        'lowercase' => 2,
                        'uppercase' => 2,
                        'numeric' => 2,
                        'length' => 8
                    ]
                ]
               </pre>",
            'allow_online_users_by_status' => "<pre>
                ['status' =>  [1, 5]]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
