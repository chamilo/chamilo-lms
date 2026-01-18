<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DocumentSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults([
                'default_document_quotum' => '1000',
                'default_group_quotum' => '250',
                'permanently_remove_deleted_files' => 'false',
                'upload_extensions_list_type' => 'blacklist',
                'upload_extensions_blacklist' => '',
                'upload_extensions_whitelist' => 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf;webm;oga;ogg;ogv;h264',
                'upload_extensions_skip' => 'true',
                'upload_extensions_replace_by' => 'dangerous',
                'permissions_for_new_directories' => '0770',
                'permissions_for_new_files' => '0660',
                'students_download_folders' => 'true',
                'users_copy_files' => 'true',
                'pdf_export_watermark_enable' => 'false',
                'pdf_export_watermark_by_course' => 'false',
                'pdf_export_watermark_text' => '',
                'students_export2pdf' => 'true',
                'show_users_folders' => 'true',
                'show_default_folders' => 'true',
                'show_documents_preview' => 'false',
                'documents_default_visibility_defined_in_course' => 'false',
                'send_notification_when_document_added' => 'false',
                'thematic_pdf_orientation' => 'landscape',
                'group_document_access' => 'false',
                'group_category_document_access' => 'false',
                'documents_hide_download_icon' => 'false',
                'enable_x_sendfile_headers' => 'false',
                'documents_custom_cloud_link_list' => '',

                'access_url_specific_files' => 'false',
                'video_features' => '',
            ])
        ;

        $allowedTypes = [
            'default_document_quotum' => ['string'],
            'default_group_quotum' => ['string'],
        ];

        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('default_document_quotum')
            ->add('default_group_quotum')
            ->add('permanently_remove_deleted_files', YesNoType::class)
            ->add('upload_extensions_list_type', ChoiceType::class, [
                'choices' => [
                    'Black list' => 'blacklist',
                    'White list' => 'whitelist',
                ],
            ])
            ->add('upload_extensions_blacklist', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('upload_extensions_whitelist', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('upload_extensions_skip', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('upload_extensions_replace_by', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('permissions_for_new_directories')
            ->add('permissions_for_new_files')
            ->add('students_download_folders', YesNoType::class)
            ->add('users_copy_files', YesNoType::class)
            ->add('pdf_export_watermark_enable', YesNoType::class)
            ->add('pdf_export_watermark_by_course', YesNoType::class)
            ->add('pdf_export_watermark_text', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('students_export2pdf', YesNoType::class)
            ->add('show_users_folders', YesNoType::class)
            ->add('show_default_folders', YesNoType::class)
            ->add('show_documents_preview', YesNoType::class)
            ->add('send_notification_when_document_added', YesNoType::class)
            ->add('thematic_pdf_orientation', ChoiceType::class, [
                'choices' => [
                    'Portrait' => 'portrait',
                    'Landscape' => 'landscape',
                ],
            ])
            ->add('group_document_access', YesNoType::class)
            ->add('group_category_document_access', YesNoType::class)
            ->add('documents_hide_download_icon', YesNoType::class)
            ->add('enable_x_sendfile_headers', YesNoType::class)
            ->add('documents_custom_cloud_link_list', TextareaType::class, [
                'attr' => ['rows' => 3, 'style' => 'font-family: monospace;'],
            ])
            ->add('access_url_specific_files', YesNoType::class)
            ->add('video_features', TextareaType::class, [
                'attr' => ['rows' => 4, 'style' => 'font-family: monospace;'],
                'help' => 'Optional JSON. Example: {"speed": true}',
            ])
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
