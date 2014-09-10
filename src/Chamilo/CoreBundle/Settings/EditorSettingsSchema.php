<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class EditorSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class EditorSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'allow_email_editor' => '',
                'math_mimetex' => '',
                'math_asciimathML' => '',
                'enabled_asciisvg' => '',
                'include_asciimathml_script' => '',
                'youtube_for_students' => '',
                'block_copy_paste_for_students' => '',
                'more_buttons_maximized_mode' => '',
                'enabled_wiris' => '',
                'allow_spellcheck' => '',
                'force_wiki_paste_as_plain_text' => '',
                'enabled_googlemaps' => '',
                'enabled_imgmap' => '',
                'enabled_support_svg' => '',
                'enabled_insertHtml' => '',
                'enabled_support_pixlr' => '',
                'htmlpurifier_wiki' => '',
                'enable_iframe_inclusion' => ''
            ))
            ->setAllowedTypes(array(
                //'allow_personal_agenda' => array('string')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_email_editor', 'yes_no')
            ->add('math_mimetex', 'yes_no')
            ->add('math_asciimathML', 'yes_no')
            ->add('enabled_asciisvg', 'yes_no')
            ->add('include_asciimathml_script', 'yes_no')
            ->add('youtube_for_students', 'yes_no')
            ->add('block_copy_paste_for_students', 'yes_no')
            ->add('more_buttons_maximized_mode', 'yes_no')
            ->add('enabled_wiris', 'yes_no')
            ->add('allow_spellcheck', 'yes_no')
            ->add('force_wiki_paste_as_plain_text', 'yes_no')
            ->add('enabled_googlemaps', 'yes_no')
            ->add('enabled_imgmap', 'yes_no')
            ->add('enabled_support_svg', 'yes_no')
            ->add('enabled_insertHtml', 'yes_no')
            ->add('enabled_support_pixlr', 'yes_no')
            ->add('htmlpurifier_wiki', 'yes_no')
            ->add('enable_iframe_inclusion', 'yes_no')
        ;
    }
}
