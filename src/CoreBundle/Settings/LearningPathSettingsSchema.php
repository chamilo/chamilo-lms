<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class LearningPathSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'fixed_encoding' => 'false',
                    'show_invisible_exercise_in_lp_toc' => 'false',
                    'add_all_files_in_lp_export' => 'false',
                    'show_prerequisite_as_blocked' => 'false',
                    'hide_lp_time' => 'false',
                    'lp_category_accordion' => 'false',
                    'lp_view_accordion' => 'false',
                    'disable_js_in_lp_view' => 'true',
                    'allow_teachers_to_access_blocked_lp_by_prerequisite' => 'false',
                    'allow_lp_chamilo_export' => 'false',
                    'hide_accessibility_label_on_lp_item' => 'true',
                    'lp_minimum_time' => 'false',
                    'validate_lp_prerequisite_from_other_session' => 'false',
                    'show_hidden_exercise_added_to_lp' => 'true',
                    'lp_menu_location' => 'left',
                    'lp_score_as_progress_enable' => 'false',
                    'lp_prevents_beforeunload' => 'false',
                    'disable_my_lps_page' => 'false',
                    'scorm_api_username_as_student_id' => 'false',
                    'scorm_api_extrafield_to_use_as_student_id' => '',
                    'allow_import_scorm_package_in_course_builder' => 'false',
                    'allow_htaccess_import_from_scorm' => 'false',
                    'allow_session_lp_category' => 'false',
                    'ticket_lp_quiz_info_add' => 'false',
                    'lp_subscription_settings' => '',
                    'lp_view_settings' => '',
                    'download_files_after_all_lp_finished' => '',
                    'allow_lp_subscription_to_usergroups' => 'false',
                    'lp_fixed_encoding' => 'false',
                    'lp_prerequisite_use_last_attempt_only' => 'false',
                    'show_invisible_exercise_in_lp_list' => 'false',
                    'force_edit_exercise_in_lp' => 'false',
                    'student_follow_page_add_LP_subscription_info' => 'false',
                    'lp_show_max_progress_instead_of_average' => 'false',
                    'lp_show_max_progress_or_average_enable_course_level_redefinition' => 'false',
                    'lp_allow_export_to_students' => 'false',
                    'show_invisible_lp_in_course_home' => 'false',
                    'lp_start_and_end_date_visible_in_student_view' => 'false',
                    'scorm_lms_update_sco_status_all_time' => 'false',
                    'scorm_upload_from_cache' => 'false',
                    'lp_prerequisit_on_quiz_unblock_if_max_attempt_reached' => 'false',
                    'student_follow_page_hide_lp_tests_average' => 'false',
                    'student_follow_page_add_LP_acquisition_info' => 'false',
                    'student_follow_page_add_LP_invisible_checkbox' => 'false',
                    'student_follow_page_include_not_subscribed_lp_students' => 'false',
                    'my_progress_course_tools_order' => '',
                    'lp_enable_flow' => 'false',
                    'lp_item_prerequisite_dates' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'fixed_encoding' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('fixed_encoding', YesNoType::class)
            ->add('show_invisible_exercise_in_lp_toc', YesNoType::class)
            ->add('add_all_files_in_lp_export', YesNoType::class)
            ->add('show_prerequisite_as_blocked', YesNoType::class)
            ->add('hide_lp_time', YesNoType::class)
            ->add('lp_category_accordion', YesNoType::class)
            ->add('lp_view_accordion', YesNoType::class)
            ->add('disable_js_in_lp_view', YesNoType::class)
            ->add('allow_teachers_to_access_blocked_lp_by_prerequisite', YesNoType::class)
            ->add('allow_lp_chamilo_export', YesNoType::class)
            ->add('hide_accessibility_label_on_lp_item', YesNoType::class)
            ->add('lp_minimum_time', YesNoType::class)
            ->add('validate_lp_prerequisite_from_other_session', YesNoType::class)
            ->add('show_hidden_exercise_added_to_lp', YesNoType::class)
            ->add(
                'lp_menu_location',
                ChoiceType::class,
                [
                    'choices' => [
                        'Left' => 'left',
                        'Right' => 'right',
                    ],
                ]
            )
            ->add('lp_score_as_progress_enable', YesNoType::class)
            ->add('lp_prevents_beforeunload', YesNoType::class)
            ->add('disable_my_lps_page', YesNoType::class)
            ->add('scorm_api_username_as_student_id', YesNoType::class)
            ->add(
                'scorm_api_extrafield_to_use_as_student_id',
                TextType::class,
                [
                    'label' => 'ScormApiExtrafieldToUseAsStudentIdTitle',
                    'help' => 'ScormApiExtrafieldToUseAsStudentIdComment',
                ]
            )
            ->add('allow_import_scorm_package_in_course_builder', YesNoType::class)
            ->add('allow_htaccess_import_from_scorm', YesNoType::class)
            ->add('allow_session_lp_category', YesNoType::class)
            ->add('ticket_lp_quiz_info_add', YesNoType::class)
            ->add(
                'lp_subscription_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow or block user subscriptions to a lp/lp category').
                        $this->settingArrayHelpValue('lp_subscription_settings'),
                ]
            )
            ->add(
                'lp_view_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('LP view custom settings').
                        $this->settingArrayHelpValue('lp_view_settings'),
                ]
            )
            ->add(
                'download_files_after_all_lp_finished',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Show download files button after finishing all LP. Example: ABC is the course code, and 1 and 100 are the doc id').
                        $this->settingArrayHelpValue('download_files_after_all_lp_finished'),
                ]
            )
            ->add('allow_lp_subscription_to_usergroups', YesNoType::class)
            ->add('lp_fixed_encoding', YesNoType::class)
            ->add('lp_prerequisite_use_last_attempt_only', YesNoType::class)
            ->add('show_invisible_exercise_in_lp_list', YesNoType::class)
            ->add('force_edit_exercise_in_lp', YesNoType::class)
            ->add('student_follow_page_add_LP_subscription_info', YesNoType::class)
            ->add('lp_show_max_progress_instead_of_average', YesNoType::class)
            ->add('lp_show_max_progress_or_average_enable_course_level_redefinition', YesNoType::class)
            ->add('lp_allow_export_to_students', YesNoType::class)
            ->add('show_invisible_lp_in_course_home', YesNoType::class)
            ->add('lp_start_and_end_date_visible_in_student_view', YesNoType::class)
            ->add('scorm_lms_update_sco_status_all_time', YesNoType::class)
            ->add('scorm_upload_from_cache', YesNoType::class)
            ->add('lp_prerequisit_on_quiz_unblock_if_max_attempt_reached', YesNoType::class)
            ->add('student_follow_page_hide_lp_tests_average', YesNoType::class)
            ->add('student_follow_page_add_LP_acquisition_info', YesNoType::class)
            ->add('student_follow_page_add_LP_invisible_checkbox', YesNoType::class)
            ->add('student_follow_page_include_not_subscribed_lp_students', YesNoType::class)
            ->add(
                'my_progress_course_tools_order',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow change the order to show the tools in "My progress" page').
                        $this->settingArrayHelpValue('my_progress_course_tools_order'),
                ]
            )
            ->add('lp_enable_flow', YesNoType::class)
            ->add('lp_item_prerequisite_dates', YesNoType::class)
        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'lp_subscription_settings' => "<pre>
                [
                    'options' => [
                        'allow_add_users_to_lp' => true,
                        'allow_add_users_to_lp_category' => true,
                    ]
                ]
                </pre>",
            'lp_view_settings' => "<pre>
                [
                    'display' => [
                        'show_reporting_icon' => true,
                        'hide_lp_arrow_navigation' => false,
                        'show_toolbar_by_default' => false,
                        'navigation_in_the_middle' => false,
                    ],
                ]
                </pre>",
            'download_files_after_all_lp_finished' => "<pre>
                ['courses' => ['ABC' => [1, 100]]]
                </pre>",
            'my_progress_course_tools_order' => "<pre>
                [
                    'order' => ['quizzes', 'learning_paths', 'skills'],
                ]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
