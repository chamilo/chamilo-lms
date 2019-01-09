<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class WebServiceSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class WebServiceSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'decode_utf8' => 'true',
                    'messaging_allow_send_push_notification' => 'false',
                    'messaging_gdc_project_number' => '',
                    'messaging_gdc_api_key' => '',
                    'allow_download_documents_by_api_key' => 'false',
                ]
            );
        $allowedTypes = [
            // commenting this line allows setting to be null
            //'header_extra_content' => array('string'),
            //'footer_extra_content' => array('string'),
            'messaging_gdc_project_number' => ['string'],
            'messaging_gdc_api_key' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('decode_utf8', YesNoType::class)
            ->add('messaging_allow_send_push_notification', YesNoType::class)
            ->add('messaging_gdc_project_number')
            ->add('messaging_gdc_api_key')
            ->add('decode_utf8', YesNoType::class)
            ->add('allow_download_documents_by_api_key', YesNoType::class)
        ;
    }
}
