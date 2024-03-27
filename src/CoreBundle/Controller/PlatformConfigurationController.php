<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use bbb;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TicketManager;

#[Route('/platform-config')]
class PlatformConfigurationController extends AbstractController
{
    use ControllerTrait;

    #[Route('/list', name: 'platform_config_list', methods: ['GET'])]
    public function list(SettingsManager $settingsManager): Response
    {
        $requestSession = $this->getRequest()->getSession();

        $configuration = [
            'settings' => [],
            'studentview' => $requestSession->get('studentview'),
            'plugins' => [],
        ];
        $variables = [];

        if ($this->isGranted('ROLE_USER')) {
            $variables = [
                'platform.site_name',
                'platform.timezone',
                'platform.theme',
                'platform.registered',
                'platform.donotlistcampus',
                'platform.load_term_conditions_section',
                'platform.cookie_warning',
                'platform.show_tabs',
                'platform.catalog_show_courses_sessions',

                'admin.admin_chamilo_announcements_disable',
                'admin.administrator_name',
                'admin.administrator_surname',

                'editor.enabled_mathjax',
                'editor.translate_html',

                'display.show_admin_toolbar',

                'registration.allow_terms_conditions',

                'agenda.allow_personal_agenda',
                'agenda.personal_calendar_show_sessions_occupation',
                // 'agenda.agenda_reminders',
                'agenda.agenda_collective_invitations',
                'agenda.agenda_event_subscriptions',

                'social.social_enable_messages_feedback',
                'social.disable_dislike_option',

                'skill.allow_skills_tool',

                'gradebook.gradebook_enable_grade_model',
                'gradebook.gradebook_dependency',

                'course.course_validation',
                'course.student_view_enabled',
                'course.allow_edit_tool_visibility_in_session',

                'session.limit_session_admin_role',
                'session.allow_session_admin_read_careers',
                'session.limit_session_admin_list_users',
                'platform.redirect_index_to_url_for_logged_users',

                'language.platform_language',
                'language.language_priority_1',
                'language.language_priority_2',
                'language.language_priority_3',
                'language.language_priority_4',
                'profile.allow_social_map_fields',
                'forum.global_forums_course_id',
            ];

            /** @var User|null $user */
            $user = $this->getUser();

            $configuration['settings']['display.show_link_ticket_notification'] = 'false';

            if (!empty($user)) {
                $userIsAllowedInProject = TicketManager::userIsAllowInProject(1);

                if ($userIsAllowedInProject
                    && 'true' === $settingsManager->getSetting('display.show_link_ticket_notification')
                ) {
                    $configuration['settings']['display.show_link_ticket_notification'] = 'true';
                }
            }

            $configuration['plugins']['bbb'] = [
                'show_global_conference_link' => bbb::showGlobalConferenceLink([
                    'username' => $user->getUserIdentifier(),
                    'status' => $user->getStatus(),
                ]),
                'listingURL' => (new bbb('', '', true, $user->getId()))->getListingUrl(),
            ];
        }

        foreach ($variables as $variable) {
            $value = $settingsManager->getSetting($variable, true);

            $configuration['settings'][$variable] = $value;
        }

        return new JsonResponse($configuration);
    }
}
