<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GradebookSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class TicketSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'show_terms_if_profile_completed' => 'false',
                    'ticket_allow_category_edition' => 'false',
                    'ticket_allow_student_add' => 'false',
                    'ticket_send_warning_to_all_admins' => 'false',
                    'ticket_warn_admin_no_user_in_category' => 'false',
                ]
            );

        $allowedTypes = [
            'show_terms_if_profile_completed' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('show_terms_if_profile_completed', YesNoType::class)
            ->add('ticket_allow_category_edition', YesNoType::class)
            ->add('ticket_allow_student_add')
            ->add('ticket_send_warning_to_all_admins', YesNoType::class)
            ->add('ticket_warn_admin_no_user_in_category', YesNoType::class)
        ;
    }
}
