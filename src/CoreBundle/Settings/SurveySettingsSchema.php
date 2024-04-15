<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class SurveySettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'survey_email_sender_noreply' => 'coach',
                    'extend_rights_for_coach_on_survey' => 'true',
                    'hide_survey_reporting_button' => 'false',
                    'survey_mark_question_as_required' => 'false',
                    'survey_anonymous_show_answered' => 'false',
                    'survey_allow_answered_question_edit' => 'false',
                    'survey_duplicate_order_by_name' => 'true',
                    'survey_backwards_enable' => 'false',
                    'hide_survey_edition' => '',
                    'survey_additional_teacher_modify_actions' => '',
                    'show_surveys_base_in_sessions' => 'false',
                ]
            )
        ;
        //            ->setAllowedTypes(
        //                array(
        //                    //'survey_email_sender_noreply' => array('string'),
        //                )
        //            );
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add(
                'survey_email_sender_noreply',
                ChoiceType::class,
                [
                    'choices' => [
                        'CourseCoachEmailSender' => 'coach',
                        'NoReplyEmailSender' => 'noreply',
                    ],
                ]
            )
            ->add('extend_rights_for_coach_on_survey', YesNoType::class)
            ->add('hide_survey_reporting_button', YesNoType::class)
            ->add('survey_mark_question_as_required', YesNoType::class)
            ->add('survey_anonymous_show_answered', YesNoType::class)
            ->add('survey_allow_answered_question_edit', YesNoType::class)
            ->add('survey_duplicate_order_by_name', YesNoType::class)
            ->add('survey_backwards_enable', YesNoType::class)
            ->add(
                'hide_survey_edition',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Hide survey edition tools for all or some surveys. Set an asterisk to hide for all, otherwise set an array with the survey codes in which the options will be blocked').
                        $this->settingArrayHelpValue('hide_survey_edition'),
                ]
            )
            ->add(
                'survey_additional_teacher_modify_actions',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow add additional actions (as links) in survey list for teachers').
                        $this->settingArrayHelpValue('survey_additional_teacher_modify_actions'),
                ]
            )
            ->add('show_surveys_base_in_sessions', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'hide_survey_edition' => "<pre>
                ['codes' => []]
                </pre>",
            'survey_additional_teacher_modify_actions' => "<pre>
                    ['myplugin' => ['MyPlugin', 'urlGeneratorCallback']]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
