<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class CourseSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
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
                'enable_iframe_inclusion' => '',
            ))
            ->setAllowedTypes(array(
                'allow_personal_agenda' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_personal_agenda')
        ;
    }
}
