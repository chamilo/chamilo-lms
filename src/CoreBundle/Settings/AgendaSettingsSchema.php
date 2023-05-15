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
                    //'display_mini_month_calendar' => '', ??
                    'display_upcoming_events' => '',
                    // ??
                    //'number_of_upcoming_events' => '0',
                    'default_calendar_view' => 'month',
                    'personal_calendar_show_sessions_occupation' => 'false',
                    'personal_agenda_show_all_session_events' => 'false',
                    'allow_agenda_edit_for_hrm' => 'false',
                    'agenda_legend' => '',
                    'agenda_colors' => '',
                    'agenda_on_hover_info' => '',
                    'agenda_collective_invitations' => 'false',
                    'agenda_event_subscriptions' => 'false',
                    'agenda_reminders' => 'false',
                    'agenda_reminders_sender_id' => '0',
                    'fullcalendar_settings' => '',
                ]
            )
        ;

        $allowedTypes = [
            'allow_personal_agenda' => ['string'],
            //'display_mini_month_calendar' => array('string'),
            'display_upcoming_events' => ['string'],
            //'number_of_upcoming_events' => array('string'),
            'default_calendar_view' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_personal_agenda', YesNoType::class)
            //->add('display_mini_month_calendar', YesNoType::class)
            ->add('display_upcoming_events', YesNoType::class)
            //->add('number_of_upcoming_events')
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
                    'help' => get_lang('Agenda legend options').
                        $this->settingArrayHelpValue('agenda_legend'),
                ]
            )
            ->add(
                'agenda_colors',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Set customs colors to agenda events').
                        $this->settingArrayHelpValue('agenda_colors'),
                ]
            )
            ->add(
                'agenda_on_hover_info',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Customize on hover agenda view. Show agenda comment and/or description').
                        $this->settingArrayHelpValue('agenda_on_hover_info'),
                ]
            )
            ->add('agenda_collective_invitations', YesNoType::class)
            ->add('agenda_event_subscriptions', YesNoType::class)
            ->add('agenda_reminders', YesNoType::class)
            ->add('agenda_reminders_sender_id', TextType::class)
            ->add(
                'fullcalendar_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Extra settings for the agenda (FullCalendar v3)').
                        $this->settingArrayHelpValue('fullcalendar_settings'),
                ]
            )

        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'agenda_legend' => "<pre>
                [
                    'red' => 'red caption',
                    '#f0f' => 'another caption'
                ]
                </pre>",
            'agenda_colors' => "<pre>
                [
                    'platform' => 'red',
                    'course' => '#458B00',
                    'group' => '#A0522D',
                    'session' => '#00496D',
                    'other_session' => '#999',
                    'personal' => 'steel blue',
                    'student_publication' => '#FF8C00'
                ]
                </pre>",
            'agenda_on_hover_info' => "<pre>
                [
                    'options' => [
                        'comment' => true,
                        'description' => true,
                    ]
                ]
                </pre>",
            'fullcalendar_settings' => "<pre>
                [
                    'settings' => [
                        'businessHours' => [
                            // days of week. an array of zero-based day of week integers (0=Sunday)
                            'dow' => [0, 1, 2, 3, 4], // Sunday - Thursday
                            'start'  => '10:00',
                            'end' => '18:00',
                        ],
                        'firstDay' => 0, // 0 = Sunday, 1 = Monday
                    ]
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
