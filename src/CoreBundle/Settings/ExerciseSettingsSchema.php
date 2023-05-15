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

class ExerciseSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'exercise_min_score' => '0',
                    'exercise_max_score' => '20',
                    'enable_quiz_scenario' => 'true',
                    'allow_coach_feedback_exercises' => 'true',
                    'show_official_code_exercise_result_list' => 'false',
                    'email_alert_manager_on_new_quiz' => 'true',
                    'exercise_max_ckeditors_in_page' => '0',
                    'configure_exercise_visibility_in_course' => 'false',
                    'exercise_invisible_in_session' => 'false',
                    'allow_edit_exercise_in_lp' => 'false',
                    'exercise_hide_label' => 'false',
                    'block_quiz_mail_notification_general_coach' => 'false',
                    'allow_quiz_question_feedback' => 'false',
                    'allow_quiz_show_previous_button_setting' => 'false',
                    'allow_teacher_comment_audio' => 'true',
                    'quiz_prevent_copy_paste' => 'false',
                    'quiz_show_description_on_results_page' => 'false',
                    'quiz_generate_certificate_ending' => 'false',
                    'quiz_open_question_decimal_score' => 'false',
                    'quiz_check_button_enable' => 'false',
                    'allow_notification_setting_per_exercise' => 'false',
                    'hide_free_question_score' => 'false',
                    'hide_user_info_in_quiz_result' => 'false',
                    'exercise_attempts_report_show_username' => 'false',
                    'allow_exercise_auto_launch' => 'false',
                    'disable_clean_exercise_results_for_teachers' => 'true',
                    'show_exercise_question_certainty_ribbon_result' => 'false',
                    'quiz_results_answers_report' => 'false',
                    'send_score_in_exam_notification_mail_to_manager' => 'false',
                    'show_exercise_expected_choice' => 'false',
                    'exercise_category_round_score_in_export' => 'false',
                    'exercises_disable_new_attempts' => 'false',
                    'show_question_id' => 'false',
                    'show_question_pagination' => '100',
                    'question_pagination_length' => '20',
                    'limit_exercise_teacher_access' => 'false',
                    'block_category_questions' => 'false',
                    'exercise_score_format' => '0',
                    'exercise_additional_teacher_modify_actions' => '',
                    'quiz_confirm_saved_answers' => 'false',
                    'allow_exercise_categories' => 'false',
                    'allow_quiz_results_page_config' => 'false',
                    'quiz_image_zoom' => '',
                    'quiz_answer_extra_recording' => 'false',
                    'allow_mandatory_question_in_category' => 'false',
                    'add_exercise_best_attempt_in_report' => '',
                    'exercise_category_report_user_extra_fields' => '',
                    'score_grade_model' => '',
                    'allow_time_per_question' => 'false',
                    'my_courses_show_pending_exercise_attempts' => 'false',
                    'allow_quick_question_description_popup' => 'false',
                    'exercise_hide_ip' => 'false',
                    'tracking_my_progress_show_deleted_exercises' => 'false',
                    'show_exercise_attempts_in_all_user_sessions' => 'false',
                    'show_exercise_session_attempts_in_base_course' => 'false',
                    'quiz_check_all_answers_before_end_test' => 'false',
                    'quiz_discard_orphan_in_course_export' => 'false',
                    'exercise_result_end_text_html_strict_filtering' => 'false',
                    'question_exercise_html_strict_filtering' => 'false',
                    'quiz_question_delete_automatically_when_deleting_exercise' => 'false',
                    'quiz_question_allow_inter_course_linking' => 'false',
                    'quiz_hide_attempts_table_on_start_page' => 'false',
                    'quiz_hide_question_number' => 'false',
                    'quiz_keep_alive_ping_interval' => '0',
                    'exercise_embeddable_extra_types' => '',
                ]
            )
        ;
        $allowedTypes = [
            'exercise_min_score' => ['string', 'null'],
            'exercise_max_score' => ['string', 'null'],
            'enable_quiz_scenario' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('exercise_min_score')
            ->add('exercise_max_score')
            ->add('enable_quiz_scenario', YesNoType::class)
            ->add('allow_coach_feedback_exercises', YesNoType::class)
            ->add('show_official_code_exercise_result_list', YesNoType::class)
            ->add('email_alert_manager_on_new_quiz', YesNoType::class)
            ->add('exercise_max_ckeditors_in_page')
            ->add('configure_exercise_visibility_in_course', YesNoType::class)
            ->add('exercise_invisible_in_session', YesNoType::class)
            ->add('exercise_hide_label', YesNoType::class)
            ->add('block_quiz_mail_notification_general_coach', YesNoType::class)
            ->add('allow_quiz_question_feedback', YesNoType::class)
            ->add('allow_quiz_show_previous_button_setting', YesNoType::class)
            ->add('allow_teacher_comment_audio', YesNoType::class)
            ->add('quiz_prevent_copy_paste', YesNoType::class)
            ->add('quiz_show_description_on_results_page', YesNoType::class)
            ->add('quiz_generate_certificate_ending', YesNoType::class)
            ->add('quiz_open_question_decimal_score', YesNoType::class)
            ->add('quiz_check_button_enable', YesNoType::class)
            ->add('allow_notification_setting_per_exercise', YesNoType::class)
            ->add('hide_free_question_score', YesNoType::class)
            ->add('hide_user_info_in_quiz_result', YesNoType::class)
            ->add('exercise_attempts_report_show_username', YesNoType::class)
            ->add('allow_exercise_auto_launch', YesNoType::class)
            ->add('disable_clean_exercise_results_for_teachers', YesNoType::class)
            ->add('show_exercise_question_certainty_ribbon_result', YesNoType::class)
            ->add('quiz_results_answers_report', YesNoType::class)
            ->add('send_score_in_exam_notification_mail_to_manager', YesNoType::class)
            ->add('show_exercise_expected_choice', YesNoType::class)
            ->add('exercise_category_round_score_in_export', YesNoType::class)
            ->add('exercises_disable_new_attempts', YesNoType::class)
            ->add('show_question_id', YesNoType::class)
            ->add('show_question_pagination', TextType::class)
            ->add('question_pagination_length', TextType::class)
            ->add('limit_exercise_teacher_access', YesNoType::class)
            ->add('block_category_questions', YesNoType::class)
            ->add(
                'exercise_score_format',
                ChoiceType::class,
                [
                    'choices' => [
                        'none' => '0',
                        'SCORE_AVERAGE' => '1',
                        'SCORE_PERCENT' => '2',
                        'SCORE_DIV_PERCENT' => '3',
                    ],
                ],
            )
            ->add(
                'exercise_additional_teacher_modify_actions',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow add additional actions (as links) in exercises list for teachers').
                        $this->settingArrayHelpValue('exercise_additional_teacher_modify_actions'),
                ]
            )
            ->add('quiz_confirm_saved_answers', YesNoType::class)
            ->add('allow_exercise_categories', YesNoType::class)
            ->add('allow_quiz_results_page_config', YesNoType::class)
            ->add(
                'quiz_image_zoom',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Zoom in description images quiz').
                        $this->settingArrayHelpValue('quiz_image_zoom'),
                ]
            )
            ->add('quiz_answer_extra_recording', YesNoType::class)
            ->add('allow_mandatory_question_in_category', YesNoType::class)
            ->add(
                'add_exercise_best_attempt_in_report',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Shows the best exercise score attempt for a student in the reports').
                        $this->settingArrayHelpValue('add_exercise_best_attempt_in_report'),
                ]
            )
            ->add(
                'exercise_category_report_user_extra_fields',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Add user extra fields in report: main/mySpace/exercise_category_report.php').
                        $this->settingArrayHelpValue('exercise_category_report_user_extra_fields'),
                ]
            )
            ->add(
                'score_grade_model',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow to convert a score into a text/color label using a model if score is inside those values. See BT#12898').
                        $this->settingArrayHelpValue('score_grade_model'),
                ]
            )
            ->add('allow_time_per_question', YesNoType::class)
            ->add('my_courses_show_pending_exercise_attempts', YesNoType::class)
            ->add('allow_quick_question_description_popup', YesNoType::class)
            ->add('exercise_hide_ip', YesNoType::class)
            ->add('tracking_my_progress_show_deleted_exercises', YesNoType::class)
            ->add('show_exercise_attempts_in_all_user_sessions', YesNoType::class)
            ->add('show_exercise_session_attempts_in_base_course', YesNoType::class)
            ->add('quiz_check_all_answers_before_end_test', YesNoType::class)
            ->add('quiz_discard_orphan_in_course_export', YesNoType::class)
            ->add('exercise_result_end_text_html_strict_filtering', YesNoType::class)
            ->add('question_exercise_html_strict_filtering', YesNoType::class)
            ->add('quiz_question_delete_automatically_when_deleting_exercise', YesNoType::class)
            ->add('quiz_question_allow_inter_course_linking', YesNoType::class)
            ->add('quiz_hide_attempts_table_on_start_page', YesNoType::class)
            ->add('quiz_hide_question_number', YesNoType::class)
            ->add('quiz_keep_alive_ping_interval', TextType::class)
            ->add(
                'exercise_embeddable_extra_types',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow extends allowed question types for embeddable exercises. By default, only the following question types are allowed: 1, 2, 17').
                        $this->settingArrayHelpValue('exercise_embeddable_extra_types'),
                ]
            )

        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'exercise_additional_teacher_modify_actions' => "<pre>
                    ['myplugin' => ['MyPlugin', 'urlGeneratorCallback']]
                </pre>",
            'quiz_image_zoom' => "<pre>
                    [
                        'options' => [
                              'zoomWindowWidth' => 400,
                              'zoomWindowHeight' => 400,
                         ]
                     ]
                </pre>",
            'add_exercise_best_attempt_in_report' => "<pre>
                    [
                        'courses' => [
                            'ABC' => [88, 89], // Where ABC is the course code and 88 is the exercise id
                        ]
                    ]
                </pre>",
            'exercise_category_report_user_extra_fields' => "<pre>
                    ['fields' => ['skype', 'rssfeeds']]
                </pre>",
            'score_grade_model' => "<pre>
                    [
                        'models' => [
                            [
                                'id' => 1,
                                'name' => 'ThisIsMyModel', // Value will be translated using get_lang
                                'score_list' => [
                                    [
                                        'name' => 'VeryBad', // Value will be translated using get_lang
                                        'css_class' => 'btn-danger',
                                        'min' => 0,
                                        'max' => 20,
                                        'score_to_qualify' => 0
                                    ],
                                    [
                                        'name' => 'Bad',
                                        'css_class' => 'btn-danger',
                                        'min' => 21,
                                        'max' => 50,
                                        'score_to_qualify' => 25
                                    ],
                                    [
                                        'name' => 'Good',
                                        'css_class' => 'btn-warning',
                                        'min' => 51,
                                        'max' => 70,
                                        'score_to_qualify' => 60
                                    ],
                                    [
                                        'name' => 'VeryGood',
                                        'css_class' => 'btn-success',
                                        'min' => 71,
                                        'max' => 100,
                                        'score_to_qualify' => 100
                                    ]
                                ]
                            ]
                        ]
                    ]
                </pre>",
            'exercise_embeddable_extra_types' => "<pre>
                Add this types to allow them in embeddable exercises:
                 1 = Multiple choice
                 2 = Multiple answers
                 3 = Fill blanks or form
                 4 = Matching
                 5 = Open question
                 9 = Exact Selection
                10 = Unique answer with unknown
                11 = Multiple answer true/false/don't know
                12 = Combination true/false/don't know
                13 = Oral expression
                14 = Global multiple answer
                16 = Calculated question
                17 = Unique answer image
                21 = Reading comprehension
                22 = Multiple answer true/false/degree of certainty
                23 = Upload answer

                [
                    'types' => [],
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
