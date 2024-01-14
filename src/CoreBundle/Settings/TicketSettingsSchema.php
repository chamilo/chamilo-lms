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
        $builder
            ->setDefaults(
                [
                    'show_terms_if_profile_completed' => 'false',
                    'ticket_allow_category_edition' => 'false',
                    'ticket_allow_student_add' => 'false',
                    'ticket_send_warning_to_all_admins' => 'false',
                    'ticket_warn_admin_no_user_in_category' => 'false',
                    'ticket_project_user_roles' => '',
                ]
            )
        ;

        $allowedTypes = [
            'show_terms_if_profile_completed' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('show_terms_if_profile_completed', YesNoType::class)
            ->add('ticket_allow_category_edition', YesNoType::class)
            ->add('ticket_allow_student_add', YesNoType::class)
            ->add('ticket_send_warning_to_all_admins', YesNoType::class)
            ->add('ticket_warn_admin_no_user_in_category', YesNoType::class)
            ->add(
                'ticket_project_user_roles',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Ticket project user roles').
                        $this->settingArrayHelpValue('ticket_project_user_roles'),
                ]
            )
        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'ticket_project_user_roles' => "<pre>
                [
                    'permissions' => [
                        1 => [17,1], // project_id = 1 STUDENT_BOSS = 17
                    ]
                ]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
