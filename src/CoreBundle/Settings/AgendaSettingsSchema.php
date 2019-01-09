<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AgendaSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class AgendaSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'allow_personal_agenda' => 'true',
                    //'display_mini_month_calendar' => '', ??
                    'display_upcoming_events' => '', // ??
                    //'number_of_upcoming_events' => '0',
                    'default_calendar_view' => 'month',
                ]
            );

        $allowedTypes = [
            'allow_personal_agenda' => ['string'],
            //'display_mini_month_calendar' => array('string'),
            'display_upcoming_events' => ['string'],
            //'number_of_upcoming_events' => array('string'),
            'default_calendar_view' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
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
        ;
    }
}
