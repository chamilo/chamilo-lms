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
                    'changeable_options' => ['name', 'officialcode', 'email', 'picture', 'login', 'password', 'language', 'phone', 'theme'],
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
                    'send_notification_when_user_added' => '',
                    'show_conditions_to_user' => '',
                    'allow_teachers_to_classes' => 'false',
                    'profile_fields_visibility' => '',
                    'user_import_settings' => '',
                    'user_search_on_extra_fields' => '',
                    'allow_career_users' => 'false',
                    'community_managers_user_list' => '',
                    'allow_social_map_fields' => '',
                    'career_diagram_legend' => 'false',
                    'career_diagram_disclaimer' => 'false',
                    'linkedin_organization_id' => 'false',
                    'visible_options' => ['name', 'officialcode', 'email', 'picture', 'login', 'password', 'language', 'phone', 'theme'],
                ]
            )
            ->setTransformer(
                'changeable_options',
                new ArrayToIdentifierTransformer()
            )
            ->setTransformer(
                'visible_options',
                new ArrayToIdentifierTransformer()
            )
        ;
        $allowedTypes = [
            'changeable_options' => ['array'],
            'visible_options' => ['array'],
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
                        'Theme' => 'theme',
                    ],
                ]
            )
            ->add(
                'visible_options',
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
                        'Theme' => 'theme',
                    ],
                ]
            )
            ->add('extended_profile', YesNoType::class)
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
            ->add('hide_username_with_complete_name', YesNoType::class)
            ->add('hide_username_in_course_chat', YesNoType::class)
            ->add('show_official_code_whoisonline', YesNoType::class)
            ->add('allow_career_diagram', YesNoType::class)
            ->add('disable_change_user_visibility_for_public_courses', YesNoType::class)
            ->add('my_space_users_items_per_page', TextType::class)
            ->add('add_user_course_information_in_mailto', YesNoType::class)
            ->add('pass_reminder_custom_link', TextType::class)
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
                    'help' => $this->settingArrayHelpValue('hide_user_field_from_list'),
                ]
            )
            ->add(
                'send_notification_when_user_added',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('send_notification_when_user_added'),
                ]
            )
            ->add(
                'show_conditions_to_user',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('show_conditions_to_user'),
                ]
            )
            ->add('allow_teachers_to_classes', YesNoType::class)
            ->add(
                'profile_fields_visibility',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('profile_fields_visibility'),
                ]
            )
            ->add(
                'user_import_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('user_import_settings'),
                ]
            )
            ->add(
                'user_search_on_extra_fields',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('user_search_on_extra_fields'),
                ]
            )
            ->add('allow_career_users', YesNoType::class)
            ->add(
                'community_managers_user_list',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('community_managers_user_list'),
                ]
            )
            ->add(
                'allow_social_map_fields',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => $this->settingArrayHelpValue('allow_social_map_fields'),
                ]
            )
            ->add('career_diagram_legend', YesNoType::class)
            ->add('career_diagram_disclaimer', YesNoType::class)
            ->add('linkedin_organization_id', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'hide_user_field_from_list' => "<pre>
                ['fields' => ['username']]
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
                        'firstname' => true,
                        'lastname' => true,
                        'photo' => true,
                        'email' => false,
                        'language' => true,
                        'chat' => true,
                        'terms_ville' => true, // extra field value
                        'terms_datedenaissance' => true,
                        'terms_paysresidence' => false,
                        'filiere_user' => true,
                        'terms_villedustage' => true,
                        'hobbies' => true,
                        'langue_cible' => true,
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
