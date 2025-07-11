<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class EditorSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'allow_email_editor' => '',
            'math_asciimathML' => '',
            'enabled_asciisvg' => '',
            'include_asciimathml_script' => '',
            'youtube_for_students' => '',
            'block_copy_paste_for_students' => '',
            'more_buttons_maximized_mode' => 'true',
            'enabled_wiris' => '',
            'allow_spellcheck' => '',
            'force_wiki_paste_as_plain_text' => '',
            'enabled_googlemaps' => '',
            'enabled_imgmap' => '',
            'enabled_support_svg' => '',
            'enabled_insertHtml' => '',
            'htmlpurifier_wiki' => '',
            'enable_iframe_inclusion' => '',
            'enabled_mathjax' => '',
            'translate_html' => 'false',
            'save_titles_as_html' => 'false',
            'full_ckeditor_toolbar_set' => 'false',
            'ck_editor_block_image_copy_paste' => 'false',
            'editor_driver_list' => '',
            'enable_uploadimage_editor' => 'false',
            'editor_settings' => '',
            'video_context_menu_hidden' => 'false',
            'video_player_renderers' => '',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_email_editor', YesNoType::class)
            ->add('math_asciimathML', YesNoType::class)
            ->add('enabled_asciisvg', YesNoType::class)
            ->add('include_asciimathml_script', YesNoType::class)
            ->add('youtube_for_students', YesNoType::class)
            ->add('block_copy_paste_for_students', YesNoType::class)
            ->add('more_buttons_maximized_mode', YesNoType::class)
            ->add('enabled_wiris', YesNoType::class)
            ->add('allow_spellcheck', YesNoType::class)
            ->add('force_wiki_paste_as_plain_text', YesNoType::class)
            ->add('enabled_googlemaps', YesNoType::class)
            ->add('enabled_imgmap', YesNoType::class)
            ->add('enabled_support_svg', YesNoType::class)
            ->add('enabled_insertHtml', YesNoType::class)
            ->add('htmlpurifier_wiki', YesNoType::class)
            ->add('enable_iframe_inclusion', YesNoType::class)
            ->add('enabled_mathjax', YesNoType::class)
            ->add('translate_html', YesNoType::class)
            ->add('save_titles_as_html', YesNoType::class)
            ->add('full_ckeditor_toolbar_set', YesNoType::class)
            ->add('ck_editor_block_image_copy_paste', YesNoType::class)
            ->add('editor_driver_list', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('enable_uploadimage_editor', YesNoType::class)
            ->add('editor_settings', TextareaType::class, [
                'attr' => ['rows' => 5, 'style' => 'font-family: monospace;'],
            ])
            ->add('video_context_menu_hidden', YesNoType::class)
            ->add('video_player_renderers', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
