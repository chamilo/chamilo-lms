<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AgendaSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class AgendaSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'allow_personal_agenda' => '',
                'display_mini_month_calendar' => '',
                'display_upcoming_events' => '',
                'number_of_upcoming_events' => 0,
                //'default_calendar_view' => '',
            ))
            ->setAllowedTypes(array(
                'allow_personal_agenda' => array('string'),
                'display_mini_month_calendar' => array('string'),
                'display_upcoming_events' => array('string'),
                'number_of_upcoming_events' => array('integer'),
                //'default_calendar_view' => array('string'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_personal_agenda', 'yes_no')
            ->add('display_mini_month_calendar', 'yes_no')
            ->add('display_upcoming_events', 'yes_no')
            ->add('number_of_upcoming_events')
            //->add('default_calendar_view', 'yes_no')

        ;
    }
}
