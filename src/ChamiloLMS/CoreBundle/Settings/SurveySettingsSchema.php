<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SurveySettingsSchema
 * @package ChamiloLMS\CoreBundle\Settings
 */
class SurveySettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'survey_email_sender_noreply' => '',
                'extend_rights_for_coach_on_survey' => ''

            ))
            ->setAllowedTypes(array(
                'survey_email_sender_noreply' => array('string')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'survey_email_sender_noreply',
                'choice',
                array(
                    'choices' => array(
                        'coach' => 'Coach email address',
                        'noreply' => 'No reply address'
                    )
                )
            )
            ->add('extend_rights_for_coach_on_survey', 'yes_no')
        ;
    }
}
