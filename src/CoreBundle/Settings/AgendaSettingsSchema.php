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

class AgendaSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'allow_personal_agenda' => 'true',
                    'default_calendar_view' => 'month',
                    'personal_calendar_show_sessions_occupation' => 'false',
                    'personal_agenda_show_all_session_events' => 'false',
                    'allow_agenda_edit_for_hrm' => 'false',
                    'agenda_legend' => '',
                    'agenda_colors' => '',
                    'agenda_on_hover_info' => '',
                    'agenda_reminders_sender_id' => '0',
                    'fullcalendar_settings' => '',
                    'allow_careers_in_global_agenda' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'allow_personal_agenda' => ['string'],
            'default_calendar_view' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_personal_agenda', YesNoType::class)
            ->add(
                'default_calendar_view',
                ChoiceType::class,
                [
                    'choices' => [
                        'Month' => 'month',
                        'Week' => 'week',
                    ],
                ]
            )
            ->add('personal_calendar_show_sessions_occupation', YesNoType::class)
            ->add('personal_agenda_show_all_session_events', YesNoType::class)
            ->add('allow_agenda_edit_for_hrm', YesNoType::class)
            ->add(
                'agenda_legend',
                TextareaType::class,
                [
                    'help_html' => true,
                ]
            )
            ->add(
                'agenda_colors',
                TextareaType::class,
                [
                    'help_html' => true,
                ]
            )
            ->add(
                'agenda_on_hover_info',
                TextareaType::class,
                [
                    'help_html' => true,
                ]
            )
            ->add('agenda_reminders_sender_id', TextType::class)
            ->add(
                'fullcalendar_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                ]
            )
            ->add('allow_careers_in_global_agenda', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
