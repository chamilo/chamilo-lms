<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'block_student_publication_edition' => 'false',
                    'block_student_publication_add_documents' => 'false',
                    'block_student_publication_score_edition' => 'false',
                    'allow_only_one_student_publication_per_user' => 'false',
                    'allow_my_student_publication_page' => 'false',
                    'assignment_prevent_duplicate_upload' => 'false',
                    'considered_working_time' => 'work_time',
                    'force_download_doc_before_upload_work' => 'true',
                    'allow_redirect_to_main_page_after_work_upload' => 'false',
                    'my_courses_show_pending_work' => 'false',
                ]
            )
        ;
        //$this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('block_student_publication_edition', YesNoType::class)
            ->add('block_student_publication_add_documents', YesNoType::class)
            ->add('block_student_publication_score_edition', YesNoType::class)
            ->add('allow_only_one_student_publication_per_user', YesNoType::class)
            ->add('allow_my_student_publication_page', YesNoType::class)
            ->add('assignment_prevent_duplicate_upload', YesNoType::class)
            ->add('considered_working_time', TextType::class)
            ->add('force_download_doc_before_upload_work', YesNoType::class)
            ->add('allow_redirect_to_main_page_after_work_upload', YesNoType::class)
            ->add('my_courses_show_pending_work', YesNoType::class)

        ;
    }
}
