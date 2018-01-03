<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GradebookSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class GradebookSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'gradebook_enable' => 'true',
                    'gradebook_score_display_custom' => 'false',
                    'gradebook_score_display_colorsplit' => '50',
                    'gradebook_score_display_upperlimit' => 'false',
                    'gradebook_number_decimals' => '0',
                    'teachers_can_change_score_settings' => 'true',
                    'teachers_can_change_grade_model_settings' => 'true',
                    'gradebook_enable_grade_model' => 'false',
                    'gradebook_default_weight' => '100',
                    'gradebook_locking_enabled' => 'false',
                    'gradebook_default_grade_model_id' => '',
                    'gradebook_show_percentage_in_reports' => '',
                    'my_display_coloring' => 'false',
                    'student_publication_to_take_in_gradebook' => 'first',
                    'gradebook_detailed_admin_view' => 'false',
                    'openbadges_backpack' => 'https://backpack.openbadges.org/',
                    'hide_certificate_export_link' => 'false'
                )
            );
        $allowedTypes = array(
            'gradebook_enable' => array('string'),
            'gradebook_number_decimals' => array('string'),
            'gradebook_default_weight' => array('string'),
            'student_publication_to_take_in_gradebook' => array('string'),
            'gradebook_detailed_admin_view' => array('string')
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('gradebook_enable', YesNoType::class)
            ->add('gradebook_score_display_custom', YesNoType::class)
            ->add('gradebook_score_display_colorsplit')
            ->add('gradebook_score_display_upperlimit', YesNoType::class)
            ->add('gradebook_number_decimals')
            ->add('teachers_can_change_score_settings', YesNoType::class)
            ->add('gradebook_enable_grade_model', YesNoType::class)
            ->add('teachers_can_change_grade_model_settings', YesNoType::class)
            ->add('gradebook_default_weight')
            ->add('gradebook_locking_enabled', YesNoType::class)
            ->add('gradebook_default_grade_model_id')
            ->add('gradebook_show_percentage_in_reports')
            ->add('my_display_coloring')
            ->add(
                'student_publication_to_take_in_gradebook',
                'choice',
                ['choices' => [
                    'first' => 'First',
                    'last' => 'Last'
                ]]
            )
            ->add('gradebook_detailed_admin_view')
            ->add('openbadges_backpack')
            ->add('hide_certificate_export_link', YesNoType::class)

        ;
    }
}
