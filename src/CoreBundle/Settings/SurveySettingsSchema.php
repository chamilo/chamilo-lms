<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                    'allow_required_survey_questions' => 'false',
                    'hide_survey_reporting_button' => 'false',
                    'allow_survey_availability_datetime' => 'false',
                    'survey_mark_question_as_required' => 'false',
                    'survey_anonymous_show_answered' => 'false',
                    'survey_question_dependency' => 'true',
                    'survey_allow_answered_question_edit' => 'false',
                    'survey_duplicate_order_by_name' => 'true',
                    'survey_backwards_enable' => 'false',
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
            ->add('allow_required_survey_questions', YesNoType::class)
            ->add('hide_survey_reporting_button', YesNoType::class)
            ->add('allow_survey_availability_datetime', YesNoType::class)
            ->add('survey_mark_question_as_required', YesNoType::class)
            ->add('survey_anonymous_show_answered', YesNoType::class)
            ->add('survey_question_dependency', YesNoType::class)
            ->add('survey_allow_answered_question_edit', YesNoType::class)
            ->add('survey_duplicate_order_by_name', YesNoType::class)
            ->add('survey_backwards_enable', YesNoType::class)
        ;
    }
}
