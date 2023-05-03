<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DocumentSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'default_document_quotum' => '100000000',
                    'default_group_quotum' => '100000000',
                    'permanently_remove_deleted_files' => 'false',
                    'upload_extensions_list_type' => 'blacklist',
                    'upload_extensions_blacklist' => '',
                    'upload_extensions_whitelist' => 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf;webm;oga;ogg;ogv;h264',
                    'upload_extensions_skip' => 'true',
                    'upload_extensions_replace_by' => 'dangerous',
                    'permissions_for_new_directories' => '0777',
                    'permissions_for_new_files' => '0666',
                    'show_glossary_in_documents' => 'none',
                    'students_download_folders' => 'true',
                    'users_copy_files' => 'true',
                    'pdf_export_watermark_enable' => 'false',
                    'pdf_export_watermark_by_course' => 'false',
                    'pdf_export_watermark_text' => '',
                    'students_export2pdf' => 'true',
                    'show_users_folders' => 'true',
                    'show_default_folders' => 'true',
                    'enabled_text2audio' => 'false',
                    //'enable_nanogong' => 'false',
                    'show_documents_preview' => 'false',
                    'enable_wami_record' => 'false',
                    'enable_webcam_clip' => 'false',
                    'tool_visible_by_default_at_creation' => [
                        'documents',
                        'learning_path',
                        'links',
                        'announcements',
                        'forums',
                        'quiz',
                        'gradebook',
                    ],
                    'documents_default_visibility_defined_in_course' => 'false',
                    // ?
                    'allow_personal_user_files' => '',
                    // ?
                    'if_file_exists_option' => 'rename',
                    'send_notification_when_document_added' => 'false',
                    'thematic_pdf_orientation' => 'landscape',
                    'certificate_pdf_orientation' => 'landscape',
                    'allow_general_certificate' => 'false',
                    'group_document_access' => 'false',
                    'group_category_document_access' => 'false',
                    'allow_compilatio_tool' => 'false',
                    'compilatio_tool' => '',
                    'documents_hide_download_icon' => 'false',
                    'enable_x_sendfile_headers' => 'false',
                    'documents_custom_cloud_link_list' => '',
                ]
            )
            ->setTransformer(
                'tool_visible_by_default_at_creation',
                new ArrayToIdentifierTransformer()
            )
        ;

        $allowedTypes = [
            'tool_visible_by_default_at_creation' => ['array'],
            'default_document_quotum' => ['string'],
            'default_group_quotum' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('allow_personal_user_files', YesNoType::class)
            ->add('default_document_quotum')
            ->add('default_group_quotum')
            ->add('permanently_remove_deleted_files', YesNoType::class)
            ->add(
                'upload_extensions_list_type',
                ChoiceType::class,
                [
                    'choices' => [
                        'Blacklist' => 'blacklist',
                        'Whitelist' => 'whitelist',
                    ],
                ]
            )
            ->add('upload_extensions_blacklist', TextareaType::class)
            ->add('upload_extensions_whitelist', TextareaType::class)
            ->add('upload_extensions_skip', TextareaType::class)
            ->add('upload_extensions_replace_by', TextareaType::class)
            ->add('permissions_for_new_directories')
            ->add('permissions_for_new_files')
            ->add(
                'show_glossary_in_documents',
                ChoiceType::class,
                [
                    'choices' => [
                        'ShowGlossaryInDocumentsIsNone' => 'none',
                        'ShowGlossaryInDocumentsIsManual' => 'ismanual',
                        'ShowGlossaryInDocumentsIsAutomatic' => 'isautomatic',
                    ],
                ]
            )
            ->add('students_download_folders', YesNoType::class)
            ->add('users_copy_files', YesNoType::class)
            ->add('pdf_export_watermark_enable', YesNoType::class)
            ->add('pdf_export_watermark_by_course', YesNoType::class)
            ->add('pdf_export_watermark_text', TextareaType::class)
            ->add('students_export2pdf', YesNoType::class)
            ->add('show_users_folders', YesNoType::class)
            ->add('show_default_folders', YesNoType::class)
            ->add('enabled_text2audio', YesNoType::class)
            //->add('enable_nanogong', YesNoType::class)
            ->add('show_documents_preview', YesNoType::class)
            ->add('enable_wami_record', YesNoType::class)
            ->add('enable_webcam_clip', YesNoType::class)
            ->add(
                'tool_visible_by_default_at_creation',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => [
                        'Documents' => 'documents',
                        'LearningPath' => 'learning_path',
                        'Links' => 'links',
                        'Announcements' => 'announcements',
                        'Forums' => 'forums',
                        'Quiz' => 'quiz',
                        'Gradebook' => 'gradebook',
                    ],
                ]
            )
            ->add(
                'if_file_exists_option',
                ChoiceType::class,
                [
                    'choices' => [
                        'Rename' => 'rename',
                        'Overwrite' => 'overwrite',
                    ],
                ]
            )
            ->add('send_notification_when_document_added', YesNoType::class)
            ->add(
                'thematic_pdf_orientation',
                ChoiceType::class,
                [
                    'choices' => [
                        'Portrait' => 'portrait',
                        'Landscape' => 'landscape',
                    ],
                ]
            )
            ->add(
                'certificate_pdf_orientation',
                ChoiceType::class,
                [
                    'choices' => [
                        'Portrait' => 'portrait',
                        'Landscape' => 'landscape',
                    ],
                ]
            )
            ->add('allow_general_certificate', YesNoType::class)
            ->add('group_document_access', YesNoType::class)
            ->add('group_category_document_access', YesNoType::class)
            ->add('allow_compilatio_tool', YesNoType::class)
            ->add(
                'compilatio_tool',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Allow compilatio plagiarism prevention tool, requires extension "php-soap"  sudo apt-get install php-soap').
                        $this->settingArrayHelpValue('compilatio_tool'),
                ]
            )
            ->add('documents_hide_download_icon', YesNoType::class)
            ->add('enable_x_sendfile_headers', YesNoType::class)
            ->add(
                'documents_custom_cloud_link_list',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Custom cloud link URLS, this requires enable_add_file_link = true').
                        $this->settingArrayHelpValue('documents_custom_cloud_link_list'),
                ]
            )


        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'compilatio_tool' => "<pre>
                [
                    'settings' => [
                        'key' => '',
                        'soap_url' => '',
                        'proxy_host' => '',
                        'proxy_port' => '',
                        'max_filesize' => '',
                        'transport_mode' => '',
                        'wget_uri' => '',
                        'wget_login' => '',
                        'wget_password' => '',
                    ]
                ]
                </pre>",
            'documents_custom_cloud_link_list' => "<pre>
                ['links' => ['example.com', 'example2.com']]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
