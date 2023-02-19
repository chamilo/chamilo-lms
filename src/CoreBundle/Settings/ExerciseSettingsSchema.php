<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
        ;
    }
}
