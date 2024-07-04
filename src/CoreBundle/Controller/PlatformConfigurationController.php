<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use bbb;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\ServiceHelper\TicketProjectHelper;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/platform-config')]
class PlatformConfigurationController extends AbstractController
{
    use ControllerTrait;

    public function __construct(
        private readonly TicketProjectHelper $ticketProjectHelper,
        private readonly UserHelper $userHelper,
    ) {}

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
                'admin.administrator_name',
                'admin.administrator_surname',
                'editor.enabled_mathjax',
                'editor.translate_html',
                'display.show_admin_toolbar',
                'registration.allow_terms_conditions',
                'agenda.allow_personal_agenda',
                'agenda.personal_calendar_show_sessions_occupation',
                'social.social_enable_messages_feedback',
                'social.disable_dislike_option',
                'skill.allow_skills_tool',
                'gradebook.gradebook_enable_grade_model',
                'gradebook.gradebook_dependency',
                'course.course_validation',
                'course.student_view_enabled',
                'course.allow_edit_tool_visibility_in_session',
                'course.enable_record_audio',
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
                'document.students_download_folders',
                'social.hide_social_groups_block',
            ];

            $user = $this->userHelper->getCurrent();

            $configuration['settings']['display.show_link_ticket_notification'] = 'false';

            if (!empty($user)) {
                $userIsAllowedInProject = $this->ticketProjectHelper->userIsAllowInProject(1);

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

    #[Route('/list/course_settings', name: 'course_settings_list', methods: ['GET'])]
    public function courseSettingsList(
        SettingsCourseManager $courseSettingsManager,
        CourseRepository $courseRepository,
        Request $request
    ): JsonResponse {
        $courseId = $request->query->get('cid');
        if (!$courseId) {
            return new JsonResponse(['error' => 'Course ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $course = $courseRepository->find($courseId);
        if (!$course) {
            return new JsonResponse(['error' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $courseSettingsManager->setCourse($course);
        $settings = [
            'show_course_in_user_language' => $courseSettingsManager->getCourseSettingValue('show_course_in_user_language'),
            'allow_user_edit_agenda' => $courseSettingsManager->getCourseSettingValue('allow_user_edit_agenda'),
        ];

        return new JsonResponse(['settings' => $settings]);
    }
}
