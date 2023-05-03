<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class CertificateSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'hide_my_certificate_link' => 'false',
                    'hide_header_footer' => 'false',
                    'add_certificate_pdf_footer' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'hide_my_certificate_link' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('hide_my_certificate_link', YesNoType::class)
            ->add('hide_header_footer', YesNoType::class)
            ->add('add_certificate_pdf_footer', YesNoType::class)
        ;
    }
}
