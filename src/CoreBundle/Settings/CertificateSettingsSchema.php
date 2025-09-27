<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class CertificateSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'hide_my_certificate_link' => 'false',
            'add_certificate_pdf_footer' => 'false',
            'session_admin_can_download_all_certificates' => 'false',
            'allow_public_certificates' => 'false',

            'certificate_pdf_orientation' => 'landscape',
            'allow_general_certificate' => 'false',
            'hide_certificate_export_link' => 'false',
            'add_gradebook_certificates_cron_task_enabled' => 'false',
            'certificate_filter_by_official_code' => 'false',
            'hide_certificate_export_link_students' => 'false',
        ]);

        $allowedTypes = [
            'hide_my_certificate_link' => ['string'],
            'add_certificate_pdf_footer' => ['string'],
            'session_admin_can_download_all_certificates' => ['string'],
            'certificate_filter_by_official_code' => ['string'],
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
            ->add('certificate_pdf_orientation', ChoiceType::class, [
                'choices' => [
                    'Portrait' => 'portrait',
                    'Landscape' => 'landscape',
                ],
            ])
            ->add('allow_general_certificate', YesNoType::class)
            ->add('hide_certificate_export_link', YesNoType::class)
            ->add('add_gradebook_certificates_cron_task_enabled', YesNoType::class)
            ->add('certificate_filter_by_official_code', YesNoType::class)
            ->add('hide_certificate_export_link_students', YesNoType::class);

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
