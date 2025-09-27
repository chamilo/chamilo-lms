<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class TicketSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'ticket_allow_category_edition' => 'false',
            'ticket_allow_student_add' => 'false',
            'ticket_send_warning_to_all_admins' => 'false',
            'ticket_warn_admin_no_user_in_category' => 'false',
            'ticket_project_user_roles' => '',
            'show_link_ticket_notification' => 'false',
            'show_link_bug_notification' => 'false',
        ]);

        $allowedTypes = [];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('ticket_allow_category_edition', YesNoType::class)
            ->add('ticket_allow_student_add', YesNoType::class)
            ->add('ticket_send_warning_to_all_admins', YesNoType::class)
            ->add('ticket_warn_admin_no_user_in_category', YesNoType::class)
            ->add('ticket_project_user_roles', TextareaType::class)
            ->add('show_link_ticket_notification', YesNoType::class)
            ->add('show_link_bug_notification', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
