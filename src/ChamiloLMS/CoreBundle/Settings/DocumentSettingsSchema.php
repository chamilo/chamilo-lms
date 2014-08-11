<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class DocumentSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'default_document_quotum' => '',
                'default_group_quotum' => '',
                'permanently_remove_deleted_files' => '',
                'upload_extensions_list_type' => '',
                'upload_extensions_blacklist' => '',
                'upload_extensions_whitelist' => '',
                'upload_extensions_skip' => '',
                'upload_extensions_replace_by' => '',
                'permissions_for_new_directories' => '',
                'permissions_for_new_files' => '',
                'show_glossary_in_documents' => '',
                'students_download_folders' => '',
                'users_copy_files' => '',
                'pdf_export_watermark_enable' => '',
                'pdf_export_watermark_by_course' => '',
                'pdf_export_watermark_text' => '',
                'students_export2pdf' => '',
                'show_users_folders' => '',
                'show_default_folders' => '',
                'enabled_text2audio' => '',
                'enable_nanogong' => '',
                'show_documents_preview' => '',
                'enable_wami_record' => '',
                'enable_webcam_clip' => '',
                'tool_visible_by_default_at_creation' => '',// documents
                'documents_default_visibility_defined_in_course' => '',
                'allow_personal_user_files' => '',
            ))
            ->setAllowedTypes(array(
                'default_document_quotum' => array('string'),
                'default_group_quotum' => array('string'),
                'permanently_remove_deleted_files' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('default_document_quotum')
            ->add('default_group_quotum')
            ->add('permanently_remove_deleted_files')
        ;
    }
}
