<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SearchSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class SearchSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'search_enabled' => '',
                'search_prefilter_prefix' => '',
                'search_show_unlinked_results' => '',
                'number_of_upcoming_events' => 0,

            ))
            ->setAllowedTypes(array(
                //'allow_personal_agenda' => array('string'),
                'number_of_upcoming_events' => array('string')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('search_enabled', 'yes_no')
            ->add('search_prefilter_prefix', 'yes_no')
            ->add(
                'search_show_unlinked_results',
                'choice',
                array(
                    'choices' => array(
                        'search_show_unlinked_results',
                        'search_show_unlinked_results'
                    )
                )
            )
            ->add('number_of_upcoming_events')
        ;
    }
}
