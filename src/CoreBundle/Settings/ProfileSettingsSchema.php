<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'changeable_options' => [],
                    'extended_profile' => 'false',
                    'account_valid_duration' => '3660',
                    'split_users_upload_directory' => 'true',
                    'user_selected_theme' => 'false',
                    'use_users_timezone' => 'true',
                    'allow_users_to_change_email_with_no_password' => 'false',
                    'login_is_email' => 'false',
                    'profiling_filter_adding_users' => '',
                    'enable_profile_user_address_geolocalization' => '',
                    'allow_show_skype_account' => '',
                    'allow_show_linkedin_url' => '',
                    'is_editable' => 'true',
                    'hide_username_with_complete_name' => 'false',
                    'hide_username_in_course_chat' => 'false',
                    'show_official_code_whoisonline' => 'false',
                    'allow_career_diagram' => 'false',
                    'disable_change_user_visibility_for_public_courses' => 'true',
                    'my_space_users_items_per_page' => '10',
                    'add_user_course_information_in_mailto' => 'false',
                    'pass_reminder_custom_link' => '',
                    'registration_add_helptext_for_2_names' => 'false',
                    'disable_gdpr' => 'true',
                    'data_protection_officer_name' => '',
                    'data_protection_officer_role' => '',
                    'data_protection_officer_email' => '',
                    'hide_user_field_from_list' => '',
                    'allow_fields_inscription' => '',
                    'send_notification_when_user_added' => '',
                    'show_conditions_to_user' => '',
                    'allow_teachers_to_classes' => 'false',
                    'profile_fields_visibility' => '',
                    'user_import_settings' => '',
                    'user_search_on_extra_fields' => '',
                    'allow_career_users' => 'false',
                    'required_extra_fields_in_inscription' => '',
                    'community_managers_user_list' => '',
                    'allow_social_map_fields' => '',
                    'career_diagram_legend' => 'false',
                    'career_diagram_disclaimer' => 'false',
                    'linkedin_organization_id' => 'false',
                ]
            )
            ->setTransformer(
                'changeable_options',
                new ArrayToIdentifierTransformer()
            )
        ;
        $allowedTypes = [
            'changeable_options' => ['array'],
            'account_valid_duration' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add(
                'changeable_options',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => [
                        'Name' => 'name',
                        'Official code' => 'officialcode',
                        'E-mail' => 'email',
                        'Picture' => 'picture',
                        'Login' => 'login',
                        'Password' => 'password',
                        'Language' => 'language',
                        'Phone' => 'phone',
                        //'openid' => 'openid',
                        'Theme' => 'theme',
                        //'apikeys' => 'apikeys',
                    ],
                ]
            )
            ->add(
                'extended_profile',
                YesNoType::class,
                [
                    'label' => 'ExtendedProfileTitle',
                    'help' => 'ExtendedProfileComment',
                ]
            )
            ->add('account_valid_duration')
            ->add('split_users_upload_directory', YesNoType::class)
            ->add('user_selected_theme', YesNoType::class)
            ->add('use_users_timezone', YesNoType::class)
            ->add('allow_users_to_change_email_with_no_password', YesNoType::class)
            ->add('login_is_email', YesNoType::class, [
                'label' => 'LoginIsEmailTitle',
            ])
            ->add('profiling_filter_adding_users', YesNoType::class)
            ->add('enable_profile_user_address_geolocalization', YesNoType::class)
            ->add('allow_show_skype_account', YesNoType::class)
            ->add('allow_show_linkedin_url', YesNoType::class)
            ->add('is_editable', YesNoType::class)
            ->add('hide_username_with_complete_name', YesNoType::class)
            ->add('hide_username_in_course_chat', YesNoType::class)
            ->add('show_official_code_whoisonline', YesNoType::class)
            ->add('allow_career_diagram', YesNoType::class)
            ->add('disable_change_user_visibility_for_public_courses', YesNoType::class)
            ->add('my_space_users_items_per_page', TextType::class)
            ->add('add_user_course_information_in_mailto', YesNoType::class)
            ->add(
                'pass_reminder_custom_link',
                TextType::class,
                [
                    'label' => 'PassReminderCustomLinkTitle',
                    'help' => 'PassReminderCustomLinkComment',
                ]
            )
            ->add('registration_add_helptext_for_2_names', YesNoType::class)
            ->add('disable_gdpr', YesNoType::class)
            ->add('data_protection_officer_name', TextType::class)
            ->add('data_protection_officer_role', TextType::class)
            ->add('data_protection_officer_email', TextType::class)
            ->add(
                'hide_user_field_from_list',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Hide fields from this list array').
                        $this->settingArrayHelpValue('hide_user_field_from_list'),
                ]
            )
            ->add(
                'allow_fields_inscription',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Only shows the fields in this list').
                        $this->settingArrayHelpValue('allow_fields_inscription'),
                ]
            )
            ->add(
                'send_notification_when_user_added',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Send email notification to admin when a user is created').
                        $this->settingArrayHelpValue('send_notification_when_user_added'),
                ]
            )
            ->add(
                'show_conditions_to_user',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Show multiple conditions to user during sign up process. Example with a GDPR condition').
                        $this->settingArrayHelpValue('show_conditions_to_user'),
                ]
            )
            ->add('allow_teachers_to_classes', YesNoType::class)
            ->add(
                'profile_fields_visibility',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Validate user login via a webservice, Chamilo will send a "login" and "password" parameters to the "myWebServiceFunctionToLogin" function, the result should be "1" if the user have access').
                        $this->settingArrayHelpValue('profile_fields_visibility'),
                ]
            )
            ->add(
                'user_import_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('This option sets default parameters in the main/admin/user_import.php').
                        $this->settingArrayHelpValue('user_import_settings'),
                ]
            )
            ->add(
                'user_search_on_extra_fields',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Search user by extra field in the user list').
                        $this->settingArrayHelpValue('user_search_on_extra_fields'),
                ]
            )
            ->add('allow_career_users', YesNoType::class)
            ->add(
                'required_extra_fields_in_inscription',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Set extra fields as required in the inscription.php page').
                        $this->settingArrayHelpValue('required_extra_fields_in_inscription'),
                ]
            )
            ->add(
                'community_managers_user_list',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Community manager users').
                        $this->settingArrayHelpValue('community_managers_user_list'),
                ]
            )
            ->add(
                'allow_social_map_fields',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow to show users in a map, users need to have a coordinates extra field BT#15176').
                        $this->settingArrayHelpValue('allow_social_map_fields'),
                ]
            )
            ->add('career_diagram_legend', YesNoType::class)
            ->add('career_diagram_disclaimer', YesNoType::class)
            ->add('linkedin_organization_id', YesNoType::class)

        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'hide_user_field_from_list' => "<pre>
                ['fields' => ['username']]
                </pre>",
            'allow_fields_inscription' => "<pre>
                [
                    'fields' => [
                        'official_code',
                        'phone',
                        'status',
                        'language'
                    ],
                    'extra_fields' => [
                        'birthday'
                    ]
                ]
                </pre>",
            'send_notification_when_user_added' => "<pre>
                ['admins' => [1]]
                </pre>",
            'show_conditions_to_user' => "<pre>
                [
                    'conditions' => [
                        [
                            'variable' => 'gdpr', // internal extra field name
                            'display_text' => 'GDPRTitle', // checkbox title will be translated with get_lang('GDPRTitle')
                            'text_area' => 'GDPRTextArea', // this will be translated using get_lang('GDPRTextArea')
                        ],
                        [
                            'variable' => 'my_terms',
                            'display_text' => 'My test conditions',
                            'text_area' => 'This is a long text area, with lot of terms and conditions ... ',
                        ],
                    ],
                ]
                </pre>",
            'profile_fields_visibility' => "<pre>
                [
                    'options' => [
                        'vcard' => false,
                        'firstname' => false,
                        'lastname' => false,
                        'photo' => true,
                        'email' => true,
                        'chat' => true,
                        'terms_ville' => false, // extra field value
                    ]
                ]
                </pre>",
            'user_import_settings' => "<pre>
                [
                    'options' =>  [
                        'send_mail_default_option' => '1',
                    ]
                ]
                </pre>",
            'user_search_on_extra_fields' => "<pre>
                ['extra_fields' => ['variable1', 'variable2']]
                </pre>",
            'required_extra_fields_in_inscription' => "<pre>
                [
                    'options' => [
                        'terms_ville',
                        'terms_paysresidence',
                    ],
                ]
                </pre>",
            'community_managers_user_list' => "<pre>
                ['users' => [1]]
                </pre>",
            'allow_social_map_fields' => "<pre>
                ['fields' => ['terms_villedustage', 'terms_ville']]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
