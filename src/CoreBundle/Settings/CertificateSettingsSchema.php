<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CertificateSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class CertificateSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'hide_my_certificate_link' => 'false',
                    'hide_header_footer' => 'false',
                ]
            );

        $allowedTypes = [
            'hide_my_certificate_link' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('hide_my_certificate_link', YesNoType::class)
            ->add('hide_header_footer', YesNoType::class)
        ;
    }
}
