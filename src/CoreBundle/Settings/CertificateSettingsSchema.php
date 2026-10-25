<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Json;

class CertificateSettingsSchema extends AbstractSettingsSchema
{
    public const string DEFAULT_TRANSCRIPT_GRADING_SCALE = '[{"min":90,"letter":"A","points":4.0},{"min":80,"letter":"B","points":3.0},{"min":70,"letter":"C","points":2.0},{"min":60,"letter":"D","points":1.0},{"min":0,"letter":"F","points":0.0}]';

    public const string DEFAULT_TRANSCRIPT_GPA_NOTE = 'The GPA is the unweighted average of the grade points of the courses listed in this transcript.';

    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'hide_my_certificate_link' => 'false',
            'add_certificate_pdf_footer' => 'false',
            'session_admin_can_download_all_certificates' => 'false',
            'allow_public_certificates' => 'false',
            'blockchain_verification_enabled' => 'false',
            'allow_certificates_search' => 'false',
            'certificate_pdf_orientation' => 'landscape',
            'allow_general_certificate' => 'false',
            'hide_certificate_export_link' => 'false',
            'certificate_filter_by_official_code' => 'false',
            'hide_certificate_export_link_students' => 'false',
            'transcript_enabled' => 'false',
            'transcript_program_title' => '',
            'transcript_course_code_source' => 'visual_code',
            'transcript_hours_extra_field' => '',
            'transcript_completed_hours_are_required_hours' => 'false',
            'transcript_student_number_extra_field' => '',
            'transcript_address_extra_fields' => '',
            'transcript_grading_scale' => self::DEFAULT_TRANSCRIPT_GRADING_SCALE,
            'transcript_gpa_note' => self::DEFAULT_TRANSCRIPT_GPA_NOTE,
            'transcript_signatory_name' => '',
            'transcript_signatory_title' => '',
        ]);

        $allowedTypes = [
            'hide_my_certificate_link' => ['string'],
            'add_certificate_pdf_footer' => ['string'],
            'session_admin_can_download_all_certificates' => ['string'],
            'certificate_filter_by_official_code' => ['string'],
            'allow_certificates_search' => ['string'],
        ];

        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('hide_my_certificate_link', YesNoType::class)
            ->add('add_certificate_pdf_footer', YesNoType::class)
            ->add('session_admin_can_download_all_certificates', YesNoType::class)

            ->add('allow_public_certificates', YesNoType::class)
            ->add('blockchain_verification_enabled', YesNoType::class)
            ->add('allow_certificates_search', YesNoType::class)
            ->add('certificate_pdf_orientation', ChoiceType::class, [
                'choices' => [
                    'Portrait' => 'portrait',
                    'Landscape' => 'landscape',
                ],
            ])
            ->add('allow_general_certificate', YesNoType::class)
            ->add('hide_certificate_export_link', YesNoType::class)
            ->add('certificate_filter_by_official_code', YesNoType::class)
            ->add('hide_certificate_export_link_students', YesNoType::class)
            ->add('transcript_enabled', YesNoType::class)
            ->add('transcript_program_title', TextType::class)
            ->add('transcript_course_code_source', ChoiceType::class, [
                'choices' => [
                    'Course code' => 'visual_code',
                    'Department name' => 'department_name',
                ],
            ])
            ->add('transcript_hours_extra_field', TextType::class)
            ->add('transcript_completed_hours_are_required_hours', YesNoType::class)
            ->add('transcript_student_number_extra_field', TextType::class)
            ->add('transcript_address_extra_fields', TextType::class)
            ->add('transcript_grading_scale', TextareaType::class, [
                'constraints' => [new Json()],
            ])
            ->add('transcript_gpa_note', TextareaType::class)
            ->add('transcript_signatory_name', TextType::class)
            ->add('transcript_signatory_title', TextType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
