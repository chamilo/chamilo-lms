<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TicketManager;

#[Route('/platform-config')]
class PlatformConfigurationController extends AbstractController
{
    #[Route('/list', name: 'platform_config_list', methods: ['GET'])]
    public function list(SettingsManager $settingsManager): Response
    {
        $configuration = [
            'settings' => [],
        ];
        $variables = [];

        if ($this->isGranted('ROLE_USER')) {
            $variables = [
                'platform.site_name',
                'platform.timezone',
                'platform.theme',
                'platform.administrator_name',
                'platform.administrator_surname',
                'platform.registered',
                'platform.donotlistcampus',
                'platform.load_term_conditions_section',
                'platform.cookie_warning',

                'editor.enabled_mathjax',
                'editor.translate_html',

                'display.show_admin_toolbar',

                'registration.allow_terms_conditions',

                'agenda.personal_calendar_show_sessions_occupation',

                'social.social_enable_messages_feedback',
                'social.disable_dislike_option',

                'skill.allow_skills_tool',

                'gradebook.gradebook_enable_grade_model',

                'course.course_validation',

                'session.limit_session_admin_role',
            ];

            $user = $this->getUser();

            $configuration['settings']['display.show_link_ticket_notification'] = 'false';

            if (!empty($user)) {
                $userIsAllowedInProject = TicketManager::userIsAllowInProject($user, 1);

                if ($userIsAllowedInProject
                    && 'true' === $settingsManager->getSetting('display.show_link_ticket_notification')
                ) {
                    $configuration['settings']['display.show_link_ticket_notification'] = 'true';
                }
            }
        }

        foreach ($variables as $variable) {
            $value = $settingsManager->getSetting($variable);

            $configuration['settings'][$variable] = $value;
        }

        return new JsonResponse($configuration);
    }
}
