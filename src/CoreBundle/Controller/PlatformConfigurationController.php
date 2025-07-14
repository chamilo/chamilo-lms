<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Bbb;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Helpers\TicketProjectHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/platform-config')]
class PlatformConfigurationController extends AbstractController
{
    use ControllerTrait;

    public function __construct(
        private readonly TicketProjectHelper $ticketProjectHelper,
        private readonly UserHelper $userHelper,
        private readonly ThemeHelper $themeHelper,
        private readonly AuthenticationConfigHelper $authenticationConfigHelper,
    ) {}

    #[Route('/list', name: 'platform_config_list', methods: ['GET'])]
    public function list(SettingsManager $settingsManager): Response
    {
        $requestSession = $this->getRequest()->getSession();

        $configuration = [
            'settings' => [],
            'studentview' => $requestSession->get('studentview'),
            'plugins' => [],
            'visual_theme' => $this->themeHelper->getVisualTheme(),
            'oauth2_providers' => $this->authenticationConfigHelper->getEnabledOAuthProviders(),
        ];

        $configuration['settings']['registration.allow_registration'] = $settingsManager->getSetting('registration.allow_registration', true);
        $configuration['settings']['course.course_catalog_published'] = $settingsManager->getSetting('course.course_catalog_published', true);
        $configuration['settings']['course.catalog_hide_public_link'] = $settingsManager->getSetting('course.catalog_hide_public_link', true);
        $configuration['settings']['course.allow_course_extra_field_in_catalog'] = $settingsManager->getSetting('course.allow_course_extra_field_in_catalog', true);
        $configuration['settings']['course.course_catalog_display_in_home'] = $settingsManager->getSetting('course.course_catalog_display_in_home', true);
        $configuration['settings']['course.courses_catalogue_show_only_category'] = $settingsManager->getSetting('course.courses_catalogue_show_only_category', true);
        $configuration['settings']['display.allow_students_to_browse_courses'] = $settingsManager->getSetting('display.allow_students_to_browse_courses', true);
        $configuration['settings']['session.catalog_allow_session_auto_subscription'] = $settingsManager->getSetting('session.catalog_allow_session_auto_subscription', true);
        $configuration['settings']['session.catalog_course_subscription_in_user_s_session'] = $settingsManager->getSetting('session.catalog_course_subscription_in_user_s_session', true);
        $rawCourseCatalogSetting = $settingsManager->getSetting('course.course_catalog_settings', true);
        $configuration['settings']['course.course_catalog_settings'] = 'false' !== $rawCourseCatalogSetting ? $this->decodeSettingArray($rawCourseCatalogSetting) : 'false';
        $rawSessionCatalogSetting = $settingsManager->getSetting('session.catalog_settings', true);
        $configuration['settings']['session.catalog_settings'] = 'false' !== $rawSessionCatalogSetting ? $this->decodeSettingArray($rawSessionCatalogSetting) : 'false';
        $configuration['settings']['admin.chamilo_latest_news'] = $settingsManager->getSetting('admin.chamilo_latest_news', true);
        $configuration['settings']['admin.chamilo_support'] = $settingsManager->getSetting('admin.chamilo_support', true);

        $variables = [];

        if ($this->isGranted('ROLE_USER')) {
            $variables = [
                'platform.site_name',
                'platform.timezone',
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
                'course.show_course_duration',
                'attendance.attendance_allow_comments',
                'attendance.multilevel_grading',
                'attendance.enable_sign_attendance_sheet',
                'exercise.allow_exercise_auto_launch',
                'course.access_url_specific_files',
                'platform.course_catalog_hide_private',
                'course.show_courses_descriptions_in_catalog',
                'session.session_automatic_creation_user_id',
                'session.session_list_view_remaining_days',
                'profile.use_users_timezone',
                'registration.redirect_after_login',
                'platform.show_tabs_per_role',
                'platform.session_admin_user_subscription_search_extra_field_to_search',
                'platform.push_notification_settings',
                'session.user_session_display_mode',
                'course.resource_sequence_show_dependency_in_course_intro',
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
                'show_global_conference_link' => Bbb::showGlobalConferenceLink([
                    'username' => $user->getUserIdentifier(),
                    'status' => $user->getStatus(),
                ]),
                'listingURL' => (new Bbb('', '', true, $user->getId()))->getListingUrl(),
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
            'enable_document_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_document_auto_launch'),
            'enable_exercise_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_exercise_auto_launch'),
            'enable_lp_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_lp_auto_launch'),
            'enable_forum_auto_launch' => $courseSettingsManager->getCourseSettingValue('enable_forum_auto_launch'),
        ];

        return new JsonResponse(['settings' => $settings]);
    }

    /**
     * Attempts to decode a setting value that may be stored as:
     * - native PHP array
     * - JSON string
     * - PHP array code string
     */
    private function decodeSettingArray(mixed $setting): array
    {
        // Already an array, return as is
        if (\is_array($setting)) {
            return $setting;
        }

        // Try to decode JSON string
        if (\is_string($setting)) {
            $json = json_decode($setting, true);
            if (\is_array($json)) {
                return $json;
            }

            // Try to evaluate PHP-style array string
            $trimmed = rtrim($setting, ';');

            try {
                $evaluated = eval("return $trimmed;");
                if (\is_array($evaluated)) {
                    return $evaluated;
                }
            } catch (Throwable $e) {
                // Log error and continue
                error_log('Failed to eval setting value: '.$e->getMessage());
            }
        }

        // Return empty array as fallback
        return [];
    }
}
