<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class MailSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class MailSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                [
                    'noreply_email_address' => 'no_reply@example.com',
                ]
            )
        ;
        //$this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('noreply_email_address', 'email')
        ;
    }
}
